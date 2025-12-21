<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">支払手段管理</h2>
            <a href="{{ route('accounts.create') }}" class="btn btn-primary">新規登録</a>
        </div>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>名称</th>
                        <th>種別</th>
                        <th>状態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $account)
                    <tr>
                        <td>{{ $account->name }}</td>
                        <td>{{ $account->type }}</td>
                        <td>
                            <span class="badge bg-{{ $account->enabled ? 'success' : 'secondary' }}">
                                {{ $account->enabled ? '有効' : '無効' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('accounts.edit', $account) }}" class="btn btn-sm btn-outline-primary">編集</a>
                            <form method="POST" action="{{ route('accounts.destroy', $account) }}" class="d-inline">
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

