<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">定期支出編集</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('recurring-rules.update', $recurringRule) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="account_id" class="form-label">支払手段</label>
                    <select name="account_id" id="account_id" class="form-select" required>
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ old('account_id', $recurringRule->account_id) == $account->id ? 'selected' : '' }}>
                            {{ $account->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="day_of_month" class="form-label">引落日</label>
                    <input type="number" name="day_of_month" id="day_of_month" class="form-control" value="{{ old('day_of_month', $recurringRule->day_of_month) }}" min="1" max="31" required>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">項目名</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $recurringRule->name) }}" required>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">金額</label>
                    <input type="number" name="amount" id="amount" class="form-control" value="{{ old('amount', $recurringRule->amount) }}" min="0" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label for="category_id" class="form-label">分類</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">未分類</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $recurringRule->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="enabled" id="enabled" class="form-check-input" value="1" {{ old('enabled', $recurringRule->enabled) ? 'checked' : '' }}>
                        <label for="enabled" class="form-check-label">有効</label>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">更新</button>
                    <a href="{{ route('recurring-rules.index') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

