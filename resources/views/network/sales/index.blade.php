@extends('network.layout')

@section('title', 'بيع البطاقات')

@section('content')
    @php
        $money = fn ($value) => number_format((float) $value, 2) . ' شيكل';
    @endphp

    <header class="page-head">
        <div>
            <h1 class="page-title">بيع البطاقات</h1>
            <p class="page-subtitle">سجل عدد البطاقات وسعرها، ثم راقب إحصائية البيع كل يوم.</p>
        </div>
        <form class="toolbar" method="GET" action="{{ route('network.sales.index') }}">
            <input type="month" name="month" value="{{ $selectedMonth }}" aria-label="الشهر">
            <button class="ghost-button" type="submit">
                <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M4 5h16M7 12h10M10 19h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                فلترة
            </button>
        </form>
    </header>

    <section class="metric-grid">
        <article class="metric-card amber">
            <span>عدد البطاقات المباعة</span>
            <strong>{{ number_format($summary['cards_count']) }}</strong>
            <span>للشهر المحدد</span>
        </article>
        <article class="metric-card teal">
            <span>دخل البطاقات</span>
            <strong>{{ $money($summary['total_amount']) }}</strong>
            <span>إجمالي السعر</span>
        </article>
        <article class="metric-card violet">
            <span>أيام البيع</span>
            <strong>{{ number_format($summary['days_count']) }}</strong>
            <span>يوم فيه حركة بيع</span>
        </article>
        <article class="metric-card red">
            <span>متوسط اليوم</span>
            <strong>{{ $money($summary['days_count'] ? $summary['total_amount'] / $summary['days_count'] : 0) }}</strong>
            <span>حسب أيام البيع</span>
        </article>
    </section>

    <section class="panel">
        <h2 class="panel-title">تسجيل بيع بطاقات</h2>
        <form method="POST" action="{{ route('network.sales.store') }}" data-sale-form>
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="sale_date">تاريخ البيع</label>
                    <input id="sale_date" name="sale_date" type="date" value="{{ old('sale_date', now()->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label for="card_name">نوع البطاقة</label>
                    <input id="card_name" name="card_name" list="card_names" value="{{ old('card_name') }}" placeholder="بطاقة 10 شيكل" required>
                    <datalist id="card_names">
                        <option value="بطاقة 5 شيكل">
                        <option value="بطاقة 10 شيكل">
                        <option value="بطاقة 20 شيكل">
                        <option value="بطاقة شهرية">
                    </datalist>
                </div>
                <div class="field">
                    <label for="cards_count">كم بطاقة</label>
                    <input id="cards_count" name="cards_count" type="number" min="1" value="{{ old('cards_count', 1) }}" required data-count>
                </div>
                <div class="field">
                    <label for="unit_price">سعر البطاقة</label>
                    <input id="unit_price" name="unit_price" type="number" step="0.01" min="0" value="{{ old('unit_price', 0) }}" required data-price>
                </div>
                <div class="field">
                    <label for="payment_method">طريقة الدفع</label>
                    <select id="payment_method" name="payment_method" required>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>المجموع</label>
                    <input value="0.00 شيكل" readonly data-total>
                </div>
                <div class="field wide">
                    <label for="notes">ملاحظات</label>
                    <input id="notes" name="notes" value="{{ old('notes') }}">
                </div>
            </div>
            <div class="actions" style="margin-top: 14px;">
                <button class="button" type="submit">
                    <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    حفظ البيع
                </button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2 class="panel-title">إحصائية يومية</h2>
        @if ($dailyRows->isEmpty())
            <div class="empty">لا يوجد بيع بطاقات في هذا الشهر.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>اليوم</th>
                            <th>عدد البطاقات</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dailyRows as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td>{{ number_format($row['cards_count']) }}</td>
                                <td>{{ $money($row['total_amount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="panel">
        <h2 class="panel-title">عمليات البيع</h2>
        @if ($sales->isEmpty())
            <div class="empty">لا توجد عمليات بيع مسجلة.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>نوع البطاقة</th>
                            <th>العدد</th>
                            <th>سعر البطاقة</th>
                            <th>المجموع</th>
                            <th>ملاحظات</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr>
                                <td>{{ $sale->sale_date->format('Y-m-d') }}</td>
                                <td>{{ $sale->card_name }}</td>
                                <td>{{ number_format($sale->cards_count) }}</td>
                                <td>{{ $money($sale->unit_price) }}</td>
                                <td><span class="badge green">{{ $money($sale->total_amount) }}</span></td>
                                <td>{{ $sale->notes ?: '-' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('network.sales.destroy', $sale) }}" onsubmit="return confirm('حذف عملية البيع؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger-button" type="submit">
                                            <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M10 11v6M14 11v6M9 7l1-3h4l1 3M8 7v12h8V7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            حذف
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $sales->links() }}</div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-sale-form]').forEach((form) => {
            const count = form.querySelector('[data-count]');
            const price = form.querySelector('[data-price]');
            const total = form.querySelector('[data-total]');
            const refresh = () => {
                const value = (Number(count.value || 0) * Number(price.value || 0)).toFixed(2);
                total.value = `${value} شيكل`;
            };

            count.addEventListener('input', refresh);
            price.addEventListener('input', refresh);
            refresh();
        });
    </script>
@endpush
