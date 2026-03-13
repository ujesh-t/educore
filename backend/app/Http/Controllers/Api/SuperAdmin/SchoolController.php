<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\SchoolModule;
use App\Models\Module;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class SchoolController extends Controller
{
    /**
     * Display a listing of schools.
     */
    public function index(Request $request)
    {
        $query = School::query()->with(['subscription', 'enabledModules']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('plan')) {
            $query->whereHas('subscription', function ($q) use ($request) {
                if ($request->plan === 'custom') {
                    // For custom plans, check if plan key starts with 'custom_'
                    $q->where('plan', 'like', 'custom_%');
                } else {
                    // For predefined plans (free, basic, standard, premium)
                    $q->where('plan', $request->plan);
                }
            });
        }

        $schools = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => ['schools' => $schools],
        ]);
    }

    /**
     * Store a newly created school.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:schools,email',
            'phone' => 'nullable|string|max:20',
            'subdomain' => 'nullable|string|unique:schools,subdomain',
            'domain' => 'nullable|string|unique:schools,domain',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:50',
            'logo' => 'nullable|image|max:2048',
            'config' => 'nullable|array',
            'is_active' => 'boolean',
            'admin_email' => 'required_without:id|email',
            'admin_name' => 'required_without:id|string|max:255',
            'admin_password' => 'required_without:id|min:8',
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
            'plan' => 'nullable|in:free,basic,standard,premium,custom',
            'billing_cycle' => 'nullable|in:monthly,yearly,lifetime',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
        ]);

        // Create school
        $school = School::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'subdomain' => $validated['subdomain'] ?? null,
            'domain' => $validated['domain'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? 'US',
            'timezone' => $validated['timezone'] ?? 'UTC',
            'config' => $validated['config'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $school->update([
                'logo' => $request->file('logo')->store('school_logos', 'public'),
            ]);
        }

        // Create subscription
        $plan = null;
        if ($request->has('subscription_plan_id')) {
            $plan = SubscriptionPlan::find($request->subscription_plan_id);
        }

        $trialDays = $request->get('trial_days', 0);
        $trialEndsAt = $trialDays > 0 ? now()->addDays($trialDays) : null;

        $subscription = Subscription::create([
            'school_id' => $school->id,
            'subscription_plan_id' => $plan?->id,
            'plan' => $plan?->key ?? $request->get('plan', 'free'),
            'status' => 'active',
            'amount' => $request->get('amount', $plan?->price ?? 0),
            'currency' => $request->get('currency', $plan?->currency ?? 'USD'),
            'billing_cycle' => $request->get('billing_cycle', $plan?->billing_cycle ?? 'monthly'),
            'trial_ends_at' => $trialEndsAt,
            'starts_at' => now(),
            'metadata' => [
                'created_by' => auth()->id(),
            ],
        ]);

        // Sync modules based on plan
        $this->syncModulesWithPlan($subscription);

        // Create admin user for the school
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        $admin = User::create([
            'school_id' => $school->id,
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($validated['admin_password']),
            'role_id' => $adminRole?->id,
            'is_active' => true,
        ]);

        AuditLog::log('school_created', $school, "School {$school->name} created with admin {$admin->email}");

        return response()->json([
            'success' => true,
            'message' => 'School created successfully',
            'data' => [
                'school' => $school->load(['subscription', 'enabledModules']),
                'admin' => $admin,
            ],
        ], 201);
    }

    /**
     * Display the specified school.
     */
    public function show($id)
    {
        $school = School::with(['subscription', 'modules', 'enabledModules', 'users.role'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => ['school' => $school],
        ]);
    }

    /**
     * Update the specified school.
     */
    public function update(Request $request, $id)
    {
        $school = School::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['nullable', 'email', Rule::unique('schools')->ignore($id)],
            'phone' => 'nullable|string|max:20',
            'subdomain' => ['nullable', Rule::unique('schools')->ignore($id)],
            'domain' => ['nullable', Rule::unique('schools')->ignore($id)],
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:50',
            'logo' => 'nullable|image|max:2048',
            'config' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('school_logos', 'public');
        }

        $school->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'School updated successfully',
            'data' => ['school' => $school->fresh(['subscription', 'enabledModules'])],
        ]);
    }

    /**
     * Remove the specified school.
     */
    public function destroy($id)
    {
        $school = School::findOrFail($id);
        $school->delete();

        return response()->json([
            'success' => true,
            'message' => 'School deleted successfully',
        ]);
    }

    /**
     * Toggle school active status.
     */
    public function toggleStatus($id)
    {
        $school = School::findOrFail($id);
        $school->update(['is_active' => !$school->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'School status updated',
            'data' => ['is_active' => $school->is_active],
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
