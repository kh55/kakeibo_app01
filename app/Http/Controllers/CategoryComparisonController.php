<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CategoryComparisonService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CategoryComparisonController extends Controller
{
    public function __construct(
        private CategoryComparisonService $service
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();

        $categories = Category::where('user_id', $user->id)
            ->where('type', 'expense')
            ->orderBy('sort_order')
            ->get();

        $categoryId = (int) $request->get('category_id', 0);
        $selectedCategory = $categories->firstWhere('id', $categoryId) ?? $categories->first();

        $baseMonth = $this->parseBaseMonth($request->get('month'));
        $baseYear = (int) $request->get('year', Carbon::now()->year);

        $daily = null;
        $monthly = null;
        if ($selectedCategory) {
            $daily = $this->service->getDailyComparison($user, $selectedCategory->id, $baseMonth);
            $monthly = $this->service->getMonthlyComparison($user, $selectedCategory->id, $baseYear);
        }

        return view('category-comparison.index', [
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'baseMonth' => $baseMonth->format('Y-m'),
            'baseYear' => $baseYear,
            'daily' => $daily,
            'monthly' => $monthly,
        ]);
    }

    private function parseBaseMonth(?string $value): Carbon
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        }

        return Carbon::now()->startOfMonth();
    }
}
