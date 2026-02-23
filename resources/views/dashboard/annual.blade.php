<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">年間収支表</h2>
    </x-slot>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('annual-summary.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="year" class="form-label">年</label>
                    <input type="number" name="year" id="year" class="form-control" value="{{ $year }}" min="2000" max="2100" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">表示</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">{{ $year }}年 月別収支</h5>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>月</th>
                            <th class="text-end">収入</th>
                            <th class="text-end">支出</th>
                            <th class="text-end">差額</th>
                            <th class="text-end">繰越</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        <tr>
                            <td>{{ $row['month'] }}月</td>
                            <td class="text-end text-success">{{ number_format($row['income']) }}円</td>
                            <td class="text-end text-danger">{{ number_format($row['expense']) }}円</td>
                            <td class="text-end {{ $row['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($row['balance']) }}円
                            </td>
                            <td class="text-end">{{ number_format($row['carryover']) }}円</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary fw-bold">
                        <tr>
                            <td>合計（収支のみ）</td>
                            <td class="text-end text-success">{{ number_format(collect($rows)->sum('income')) }}円</td>
                            <td class="text-end text-danger">{{ number_format(collect($rows)->sum('expense')) }}円</td>
                            <td class="text-end {{ collect($rows)->sum('balance') >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format(collect($rows)->sum('balance')) }}円
                            </td>
                            <td class="text-end">—</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-2">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">ダッシュボードに戻る</a>
    </div>
</x-app-layout>
