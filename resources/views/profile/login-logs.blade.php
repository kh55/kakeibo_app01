<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">ログインログ</h2>
            <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">プロフィールに戻る</a>
        </div>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <p class="text-muted mb-3">
                あなたのアカウントへのログイン試行履歴を表示しています。不審なアクセスがないか定期的に確認してください。
            </p>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>日時</th>
                            <th>ステータス</th>
                            <th>IPアドレス</th>
                            <th>User-Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loginLogs as $log)
                        <tr>
                            <td>{{ $log->login_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                @if($log->status === 'success')
                                    <span class="badge bg-success">成功</span>
                                @else
                                    <span class="badge bg-danger">失敗</span>
                                @endif
                            </td>
                            <td>
                                <code>{{ $log->ip_address }}</code>
                            </td>
                            <td>
                                <small class="text-muted">{{ Str::limit($log->user_agent ?? '不明', 60) }}</small>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                ログインログがありません
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $loginLogs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
