<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">予定表（キャッシュフロー）</h2>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('cashflow.sync') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    <button type="submit" class="btn btn-success">定期支出から同期</button>
                </form>
                <a href="{{ route('cashflow.create') }}" class="btn btn-primary">新規登録</a>
            </div>
        </div>
    </x-slot>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('cashflow.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">開始日</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">終了日</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary">フィルタ</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>項目名</th>
                        <th class="text-end">収入</th>
                        <th class="text-end">支出</th>
                        <th class="text-end">残高</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @php($today = \Carbon\Carbon::today())
                    @foreach($balance as $index => $entry)
                    @php(
                        $entryDate = is_object($entry['date']) ? $entry['date'] : \Carbon\Carbon::parse($entry['date'])
                    )
                    @php($isPast = $entryDate->lt($today))
                    <tr @class(['text-muted opacity-50' => $isPast])>
                        <td>{{ $entryDate->format('Y-m-d') }}</td>
                        <td>{{ $entry['name'] }}</td>
                        <td class="text-end {{ $isPast ? '' : 'text-success' }}">{{ $entry['income'] > 0 ? number_format($entry['income']) . '円' : '' }}</td>
                        <td class="text-end {{ $isPast ? '' : 'text-danger' }}">{{ $entry['expense'] > 0 ? number_format($entry['expense']) . '円' : '' }}</td>
                        <td class="text-end">{{ number_format($entry['balance']) }}円</td>
                        <td>
                            @if(isset($entries[$index]))
                            <div class="d-flex gap-1">
                                <a href="{{ route('cashflow.edit', $entries[$index]) }}" class="btn btn-sm btn-outline-primary">編集</a>
                                <a href="{{ route('cashflow.duplicate', $entries[$index]) }}" class="btn btn-sm btn-outline-secondary">複製</a>
                                <form method="POST" action="{{ route('cashflow.destroy', $entries[$index]) }}"
                                    onsubmit="return confirm('この予定を削除しますか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
                                </form>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

