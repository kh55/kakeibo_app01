<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 mb-0">分類別比較</h2>
    </x-slot>

    <form method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-auto">
            <label class="form-label mb-1">分類（支出）</label>
            <select name="category_id" class="form-select" onchange="this.form.submit()">
                @forelse($categories as $category)
                    <option value="{{ $category->id }}"
                        @selected($selectedCategory && $selectedCategory->id === $category->id)>
                        {{ $category->name }}
                    </option>
                @empty
                    <option value="">支出分類がありません</option>
                @endforelse
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label mb-1">基準月（日毎）</label>
            <input type="month" name="month" value="{{ $baseMonth }}" class="form-control"
                onchange="this.form.submit()">
        </div>
        <div class="col-auto">
            <label class="form-label mb-1">基準年（月毎）</label>
            <input type="number" name="year" value="{{ $baseYear }}" class="form-control"
                style="width: 7rem" onchange="this.form.submit()">
        </div>
    </form>

    @if(! $selectedCategory)
        <div class="alert alert-info">支出分類が登録されていません。先に「マスタ &gt; 分類」で支出分類を登録してください。</div>
    @else
        <div x-data="{ tab: 'daily' }">
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link" href="#" :class="{ active: tab === 'daily' }"
                        @click.prevent="tab = 'daily'">日毎（前月比）</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" :class="{ active: tab === 'monthly' }"
                        @click.prevent="tab = 'monthly'">月毎（前年比）</a>
                </li>
            </ul>

            <div x-show="tab === 'daily'">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            {{ $selectedCategory->name }} 日別支出（{{ $daily['currentLabel'] }} vs {{ $daily['previousLabel'] }}）
                        </h5>
                    </div>
                    <div class="card-body">
                        @include('category-comparison._summary', ['data' => $daily, 'changeLabel' => '前月比'])
                        <canvas id="dailyChart" data-daily='@json($daily)'></canvas>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'monthly'">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            {{ $selectedCategory->name }} 月別支出（{{ $monthly['currentLabel'] }} vs {{ $monthly['previousLabel'] }}）
                        </h5>
                    </div>
                    <div class="card-body">
                        @include('category-comparison._summary', ['data' => $monthly, 'changeLabel' => '前年比'])
                        <canvas id="monthlyChart" data-monthly='@json($monthly)'></canvas>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lineOptions = {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } },
                spanGaps: false,
            };

            function buildLine(canvas, payload) {
                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: payload.labels,
                        datasets: [
                            {
                                label: payload.currentLabel,
                                data: payload.current,
                                borderColor: '#0d6efd',
                                backgroundColor: '#0d6efd',
                                tension: 0.2,
                            },
                            {
                                label: payload.previousLabel,
                                data: payload.previous,
                                borderColor: '#adb5bd',
                                backgroundColor: '#adb5bd',
                                borderDash: [5, 5],
                                tension: 0.2,
                            },
                        ]
                    },
                    options: lineOptions,
                });
            }

            const dailyCanvas = document.getElementById('dailyChart');
            if (dailyCanvas) {
                buildLine(dailyCanvas, JSON.parse(dailyCanvas.dataset.daily));
            }

            const monthlyCanvas = document.getElementById('monthlyChart');
            if (monthlyCanvas) {
                buildLine(monthlyCanvas, JSON.parse(monthlyCanvas.dataset.monthly));
            }
        });
        </script>
    @endif
</x-app-layout>
