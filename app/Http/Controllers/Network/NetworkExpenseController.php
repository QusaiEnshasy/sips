<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Models\NetworkExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NetworkExpenseController extends Controller
{
    public function index(Request $request)
    {
        [$monthStart, $monthEnd, $selectedMonth] = $this->monthWindow($request->string('month')->toString());

        $baseQuery = NetworkExpense::query()
            ->whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $summary = [
            'total_amount' => (float) (clone $baseQuery)->sum('amount'),
            'items_count' => (int) (clone $baseQuery)->count(),
        ];

        $expenses = (clone $baseQuery)
            ->latest('expense_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('network.expenses.index', [
            'expenses' => $expenses,
            'summary' => $summary,
            'selectedMonth' => $selectedMonth,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        NetworkExpense::create($validated);

        return redirect()
            ->back()
            ->with('success', 'تم تسجيل المصروف.');
    }

    public function destroy(NetworkExpense $expense)
    {
        $expense->delete();

        return redirect()
            ->back()
            ->with('success', 'تم حذف المصروف.');
    }

    private function monthWindow(?string $month): array
    {
        try {
            $monthStart = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            $monthStart = now()->startOfMonth();
        }

        return [$monthStart->copy(), $monthStart->copy()->endOfMonth(), $monthStart->format('Y-m')];
    }
}
