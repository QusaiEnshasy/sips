<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Models\NetworkCardSale;
use App\Models\NetworkExpense;
use App\Models\NetworkPayment;
use App\Models\NetworkSubscriber;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NetworkDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        [$monthStart, $monthEnd, $selectedMonth] = $this->monthWindow($request->string('month')->toString());

        $activeSubscribers = NetworkSubscriber::active()
            ->orderBy('name')
            ->get();

        $paidBySubscriber = NetworkPayment::query()
            ->whereBetween('period_month', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('network_subscriber_id, SUM(amount) as total')
            ->groupBy('network_subscriber_id')
            ->pluck('total', 'network_subscriber_id');

        $decoratedSubscribers = $activeSubscribers->map(function (NetworkSubscriber $subscriber) use ($paidBySubscriber) {
            $paid = (float) ($paidBySubscriber[$subscriber->id] ?? 0);
            $fee = (float) $subscriber->monthly_fee;
            $balance = max($fee - $paid, 0);

            $subscriber->setAttribute('paid_this_month', $paid);
            $subscriber->setAttribute('balance_this_month', $balance);
            $subscriber->setAttribute('payment_state_label', $this->paymentStateLabel($fee, $paid));

            return $subscriber;
        });

        $subscriptionRevenue = (float) $paidBySubscriber->sum();
        $expectedRevenue = (float) $decoratedSubscribers->sum('monthly_fee');
        $unpaidBalance = (float) $decoratedSubscribers->sum('balance_this_month');
        $cardRevenue = (float) NetworkCardSale::query()
            ->whereBetween('sale_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('total_amount');
        $cardsSold = (int) NetworkCardSale::query()
            ->whereBetween('sale_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('cards_count');
        $expenses = (float) NetworkExpense::query()
            ->whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');

        $today = now()->toDateString();
        $todayCards = NetworkCardSale::query()
            ->whereDate('sale_date', $today)
            ->selectRaw('COALESCE(SUM(cards_count), 0) as cards_count, COALESCE(SUM(total_amount), 0) as total_amount')
            ->first();

        $stats = [
            'active_subscribers' => $activeSubscribers->count(),
            'expected_revenue' => $expectedRevenue,
            'subscription_revenue' => $subscriptionRevenue,
            'card_revenue' => $cardRevenue,
            'total_revenue' => $subscriptionRevenue + $cardRevenue,
            'expenses' => $expenses,
            'net_revenue' => $subscriptionRevenue + $cardRevenue - $expenses,
            'unpaid_balance' => $unpaidBalance,
            'cards_sold' => $cardsSold,
            'today_cards' => (int) ($todayCards->cards_count ?? 0),
            'today_card_revenue' => (float) ($todayCards->total_amount ?? 0),
        ];

        $unpaidSubscribers = $decoratedSubscribers
            ->filter(fn (NetworkSubscriber $subscriber) => (float) $subscriber->balance_this_month > 0)
            ->sortByDesc('balance_this_month')
            ->take(10)
            ->values();

        $dailyRows = $this->dailyRows();

        return view('network.dashboard', [
            'selectedMonth' => $selectedMonth,
            'stats' => $stats,
            'unpaidSubscribers' => $unpaidSubscribers,
            'dailyRows' => $dailyRows,
        ]);
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

    private function paymentStateLabel(float $fee, float $paid): string
    {
        if ($paid >= $fee) {
            return 'واصل كامل';
        }

        if ($paid > 0) {
            return 'واصل جزئي';
        }

        return 'مش واصل';
    }

    private function dailyRows(): array
    {
        $from = now()->subDays(13)->startOfDay();
        $to = now()->endOfDay();

        $payments = NetworkPayment::query()
            ->whereBetween('paid_at', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (NetworkPayment $payment) => $payment->paid_at->format('Y-m-d'));

        $sales = NetworkCardSale::query()
            ->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (NetworkCardSale $sale) => $sale->sale_date->format('Y-m-d'));

        $expenses = NetworkExpense::query()
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (NetworkExpense $expense) => $expense->expense_date->format('Y-m-d'));

        $rows = [];
        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $day = $date->format('Y-m-d');
            $dayPayments = (float) ($payments[$day] ?? collect())->sum('amount');
            $dayCardRevenue = (float) ($sales[$day] ?? collect())->sum('total_amount');
            $dayExpenses = (float) ($expenses[$day] ?? collect())->sum('amount');

            $rows[] = [
                'date' => $day,
                'cards_count' => (int) ($sales[$day] ?? collect())->sum('cards_count'),
                'card_revenue' => $dayCardRevenue,
                'subscription_revenue' => $dayPayments,
                'expenses' => $dayExpenses,
                'net' => $dayPayments + $dayCardRevenue - $dayExpenses,
            ];
        }

        return array_reverse($rows);
    }
}
