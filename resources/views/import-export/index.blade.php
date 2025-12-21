<x-app-layout>
    <x-slot name="header">
        <h2 class="h4">インポート/エクスポート</h2>
    </x-slot>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">CSVインポート</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('import-export.import') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="file" class="form-label">CSVファイル</label>
                            <input type="file" name="file" id="file" class="form-control" accept=".csv,.txt" required>
                        </div>
                        <div class="mb-3">
                            <label for="year" class="form-label">年</label>
                            <input type="number" name="year" id="year" class="form-control" value="{{ date('Y') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="month" class="form-label">月</label>
                            <input type="number" name="month" id="month" class="form-control" value="{{ date('m') }}" min="1" max="12" required>
                        </div>
                        <button type="submit" class="btn btn-primary">インポート</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">CSVエクスポート</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('import-export.export') }}">
                        <div class="mb-3">
                            <label for="export_year" class="form-label">年</label>
                            <input type="number" name="year" id="export_year" class="form-control" value="{{ date('Y') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="export_month" class="form-label">月</label>
                            <input type="number" name="month" id="export_month" class="form-control" value="{{ date('m') }}" min="1" max="12" required>
                        </div>
                        <button type="submit" class="btn btn-success">エクスポート</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

