<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">定期支出管理</h2>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('recurring-rules.generate') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">今月分を生成</button>
                </form>
                <a href="{{ route('recurring-rules.create') }}" class="btn btn-primary">新規登録</a>
            </div>
        </div>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>支払手段</th>
                        <th>引落日</th>
                        <th>項目名</th>
                        <th class="text-end">金額</th>
                        <th>分類</th>
                        <th>状態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rules as $rule)
                    <tr>
                        <td>{{ $rule->account->name }}</td>
                        <td>{{ $rule->day_of_month }}日</td>
                        <td>{{ $rule->name }}</td>
                        <td class="text-end">{{ number_format($rule->amount) }}円</td>
                        <td>{{ $rule->category?->name ?? '未分類' }}</td>
                        <td>
                            <span class="badge bg-{{ $rule->enabled ? 'success' : 'secondary' }}">
                                {{ $rule->enabled ? '有効' : '無効' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('recurring-rules.edit', $rule) }}" class="btn btn-sm btn-outline-primary">編集</a>
                            <form method="POST" action="{{ route('recurring-rules.destroy', $rule) }}" class="d-inline">
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

