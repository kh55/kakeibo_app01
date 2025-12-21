<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">予算管理</h2>
            <a href="{{ route('budgets.create', ['year' => $year, 'month' => $month]) }}" class="btn btn-primary">新規登録</a>
        </div>
    </x-slot>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('budgets.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="number" name="year" value="{{ $year }}" class="form-control" placeholder="年">
                </div>
                <div class="col-md-4">
                    <input type="number" name="month" value="{{ $month }}" class="form-control" placeholder="月" min="1" max="12">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-secondary">フィルタ</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>分類</th>
                        <th class="text-end">予算額</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($budgets as $budget)
                    <tr>
                        <td>{{ $budget->category->name }}</td>
                        <td class="text-end">{{ number_format($budget->amount) }}円</td>
                        <td>
                            <a href="{{ route('budgets.edit', $budget) }}" class="btn btn-sm btn-outline-primary">編集</a>
                            <form method="POST" action="{{ route('budgets.destroy', $budget) }}" class="d-inline">
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
    </div>
</x-app-layout>

