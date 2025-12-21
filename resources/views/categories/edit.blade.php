<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">分類編集</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.update', $category) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">名称</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $category->name) }}" required>
                </div>
                <div class="mb-3">
                    <label for="type" class="form-label">種別</label>
                    <select name="type" id="type" class="form-select" required>
                        <option value="expense" {{ old('type', $category->type) === 'expense' ? 'selected' : '' }}>支出</option>
                        <option value="income" {{ old('type', $category->type) === 'income' ? 'selected' : '' }}>収入</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="color" class="form-label">色（HEXコード）</label>
                    <input type="color" name="color" id="color" class="form-control form-control-color" value="{{ old('color', $category->color ?? '#007bff') }}">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">更新</button>
                    <a href="{{ route('categories.index') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

