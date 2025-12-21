<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">予定編集</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('cashflow.update', $cashflowEntry) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="date" class="form-label">日付</label>
                    <input type="date" name="date" id="date" class="form-control" value="{{ old('date', $cashflowEntry->date->format('Y-m-d')) }}" required>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">項目名</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $cashflowEntry->name) }}" required>
                </div>
                <div class="mb-3">
                    <label for="income_amount" class="form-label">収入額</label>
                    <input type="number" name="income_amount" id="income_amount" class="form-control" value="{{ old('income_amount', $cashflowEntry->income_amount) }}" min="0" step="0.01">
                </div>
                <div class="mb-3">
                    <label for="expense_amount" class="form-label">支出額</label>
                    <input type="number" name="expense_amount" id="expense_amount" class="form-control" value="{{ old('expense_amount', $cashflowEntry->expense_amount) }}" min="0" step="0.01">
                </div>
                <div class="mb-3">
                    <label for="memo" class="form-label">メモ</label>
                    <textarea name="memo" id="memo" class="form-control" rows="3">{{ old('memo', $cashflowEntry->memo) }}</textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">更新</button>
                    <a href="{{ route('cashflow.index') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

