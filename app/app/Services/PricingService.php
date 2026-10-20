<?php

namespace App\Services;

use App\Models\CustomerServiceRate;
use App\Models\Part;
use App\Models\Service;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * BR-P1: Harga jasa pada order = tarif khusus customer bila ada, jika tidak services.default_price.
     */
    public function resolveServicePrice(int $customerId, int $serviceId): int
    {
        $rate = CustomerServiceRate::where('customer_id', $customerId)
            ->where('service_id', $serviceId)
            ->first();

        if ($rate !== null) {
            return (int) $rate->price;
        }

        $service = Service::findOrFail($serviceId);
        return (int) $service->default_price;
    }

    /**
     * BR-P4: Harga jual tidak boleh lebih rendah dari harga pokok rata-rata saat dibuat;
     * admin boleh override dengan alasan (tercatat di audit log).
     */
    public function validateSellPriceAgainstCost(int $sellPrice, int $avgCost, ?string $overrideReason = null): void
    {
        if ($avgCost > 0 && $sellPrice < $avgCost) {
            if (empty(trim((string) $overrideReason))) {
                throw ValidationException::withMessages([
                    'sell_price' => 'Harga jual tidak boleh lebih rendah dari harga pokok rata-rata (HPP), kecuali admin menyertakan alasan override (BR-P4).',
                ]);
            }
        }
    }

    /**
     * Update harga jual sparepart dan catat audit log jika harga berubah.
     */
    public function updatePartPrice(Part $part, int $newSellPrice, ?string $overrideReason = null, ?int $userId = null): Part
    {
        $this->validateSellPriceAgainstCost($newSellPrice, (int) $part->avg_cost, $overrideReason);

        $oldPrice = (int) $part->sell_price;
        if ($oldPrice !== $newSellPrice) {
            $part->sell_price = $newSellPrice;
            $part->save();

            $newValues = ['sell_price' => $newSellPrice];
            if (!empty($overrideReason)) {
                $newValues['override_reason'] = $overrideReason;
            }

            $this->auditService->log(
                action: 'price_change',
                entityType: 'Part',
                entityId: $part->id,
                oldValues: ['sell_price' => $oldPrice],
                newValues: $newValues,
                userId: $userId
            );
        }

        return $part;
    }

    /**
     * Update harga default jasa dan catat audit log jika harga berubah.
     */
    public function updateServiceDefaultPrice(Service $service, int $newPrice, ?int $userId = null): Service
    {
        $oldPrice = (int) $service->default_price;
        if ($oldPrice !== $newPrice) {
            $service->default_price = $newPrice;
            $service->save();

            $this->auditService->log(
                action: 'price_change',
                entityType: 'Service',
                entityId: $service->id,
                oldValues: ['default_price' => $oldPrice],
                newValues: ['default_price' => $newPrice],
                userId: $userId
            );
        }

        return $service;
    }

    /**
     * Pasang / perbarui tarif jasa khusus customer dan catat audit log.
     */
    public function setCustomerServiceRate(int $customerId, int $serviceId, int $price, ?int $userId = null): CustomerServiceRate
    {
        $rate = CustomerServiceRate::where('customer_id', $customerId)
            ->where('service_id', $serviceId)
            ->first();

        if ($rate) {
            $oldPrice = (int) $rate->price;
            if ($oldPrice !== $price) {
                $rate->price = $price;
                $rate->save();

                $this->auditService->log(
                    action: 'price_change',
                    entityType: 'CustomerServiceRate',
                    entityId: $rate->id,
                    oldValues: ['customer_id' => $customerId, 'service_id' => $serviceId, 'price' => $oldPrice],
                    newValues: ['customer_id' => $customerId, 'service_id' => $serviceId, 'price' => $price],
                    userId: $userId
                );
            }
        } else {
            $rate = CustomerServiceRate::create([
                'customer_id' => $customerId,
                'service_id' => $serviceId,
                'price' => $price,
            ]);

            $this->auditService->log(
                action: 'rate_created',
                entityType: 'CustomerServiceRate',
                entityId: $rate->id,
                oldValues: null,
                newValues: ['customer_id' => $customerId, 'service_id' => $serviceId, 'price' => $price],
                userId: $userId
            );
        }

        return $rate;
    }

    /**
     * Hapus tarif jasa khusus customer dan catat audit log.
     */
    public function deleteCustomerServiceRate(CustomerServiceRate $rate, ?int $userId = null): void
    {
        $oldValues = [
            'customer_id' => $rate->customer_id,
            'service_id' => $rate->service_id,
            'price' => $rate->price,
        ];
        $rateId = $rate->id;
        $rate->delete();

        $this->auditService->log(
            action: 'rate_deleted',
            entityType: 'CustomerServiceRate',
            entityId: $rateId,
            oldValues: $oldValues,
            newValues: null,
            userId: $userId
        );
    }
}
