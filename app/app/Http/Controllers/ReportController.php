<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Resolusi rentang tanggal dari request (Hari ini, Bulan ini, Tahun ini, atau Custom).
     */
    protected function resolveDateRange(Request $request): array
    {
        $preset = $request->query('preset');
        $now = Carbon::now();

        if ($preset === 'today') {
            $startDate = $now->toDateString();
            $endDate = $now->toDateString();
        } elseif ($preset === 'this_month') {
            $startDate = $now->copy()->startOfMonth()->toDateString();
            $endDate = $now->copy()->endOfMonth()->toDateString();
        } elseif ($preset === 'last_month') {
            $startDate = $now->copy()->subMonth()->startOfMonth()->toDateString();
            $endDate = $now->copy()->subMonth()->endOfMonth()->toDateString();
        } elseif ($preset === 'this_year') {
            $startDate = $now->copy()->startOfYear()->toDateString();
            $endDate = $now->copy()->endOfYear()->toDateString();
        } else {
            $startDate = $request->query('start_date', $now->copy()->startOfMonth()->toDateString());
            $endDate = $request->query('end_date', $now->copy()->endOfMonth()->toDateString());
        }

        return [$startDate, $endDate, $preset];
    }

    /**
     * Laporan Omzet (BR-R1).
     */
    public function revenue(Request $request): View
    {
        Gate::authorize('reports.view');

        [$startDate, $endDate, $preset] = $this->resolveDateRange($request);
        $report = $this->reportService->getRevenueReport($startDate, $endDate);

        return view('reports.revenue', compact('report', 'startDate', 'endDate', 'preset'));
    }

    /**
     * Laporan Laba Rugi Sederhana (BR-R2, BR-R3).
     */
    public function profitLoss(Request $request): View
    {
        Gate::authorize('reports.view');

        [$startDate, $endDate, $preset] = $this->resolveDateRange($request);
        $report = $this->reportService->getProfitLossReport($startDate, $endDate);

        return view('reports.profit_loss', compact('report', 'startDate', 'endDate', 'preset'));
    }

    /**
     * Laporan Arus Kas (BR-R4, BR-R5).
     */
    public function cashFlow(Request $request): View
    {
        Gate::authorize('reports.view');

        [$startDate, $endDate, $preset] = $this->resolveDateRange($request);
        $report = $this->reportService->getCashFlowReport($startDate, $endDate);

        return view('reports.cash_flow', compact('report', 'startDate', 'endDate', 'preset'));
    }

    /**
     * Laporan Piutang & Aging (BR-R6, BR-R7).
     * Dapat diakses Admin dan Kasir (PRD).
     */
    public function receivables(Request $request): View
    {
        Gate::authorize('reports.receivables.view');

        $asOfDate = $request->query('as_of_date', Carbon::today()->toDateString());
        $report = $this->reportService->getReceivablesReport($asOfDate);

        return view('reports.receivables', compact('report', 'asOfDate'));
    }

    /**
     * Ekspor Laporan ke format Excel (.csv dengan delimiter kompatibel & BOM UTF-8).
     */
    public function exportExcel(Request $request, string $type): StreamedResponse
    {
        if ($type === 'receivables') {
            Gate::authorize('reports.receivables.view');
        } else {
            Gate::authorize('reports.view');
        }

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $filename = "laporan_{$type}_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($type, $startDate, $endDate, $request) {
            $handle = fopen('php://output', 'w');
            // Tulis BOM UTF-8 agar Excel membuka file teks dengan format karakter yang tepat
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            switch ($type) {
                case 'revenue':
                    $data = $this->reportService->getRevenueReport($startDate, $endDate);
                    fputcsv($handle, ['LAPORAN OMZET / PENJUALAN']);
                    fputcsv($handle, ['Periode', "{$startDate} s/d {$endDate}"]);
                    fputcsv($handle, ['Total Omzet', 'Rp ' . number_format($data['total_revenue'], 0, ',', '.')]);
                    fputcsv($handle, []);
                    fputcsv($handle, ['No. Faktur', 'Tanggal', 'Customer', 'Sumber', 'Subtotal', 'Diskon', 'Pajak', 'Total']);
                    foreach ($data['invoices'] as $inv) {
                        fputcsv($handle, [
                            $inv->invoice_number,
                            $inv->issue_date->format('Y-m-d'),
                            $inv->customer?->name ?? '-',
                            $inv->source_type === 'work_order' ? 'Order Servis' : 'Penjualan Toko',
                            $inv->subtotal,
                            $inv->discount_amount,
                            $inv->tax_amount,
                            $inv->total,
                        ]);
                    }
                    break;

                case 'profit-loss':
                    $data = $this->reportService->getProfitLossReport($startDate, $endDate);
                    fputcsv($handle, ['LAPORAN LABA RUGI SEDERHANA']);
                    fputcsv($handle, ['Periode', "{$startDate} s/d {$endDate}"]);
                    fputcsv($handle, ['Catatan BR-R2', 'Jasa dianggap berbiaya HPP Rp 0 (penyederhanaan demo)']);
                    fputcsv($handle, []);
                    fputcsv($handle, ['Komponen', 'Nominal (Rp)']);
                    fputcsv($handle, ['1. Pendapatan (Omzet)', $data['total_revenue']]);
                    fputcsv($handle, ['2. Harga Pokok Penjualan (HPP Part)', $data['total_hpp']]);
                    fputcsv($handle, ['LABA KOTOR (Omzet - HPP)', $data['gross_profit']]);
                    fputcsv($handle, ['3. Beban Operasional', $data['total_expenses']]);
                    fputcsv($handle, ['LABA BERSIH SEDERHANA', $data['net_profit']]);
                    fputcsv($handle, []);
                    fputcsv($handle, ['Rincian Pengeluaran Operasional']);
                    fputcsv($handle, ['Tanggal', 'Kategori', 'Keterangan', 'Jumlah']);
                    foreach ($data['expenses'] as $exp) {
                        fputcsv($handle, [
                            $exp->expense_date->format('Y-m-d'),
                            $exp->category,
                            $exp->description ?? '-',
                            $exp->amount,
                        ]);
                    }
                    break;

                case 'cash-flow':
                    $data = $this->reportService->getCashFlowReport($startDate, $endDate);
                    fputcsv($handle, ['LAPORAN ARUS KAS']);
                    fputcsv($handle, ['Periode', "{$startDate} s/d {$endDate}"]);
                    fputcsv($handle, ['Catatan BR-R5', 'Pembelian supplier dianggap tunai di tanggal pembelian; hutang supplier di luar scope']);
                    fputcsv($handle, []);
                    fputcsv($handle, ['Komponen Arus Kas', 'Nominal (Rp)']);
                    fputcsv($handle, ['Arus Kas Masuk (Pembayaran Pelanggan)', $data['cash_in']]);
                    fputcsv($handle, ['Arus Kas Keluar - Pembelian Barang Masuk', $data['total_purchases']]);
                    fputcsv($handle, ['Arus Kas Keluar - Biaya Operasional', $data['total_expenses']]);
                    fputcsv($handle, ['Total Arus Kas Keluar', $data['cash_out']]);
                    fputcsv($handle, ['ARUS KAS BERSIH', $data['net_cash_flow']]);
                    break;

                case 'receivables':
                    $asOfDate = $request->query('as_of_date', Carbon::today()->toDateString());
                    $data = $this->reportService->getReceivablesReport($asOfDate);
                    fputcsv($handle, ['LAPORAN PIUTANG & AGING']);
                    fputcsv($handle, ['Per Tanggal', $asOfDate]);
                    fputcsv($handle, ['Total Piutang', 'Rp ' . number_format($data['total_receivables'], 0, ',', '.')]);
                    fputcsv($handle, []);
                    fputcsv($handle, ['Distribusi Aging']);
                    fputcsv($handle, ['Belum Jatuh Tempo', $data['aging']['not_due']]);
                    fputcsv($handle, ['1 - 30 Hari', $data['aging']['days_1_30']]);
                    fputcsv($handle, ['31 - 60 Hari', $data['aging']['days_31_60']]);
                    fputcsv($handle, ['61 - 90 Hari', $data['aging']['days_61_90']]);
                    fputcsv($handle, ['> 90 Hari', $data['aging']['days_over_90']]);
                    fputcsv($handle, []);
                    fputcsv($handle, ['Rekap Piutang per Customer']);
                    fputcsv($handle, ['Kode Customer', 'Nama Customer', 'Jumlah Faktur', 'Total Piutang (Rp)']);
                    foreach ($data['customer_summary'] as $row) {
                        fputcsv($handle, [
                            $row['customer']->code,
                            $row['customer']->name,
                            $row['invoice_count'],
                            $row['total_receivable'],
                        ]);
                    }
                    break;
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Tampilan Cetak / Print / Simpan PDF Laporan.
     */
    public function print(Request $request, string $type): View
    {
        if ($type === 'receivables') {
            Gate::authorize('reports.receivables.view');
            $asOfDate = $request->query('as_of_date', Carbon::today()->toDateString());
            $data = $this->reportService->getReceivablesReport($asOfDate);
            $startDate = null;
            $endDate = null;
        } else {
            Gate::authorize('reports.view');
            [$startDate, $endDate] = $this->resolveDateRange($request);

            $data = match ($type) {
                'revenue' => $this->reportService->getRevenueReport($startDate, $endDate),
                'profit-loss' => $this->reportService->getProfitLossReport($startDate, $endDate),
                'cash-flow' => $this->reportService->getCashFlowReport($startDate, $endDate),
                default => abort(404),
            };
            $asOfDate = null;
        }

        $shopName = Setting::get('shop_name', 'Bengkel Alat Berat & Toko Sparepart');

        return view('reports.print', compact('type', 'data', 'startDate', 'endDate', 'asOfDate', 'shopName'));
    }
}
