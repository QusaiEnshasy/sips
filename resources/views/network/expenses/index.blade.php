@extends('network.layout')

@section('title', 'المصاريف')

@section('content')
    @php
        $money = fn ($value) => number_format((float) $value, 2) . ' شيكل';
    @endphp

    <header class="page-head">
        <div>
            <h1 class="page-title">المصاريف</h1>
            <p class="page-subtitle">سجل مصاريف الشبكة حتى يظهر الصافي الحقيقي في اللوحة.</p>
        </div>
        <form class="toolbar" method="GET" action="{{ route('network.expenses.index') }}">
            <input type="month" name="month" value="{{ $selectedMonth }}" aria-label="الشهر">
            <button class="ghost-button" type="submit">
                <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M4 5h16M7 12h10M10 19h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                فلترة
            </button>
        </form>
    </header>

    <section class="metric-grid">
        <article class="metric-card red">
            <span>إجمالي المصاريف</span>
            <strong>{{ $money($summary['total_amount']) }}</strong>
            <span>للشهر المحدد</span>
        </article>
        <article class="metric-card amber">
            <span>عدد البنود</span>
            <strong>{{ number_format($summary['items_count']) }}</strong>
            <span>عملية مصروف مسجلة</span>
        </article>
        <article class="metric-card teal">
            <span>متوسط المصروف</span>
            <strong>{{ $money($summary['items_count'] ? $summary['total_amount'] / $summary['items_count'] : 0) }}</strong>
            <span>حسب عدد البنود</span>
        </article>
        <article class="metric-card violet">
            <span>الشهر</span>
            <strong>{{ $selectedMonth }}</strong>
            <span>الفترة المعروضة</span>
        </article>
    </section>

    <section class="panel">
        <h2 class="panel-title">تسجيل مصروف</h2>
        <form method="POST" action="{{ route('network.expenses.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="expense_date">تاريخ المصروف</label>
                    <input id="expense_date" name="expense_date" type="date" value="{{ old('expense_date', now()->toDateString()) }}" required>
                </div>
                <div class="field wide">
                    <label for="title">اسم المصروف</label>
                    <input id="title" name="title" value="{{ old('title') }}" placeholder="مثلاً: صيانة برج" required>
                </div>
                <div class="field">
                    <label for="category">التصنيف</label>
                    <input id="category" name="category" list="expense_categories" value="{{ old('category') }}" placeholder="صيانة">
                    <datalist id="expense_categories">
                        <option value="صيانة">
                        <option value="كهرباء">
                        <option value="إنترنت مصدر">
                        <option value="معدات">
                        <option value="رواتب">
                        <option value="مواصلات">
                    </datalist>
                </div>
                <div class="field">
                    <label for="amount">المبلغ</label>
                    <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                </div>
                <div class="field wide">
                    <label for="notes">ملاحظات</label>
                    <input id="notes" name="notes" value="{{ old('notes') }}">
                </div>
            </div>
            <div class="actions" style="margin-top: 14px;">
                <button class="button" type="submit">
                    <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    حفظ المصروف
                </button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2 class="panel-title">قائمة المصاريف</h2>
        @if ($expenses->isEmpty())
            <div class="empty">لا توجد مصاريف مسجلة لهذا الشهر.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>المصروف</th>
                            <th>التصنيف</th>
                            <th>المبلغ</th>
                            <th>ملاحظات</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($expenses as $expense)
                            <tr>
                                <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                                <td>{{ $expense->title }}</td>
                                <td>{{ $expense->category ?: '-' }}</td>
                                <td><span class="badge red">{{ $money($expense->amount) }}</span></td>
                                <td>{{ $expense->notes ?: '-' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('network.expenses.destroy', $expense) }}" onsubmit="return confirm('حذف المصروف؟')">
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
            <div class="pagination">{{ $expenses->links() }}</div>
        @endif
    </section>
@endsection
