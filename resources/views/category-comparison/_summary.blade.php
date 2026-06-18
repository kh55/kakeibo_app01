@php
    $cur = $data['currentTotal'];
    $prev = $data['previousTotal'];
    $diff = $cur - $prev;
    $pct = $prev > 0 ? (int) round($diff / $prev * 100) : null;
@endphp
<div class="d-flex flex-wrap align-items-baseline gap-3 mb-3">
    <span>{{ $data['currentLabel'] }} 合計: <strong class="fs-5">{{ number_format($cur) }}円</strong></span>
    <span class="text-muted">{{ $data['previousLabel'] }} 合計: {{ number_format($prev) }}円</span>
    <span class="{{ $diff > 0 ? 'text-danger' : ($diff < 0 ? 'text-success' : 'text-muted') }}">
        {{ $changeLabel }}: {{ $diff > 0 ? '+' : '' }}{{ number_format($diff) }}円（{{ $pct === null ? '—' : ($pct > 0 ? '+' : '').$pct.'%' }}）
    </span>
</div>
