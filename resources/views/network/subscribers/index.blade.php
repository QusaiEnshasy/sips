@extends('network.layout')

@section('title', 'المشتركين')

@section('content')
    @php
        $money = fn ($value) => number_format((float) $value, 2) . ' شيكل';
        $badgeForPayment = fn ($state) => match ($state) {
            'paid' => 'green',
            'partial' => 'amber',
            default => 'red',
        };
    @endphp

    <header class="page-head">
        <div>
            <h1 class="page-title">المشتركين</h1>
            <p class="page-subtitle">الأسماء، المواقع، نوع الاشتراك، IP، الراوتر، الجوال، وحالة الدفع.</p>
        </div>
        <form class="toolbar" method="GET" action="{{ route('network.subscribers.index') }}">
            <input type="month" name="month" value="{{ $selectedMonth }}" aria-label="الشهر">
            <button class="ghost-button" type="submit">
                <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M4 5h16M7 12h10M10 19h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                تحديث
            </button>
        </form>
    </header>

    <section class="panel">
        <h2 class="panel-title">إضافة مشترك</h2>
        <form method="POST" action="{{ route('network.subscribers.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="subscriber_code">رقم/كود المشترك</label>
                    <input id="subscriber_code" name="subscriber_code" value="{{ old('subscriber_code') }}" placeholder="اختياري">
                </div>
                <div class="field">
                    <label for="name">اسم المشترك</label>
                    <input id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label for="phone">رقم الجوال</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" inputmode="tel">
                </div>
                <div class="field">
                    <label for="location">الموقع</label>
                    <input id="location" name="location" value="{{ old('location') }}" required>
                </div>
                <div class="field">
                    <label for="subscription_type">نوع الاشتراك</label>
                    <select id="subscription_type" name="subscription_type" required>
                        @foreach ($subscriptionTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('subscription_type', 'broadband') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="ip_address">IP المشترك</label>
                    <input id="ip_address" name="ip_address" value="{{ old('ip_address') }}" placeholder="192.168.1.10">
                </div>
                <div class="field">
                    <label for="service_speed">السرعة</label>
                    <input id="service_speed" name="service_speed" value="{{ old('service_speed') }}" placeholder="مثلاً 20M">
                </div>
                <div class="field">
                    <label for="router_model">نوع الراوتر</label>
                    <input id="router_model" name="router_model" value="{{ old('router_model') }}">
                </div>
                <div class="field">
                    <label for="monthly_fee">سعر الاشتراك الشهري</label>
                    <input id="monthly_fee" name="monthly_fee" type="number" step="0.01" min="0" value="{{ old('monthly_fee', 0) }}" required>
                </div>
                <div class="field">
                    <label for="due_day">يوم الاستحقاق</label>
                    <input id="due_day" name="due_day" type="number" min="1" max="31" value="{{ old('due_day', 1) }}" required>
                </div>
                <div class="field">
                    <label for="activation_date">تاريخ التفعيل</label>
                    <input id="activation_date" name="activation_date" type="date" value="{{ old('activation_date') }}">
                </div>
                <div class="field">
                    <label for="status">حالة الخط</label>
                    <select id="status" name="status" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field wide">
                    <label for="installation_address">العنوان التفصيلي</label>
                    <input id="installation_address" name="installation_address" value="{{ old('installation_address') }}">
                </div>
                <div class="field wide">
                    <label for="notes">ملاحظات</label>
                    <input id="notes" name="notes" value="{{ old('notes') }}" placeholder="مثلاً: يحتاج متابعة إشارة">
                </div>
            </div>
            <div class="actions" style="margin-top: 14px;">
                <button class="button" type="submit">
                    <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    حفظ المشترك
                </button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2 class="panel-title">فلترة القائمة</h2>
        <form class="form-grid" method="GET" action="{{ route('network.subscribers.index') }}">
            <div class="field wide">
                <label for="search">بحث</label>
                <input id="search" name="search" value="{{ request('search') }}" placeholder="اسم، جوال، موقع، IP">
            </div>
            <div class="field">
                <label for="filter_type">نوع الاشتراك</label>
                <select id="filter_type" name="subscription_type">
                    <option value="">الكل</option>
                    @foreach ($subscriptionTypes as $value => $label)
                        <option value="{{ $value }}" @selected(request('subscription_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="filter_status">حالة الخط</label>
                <select id="filter_status" name="status">
                    <option value="">الكل</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="payment_status">الدفع</label>
                <select id="payment_status" name="payment_status">
                    <option value="">الكل</option>
                    <option value="paid" @selected(request('payment_status') === 'paid')>واصل كامل</option>
                    <option value="partial" @selected(request('payment_status') === 'partial')>واصل جزئي</option>
                    <option value="unpaid" @selected(request('payment_status') === 'unpaid')>مش واصل</option>
                </select>
            </div>
            <div class="field">
                <label for="filter_month">الشهر</label>
                <input id="filter_month" type="month" name="month" value="{{ $selectedMonth }}">
            </div>
            <div class="field" style="align-self: end;">
                <button class="ghost-button" type="submit">
                    <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M4 5h16M7 12h10M10 19h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    تطبيق
                </button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="page-head">
            <div>
                <h2 class="panel-title">قائمة المشتركين</h2>
                <p class="page-subtitle">{{ number_format($subscribers->total()) }} مشترك حسب الفلترة الحالية.</p>
            </div>
        </div>

        @if ($subscribers->isEmpty())
            <div class="empty">لا يوجد مشتركين حتى الآن.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>المشترك</th>
                            <th>الموقع</th>
                            <th>نوع الاشتراك</th>
                            <th>IP والراوتر</th>
                            <th>الاشتراك</th>
                            <th>الدفع</th>
                            <th>تسجيل دفعة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscribers as $subscriber)
                            <tr>
                                <td>
                                    <div class="stack">
                                        <strong>{{ $subscriber->name }}</strong>
                                        <span class="muted">{{ $subscriber->subscriber_code ?: 'بدون كود' }}</span>
                                        <span>{{ $subscriber->phone ?: '-' }}</span>
                                        <span class="badge {{ $subscriber->status === 'active' ? 'green' : ($subscriber->status === 'suspended' ? 'amber' : 'red') }}">
                                            {{ $subscriber->statusLabel() }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="stack">
                                        <span>{{ $subscriber->location }}</span>
                                        <span class="muted">{{ $subscriber->installation_address ?: '-' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge violet">{{ $subscriber->subscriptionTypeLabel() }}</span>
                                    <div class="muted">{{ $subscriber->service_speed ?: 'بدون سرعة' }}</div>
                                </td>
                                <td>
                                    <div class="stack">
                                        <span>{{ $subscriber->ip_address ?: '-' }}</span>
                                        <span class="muted">{{ $subscriber->router_model ?: 'بدون نوع راوتر' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="stack">
                                        <strong>{{ $money($subscriber->monthly_fee) }}</strong>
                                        <span class="muted">استحقاق يوم {{ $subscriber->due_day }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="stack">
                                        <span class="badge {{ $badgeForPayment($subscriber->payment_state) }}">{{ $subscriber->payment_state_label }}</span>
                                        <span>واصل: {{ $money($subscriber->paid_this_month) }}</span>
                                        <span>الباقي: {{ $money($subscriber->balance_this_month) }}</span>
                                    </div>
                                </td>
                                <td>
                                    <form class="inline-form" method="POST" action="{{ route('network.subscribers.payments.store', $subscriber) }}">
                                        @csrf
                                        <input type="hidden" name="period_month" value="{{ $selectedMonth }}">
                                        <input type="hidden" name="paid_at" value="{{ now()->toDateString() }}">
                                        <input type="number" step="0.01" min="0.01" name="amount" placeholder="المبلغ" aria-label="المبلغ" required>
                                        <select name="payment_method" aria-label="طريقة الدفع">
                                            @foreach ($paymentMethods as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="button" type="submit" title="تسجيل دفعة">
                                            <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                            دفع
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a class="ghost-button" href="{{ route('network.subscribers.edit', $subscriber) }}">
                                            <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="m5 16 1 3 3-1L18 9l-4-4-9 11Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                                            تعديل
                                        </a>
                                        <form method="POST" action="{{ route('network.subscribers.destroy', $subscriber) }}" onsubmit="return confirm('حذف المشترك؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="danger-button" type="submit">
                                                <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M10 11v6M14 11v6M9 7l1-3h4l1 3M8 7v12h8V7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $subscribers->links() }}</div>
        @endif
    </section>
@endsection
