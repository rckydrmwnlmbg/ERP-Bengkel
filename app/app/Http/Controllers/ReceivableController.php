<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReceivableController extends Controller
{
    public function index(Request $request): View
    {
        // Admin dan Kasir berhak melihat piutang (PRD)
        Gate::authorize('invoices.manage');

        // Ambil semua invoice aktif non-void yang belum lunas
        $unpaidInvoices = Invoice::with(['customer', 'validPayments'])
            ->where('status', '!=', 'void')
            ->whereIn('status', ['issued', 'partially_paid'])
            ->orderBy('due_date', 'asc')
            ->get()
            ->filter(fn (Invoice $inv) => $inv->remainingBalance() > 0);

        // Hitung total piutang keseluruhan
        $totalReceivables = $unpaidInvoices->sum(fn (Invoice $inv) => $inv->remainingBalance());

        // Hitung aging piutang berdasarkan due_date (BR-R7)
        $today = Carbon::today();
        $aging = [
            'not_due' => 0,     // <= 0 hari
            'days_1_30' => 0,   // 1 - 30 hari
            'days_31_60' => 0,  // 31 - 60 hari
            'days_61_90' => 0,  // 61 - 90 hari
            'days_over_90' => 0 // > 90 hari
        ];

        foreach ($unpaidInvoices as $inv) {
            $rem = $inv->remainingBalance();
            $daysOverdue = (int) $inv->due_date->diffInDays($today, false);

            if ($daysOverdue <= 0) {
                $aging['not_due'] += $rem;
            } elseif ($daysOverdue <= 30) {
                $aging['days_1_30'] += $rem;
            } elseif ($daysOverdue <= 60) {
                $aging['days_31_60'] += $rem;
            } elseif ($daysOverdue <= 90) {
                $aging['days_61_90'] += $rem;
            } else {
                $aging['days_over_90'] += $rem;
            }
        }

        // Rekap per customer (BR-R6)
        $customerSummary = Customer::with(['invoices' => function ($q) {
            $q->where('status', '!=', 'void')
              ->whereIn('status', ['issued', 'partially_paid'])
              ->with('validPayments');
        }])->get()
        ->map(function (Customer $cust) {
            $rem = $cust->invoices->sum(fn (Invoice $inv) => $inv->remainingBalance());
            return [
                'customer' => $cust,
                'total_receivable' => $rem,
                'invoice_count' => $cust->invoices->filter(fn ($i) => $i->remainingBalance() > 0)->count(),
            ];
        })
        ->filter(fn ($row) => $row['total_receivable'] > 0)
        ->sortByDesc('total_receivable')
        ->values();

        return view('receivables.index', compact(
            'unpaidInvoices',
            'totalReceivables',
            'aging',
            'customerSummary'
        ));
    }
}
