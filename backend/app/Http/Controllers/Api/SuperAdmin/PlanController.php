<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    /**
     * Display a listing of plans.
     */
    public function index(Request $request)
    {
        $query = SubscriptionPlan::query();

        if ($request->has('custom')) {
            $query->where('is_custom', $request->custom === 'true');
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->active === 'true');
        }

        $plans = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => ['plans' => $plans],
        ]);
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10|default:USD',
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
            'modules' => 'nullable|array',
            'modules.*' => 'string|exists:modules,key',
            'trial_days' => 'integer|min:0|max:365',
            'is_active' => 'boolean',
        ]);

        // Get all free modules (automatically included in all plans)
        $freeModules = Module::freeModules()->pluck('key')->toArray();

        // Merge free modules with selected modules (if any)
        $selectedModules = $validated['modules'] ?? [];
        $allModules = array_unique(array_merge($freeModules, $selectedModules));

        if (empty($allModules)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one module must be selected (or mark modules as free modules)',
            ], 422);
        }

        $plan = SubscriptionPlan::create([
            'name' => $validated['name'],
            'key' => 'custom_' . strtolower(str_replace(' ', '_', $validated['name'])),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'USD',
            'billing_cycle' => $validated['billing_cycle'],
            'modules' => array_values($allModules),
            'trial_days' => $validated['trial_days'] ?? 0,
            'is_custom' => true,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully with ' . count($freeModules) . ' free module(s) included',
            'data' => ['plan' => $plan],
        ], 201);
    }

    /**
     * Display the specified plan.
     */
    public function show($id)
    {
        $plan = SubscriptionPlan::withCount(['schools as active_schools_count' => function ($query) {
            $query->where('status', 'active');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => ['plan' => $plan],
        ]);
    }

    /**
     * Update the specified plan.
     */
    public function update(Request $request, $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string|max:500',
            'price' => 'sometimes|required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'billing_cycle' => 'sometimes|required|in:monthly,yearly,lifetime',
            'modules' => 'sometimes|array',
            'modules.*' => 'string|exists:modules,key',
            'trial_days' => 'sometimes|required|integer|min:0|max:365',
            'is_active' => 'boolean',
        ]);

        // If modules are being updated, merge with free modules
        if (isset($validated['modules'])) {
            $freeModules = Module::freeModules()->pluck('key')->toArray();
            $allModules = array_unique(array_merge($freeModules, $validated['modules']));
            $validated['modules'] = array_values($allModules);
        }

        $plan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plan updated successfully',
            'data' => ['plan' => $plan->fresh()],
        ]);
    }

    /**
     * Get modules for a specific plan.
     */
    public function getModules($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $allModules = Module::all();

        $planModules = $allModules->map(function ($module) use ($plan) {
            $module->is_assigned = in_array($module->key, $plan->modules ?? []);
            $module->is_free = $module->is_free_module;
            return $module;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $plan,
                'modules' => $planModules,
            ],
        ]);
    }

    /**
     * Update modules for a specific plan.
     */
    public function updateModules(Request $request, $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $validated = $request->validate([
            'modules' => 'required|array',
            'modules.*' => 'string|exists:modules,key',
        ]);

        // Get free modules (always included)
        $freeModules = Module::freeModules()->pluck('key')->toArray();

        // Merge with selected modules
        $allModules = array_unique(array_merge($freeModules, $validated['modules']));

        $plan->update(['modules' => array_values($allModules)]);

        return response()->json([
            'success' => true,
            'message' => 'Plan modules updated successfully (' . count($freeModules) . ' free modules always included)',
            'data' => ['plan' => $plan->fresh()],
        ]);
    }

    /**
     * Remove the specified plan.
     */
    public function destroy($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        // Check if any schools are using this plan
        $schoolsCount = $plan->schools()->count();
        if ($schoolsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete plan. {$schoolsCount} school(s) are using this plan.",
            ], 422);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted successfully',
        ]);
    }

    /**
     * Toggle plan active status.
     */
    public function toggleStatus($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Plan status updated',
            'data' => ['is_active' => $plan->is_active],
        ]);
    }
}
