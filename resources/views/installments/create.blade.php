<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">分割払い登録</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('installment-plans.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">案件名</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="mb-3">
                    <label for="start_date" class="form-label">開始日</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date') }}" required>
                </div>
                <div class="mb-3">
                    <label for="pay_day" class="form-label">支払日</label>
                    <input type="number" name="pay_day" id="pay_day" class="form-control" value="{{ old('pay_day') }}" min="1" max="31" required>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">毎月額</label>
                    <input type="number" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" min="0" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label for="times" class="form-label">総支払回数</label>
                    <input type="number" name="times" id="times" class="form-control" value="{{ old('times') }}" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="account_id" class="form-label">支払手段</label>
                    <select name="account_id" id="account_id" class="form-select">
                        <option value="">未選択</option>
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
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">登録</button>
                    <a href="{{ route('installment-plans.index') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

