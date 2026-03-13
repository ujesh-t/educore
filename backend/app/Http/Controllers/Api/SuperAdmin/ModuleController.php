<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\SchoolModule;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * Display a listing of modules.
     */
    public function index(Request $request)
    {
        $query = Module::query();

        if ($request->has('active')) {
            $query->where('is_active', $request->active === 'true');
        }

        if ($request->has('core')) {
            $query->where('is_core', $request->core === 'true');
        }

        $modules = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => ['modules' => $modules],
        ]);
    }

    /**
     * Store a newly created module.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:modules,key|max:50',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'route_prefix' => 'nullable|string|max:50',
            'is_core' => 'boolean',
            'is_active' => 'boolean',
            'config' => 'nullable|array',
            'sort_order' => 'integer',
        ]);

        $module = Module::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Module created successfully',
            'data' => ['module' => $module],
        ], 201);
    }

    /**
     * Display the specified module.
     */
    public function show($id)
    {
        $module = Module::with(['enabledSchools'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => ['module' => $module],
        ]);
    }

    /**
     * Update the specified module.
     */
    public function update(Request $request, $id)
    {
        $module = Module::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'route_prefix' => 'nullable|string|max:50',
            'is_core' => 'boolean',
            'is_active' => 'boolean',
            'config' => 'nullable|array',
            'sort_order' => 'integer',
        ]);

        $module->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Module updated successfully',
            'data' => ['module' => $module],
        ]);
    }

    /**
     * Remove the specified module.
     */
    public function destroy($id)
    {
        $module = Module::findOrFail($id);

        if ($module->is_core) {
            return response()->json([
                'success' => false,
                'message' => 'Core modules cannot be deleted',
            ], 422);
        }

        $module->delete();

        return response()->json([
            'success' => true,
            'message' => 'Module deleted successfully',
        ]);
    }

    /**
     * Get module statistics.
     */
    public function stats()
    {
        $modules = Module::all();
        $stats = $modules->map(function ($module) {
            return [
                'key' => $module->key,
                'name' => $module->name,
                'enabled_schools' => $module->enabledSchools()->count(),
                'is_core' => $module->is_core,
                'is_active' => $module->is_active,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => ['stats' => $stats],
        ]);
    }
}
