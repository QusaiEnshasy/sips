<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Models\NetworkCardSale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NetworkCardSaleController extends Controller
{
    public function index(Request $request)
    {
        [$monthStart, $monthEnd, $selectedMonth] = $this->monthWindow($request->string('month')->toString());

        $baseQuery = NetworkCardSale::query()
            ->whereBetween('sale_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $summary = [
            'cards_count' => (int) (clone $baseQuery)->sum('cards_count'),
            'total_amount' => (float) (clone $baseQuery)->sum('total_amount'),
            'days_count' => (clone $baseQuery)->distinct('sale_date')->count('sale_date'),
        ];

        $sales = (clone $baseQuery)
            ->latest('sale_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $dailyRows = (clone $baseQuery)
            ->get()
            ->groupBy(fn (NetworkCardSale $sale) => $sale->sale_date->format('Y-m-d'))
            ->map(fn ($sales, $date) => [
                'date' => $date,
                'cards_count' => (int) $sales->sum('cards_count'),
                'total_amount' => (float) $sales->sum('total_amount'),
            ])
            ->sortByDesc('date')
            ->values();

        return view('network.sales.index', [
            'sales' => $sales,
            'summary' => $summary,
            'dailyRows' => $dailyRows,
            'selectedMonth' => $selectedMonth,
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_date' => ['required', 'date'],
            'card_name' => ['required', 'string', 'max:255'],
            'cards_count' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in(array_keys($this->paymentMethods()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        NetworkCardSale::create($validated);

        return redirect()
            ->back()
            ->with('success', 'تم تسجيل بيع البطاقات.');
    }

    public function destroy(NetworkCardSale $sale)
    {
        $sale->delete();

        return redirect()
            ->back()
            ->with('success', 'تم حذف عملية البيع.');
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

    private function paymentMethods(): array
    {
        return [
            'cash' => 'كاش',
            'jawwal_pay' => 'جوال باي',
            'bank' => 'بنك',
            'other' => 'أخرى',
        ];
    }
}
