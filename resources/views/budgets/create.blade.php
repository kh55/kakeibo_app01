<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">予算登録</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('budgets.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="year" class="form-label">年</label>
                    <input type="number" name="year" id="year" class="form-control" value="{{ old('year', $year) }}" required>
                </div>
                <div class="mb-3">
                    <label for="month" class="form-label">月</label>
                    <input type="number" name="month" id="month" class="form-control" value="{{ old('month', $month) }}" min="1" max="12" required>
                </div>
                <div class="mb-3">
                    <label for="category_id" class="form-label">分類</label>
                    <select name="category_id" id="category_id" class="form-select" required>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">予算額</label>
                    <input type="number" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" min="0" step="0.01" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">登録</button>
                    <a href="{{ route('budgets.index') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

