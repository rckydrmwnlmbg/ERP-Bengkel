<?php

namespace App\Services;

use App\Exceptions\InvalidInvoiceOperationException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Part;
use App\Models\Setting;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        protected StockService $stockService,
        protected PricingService $pricingService,
        protected AuditService $auditService
    ) {}

    /**
     * BR-I4: Buat invoice dari order servis yang telah selesai (completed).
     * Menyalin item order (termasuk unit_cost_snapshot) tanpa membuat pergerakan stok baru.
     */
    public function createFromWorkOrder(WorkOrder $workOrder, array $params = [], ?int $userId = null): Invoice
    {
        return DB::transaction(function () use ($workOrder, $params, $userId) {
            /** @var WorkOrder $wo */
            $wo = WorkOrder::where('id', $workOrder->id)->lockForUpdate()->firstOrFail();

            if ($wo->status === 'invoiced') {
                throw new InvalidInvoiceOperationException("Order servis ini telah diterbitkan invoice sebelumnya (BR-W3).");
            }

            if ($wo->status !== 'completed') {
                throw new InvalidInvoiceOperationException("Invoice hanya dapat dibuat dari order servis berstatus 'completed' (BR-W1).");
            }

            if ($wo->items()->count() === 0) {
                throw new InvalidInvoiceOperationException("Order servis tanpa item tidak dapat diterbitkan invoice (BR-W4).");
            }

            // BR-W3: Satu order hanya boleh punya satu invoice aktif (non-void)
            if ($wo->activeInvoice()->exists()) {
                throw new InvalidInvoiceOperationException("Order servis sudah memiliki invoice aktif yang belum dibatalkan (BR-W3).");
            }

            $issueDate = $params['issue_date'] ?? now()->toDateString();
            $customer = $wo->customer;
            $paymentTermDays = (int) ($customer?->payment_term_days ?? 0);
            $dueDate = Carbon::parse($issueDate)->addDays($paymentTermDays)->toDateString(); // BR-I7

            $subtotal = $wo->calculateTotal(); // BR-I2
            $discount = (int) ($params['discount_amount'] ?? 0);

            // BR-I3: 0 <= discount_amount <= subtotal
            if ($discount < 0 || $discount > $subtotal) {
                throw new InvalidInvoiceOperationException("Nilai diskon harus di antara 0 dan subtotal (BR-I3).");
            }

            // BR-I3: Pajak dibaca dari pengaturan sistem (default 0), jangan di-hardcode
            $taxRate = Setting::getTaxRate();
            $taxableAmount = $subtotal - $discount;
            $taxAmount = (int) round($taxableAmount * ($taxRate / 100), 0, PHP_ROUND_HALF_UP);

            $total = $subtotal - $discount + $taxAmount; // BR-I2

            // BR-I8: Invoice total = 0 tidak diperbolehkan
            if ($total <= 0) {
                throw new InvalidInvoiceOperationException("Total invoice harus lebih besar dari 0 (BR-I8).");
            }

            // BR-I1: Nomor INV-YYMM-#### berurutan per bulan, anti-race
            $invoiceNumber = Invoice::generateNextNumber($issueDate);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $wo->customer_id,
                'source_type' => 'work_order',
                'work_order_id' => $wo->id,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'status' => 'issued',
                'created_by' => $userId,
            ]);

            // BR-I4: Salin item order servis ke item invoice (termasuk unit_cost_snapshot) tanpa mutasi stok
            foreach ($wo->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => $item->item_type,
                    'part_id' => $item->part_id,
                    'service_id' => $item->service_id,
                    'description' => $item->description,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'unit_cost_snapshot' => $item->unit_cost_snapshot,
                    'line_total' => $item->line_total,
                ]);
            }

            // Ubah status order servis menjadi invoiced (BR-W1)
            $wo->status = 'invoiced';
            $wo->save();

            $this->auditService->log(
                action: 'invoice_created',
                entityType: 'Invoice',
                entityId: $invoice->id,
                oldValues: null,
                newValues: [
                    'invoice_number' => $invoice->invoice_number,
                    'source_type' => 'work_order',
                    'work_order_id' => $wo->id,
                    'total' => $invoice->total,
                ],
                userId: $userId
            );

            return $invoice;
        });
    }

    /**
     * BR-S5, BR-I1..I3, BR-I7, BR-I8:
     * Penjualan sparepart langsung (counter sale).
     * Mengurangi stok seketika (SALE_OUT) saat invoice terbit.
     */
    public function createDirectSale(array $data, ?int $userId = null): Invoice
    {
        return DB::transaction(function () use ($data, $userId) {
            $customer = Customer::findOrFail($data['customer_id']);
            $issueDate = $data['issue_date'] ?? now()->toDateString();
            $paymentTermDays = (int) ($customer->payment_term_days ?? 0);
            $dueDate = Carbon::parse($issueDate)->addDays($paymentTermDays)->toDateString(); // BR-I7

            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw new InvalidInvoiceOperationException("Penjualan langsung harus memiliki minimal 1 item sparepart.");
            }

            // Hitung subtotal dan siapkan baris item
            $subtotal = 0;
            $preparedItems = [];

            foreach ($itemsData as $row) {
                $part = Part::findOrFail($row['part_id']);
                $qty = (int) $row['qty'];

                if ($qty <= 0) {
                    throw new InvalidInvoiceOperationException("Jumlah kuantitas sparepart harus lebih besar dari 0.");
                }

                // BR-P3: Harga sparepart = parts.sell_price saat item dibuat (snapshot)
                $unitPrice = (int) $part->sell_price;

                // BR-P4: Validasi harga jual terhadap HPP
                $this->pricingService->validateSellPriceAgainstCost($unitPrice, (int) $part->avg_cost, $row['override_reason'] ?? null);

                $lineTotal = $qty * $unitPrice;
                $subtotal += $lineTotal;

                $preparedItems[] = [
                    'part' => $part,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $discount = (int) ($data['discount_amount'] ?? 0);
            if ($discount < 0 || $discount > $subtotal) {
                throw new InvalidInvoiceOperationException("Nilai diskon harus di antara 0 dan subtotal (BR-I3).");
            }

            // BR-I3: Pajak dari setting
            $taxRate = Setting::getTaxRate();
            $taxableAmount = $subtotal - $discount;
            $taxAmount = (int) round($taxableAmount * ($taxRate / 100), 0, PHP_ROUND_HALF_UP);

            $total = $subtotal - $discount + $taxAmount;

            // BR-I8: Total > 0
            if ($total <= 0) {
                throw new InvalidInvoiceOperationException("Total invoice harus lebih besar dari 0 (BR-I8).");
            }

            $invoiceNumber = Invoice::generateNextNumber($issueDate);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->id,
                'source_type' => 'counter_sale',
                'work_order_id' => null,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'status' => 'issued',
                'created_by' => $userId,
            ]);

            // Simpan baris item & potong stok seketika (BR-S5: SALE_OUT saat invoice terbit)
            foreach ($preparedItems as $prep) {
                $part = $prep['part'];
                $qty = $prep['qty'];

                $movement = $this->stockService->recordSaleOut(
                    partId: $part->id,
                    qty: $qty,
                    invoiceId: $invoice->id,
                    userId: $userId
                );

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'part',
                    'part_id' => $part->id,
                    'service_id' => null,
                    'description' => $part->name,
                    'qty' => $qty,
                    'unit_price' => $prep['unit_price'],
                    'unit_cost_snapshot' => (int) $movement->unit_cost_snapshot,
                    'line_total' => $prep['line_total'],
                ]);
            }

            $this->auditService->log(
                action: 'direct_sale_invoice_created',
                entityType: 'Invoice',
                entityId: $invoice->id,
                oldValues: null,
                newValues: [
                    'invoice_number' => $invoice->invoice_number,
                    'source_type' => 'counter_sale',
                    'total' => $invoice->total,
                ],
                userId: $userId
            );

            return $invoice;
        });
    }

    /**
     * BR-I5, BR-I6: Void invoice terbit.
     * Hanya admin, alasan wajib, dan hanya jika tidak ada pembayaran valid.
     */
    public function voidInvoice(Invoice $invoice, string $reason, int $userId): Invoice
    {
        if (empty(trim($reason))) {
            throw new InvalidInvoiceOperationException("Alasan pembatalan (void) invoice wajib diisi (BR-I5).");
        }

        return DB::transaction(function () use ($invoice, $reason, $userId) {
            /** @var Invoice $inv */
            $inv = Invoice::where('id', $invoice->id)->lockForUpdate()->firstOrFail();

            if ($inv->status === 'void') {
                throw new InvalidInvoiceOperationException("Invoice ini sudah dibatalkan (void) sebelumnya.");
            }

            // BR-I6: Void hanya jika tidak ada pembayaran valid
            $hasValidPayments = $inv->validPayments()->exists();
            if ($hasValidPayments) {
                throw new InvalidInvoiceOperationException(
                    "Invoice tidak dapat dibatalkan (void) karena memiliki pembayaran yang valid. Batalkan (void) seluruh pembayaran terlebih dahulu (BR-I6)."
                );
            }

            // BR-I6: Void invoice order servis -> order kembali berstatus completed
            if ($inv->source_type === 'work_order' && $inv->work_order_id) {
                $wo = WorkOrder::where('id', $inv->work_order_id)->lockForUpdate()->first();
                if ($wo) {
                    $wo->status = 'completed';
                    $wo->save();
                }
            }

            // BR-I6, BR-S5: Void penjualan langsung -> stok sparepart dikembalikan (+qty, SALE_RETURN)
            if ($inv->source_type === 'counter_sale') {
                foreach ($inv->partItems as $item) {
                    if ($item->part_id) {
                        $this->stockService->recordSaleReturn(
                            partId: (int) $item->part_id,
                            qty: (int) $item->qty,
                            unitCostSnapshot: (int) $item->unit_cost_snapshot,
                            invoiceId: $inv->id,
                            userId: $userId
                        );
                    }
                }
            }

            $oldStatus = $inv->status;
            $inv->status = 'void';
            $inv->void_reason = $reason;
            $inv->voided_at = now();
            $inv->voided_by = $userId;
            $inv->save();

            $this->auditService->log(
                action: 'invoice_voided',
                entityType: 'Invoice',
                entityId: $inv->id,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => 'void',
                    'void_reason' => $reason,
                ],
                userId: $userId
            );

            return $inv;
        });
    }
}
