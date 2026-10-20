<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidPaymentOperationException;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\VoidPaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function store(StorePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('payments.manage');

        try {
            $payment = $this->paymentService->recordPayment(
                invoice: $invoice,
                data: $request->validated(),
                userId: Auth::id()
            );

            return redirect()->route('invoices.show', $invoice)
                ->with('success', "Pembayaran sebesar Rp" . number_format($payment->amount, 0, ',', '.') . " berhasil dicatat.");
        } catch (InvalidPaymentOperationException $e) {
            return redirect()->route('invoices.show', $invoice)
                ->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function void(VoidPaymentRequest $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('void-financial');

        $invoice = $payment->invoice;

        try {
            $this->paymentService->voidPayment(
                payment: $payment,
                reason: $request->input('reason'),
                userId: Auth::id()
            );

            return redirect()->route('invoices.show', $invoice)
                ->with('success', "Pembayaran sebesar Rp" . number_format($payment->amount, 0, ',', '.') . " berhasil dibatalkan (void). Status tagihan telah diperbarui.");
        } catch (InvalidPaymentOperationException $e) {
            return redirect()->route('invoices.show', $invoice)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
