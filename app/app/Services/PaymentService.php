<?php

namespace App\Services;

use App\Exceptions\InvalidPaymentOperationException;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * BR-Y1, BR-Y3, BR-I8:
     * Catat pembayaran invoice.
     * amount > 0, amount <= sisa tagihan, invoice tidak void.
     */
    public function recordPayment(Invoice $invoice, array $data, ?int $userId = null): Payment
    {
        $amount = (int) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw new InvalidPaymentOperationException("Jumlah pembayaran harus lebih besar dari 0 (BR-Y1).");
        }

        return DB::transaction(function () use ($invoice, $data, $amount, $userId) {
            /** @var Invoice $inv */
            $inv = Invoice::where('id', $invoice->id)->lockForUpdate()->firstOrFail();

            // BR-Y3: Tidak ada pembayaran untuk invoice void
            if ($inv->status === 'void') {
                throw new InvalidPaymentOperationException("Tidak dapat mencatat pembayaran untuk invoice yang telah dibatalkan (void) (BR-Y3).");
            }

            if ($inv->status === 'paid') {
                throw new InvalidPaymentOperationException("Invoice ini telah lunas.");
            }

            // BR-Y1: amount <= sisa tagihan. Kelebihan bayar ditolak.
            $remaining = $inv->remainingBalance();
            if ($amount > $remaining) {
                throw new InvalidPaymentOperationException(
                    "Jumlah pembayaran (Rp" . number_format($amount, 0, ',', '.') . ") melebihi sisa tagihan (Rp" . number_format($remaining, 0, ',', '.') . ") (BR-Y1)."
                );
            }

            $payment = Payment::create([
                'invoice_id' => $inv->id,
                'paid_at' => $data['paid_at'] ?? now()->toDateString(),
                'amount' => $amount,
                'method' => $data['method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'status' => 'valid',
                'created_by' => $userId,
            ]);

            // BR-I8: Perbarui status turunan invoice dari total pembayaran valid
            $this->recalculateInvoiceStatus($inv);

            $this->auditService->log(
                action: 'payment_recorded',
                entityType: 'Payment',
                entityId: $payment->id,
                oldValues: null,
                newValues: [
                    'invoice_id' => $inv->id,
                    'amount' => $payment->amount,
                    'method' => $payment->method,
                    'invoice_status_after' => $inv->status,
                ],
                userId: $userId
            );

            return $payment;
        });
    }

    /**
     * BR-Y2: Pembayaran tidak diedit/dihapus.
     * Admin dapat membatalkan (void) pembayaran dengan alasan tertulis; status invoice dihitung ulang.
     */
    public function voidPayment(Payment $payment, string $reason, ?int $userId = null): Payment
    {
        if (empty(trim($reason))) {
            throw new InvalidPaymentOperationException("Alasan pembatalan (void) pembayaran wajib diisi (BR-Y2).");
        }

        return DB::transaction(function () use ($payment, $reason, $userId) {
            /** @var Payment $p */
            $p = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($p->status === 'void') {
                throw new InvalidPaymentOperationException("Pembayaran ini sudah dibatalkan (void) sebelumnya.");
            }

            $p->status = 'void';
            $p->void_reason = $reason;
            $p->save();

            // BR-Y2, BR-I8: Hitung ulang status invoice setelah pembayaran dibatalkan
            /** @var Invoice $inv */
            $inv = Invoice::where('id', $p->invoice_id)->lockForUpdate()->firstOrFail();
            $this->recalculateInvoiceStatus($inv);

            $this->auditService->log(
                action: 'payment_voided',
                entityType: 'Payment',
                entityId: $p->id,
                oldValues: ['status' => 'valid'],
                newValues: [
                    'status' => 'void',
                    'void_reason' => $reason,
                    'invoice_status_after' => $inv->status,
                ],
                userId: $userId
            );

            return $p;
        });
    }

    /**
     * BR-I8: Status turunan dari pembayaran valid:
     * 0 -> issued
     * 0 < dibayar < total -> partially_paid
     * dibayar = total -> paid
     */
    public function recalculateInvoiceStatus(Invoice $invoice): void
    {
        if ($invoice->status === 'void') {
            return;
        }

        $totalPaid = (int) $invoice->validPayments()->sum('amount');

        if ($totalPaid >= (int) $invoice->total) {
            $invoice->status = 'paid';
        } elseif ($totalPaid > 0) {
            $invoice->status = 'partially_paid';
        } else {
            $invoice->status = 'issued';
        }

        $invoice->save();
    }
}
