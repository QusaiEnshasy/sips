@extends('network.layout')

@section('title', 'لوحة الشبكة')

@section('content')
    @php
        $money = fn ($value) => number_format((float) $value, 2) . ' شيكل';
    @endphp

    <header class="page-head">
        <div>
            <h1 class="page-title">لوحة الشبكة</h1>
            <p class="page-subtitle">ملخص الاشتراكات، بيع البطاقات، المصاريف، والمبالغ غير الواصلة.</p>
        </div>
        <form class="toolbar" method="GET" action="{{ route('network.dashboard') }}">
            <input type="month" name="month" value="{{ $selectedMonth }}" aria-label="الشهر">
            <button class="ghost-button" type="submit">
                <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M4 5h16M7 12h10M10 19h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                فلترة
            </button>
        </form>
    </header>

    <section class="metric-grid" aria-label="الإحصائيات الشهرية">
        <article class="metric-card teal">
            <span>المشتركين الفعالين</span>
            <strong>{{ number_format($stats['active_subscribers']) }}</strong>
            <span>إجمالي الفواتير: {{ $money($stats['expected_revenue']) }}</span>
        </article>
        <article class="metric-card violet">
            <span>دخل الاشتراكات</span>
            <strong>{{ $money($stats['subscription_revenue']) }}</strong>
            <span>المتبقي: {{ $money($stats['unpaid_balance']) }}</span>
        </article>
        <article class="metric-card amber">
            <span>بيع البطاقات</span>
            <strong>{{ $money($stats['card_revenue']) }}</strong>
            <span>{{ number_format($stats['cards_sold']) }} بطاقة هذا الشهر</span>
        </article>
        <article class="metric-card red">
            <span>الصافي بعد المصاريف</span>
            <strong>{{ $money($stats['net_revenue']) }}</strong>
            <span>المصاريف: {{ $money($stats['expenses']) }}</span>
        </article>
    </section>

    <section class="metric-grid" aria-label="إحصائيات اليوم">
        <article class="metric-card teal">
            <span>إجمالي الإيراد</span>
            <strong>{{ $money($stats['total_revenue']) }}</strong>
            <span>اشتراكات + بطاقات للشهر المحدد</span>
        </article>
        <article class="metric-card amber">
            <span>بطاقات اليوم</span>
            <strong>{{ number_format($stats['today_cards']) }}</strong>
            <span>{{ $money($stats['today_card_revenue']) }}</span>
        </article>
        <article class="metric-card violet">
            <span>غير واصل</span>
            <strong>{{ $money($stats['unpaid_balance']) }}</strong>
            <span>مطلوب تحصيله من المشتركين</span>
        </article>
        <article class="metric-card red">
            <span>المصاريف</span>
            <strong>{{ $money($stats['expenses']) }}</strong>
            <span>للشهر المحدد</span>
        </article>
    </section>

    <section class="panel">
        <div class="page-head">
            <div>
                <h2 class="panel-title">آخر 14 يوم</h2>
                <p class="page-subtitle">إحصائية يومية للمبيعات، الدفعات، والمصاريف.</p>
            </div>
            <a class="button" href="{{ route('network.sales.index') }}">
                <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                تسجيل بيع
            </a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>اليوم</th>
                        <th>عدد البطاقات</th>
                        <th>دخل البطاقات</th>
                        <th>دفعات المشتركين</th>
                        <th>المصاريف</th>
                        <th>الصافي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dailyRows as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ number_format($row['cards_count']) }}</td>
                            <td>{{ $money($row['card_revenue']) }}</td>
                            <td>{{ $money($row['subscription_revenue']) }}</td>
                            <td>{{ $money($row['expenses']) }}</td>
                            <td>
                                <span class="badge {{ $row['net'] >= 0 ? 'green' : 'red' }}">
                                    {{ $money($row['net']) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="page-head">
            <div>
                <h2 class="panel-title">مشتركين عليهم باقي</h2>
                <p class="page-subtitle">أعلى 10 مبالغ غير واصلة للشهر المحدد.</p>
            </div>
            <a class="ghost-button" href="{{ route('network.subscribers.index', ['payment_status' => 'unpaid', 'month' => $selectedMonth]) }}">
                <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M5 6h14M5 12h14M5 18h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                فتح القائمة
            </a>
        </div>

        @if ($unpaidSubscribers->isEmpty())
            <div class="empty">لا يوجد مبالغ غير واصلة لهذا الشهر.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>المشترك</th>
                            <th>الجوال</th>
                            <th>الموقع</th>
                            <th>قيمة الاشتراك</th>
                            <th>واصل</th>
                            <th>الباقي</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($unpaidSubscribers as $subscriber)
                            <tr>
                                <td>
                                    <strong>{{ $subscriber->name }}</strong>
                                    <div class="muted">{{ $subscriber->ip_address ?: 'بدون IP' }}</div>
                                </td>
                                <td>{{ $subscriber->phone ?: '-' }}</td>
                                <td>{{ $subscriber->location }}</td>
                                <td>{{ $money($subscriber->monthly_fee) }}</td>
                                <td>{{ $money($subscriber->paid_this_month) }}</td>
                                <td>{{ $money($subscriber->balance_this_month) }}</td>
                                <td><span class="badge amber">{{ $subscriber->payment_state_label }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
