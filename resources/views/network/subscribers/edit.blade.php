@extends('network.layout')

@section('title', 'تعديل مشترك')

@section('content')
    <header class="page-head">
        <div>
            <h1 class="page-title">تعديل مشترك</h1>
            <p class="page-subtitle">{{ $subscriber->name }}</p>
        </div>
        <a class="ghost-button" href="{{ route('network.subscribers.index') }}">
            <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            رجوع
        </a>
    </header>

    <section class="panel">
        <form method="POST" action="{{ route('network.subscribers.update', $subscriber) }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="field">
                    <label for="subscriber_code">رقم/كود المشترك</label>
                    <input id="subscriber_code" name="subscriber_code" value="{{ old('subscriber_code', $subscriber->subscriber_code) }}">
                </div>
                <div class="field">
                    <label for="name">اسم المشترك</label>
                    <input id="name" name="name" value="{{ old('name', $subscriber->name) }}" required>
                </div>
                <div class="field">
                    <label for="phone">رقم الجوال</label>
                    <input id="phone" name="phone" value="{{ old('phone', $subscriber->phone) }}" inputmode="tel">
                </div>
                <div class="field">
                    <label for="location">الموقع</label>
                    <input id="location" name="location" value="{{ old('location', $subscriber->location) }}" required>
                </div>
                <div class="field">
                    <label for="subscription_type">نوع الاشتراك</label>
                    <select id="subscription_type" name="subscription_type" required>
                        @foreach ($subscriptionTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('subscription_type', $subscriber->subscription_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="ip_address">IP المشترك</label>
                    <input id="ip_address" name="ip_address" value="{{ old('ip_address', $subscriber->ip_address) }}">
                </div>
                <div class="field">
                    <label for="service_speed">السرعة</label>
                    <input id="service_speed" name="service_speed" value="{{ old('service_speed', $subscriber->service_speed) }}">
                </div>
                <div class="field">
                    <label for="router_model">نوع الراوتر</label>
                    <input id="router_model" name="router_model" value="{{ old('router_model', $subscriber->router_model) }}">
                </div>
                <div class="field">
                    <label for="monthly_fee">سعر الاشتراك الشهري</label>
                    <input id="monthly_fee" name="monthly_fee" type="number" step="0.01" min="0" value="{{ old('monthly_fee', $subscriber->monthly_fee) }}" required>
                </div>
                <div class="field">
                    <label for="due_day">يوم الاستحقاق</label>
                    <input id="due_day" name="due_day" type="number" min="1" max="31" value="{{ old('due_day', $subscriber->due_day) }}" required>
                </div>
                <div class="field">
                    <label for="activation_date">تاريخ التفعيل</label>
                    <input id="activation_date" name="activation_date" type="date" value="{{ old('activation_date', optional($subscriber->activation_date)->format('Y-m-d')) }}">
                </div>
                <div class="field">
                    <label for="status">حالة الخط</label>
                    <select id="status" name="status" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $subscriber->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field wide">
                    <label for="installation_address">العنوان التفصيلي</label>
                    <input id="installation_address" name="installation_address" value="{{ old('installation_address', $subscriber->installation_address) }}">
                </div>
                <div class="field wide">
                    <label for="notes">ملاحظات</label>
                    <input id="notes" name="notes" value="{{ old('notes', $subscriber->notes) }}">
                </div>
            </div>
            <div class="actions" style="margin-top: 14px;">
                <button class="button" type="submit">
                    <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M5 12l4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    حفظ التعديل
                </button>
                <a class="ghost-button" href="{{ route('network.subscribers.index') }}">إلغاء</a>
            </div>
        </form>
    </section>
@endsection
