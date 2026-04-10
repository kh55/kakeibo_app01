<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">ダッシュボード</h2>
            <a href="{{ route('annual-summary.index') }}" class="btn btn-outline-primary btn-sm">年間収支表</a>
        </div>
    </x-slot>

    <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
        <a href="{{ $prevUrl }}" class="btn btn-outline-secondary btn-sm">&#8592;</a>
        <span class="fw-semibold fs-5">{{ $year }}年{{ $month }}月</span>
        @if($nextUrl)
            <a href="{{ $nextUrl }}" class="btn btn-outline-secondary btn-sm">&#8594;</a>
        @else
            <span class="btn btn-outline-secondary btn-sm disabled" aria-disabled="true">&#8594;</span>
        @endif
    </div>

    @if(($isLocal ?? false) === true)
        <div class="alert alert-warning mb-4">
            <div><strong>ローカル環境</strong></div>
            <div>テストデータ登録年月: <span class="fw-bold">{{ $localTestDataYearMonth ?? '未登録' }}</span></div>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">当月収入</h5>
                    <p class="card-text h3 text-success">{{ number_format($summary['income']) }}円</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">当月支出</h5>
                    <p class="card-text h3 text-danger">{{ number_format($summary['expense']) }}円</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">当月収支</h5>
                    <p class="card-text h3 {{ $summary['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($summary['balance']) }}円</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">繰越残高</h5>
                    <p class="card-text h3">{{ number_format($summary['carryover_balance']) }}円</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title">貯蓄率</h5>
                    @if($summary['savings_rate'] === null)
                        <p class="card-text h3 text-muted">—</p>
                    @else
                        <p class="card-text h3 {{ $summary['savings_rate'] >= 0 ? 'text-primary' : 'text-danger' }}">
                            {{ $summary['savings_rate'] }}%</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">月次推移（直近6ヶ月）</h5>
        </div>
        <div class="card-body">
            <canvas id="monthlyTrendChart"
                data-trend='@json($monthlyTrend)'></canvas>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('monthlyTrendChart');
        const trend = JSON.parse(canvas.dataset.trend);
        const labels = trend.map(function (r) {
            return r.year + '/' + r.month;
        });
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '収入',
                        data: trend.map(function (r) { return r.income; }),
                        backgroundColor: '#0d6efd',
                    },
                    {
                        label: '支出',
                        data: trend.map(function (r) { return r.expense; }),
                        backgroundColor: '#dc3545',
                    },
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    });
    </script>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">分類別支出トップ10</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>分類</th>
                                <th class="text-end">金額</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categoryExpenses as $expense)
                            <tr>
                                <td>{{ $expense['category_name'] }}</td>
                                <td class="text-end">{{ number_format($expense['total']) }}円</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">予算 vs 実支出</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>分類</th>
                                <th class="text-end">予算</th>
                                <th class="text-end">実支出</th>
                                <th class="text-end">差額</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($budgetComparison as $comparison)
                            <tr>
                                <td>{{ $comparison['category_name'] }}</td>
                                <td class="text-end">{{ number_format($comparison['budget']) }}円</td>
                                <td class="text-end">{{ number_format($comparison['actual']) }}円</td>
                                <td class="text-end {{ $comparison['difference'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($comparison['difference']) }}円
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

