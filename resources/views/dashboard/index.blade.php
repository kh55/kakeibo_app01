<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">ダッシュボード</h2>
    </x-slot>

    @if(($isLocal ?? false) === true)
        <div class="alert alert-warning mb-4">
            <div><strong>ローカル環境</strong></div>
            <div>テストデータ登録年月: <span class="fw-bold">{{ $localTestDataYearMonth ?? '未登録' }}</span></div>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">当月収入</h5>
                    <p class="card-text h3 text-success">{{ number_format($summary['income']) }}円</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">当月支出</h5>
                    <p class="card-text h3 text-danger">{{ number_format($summary['expense']) }}円</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">当月収支</h5>
                    <p class="card-text h3 {{ $summary['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($summary['balance']) }}円</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">繰越残高</h5>
                    <p class="card-text h3">{{ number_format($summary['carryover_balance']) }}円</p>
                </div>
            </div>
        </div>
    </div>

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

