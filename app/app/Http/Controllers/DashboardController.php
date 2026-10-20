<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();

        // Data dashboard disinkronkan melalui ReportService (BR-R8)
        $summary = $this->reportService->getDashboardSummary();

        return view('dashboard', compact('summary', 'user'));
    }
}
