<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Models\NetworkSubscriber;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NetworkSubscriberController extends Controller
{
    public function index(Request $request)
    {
        [$monthStart, $monthEnd, $selectedMonth] = $this->monthWindow($request->string('month')->toString());

        $query = NetworkSubscriber::query()
            ->select('network_subscribers.*')
            ->selectSub(function ($query) use ($monthStart, $monthEnd) {
                $query->from('network_payments')
                    ->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('network_payments.network_subscriber_id', 'network_subscribers.id')
                    ->whereBetween('period_month', [$monthStart->toDateString(), $monthEnd->toDateString()]);
            }, 'paid_for_selected_month')
            ->latest('id');

        if ($search = trim($request->string('search')->toString())) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('subscriber_code', 'like', "%{$search}%");
            });
        }

        if ($type = $request->string('subscription_type')->toString()) {
            $query->where('subscription_type', $type);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->string('payment_status')->toString()) {
            $paidAmountSql = '(SELECT COALESCE(SUM(amount), 0) FROM network_payments WHERE network_payments.network_subscriber_id = network_subscribers.id AND period_month BETWEEN ? AND ?)';
            $monthBindings = [$monthStart->toDateString(), $monthEnd->toDateString()];

            match ($paymentStatus) {
                'paid' => $query->whereRaw("{$paidAmountSql} >= monthly_fee", $monthBindings),
                'partial' => $query->whereRaw("{$paidAmountSql} > 0 AND {$paidAmountSql} < monthly_fee", [...$monthBindings, ...$monthBindings]),
                'unpaid' => $query->whereRaw("{$paidAmountSql} = 0 AND monthly_fee > 0", $monthBindings),
                default => null,
            };
        }

        $subscribers = $query->paginate(15)->withQueryString();
        $subscribers->getCollection()->transform(fn (NetworkSubscriber $subscriber) => $this->decorateSubscriber($subscriber));

        return view('network.subscribers.index', [
            'subscribers' => $subscribers,
            'selectedMonth' => $selectedMonth,
            'subscriptionTypes' => $this->subscriptionTypes(),
            'statuses' => $this->statuses(),
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function store(Request $request)
    {
        NetworkSubscriber::create($this->validatedSubscriber($request));

        return redirect()
            ->route('network.subscribers.index')
            ->with('success', 'تمت إضافة المشترك بنجاح.');
    }

    public function edit(NetworkSubscriber $subscriber)
    {
        return view('network.subscribers.edit', [
            'subscriber' => $subscriber,
            'subscriptionTypes' => $this->subscriptionTypes(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(Request $request, NetworkSubscriber $subscriber)
    {
        $subscriber->update($this->validatedSubscriber($request, $subscriber));

        return redirect()
            ->route('network.subscribers.index')
            ->with('success', 'تم تعديل بيانات المشترك.');
    }

    public function destroy(NetworkSubscriber $subscriber)
    {
        $subscriber->delete();

        return redirect()
            ->route('network.subscribers.index')
            ->with('success', 'تم حذف المشترك من القائمة.');
    }

    public function storePayment(Request $request, NetworkSubscriber $subscriber)
    {
        $validated = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(array_keys($this->paymentMethods()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['period_month'] = Carbon::createFromFormat('Y-m', $validated['period_month'])
            ->startOfMonth()
            ->toDateString();

        $subscriber->payments()->create($validated);

        return redirect()
            ->back()
            ->with('success', 'تم تسجيل الدفعة.');
    }

    private function validatedSubscriber(Request $request, ?NetworkSubscriber $subscriber = null): array
    {
        return $request->validate([
            'subscriber_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('network_subscribers', 'subscriber_code')->ignore($subscriber),
            ],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'location' => ['required', 'string', 'max:255'],
            'installation_address' => ['nullable', 'string', 'max:255'],
            'subscription_type' => ['required', Rule::in(array_keys($this->subscriptionTypes()))],
            'ip_address' => [
                'nullable',
                'ip',
                'max:45',
                Rule::unique('network_subscribers', 'ip_address')->ignore($subscriber),
            ],
            'service_speed' => ['nullable', 'string', 'max:100'],
            'router_model' => ['nullable', 'string', 'max:255'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'activation_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function decorateSubscriber(NetworkSubscriber $subscriber): NetworkSubscriber
    {
        $paid = (float) ($subscriber->paid_for_selected_month ?? 0);
        $fee = (float) $subscriber->monthly_fee;
        $balance = max($fee - $paid, 0);
        $state = 'unpaid';

        if ($paid >= $fee) {
            $state = 'paid';
        } elseif ($paid > 0) {
            $state = 'partial';
        }

        $subscriber->setAttribute('paid_this_month', $paid);
        $subscriber->setAttribute('balance_this_month', $balance);
        $subscriber->setAttribute('payment_state', $state);
        $subscriber->setAttribute('payment_state_label', match ($state) {
            'paid' => 'واصل كامل',
            'partial' => 'واصل جزئي',
            default => 'مش واصل',
        });

        return $subscriber;
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

    private function subscriptionTypes(): array
    {
        return [
            'broadband' => 'برود باند',
            'access_point' => 'اكسس بوينت',
        ];
    }

    private function statuses(): array
    {
        return [
            'active' => 'فعال',
            'suspended' => 'موقوف مؤقتاً',
            'cancelled' => 'ملغي',
        ];
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
