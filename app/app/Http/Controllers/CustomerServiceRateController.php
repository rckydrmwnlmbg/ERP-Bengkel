<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\SetCustomerServiceRateRequest;
use App\Models\Customer;
use App\Models\CustomerServiceRate;
use App\Services\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CustomerServiceRateController extends Controller
{
    public function __construct(
        protected PricingService $pricingService
    ) {}

    public function store(SetCustomerServiceRateRequest $request, Customer $customer): RedirectResponse
    {
        $this->pricingService->setCustomerServiceRate(
            customerId: $customer->id,
            serviceId: (int) $request->input('service_id'),
            price: (int) $request->input('price')
        );

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Tarif khusus jasa untuk pelanggan berhasil disimpan dan dicatat ke audit log.');
    }

    public function destroy(Customer $customer, CustomerServiceRate $rate): RedirectResponse
    {
        Gate::authorize('master-data.manage');

        $this->pricingService->deleteCustomerServiceRate($rate);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Tarif khusus jasa telah dihapus (kembali ke tarif standar).');
    }
}
