<?php

namespace App\Services;

use App\Exceptions\InvalidWorkOrderOperationException;
use App\Models\Part;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Support\Facades\DB;

class WorkOrderService
{
    public function __construct(
        protected PricingService $pricingService,
        protected StockService $stockService,
        protected AuditService $auditService
    ) {}

    /**
     * Buat Work Order baru dengan nomor berurutan otomatis (WO-YYMM-####).
     */
    public function createWorkOrder(array $data, ?int $userId = null): WorkOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $woNumber = WorkOrder::generateNextNumber();

            $workOrder = WorkOrder::create([
                'wo_number' => $woNumber,
                'customer_id' => $data['customer_id'],
                'equipment_unit_id' => $data['equipment_unit_id'],
                'complaint' => $data['complaint'],
                'diagnosis' => $data['diagnosis'] ?? null,
                'status' => 'open',
                'mechanic_id' => $data['mechanic_id'] ?? null,
                'opened_at' => now(),
                'created_by' => $userId,
            ]);

            $this->auditService->log(
                action: 'work_order_created',
                entityType: 'WorkOrder',
                entityId: $workOrder->id,
                oldValues: null,
                newValues: [
                    'wo_number' => $workOrder->wo_number,
                    'customer_id' => $workOrder->customer_id,
                    'equipment_unit_id' => $workOrder->equipment_unit_id,
                    'status' => 'open',
                ],
                userId: $userId
            );

