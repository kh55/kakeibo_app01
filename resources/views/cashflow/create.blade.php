<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">予定登録</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('cashflow.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="date" class="form-label">日付</label>
                    <input type="date" name="date" id="date" class="form-control" value="{{ old('date') }}" required>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">項目名</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="mb-3">
                    <label for="income_amount" class="form-label">収入額</label>
                    <input type="number" name="income_amount" id="income_amount" class="form-control" value="{{ old('income_amount', 0) }}" min="0" step="0.01">
                </div>
                <div class="mb-3">
                    <label for="expense_amount" class="form-label">支出額</label>
                    <input type="number" name="expense_amount" id="expense_amount" class="form-control" value="{{ old('expense_amount', 0) }}" min="0" step="0.01">
                </div>
                <div class="mb-3">
                    <label for="memo" class="form-label">メモ</label>
                    <textarea name="memo" id="memo" class="form-control" rows="3">{{ old('memo') }}</textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">登録</button>
                    <a href="{{ route('cashflow.index') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

