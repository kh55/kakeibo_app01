<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">取引明細</h2>
            <a href="{{ route('transactions.create') }}" class="btn btn-primary">新規登録</a>
        </div>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('transactions.index') }}" class="mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="number" name="year" value="{{ $year }}" class="form-control" placeholder="年">
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="month" value="{{ $month }}" class="form-control" placeholder="月" min="1" max="12">
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="">すべて</option>
                            <option value="income" {{ $type === 'income' ? 'selected' : '' }}>収入</option>
                            <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>支出</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-secondary">フィルタ</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>種別</th>
                            <th>支払手段</th>
                            <th>分類</th>
                            <th>項目</th>
                            <th class="text-end">金額</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->date->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge bg-{{ $transaction->type === 'income' ? 'success' : 'danger' }}">
                                    {{ $transaction->type === 'income' ? '収入' : '支出' }}
                                </span>
                            </td>
                            <td>{{ $transaction->account->name }}</td>
                            <td>{{ $transaction->category?->name ?? '未分類' }}</td>
                            <td>{{ $transaction->name }}</td>
                            <td class="text-end">{{ number_format($transaction->amount) }}円</td>
                            <td>
                                <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-sm btn-outline-primary">編集</a>
                                <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('削除しますか？')">削除</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $transactions->links() }}
        </div>
    </div>
</x-app-layout>