            return $workOrder;
        });
    }

    /**
     * BR-S4, BR-W2, BR-P3, BR-P4:
     * Tambahkan item sparepart ke order servis.
     * Mengurangi stok seketika (SERVICE_OUT) dan menyimpan snapshot harga dan HPP.
     */
    public function addPartItem(
        WorkOrder $workOrder,
        int $partId,
        int $qty,
        ?string $overrideReason = null,
        ?int $userId = null
    ): WorkOrderItem {
        // BR-W2: Item hanya bisa ditambah/diubah/dihapus saat status open atau in_progress
        if (!$workOrder->canModifyItems()) {
            throw new InvalidWorkOrderOperationException(
                "Item sparepart tidak dapat ditambahkan karena status order adalah '{$workOrder->status}' (BR-W2: hanya saat open atau in_progress)."
            );
        }

        if ($qty <= 0) {
            throw new InvalidWorkOrderOperationException("Jumlah kuantitas sparepart harus lebih besar dari 0.");
        }

        return DB::transaction(function () use ($workOrder, $partId, $qty, $overrideReason, $userId) {
            /** @var Part $part */
            $part = Part::findOrFail($partId);

            // BR-P3: Harga sparepart = parts.sell_price saat item dibuat (snapshot)
            $sellPrice = (int) $part->sell_price;

            // BR-P4: Harga jual tidak boleh lebih rendah dari HPP rata-rata tanpa alasan override admin
            $this->pricingService->validateSellPriceAgainstCost($sellPrice, (int) $part->avg_cost, $overrideReason);

            // BR-S4: Sparepart ditambahkan ke order servis -> SERVICE_OUT saat itu juga lewat StockService
            $movement = $this->stockService->recordServiceOut(
                partId: $part->id,
                qty: $qty,
                workOrderId: $workOrder->id,
                userId: $userId
            );

            // Snapshot harga jual dan snapshot HPP pada baris item
            $item = WorkOrderItem::create([
                'work_order_id' => $workOrder->id,
                'item_type' => 'part',
                'part_id' => $part->id,
                'service_id' => null,
                'description' => $part->name,
                'qty' => $qty,
                'unit_price' => $sellPrice, // snapshot BR-P2, BR-P3
                'unit_cost_snapshot' => (int) $movement->unit_cost_snapshot, // snapshot BR-S8
                'line_total' => $qty * $sellPrice,
            ]);

            return $item;
        });
    }

    /**
     * BR-W2, BR-P1, BR-P2:
     * Tambahkan item jasa ke order servis.
     * Mengambil tarif khusus customer jika ada atau default jasa, disimpan sebagai snapshot.
     */
    public function addServiceItem(
        WorkOrder $workOrder,
        int $serviceId,
        int $qty = 1,
        ?int $userId = null
    ): WorkOrderItem {
        // BR-W2: Item hanya bisa ditambah/diubah/dihapus saat status open atau in_progress
        if (!$workOrder->canModifyItems()) {
            throw new InvalidWorkOrderOperationException(
                "Item jasa tidak dapat ditambahkan karena status order adalah '{$workOrder->status}' (BR-W2: hanya saat open atau in_progress)."
            );
        }

        if ($qty <= 0) {
            throw new InvalidWorkOrderOperationException("Jumlah kuantitas jasa harus lebih besar dari 0.");
        }

        return DB::transaction(function () use ($workOrder, $serviceId, $qty, $userId) {
            /** @var Service $service */
            $service = Service::findOrFail($serviceId);

            // BR-P1: Harga jasa pada order = tarif khusus customer bila ada, jika tidak services.default_price
            $unitPrice = $this->pricingService->resolveServicePrice((int) $workOrder->customer_id, (int) $service->id);

            // BR-P2: Snapshot unit_price ke baris item
            $item = WorkOrderItem::create([
                'work_order_id' => $workOrder->id,
                'item_type' => 'service',
                'part_id' => null,
                'service_id' => $service->id,
                'description' => $service->name,
                'qty' => $qty,
                'unit_price' => $unitPrice, // snapshot BR-P1, BR-P2
                'unit_cost_snapshot' => 0, // BR-R2: Biaya jasa disederhanakan 0
                'line_total' => $qty * $unitPrice,
            ]);

            return $item;
        });
    }

    /**
     * BR-S4, BR-W2:
     * Hapus item dari order servis.
     * Jika item adalah sparepart, stok dikembalikan (SERVICE_RETURN) dengan unit_cost_snapshot semula.
     */
    public function removeItem(WorkOrder $workOrder, int $itemId, ?int $userId = null): void
    {
        // BR-W2: Item hanya bisa ditambah/diubah/dihapus saat status open atau in_progress
        if (!$workOrder->canModifyItems()) {
            throw new InvalidWorkOrderOperationException(
                "Item tidak dapat dihapus karena status order adalah '{$workOrder->status}' (BR-W2: hanya saat open atau in_progress)."
            );
        }

        DB::transaction(function () use ($workOrder, $itemId, $userId) {
            /** @var WorkOrderItem $item */
            $item = $workOrder->items()->where('id', $itemId)->lockForUpdate()->firstOrFail();

            // BR-S4: Dihapus dari order -> SERVICE_RETURN dengan unit_cost_snapshot asalnya
            if ($item->isPart() && $item->part_id) {
                $this->stockService->recordServiceReturn(
                    partId: (int) $item->part_id,
                    qty: (int) $item->qty,
                    unitCostSnapshot: (int) $item->unit_cost_snapshot,
                    workOrderId: $workOrder->id,
                    userId: $userId
                );
            }

            $item->delete();
        });
    }

    /**
     * BR-W1, BR-W3, BR-W4, BR-S4:
     * Transisi status order servis sesuai aturan bisnis.
     */
    public function transitionStatus(WorkOrder $workOrder, string $targetStatus, ?int $userId = null): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $targetStatus, $userId) {
            // Lock record work order untuk mencegah status race condition
            /** @var WorkOrder $wo */
            $wo = WorkOrder::where('id', $workOrder->id)->lockForUpdate()->firstOrFail();
            $currentStatus = $wo->status;

            if ($currentStatus === $targetStatus) {
                return $wo;
            }

            // BR-W3: Order invoiced tidak bisa diubah
            if ($currentStatus === 'invoiced') {
                throw new InvalidWorkOrderOperationException(
                    "Order servis yang telah ditagihkan (invoiced) tidak dapat diubah statusnya (BR-W3)."
                );
            }

            // Order cancelled adalah terminal
            if ($currentStatus === 'cancelled') {
                throw new InvalidWorkOrderOperationException(
                    "Order servis yang telah dibatalkan tidak dapat diaktifkan kembali."
                );
            }

            // Validasi peta transisi status legal (BR-W1)
            $allowedTransitions = [
                'open' => ['in_progress', 'cancelled'],
                'in_progress' => ['completed', 'cancelled'],
                'completed' => ['invoiced', 'cancelled'],
            ];

            $validNext = $allowedTransitions[$currentStatus] ?? [];

            if (!in_array($targetStatus, $validNext, true)) {
                throw new InvalidWorkOrderOperationException(
                    "Transisi status dari '{$currentStatus}' ke '{$targetStatus}' tidak valid (BR-W1: alur open -> in_progress -> completed -> invoiced)."
                );
            }

            // BR-W4: Order tanpa item tidak bisa diselesaikan
            if ($targetStatus === 'completed') {
                $itemCount = $wo->items()->count();
                if ($itemCount === 0) {
                    throw new InvalidWorkOrderOperationException(
                        "Order servis tanpa item pekerjaan atau sparepart tidak dapat diselesaikan (BR-W4)."
                    );
                }
                $wo->completed_at = now();
            }

            // BR-S4: Jika order dibatalkan (cancelled), kembalikan semua sparepart ke stok
            if ($targetStatus === 'cancelled') {
                $wo->load('partItems');
                foreach ($wo->partItems as $partItem) {
                    if ($partItem->part_id) {
                        $this->stockService->recordServiceReturn(
                            partId: (int) $partItem->part_id,
                            qty: (int) $partItem->qty,
                            unitCostSnapshot: (int) $partItem->unit_cost_snapshot,
                            workOrderId: $wo->id,
                            userId: $userId
                        );
                    }
                }
            }

            $wo->status = $targetStatus;
            $wo->save();

            // Catat log audit transisi status
            $this->auditService->log(
                action: 'work_order_status_changed',
                entityType: 'WorkOrder',
                entityId: $wo->id,
                oldValues: ['status' => $currentStatus],
                newValues: ['status' => $targetStatus],
                userId: $userId
            );

            return $wo;
        });
    }
}
