<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Tampilkan daftar pengeluaran operasional (Admin only).
     */
    public function index(Request $request): View
    {
        Gate::authorize('expenses.manage');

        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());
        $category = $request->query('category');

        $query = Expense::with('creator')
            ->whereDate('expense_date', '>=', $startDate)
            ->whereDate('expense_date', '<=', $endDate);

        if (!empty($category)) {
            $query->where('category', $category);
        }

        $totalAmount = (int) (clone $query)->sum('amount');
        $expenses = $query->orderBy('expense_date', 'desc')->orderBy('id', 'desc')->paginate(20)->withQueryString();

        // Kategori umum untuk filter / dropdown
        $categories = Expense::select('category')->distinct()->pluck('category')->toArray();
        if (empty($categories)) {
            $categories = ['Listrik & Air', 'Sewa Tempat', 'Gaji & Upah', 'Konsumsi', 'Transportasi & Logistik', 'Perlengkapan Bengkel', 'Lain-lain'];
        }

        return view('expenses.index', compact(
            'expenses',
            'startDate',
            'endDate',
            'category',
            'totalAmount',
            'categories'
        ));
    }

    /**
     * Form tambah pengeluaran operasional baru.
     */
    public function create(): View
    {
        Gate::authorize('expenses.manage');

        $commonCategories = ['Listrik & Air', 'Sewa Tempat', 'Gaji & Upah', 'Konsumsi', 'Transportasi & Logistik', 'Perlengkapan Bengkel', 'Lain-lain'];

        return view('expenses.create', compact('commonCategories'));
    }

    /**
     * Simpan pengeluaran operasional baru.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        Gate::authorize('expenses.manage');

        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        $expense = Expense::create($validated);

        $this->auditService->log(
            action: 'expense_created',
            entityType: 'Expense',
            entityId: $expense->id,
            oldValues: null,
            newValues: $expense->only(['expense_date', 'category', 'amount', 'description']),
            userId: $request->user()->id
        );

        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran operasional berhasil dicatat.');
    }

    /**
     * Form edit pengeluaran operasional.
     */
    public function edit(Expense $expense): View
    {
        Gate::authorize('expenses.manage');

        $commonCategories = ['Listrik & Air', 'Sewa Tempat', 'Gaji & Upah', 'Konsumsi', 'Transportasi & Logistik', 'Perlengkapan Bengkel', 'Lain-lain'];

        return view('expenses.edit', compact('expense', 'commonCategories'));
    }

    /**
     * Perbarui pengeluaran operasional.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        Gate::authorize('expenses.manage');

        $oldValues = $expense->only(['expense_date', 'category', 'amount', 'description']);
        $validated = $request->validated();

        $expense->update($validated);

        $this->auditService->log(
            action: 'expense_updated',
            entityType: 'Expense',
            entityId: $expense->id,
            oldValues: $oldValues,
            newValues: $expense->only(['expense_date', 'category', 'amount', 'description']),
            userId: $request->user()->id
        );

        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran operasional berhasil diperbarui.');
    }

    /**
     * Hapus pengeluaran operasional.
     */
    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        Gate::authorize('expenses.manage');

        $oldValues = $expense->only(['expense_date', 'category', 'amount', 'description']);
        $expenseId = $expense->id;
        $expense->delete();

        $this->auditService->log(
            action: 'expense_deleted',
            entityType: 'Expense',
            entityId: $expenseId,
            oldValues: $oldValues,
            newValues: null,
            userId: $request->user()->id
        );

        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran operasional berhasil dihapus.');
    }
}
