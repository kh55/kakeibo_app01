<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">取引登録</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('transactions.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="date" class="form-label">日付</label>
                    <input type="date" name="date" id="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                </div>
                <div class="mb-3">
                    <label for="type" class="form-label">種別</label>
                    <select name="type" id="type" class="form-select" required>
                        <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>支出</option>
                        <option value="income" {{ old('type') === 'income' ? 'selected' : '' }}>収入</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="account_id" class="form-label">支払手段</label>
                    <select name="account_id" id="account_id" class="form-select" required>
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="category_id" class="form-label">分類</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">未分類</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">項目名</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">金額</label>
                    <input type="number" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" min="0" step="0.01" required>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_recurring" id="is_recurring" class="form-check-input" value="1" {{ old('is_recurring') ? 'checked' : '' }}>
                        <label for="is_recurring" class="form-check-label">定期支出</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="memo" class="form-label">メモ</label>
                    <textarea name="memo" id="memo" class="form-control" rows="3">{{ old('memo') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="tags" class="form-label">タグ（カンマ区切り）</label>
                    <input type="text" name="tags" id="tags" class="form-control" value="{{ old('tags') }}">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">登録</button>
                    <a href="{{ route('transactions.index') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

