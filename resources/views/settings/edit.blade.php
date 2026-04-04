<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">設定</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            @if (session('status') === 'settings-updated')
                <div class="alert alert-success" role="alert">
                    設定を保存しました。
                </div>
            @endif

            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                @method('PUT')

                <h5 class="mb-3">並び順設定</h5>
                <p class="text-muted mb-3">取引登録画面の支払手段・分類ドロップダウンの並び順を設定します。</p>

                <div class="mb-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="sort_preference" id="sort_manual"
                            value="manual" {{ $user->sort_preference === 'manual' ? 'checked' : '' }}>
                        <label class="form-check-label" for="sort_manual">
                            手動並び替え（デフォルト）
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sort_preference" id="sort_frequency"
                            value="frequency" {{ $user->sort_preference === 'frequency' ? 'checked' : '' }}>
                        <label class="form-check-label" for="sort_frequency">
                            利用頻度順（直近3ヶ月）
                        </label>
                    </div>
                    @error('sort_preference')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">保存</button>
            </form>
        </div>
    </div>
</x-app-layout>
