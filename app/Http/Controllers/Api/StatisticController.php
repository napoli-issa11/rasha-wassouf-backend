<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Statistic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    /**
     * Display all studio statistics for the public About page.
     */
    public function index(): JsonResponse
    {
        $statistics = Statistic::orderBy('sort_order', 'asc')->get();
        return response()->json($statistics);
    }

    /**
     * Update an individual statistic (Admin CMS).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $statistic = Statistic::findOrFail($id);

        $validated = $request->validate([
            'value' => 'sometimes|required|integer|min:0',
            'symbol' => 'nullable|string|max:10',
            'label' => 'sometimes|required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $statistic->update($validated);

        return response()->json([
            'message' => 'Statistic updated successfully.',
            'statistic' => $statistic,
        ]);
    }

    /**
     * Batch update multiple statistics at once (Admin CMS).
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'statistics' => 'required|array',
            'statistics.*.id' => 'required|integer|exists:statistics,id',
            'statistics.*.value' => 'required|integer|min:0',
            'statistics.*.symbol' => 'nullable|string|max:10',
            'statistics.*.label' => 'required|string|max:255',
        ]);

        $updated = [];
        foreach ($validated['statistics'] as $item) {
            $stat = Statistic::find($item['id']);
            if ($stat) {
                $stat->update([
                    'value' => $item['value'],
                    'symbol' => $item['symbol'] ?? '',
                    'label' => $item['label'],
                ]);
                $updated[] = $stat;
            }
        }

        return response()->json([
            'message' => 'Statistics updated successfully.',
            'statistics' => $updated,
        ]);
    }
}
