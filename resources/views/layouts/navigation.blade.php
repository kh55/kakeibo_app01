<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('dashboard') }}">家計簿アプリ</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard') }}">ダッシュボード</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('transactions.index') }}">取引明細</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('budgets.index') }}">予算</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('recurring-rules.index') }}">定期支出</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('installment-plans.index') }}">分割払い</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('cashflow.index') }}">予定表</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('annual-summary.index') }}">年間収支</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        マスタ
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('accounts.index') }}">支払手段</a></li>
                        <li><a class="dropdown-item" href="{{ route('categories.index') }}">分類</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('import-export.index') }}">インポート/エクスポート</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        {{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">プロフィール</a></li>
                        <li><a class="dropdown-item" href="{{ route('profile.login-logs') }}">ログインログ</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">ログアウト</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
