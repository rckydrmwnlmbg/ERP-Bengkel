<?php

namespace App\Http\Controllers;

use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Service;
use App\Services\AuditService;
use App\Services\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        protected PricingService $pricingService,
        protected AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('master-data.view');

        $query = Service::query()->withCount('customerRates');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $services = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('services.index', compact('services'));
    }

    public function create(): View
    {
        Gate::authorize('master-data.manage');

        return view('services.create');
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $service = Service::create($data);

        $this->auditService->log(
            action: 'service_created',
            entityType: 'Service',
            entityId: $service->id,
            oldValues: null,
            newValues: [
                'code' => $service->code,
                'name' => $service->name,
                'default_price' => $service->default_price,
            ]
        );

        return redirect()->route('services.index')
            ->with('success', "Jasa {$service->name} berhasil ditambahkan.");
    }

    public function edit(Service $service): View
    {
        Gate::authorize('master-data.manage');

        return view('services.edit', compact('service'));
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $newPrice = (int) $request->input('default_price');
        $this->pricingService->updateServiceDefaultPrice($service, $newPrice);

        $service->code = $request->input('code');
        $service->name = $request->input('name');
        $service->is_active = $request->boolean('is_active');
        $service->save();

        return redirect()->route('services.index')
            ->with('success', "Data jasa {$service->name} berhasil diperbarui.");
    }

    public function destroy(Service $service): RedirectResponse
    {
        Gate::authorize('master-data.manage');

        $serviceName = $service->name;
        $this->auditService->log(
            action: 'SERVICE_DELETE',
            entityType: 'Service',
            entityId: $service->id,
            oldValues: ['name' => $serviceName, 'code' => $service->code],
            newValues: null
        );

        $service->delete();

        return redirect()->route('services.index')
            ->with('success', "Jasa {$serviceName} berhasil dihapus.");
    }
}
