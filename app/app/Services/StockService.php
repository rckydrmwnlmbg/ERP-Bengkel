<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockMovementException;
use App\Models\Part;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * MENGAPA ROW LOCKING (lockForUpdate):
     * Di lingkungan sistem ERP operasional bengkel, beberapa pengguna (misalnya kasir dan mekanik)
     * dapat mengakses dan mencoba mengambil sparepart yang sama secara bersamaan (konkuren).
     * Jika stok hanya tersisa 1 unit dan dua request membaca data stok secara paralel tanpa lock,
     * keduanya akan melihat stok = 1, sehingga kedua request meloloskan pengurangan stok dan menghasilkan
     * stok minus (-1), yang melanggar aturan integritas bisnis BR-S2.
     * 
     * Dengan DB Transaction dan lockForUpdate() pada baris Part:
     * Request pertama akan memegang exclusive lock pada baris part tersebut.
     * Request kedua diwajibkan mengantre hingga request pertama selesai (commit/rollback).
     * Saat request kedua memperoleh giliran, ia membaca saldo teranyar (stok = 0),
     * sehingga permintaannya langsung ditolak secara aman dengan InsufficientStockException.
     */
    protected function applyMovement(
        int $partId,
        string $type,
        int $qtyChange,
        int $unitCostSnapshot,
        ?string $refType = null,
        ?int $refId = null,
        ?string $reason = null,
        ?int $userId = null
    ): StockMovement {
        if ($qtyChange === 0) {
            throw new InvalidStockMovementException("Perubahan kuantitas stok tidak boleh 0 (BR-S3).");
        }

        // DB::transaction dengan retry 5 kali untuk mengantisipasi lock contention / deadlock
        // pada akses konkuren tingkat tinggi (BR-S8).
        return DB::transaction(function () use (
            $partId,
            $type,
            $qtyChange,
            $unitCostSnapshot,
            $refType,
            $refId,
            $reason,
            $userId
        ) {
            // Row lock eksklusif untuk mencegah race condition (BR-S8)
            /** @var Part $part */
            $part = Part::where('id', $partId)->lockForUpdate()->firstOrFail();

            $oldQty = (int) $part->qty_on_hand;
            $newBalance = $oldQty + $qtyChange;

            // BR-S2: Stok tidak boleh minus. Tolak dengan pesan jelas jika newBalance < 0
            if ($newBalance < 0) {
                throw new InsufficientStockException(
                    "Stok sparepart '{$part->name}' ({$part->sku}) tidak mencukupi. " .
                    "Stok saat ini: {$oldQty}, permintaan pengurangan: " . abs($qtyChange) . " (BR-S2)."
                );
            }

            // BR-S7: Kalkulasi Weighted Average Cost (WAC / HPP rata-rata tertimbang)
            if ($qtyChange > 0) {
                $oldAvg = (int) $part->avg_cost;
                $inQty = $qtyChange;
                $inCost = $unitCostSnapshot;

                /*
                 * MENGAPA RUMUS WEIGHTED AVERAGE COST DIHITUNG SAAT STOK BERTAMBAH:
                 * Persediaan barang dagang/sparepart dibeli secara bertahap dengan harga beli
                 * dari supplier yang berfluktuasi (misal Rp100.000 pada batch 1 dan Rp120.000 pada batch 2).
                 * Sistem menggunakan metode Biaya Rata-rata Tertimbang (Weighted Average Cost)
                 * agar HPP per unit mencerminkan rata-rata proporsional dari total modal yang tertanam.
                 * 
                 * Rumus:
                 * total_nilai_baru = (qty_lama × avg_lama) + (qty_masuk × biaya_masuk)
                 * avg_baru = round(total_nilai_baru / total_qty_baru)
                 * 
                 * Pembulatan menggunakan round half-up ke rupiah terdekat karena aturan sistem
                 * mewajibkan seluruh nominal uang adalah integer rupiah (BIGINT) tanpa pecahan sen.
                 */
                if ($newBalance > 0) {
                    $totalValue = ($oldQty * $oldAvg) + ($inQty * $inCost);
                    $newAvg = (int) round($totalValue / $newBalance, 0, PHP_ROUND_HALF_UP);
                    $part->avg_cost = $newAvg;
                }
            } else {
                /*
                 * MENGAPA STOK KELUAR TIDAK MENGUBAH AVG COST:
                 * Pengeluaran sparepart (untuk servis atau penjualan) mengeluarkan unit fisik
                 * dari gudang berdasarkan HPP rata-rata yang sudah berlaku saat itu.
                 * Unit-unit yang masih tersisa di gudang tetap memiliki biaya perolehan rata-rata
                 * yang sama persis per unitnya, sehingga avg_cost tidak mengalami perubahan.
                 */
            }

            // BR-S1: Perbarui cache qty_on_hand pada tabel parts
            $part->qty_on_hand = $newBalance;
            $part->save();

            $currentUserId = $userId ?? Auth::id();

            // BR-S3: Catat pergerakan stok immutable (append-only)
            $movement = StockMovement::create([
                'part_id' => $part->id,
                'type' => $type,
                'qty_change' => $qtyChange,
                'unit_cost_snapshot' => $unitCostSnapshot,
                'balance_after' => $newBalance,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'reason' => $reason,
                'created_by' => $currentUserId,
                'created_at' => now(),
            ]);

            // Catat log audit untuk perubahan stok
            $this->auditService->log(
                action: 'stock_movement',
                entityType: 'Part',
                entityId: $part->id,
                oldValues: [
                    'qty_on_hand' => $oldQty,
                    'avg_cost' => (int) $part->getOriginal('avg_cost'),
                ],
                newValues: [
                    'qty_on_hand' => $newBalance,
                    'avg_cost' => (int) $part->avg_cost,
                    'movement_type' => $type,
                    'qty_change' => $qtyChange,
                    'movement_id' => $movement->id,
                ],
                userId: $currentUserId
            );

            return $movement;
        }, 5);
    }

    /**
     * BR-S3 / BR-S7: Barang masuk dari pembelian supplier (PURCHASE_IN).
     * Menambah stok dan memperbarui harga pokok rata-rata tertimbang.
     */
    public function recordPurchaseIn(
        int $partId,
        int $qty,
        int $unitCost,
        ?int $purchaseId = null,
        ?string $reference = null,
        ?int $userId = null
    ): StockMovement {
        if ($qty <= 0) {
            throw new InvalidStockMovementException("Jumlah barang masuk harus lebih besar dari 0.");
        }
        if ($unitCost < 0) {
            throw new InvalidStockMovementException("Harga beli satuan tidak boleh minus.");
        }

        return $this->applyMovement(
            partId: $partId,
            type: 'PURCHASE_IN',
            qtyChange: $qty,
            unitCostSnapshot: $unitCost,
            refType: 'Purchase',
            refId: $purchaseId,
            reason: $reference ? "Barang Masuk Ref: {$reference}" : "Barang Masuk Pembelian",
            userId: $userId
        );
    }

    /**
     * BR-S4 / BR-S8: Pengeluaran sparepart untuk order servis (SERVICE_OUT).
     * Mengurangi stok saat ditambahkan ke order servis.
     * Menyimpan unit_cost_snapshot = avg_cost saat itu untuk pencatatan HPP.
     */
    public function recordServiceOut(
        int $partId,
        int $qty,
        ?int $workOrderId = null,
        ?int $userId = null
    ): StockMovement {
        if ($qty <= 0) {
            throw new InvalidStockMovementException("Jumlah sparepart servis yang dikeluarkan harus lebih besar dari 0.");
        }

        $part = Part::findOrFail($partId);

        return $this->applyMovement(
            partId: $partId,
            type: 'SERVICE_OUT',
            qtyChange: -$qty,
            unitCostSnapshot: (int) $part->avg_cost, // BR-S8: snapshot avg saat itu
            refType: 'WorkOrder',
            refId: $workOrderId,
            reason: $workOrderId ? "Penggunaan Servis WO #{$workOrderId}" : "Penggunaan Order Servis",
            userId: $userId
        );
    }

    /**
     * BR-S4 / BR-S7: Pengembalian sparepart dari order servis yang dihapus/dibatalkan (SERVICE_RETURN).
     * Menggunakan unit_cost_snapshot pergerakan asalnya sebagai biaya_masuk.
     */
    public function recordServiceReturn(
        int $partId,
        int $qty,
        int $unitCostSnapshot,
        ?int $workOrderId = null,
        ?int $userId = null
    ): StockMovement {
        if ($qty <= 0) {
            throw new InvalidStockMovementException("Jumlah sparepart yang diretur harus lebih besar dari 0.");
        }

        return $this->applyMovement(
            partId: $partId,
            type: 'SERVICE_RETURN',
            qtyChange: $qty,
            unitCostSnapshot: $unitCostSnapshot,
            refType: 'WorkOrder',
            refId: $workOrderId,
            reason: $workOrderId ? "Retur Sparepart WO #{$workOrderId}" : "Retur Order Servis",
            userId: $userId
        );
    }

    /**
     * BR-S5 / BR-S8: Pengeluaran sparepart untuk penjualan langsung di toko (SALE_OUT).
     * Mengurangi stok saat invoice penjualan terbit.
     */
    public function recordSaleOut(
        int $partId,
        int $qty,
        ?int $invoiceId = null,
        ?int $userId = null
    ): StockMovement {
        if ($qty <= 0) {
            throw new InvalidStockMovementException("Jumlah sparepart yang dijual harus lebih besar dari 0.");
        }

        $part = Part::findOrFail($partId);

        return $this->applyMovement(
            partId: $partId,
            type: 'SALE_OUT',
            qtyChange: -$qty,
            unitCostSnapshot: (int) $part->avg_cost,
            refType: 'Invoice',
            refId: $invoiceId,
            reason: $invoiceId ? "Penjualan Langsung Invoice #{$invoiceId}" : "Penjualan Langsung Toko",
            userId: $userId
        );
    }

    /**
     * BR-S5 / BR-S7: Retur penjualan langsung karena pembatalan/void invoice (SALE_RETURN).
     * Menambah stok kembali menggunakan snapshot HPP asalnya.
     */
    public function recordSaleReturn(
        int $partId,
        int $qty,
        int $unitCostSnapshot,
        ?int $invoiceId = null,
        ?int $userId = null
    ): StockMovement {
        if ($qty <= 0) {
            throw new InvalidStockMovementException("Jumlah sparepart retur penjualan harus lebih besar dari 0.");
        }

        return $this->applyMovement(
            partId: $partId,
            type: 'SALE_RETURN',
            qtyChange: $qty,
            unitCostSnapshot: $unitCostSnapshot,
            refType: 'Invoice',
            refId: $invoiceId,
            reason: $invoiceId ? "Retur Void Penjualan Invoice #{$invoiceId}" : "Retur Penjualan Langsung",
            userId: $userId
        );
    }

    /**
     * BR-S6: Penyesuaian stok fisik (stok opname / koreksi selisih).
     * Wajib menyertakan alasan tertulis dan hanya boleh dilakukan oleh admin.
     */
    public function recordAdjustment(
        int $partId,
        string $type,
        int $qty,
        string $reason,
        ?int $userId = null
    ): StockMovement {
        if (!in_array($type, ['ADJUSTMENT_IN', 'ADJUSTMENT_OUT'], true)) {
            throw new InvalidStockMovementException("Tipe penyesuaian harus ADJUSTMENT_IN atau ADJUSTMENT_OUT.");
        }

        if ($qty <= 0) {
            throw new InvalidStockMovementException("Kuantitas penyesuaian harus lebih besar dari 0.");
        }

        if (empty(trim($reason))) {
            throw new InvalidStockMovementException("Penyesuaian stok wajib menyertakan alasan yang jelas (BR-S6).");
        }

        $part = Part::findOrFail($partId);
        $qtyChange = ($type === 'ADJUSTMENT_IN') ? $qty : -$qty;
        $unitCostSnapshot = (int) $part->avg_cost;

        return $this->applyMovement(
            partId: $partId,
            type: $type,
            qtyChange: $qtyChange,
            unitCostSnapshot: $unitCostSnapshot,
            refType: 'StockAdjustment',
            refId: null,
            reason: "Penyesuaian Stok: {$reason}",
            userId: $userId
        );
    }

    /**
     * BR-S1: Verifikasi integritas invariant stok.
     * parts.qty_on_hand harus selalu identik dengan SUM(stock_movements.qty_change).
     */
    public function verifyStockIntegrity(int $partId): bool
    {
        $part = Part::findOrFail($partId);
        $sumMovements = (int) StockMovement::where('part_id', $partId)->sum('qty_change');

        return (int) $part->qty_on_hand === $sumMovements;
    }
}
