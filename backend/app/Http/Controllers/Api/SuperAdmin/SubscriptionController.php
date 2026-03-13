<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Module;
use App\Models\School;
use App\Models\SchoolModule;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request)
    {
        $query = Subscription::with(['school', 'planModel']);

        if ($request->has('plan')) {
            $query->where('plan', $request->plan);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => ['subscriptions' => $subscriptions],
        ]);
    }

    /**
     * Display the specified subscription.
     */
    public function show($id)
    {
        $subscription = Subscription::with(['school.enabledModules', 'planModel'])->findOrFail($id);

        // Get all modules with their assignment status
        $allModules = Module::all()->map(function ($module) use ($subscription) {
            $module->is_enabled = $subscription->school->modules()
                ->wherePivot('is_enabled', true)
                ->where('module_id', $module->id)
                ->exists();
            $module->is_core = $module->is_core;
            $module->is_free = $module->is_free_module;
            return $module;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => $subscription,
                'modules' => $allModules,
                'availablePlans' => SubscriptionPlan::where('is_active', true)->get(),
            ],
        ]);
    }

    /**
     * Create a new subscription.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'status' => 'required|in:active,trial,cancelled,expired,past_due',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'billing_cycle' => 'nullable|in:monthly,yearly,lifetime',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);
        $school = School::findOrFail($validated['school_id']);

        // Check if school already has an active subscription
        $existingSubscription = $school->activeSubscription()->first();
        if ($existingSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'School already has an active subscription',
            ], 422);
        }

        // Calculate dates
        $startsAt = isset($validated['starts_at']) ? Carbon::parse($validated['starts_at']) : now();
        $trialDays = $validated['trial_days'] ?? $plan->trial_days ?? 0;
        $trialEndsAt = $trialDays > 0 ? $startsAt->copy()->addDays($trialDays) : null;

        // Calculate expiry based on billing cycle
        $expiresAt = isset($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null;
        if (!$expiresAt && isset($validated['billing_cycle'])) {
            $billingCycle = $validated['billing_cycle'] ?? $plan->billing_cycle;
            $expiresAt = match($billingCycle) {
                'monthly' => $startsAt->copy()->addMonth(),
                'yearly' => $startsAt->copy()->addYear(),
                'lifetime' => null,
                default => $startsAt->copy()->addYear(),
            };
        }

        $subscription = Subscription::create([
            'school_id' => $school->id,
            'subscription_plan_id' => $plan->id,
            'plan' => $plan->key,
            'status' => $validated['status'],
            'amount' => $validated['amount'] ?? $plan->price,
            'currency' => $validated['currency'] ?? $plan->currency,
            'billing_cycle' => $validated['billing_cycle'] ?? $plan->billing_cycle,
            'trial_ends_at' => $trialEndsAt,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'metadata' => [
                'created_by' => auth()->id(),
                'plan_name' => $plan->name,
            ],
        ]);

        // Sync modules based on plan
        $this->syncModulesWithPlan($subscription);

        AuditLog::log('subscription_created', $subscription, "Subscription created for school {$school->name}");

        return response()->json([
            'success' => true,
            'message' => 'Subscription created successfully',
            'data' => ['subscription' => $subscription->fresh(['school', 'planModel'])],
        ], 201);
    }

    /**
     * Update the specified subscription.
     */
    public function update(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);

        $validated = $request->validate([
            'subscription_plan_id' => 'sometimes|required|exists:subscription_plans,id',
            'plan' => 'sometimes|required|in:free,basic,standard,premium,custom',
            'status' => 'sometimes|required|in:active,cancelled,expired,past_due,trial',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'billing_cycle' => 'nullable|in:monthly,yearly,lifetime',
            'trial_ends_at' => 'nullable|date',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        // If plan is changing
        if (isset($validated['subscription_plan_id'])) {
            $newPlan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);
            $oldPlanName = $subscription->planModel?->name ?? $subscription->plan;

            $subscription->subscription_plan_id = $newPlan->id;
            $subscription->plan = $newPlan->key;
            $subscription->amount = $newPlan->price;
            $subscription->currency = $newPlan->currency;
            $subscription->billing_cycle = $newPlan->billing_cycle;

            // Sync modules with new plan
            $this->syncModulesWithPlan($subscription);

            AuditLog::log('subscription_plan_changed', $subscription, 
                "Plan changed from {$oldPlanName} to {$newPlan->name} for school {$subscription->school->name}");
        }

        $subscription->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subscription updated successfully',
            'data' => ['subscription' => $subscription->fresh(['school', 'planModel'])],
        ]);
    }

    /**
     * Change the plan for a subscription.
     */
    public function changePlan(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);

        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'prorate' => 'boolean',
            'effective_date' => 'nullable|date',
        ]);

        $newPlan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);
        $oldPlan = $subscription->planModel;

        // Calculate proration if requested
        $proration = null;
        if ($validated['prorate'] ?? false) {
            $daysRemaining = $subscription->expires_at 
                ? max(0, now()->diffInDays($subscription->expires_at, false))
                : 30;
            $oldPlanDailyRate = $subscription->amount / 30;
            $newPlanDailyRate = $newPlan->price / 30;
            $proration = [
                'old_plan' => $oldPlan?->name ?? $subscription->plan,
                'new_plan' => $newPlan->name,
                'days_remaining' => $daysRemaining,
                'credit_amount' => round($oldPlanDailyRate * $daysRemaining, 2),
                'additional_charge' => round(($newPlanDailyRate - $oldPlanDailyRate) * $daysRemaining, 2),
            ];
        }

        // Update subscription
        $subscription->update([
            'subscription_plan_id' => $newPlan->id,
            'plan' => $newPlan->key,
            'amount' => $newPlan->price,
            'currency' => $newPlan->currency,
            'billing_cycle' => $newPlan->billing_cycle,
            'metadata' => array_merge($subscription->metadata ?? [], [
                'plan_changed_at' => now(),
                'previous_plan' => $oldPlan?->key ?? $subscription->plan,
                'proration' => $proration,
            ]),
        ]);

        // Sync modules
        $this->syncModulesWithPlan($subscription);

        AuditLog::log('subscription_plan_changed', $subscription, 
            "Plan changed from {$oldPlan?->name} to {$newPlan->name}");

        return response()->json([
            'success' => true,
            'message' => 'Plan changed successfully',
            'data' => [
                'subscription' => $subscription->fresh(['school', 'planModel']),
                'proration' => $proration,
            ],
        ]);
    }

    /**
     * Update modules for a subscription's school.
     */
    public function updateModules(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);
        $school = $subscription->school;

        $validated = $request->validate([
            'modules' => 'required|array',
            'modules.*' => 'exists:modules,id',
        ]);

        $allModules = Module::all();
        foreach ($allModules as $module) {
            $isEnabled = in_array($module->id, $validated['modules']) || $module->is_core;

            SchoolModule::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'module_id' => $module->id,
                ],
                ['is_enabled' => $isEnabled]
            );
        }

        AuditLog::log('subscription_modules_updated', $subscription, 
            "Modules updated for school {$school->name}");

        return response()->json([
            'success' => true,
            'message' => 'Modules updated successfully',
            'data' => [
                'subscription' => $subscription->fresh(['school.enabledModules']),
            ],
        ]);
    }

    /**
     * Get subscription history.
     */
    public function history($id)
    {
        $subscription = Subscription::findOrFail($id);

        // Get audit logs related to this subscription
        $auditLogs = AuditLog::where('auditable_type', Subscription::class)
            ->where('auditable_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get plan change history from metadata
        $planChanges = [];
        $current = $subscription;
        if ($current->metadata && isset($current->metadata['plan_changed_at'])) {
            $planChanges[] = [
                'changed_at' => $current->metadata['plan_changed_at'],
                'from_plan' => $current->metadata['previous_plan'] ?? null,
                'to_plan' => $current->planModel?->name ?? $current->plan,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => $subscription,
                'audit_logs' => $auditLogs,
                'plan_changes' => $planChanges,
            ],
        ]);
    }

    /**
     * Sync modules for a subscription's school.
     */
    public function syncModules($id)
    {
        $subscription = Subscription::findOrFail($id);
        $this->syncModulesWithPlan($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Modules synced with subscription plan',
            'data' => [
                'subscription' => $subscription->fresh(['school.enabledModules']),
            ],
        ]);
    }

    /**
     * Cancel the subscription.
     */
    public function cancel($id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'metadata' => array_merge($subscription->metadata ?? [], [
                'cancelled_by' => auth()->id(),
            ]),
        ]);

        AuditLog::log('subscription_cancelled', $subscription, 
            "Subscription cancelled for school {$subscription->school->name}");

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
            'data' => ['subscription' => $subscription],
        ]);
    }

    /**
     * Reactivate the subscription.
     */
    public function reactivate($id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->update([
            'status' => 'active',
            'cancelled_at' => null,
        ]);

        AuditLog::log('subscription_reactivated', $subscription, 
            "Subscription reactivated for school {$subscription->school->name}");

        return response()->json([
            'success' => true,
            'message' => 'Subscription reactivated successfully',
            'data' => ['subscription' => $subscription],
        ]);
    }

    /**
     * Sync modules with subscription plan.
     */
    private function syncModulesWithPlan(Subscription $subscription): void
    {
        $school = $subscription->school;
        $includedModules = $subscription->getIncludedModulesAttribute();

        $modules = Module::all();
        foreach ($modules as $module) {
            // Core modules and free modules are always enabled
            // Plus modules included in the subscription plan
            $isEnabled = $module->is_core 
                || $module->is_free_module
                || in_array($module->key, $includedModules);

            SchoolModule::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'module_id' => $module->id,
                ],
                ['is_enabled' => $isEnabled]
            );
        }
    }
}
