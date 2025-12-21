<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">支払手段編集</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('accounts.update', $account) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">名称</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $account->name) }}" required>
                </div>
                <div class="mb-3">
                    <label for="type" class="form-label">種別</label>
                    <select name="type" id="type" class="form-select" required>
                        <option value="card" {{ old('type', $account->type) === 'card' ? 'selected' : '' }}>カード</option>
                        <option value="cash" {{ old('type', $account->type) === 'cash' ? 'selected' : '' }}>現金</option>
                        <option value="bank" {{ old('type', $account->type) === 'bank' ? 'selected' : '' }}>銀行</option>
                        <option value="other" {{ old('type', $account->type) === 'other' ? 'selected' : '' }}>その他</option>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="enabled" id="enabled" class="form-check-input" value="1" {{ old('enabled', $account->enabled) ? 'checked' : '' }}>
                        <label for="enabled" class="form-check-label">有効</label>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">更新</button>
                    <a href="{{ route('accounts.index') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

