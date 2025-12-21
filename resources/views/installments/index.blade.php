<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">分割払い管理</h2>
            <a href="{{ route('installment-plans.create') }}" class="btn btn-primary">新規登録</a>
        </div>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>案件名</th>
                        <th>開始日</th>
                        <th>支払日</th>
                        <th class="text-end">毎月額</th>
                        <th>残回数</th>
                        <th>状態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($plans as $plan)
                    <tr>
                        <td>{{ $plan->name }}</td>
                        <td>{{ $plan->start_date->format('Y-m-d') }}</td>
                        <td>{{ $plan->pay_day }}日</td>
                        <td class="text-end">{{ number_format($plan->amount) }}円</td>
                        <td>{{ $plan->remaining_times }} / {{ $plan->times }}</td>
                        <td>
                            <span class="badge bg-{{ $plan->enabled ? 'success' : 'secondary' }}">
                                {{ $plan->enabled ? '有効' : '完了' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('installment-plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary">編集</a>
                            @if($plan->remaining_times > 0)
                            <form method="POST" action="{{ route('installment-plans.record-payment', $plan) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success">支払記録</button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('installment-plans.destroy', $plan) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('削除しますか？')">削除</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

