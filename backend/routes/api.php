<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\SuperAdmin\SchoolController;
use App\Http\Controllers\Api\SuperAdmin\ModuleController;
use App\Http\Controllers\Api\SuperAdmin\SubscriptionController;
use App\Http\Controllers\Api\SuperAdmin\PlanController;
use App\Http\Controllers\Api\SuperAdmin\InvoiceController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\ModuleMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned to "api" middleware group.
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/password/reset', [AuthController::class, 'forgotPassword']);
    Route::post('/password/confirm', [AuthController::class, 'resetPassword']);
});

// Protected routes
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
});

// Dashboard route (example)
Route::middleware(['auth:sanctum'])->get('/dashboard/stats', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $role = $user->role?->name;
    
    $stats = [
        'user' => [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name,
        ],
        'quick_stats' => [
            'students' => \App\Models\Student::count(),
            'teachers' => \App\Models\Teacher::count(),
            'classes' => \App\Models\ClassModel::count(),
            'pending_fees' => \App\Models\Fee::where('status', '!=', 'paid')->sum('balance'),
        ],
        'today_attendance' => [
            'present' => \App\Models\Attendance::where('date', today())->where('status', 'present')->count(),
            'absent' => \App\Models\Attendance::where('date', today())->where('status', 'absent')->count(),
            'late' => \App\Models\Attendance::where('date', today())->where('status', 'late')->count(),
        ],
        'recent_announcements' => \App\Models\Announcement::where('is_active', true)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get(['id', 'title', 'created_at']),
    ];

    return response()->json([
        'success' => true,
        'data' => $stats,
    ]);
});

// Admin only routes
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])->prefix('admin')->group(function () {
    // User management
    Route::get('/users', function () {
        $users = \App\Models\User::with('role')->get();
        return response()->json(['success' => true, 'data' => ['users' => $users]]);
    });
    
    // Settings management
    Route::get('/settings', function () {
        $settings = \App\Models\Setting::all();
        return response()->json(['success' => true, 'data' => ['settings' => $settings]]);
    });
    
    Route::post('/settings', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'nullable',
            'group' => 'nullable|string',
        ]);
        
        $setting = \App\Models\Setting::updateOrCreate(
            ['key' => $validated['key']],
            [
                'value' => $validated['value'] ?? null,
                'group' => $validated['group'] ?? null,
                'updated_by' => auth()->id(),
            ]
        );
        
        \App\Models\AuditLog::log('setting_update', $setting, "Setting {$setting->key} updated");
        
        return response()->json(['success' => true, 'data' => ['setting' => $setting]]);
    });
    
    // Audit logs
    Route::get('/audit-logs', function (\Illuminate\Http\Request $request) {
        $logs = \App\Models\AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));
        
        return response()->json(['success' => true, 'data' => ['logs' => $logs]]);
    });
});

// Teacher and Admin routes
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin,teacher'])->prefix('academic')->group(function () {
    // Attendance management
    Route::post('/attendance', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
            'students' => 'required|array',
            'students.*.student_id' => 'required|exists:students,id',
            'students.*.status' => 'required|in:present,absent,late,excused',
        ]);
        
        foreach ($validated['students'] as $student) {
            \App\Models\Attendance::updateOrCreate(
                [
                    'student_id' => $student['student_id'],
                    'date' => $validated['date'],
                ],
                [
                    'class_id' => $validated['class_id'],
                    'status' => $student['status'],
                    'marked_by' => auth()->id(),
                ]
            );
        }
        
        \App\Models\AuditLog::log('attendance_marked', null, "Attendance marked for class {$validated['class_id']} on {$validated['date']}");
        
        return response()->json(['success' => true, 'message' => 'Attendance marked successfully']);
    });
    
    // Grade management
    Route::post('/grades', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'marks_obtained' => 'required|numeric|min:0',
            'max_marks' => 'required|numeric|min:1',
            'academic_year' => 'nullable|string',
            'term' => 'nullable|string',
        ]);
        
        $grade = \App\Models\Grade::create([
            ...$validated,
            'teacher_id' => auth()->id(),
            'weightage' => 100,
        ]);
        
        \App\Models\AuditLog::log('grade_created', $grade, "Grade created for student {$validated['student_id']}");
        
        return response()->json(['success' => true, 'data' => ['grade' => $grade]]);
    });
});

// Staff and Admin routes (Financial)
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin,staff'])->prefix('financial')->group(function () {
    // Fee management
    Route::post('/fees', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
        ]);
        
        $feeStructure = \App\Models\FeeStructure::findOrFail($validated['fee_structure_id']);
        $discount = $validated['discount'] ?? 0;
        $totalAmount = $validated['amount'] - $discount;
        
        $fee = \App\Models\Fee::create([
            'student_id' => $validated['student_id'],
            'fee_structure_id' => $validated['fee_structure_id'],
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'amount' => $validated['amount'],
            'discount' => $discount,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance' => $totalAmount,
            'due_date' => $validated['due_date'],
            'issued_date' => today(),
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);
        
        \App\Models\AuditLog::log('fee_created', $fee, "Fee invoice created for student {$validated['student_id']}");
        
        return response()->json(['success' => true, 'data' => ['fee' => $fee]]);
    });
    
    // Payment processing
    Route::post('/payments', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'fee_id' => 'required|exists:fees,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,online,bank_transfer,cheque',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);
        
        $fee = \App\Models\Fee::findOrFail($validated['fee_id']);
        
        if ($fee->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This fee has already been paid in full',
            ], 422);
        }
        
        $transaction = \App\Models\Transaction::create([
            'fee_id' => $fee->id,
            'student_id' => $fee->student_id,
            'transaction_id' => 'TXN-' . strtoupper(uniqid()),
            'payment_method' => $validated['payment_method'],
            'amount' => $validated['amount'],
            'status' => 'completed',
            'payment_date' => $validated['payment_date'],
            'notes' => $validated['notes'] ?? null,
            'processed_by' => auth()->id(),
        ]);
        
        // Update fee
        $fee->paid_amount += $validated['amount'];
        $fee->balance = max(0, $fee->total_amount - $fee->paid_amount);
        $fee->status = $fee->balance == 0 ? 'paid' : 'partial';
        $fee->save();
        
        \App\Models\AuditLog::log('payment_processed', $transaction, "Payment processed for fee {$fee->invoice_number}");
        
        return response()->json(['success' => true, 'data' => ['transaction' => $transaction, 'fee' => $fee]]);
    });
});

// Student and Parent routes
Route::middleware(['auth:sanctum'])->prefix('student')->group(function () {
    Route::get('/profile', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $student = $user->student;
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'No student profile found',
            ], 404);
        }
        
        return response()->json(['success' => true, 'data' => ['student' => $student->load(['class', 'attendances', 'grades'])]]);
    });
    
    Route::get('/fees', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $student = $user->student;
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'No student profile found',
            ], 404);
        }
        
        $fees = $student->fees()->with('feeStructure')->orderBy('created_at', 'desc')->get();
        
        return response()->json(['success' => true, 'data' => ['fees' => $fees]]);
    });
    
    Route::get('/grades', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $student = $user->student;
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'No student profile found',
            ], 404);
        }
        
        $grades = $student->grades()->with(['subject', 'class', 'exam', 'assignment'])->get();
        
        return response()->json(['success' => true, 'data' => ['grades' => $grades]]);
    });
});

// Announcements (all authenticated users)
Route::middleware(['auth:sanctum'])->prefix('announcements')->group(function () {
    Route::get('/', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $role = $user->role?->name;
        
        $query = \App\Models\Announcement::where('is_active', true);
        
        // Filter by role if specified
        $query->where(function ($q) use ($role) {
            $q->whereNull('target_roles')
              ->orWhereJsonContains('target_roles', $role)
              ->orWhereJsonLength('target_roles', 0);
        });
        
        // Filter by class for students
        if ($role === 'student' && $user->student) {
            $classId = $user->student->class_id;
            $query->where(function ($q) use ($classId) {
                $q->whereNull('target_class_ids')
                  ->orWhereJsonContains('target_class_ids', $classId);
            });
        }
        
        $announcements = $query->with('creator')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));
        
        return response()->json(['success' => true, 'data' => ['announcements' => $announcements]]);
    });
});

// Messages
Route::middleware(['auth:sanctum'])->prefix('messages')->group(function () {
    Route::get('/', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        
        $messages = \App\Models\Message::where('recipient_id', $user->id)
            ->where('is_deleted_by_recipient', false)
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));
        
        return response()->json(['success' => true, 'data' => ['messages' => $messages]]);
    });
    
    Route::post('/', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
            'parent_message_id' => 'nullable|exists:messages,id',
        ]);
        
        $message = \App\Models\Message::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $validated['recipient_id'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'parent_message_id' => $validated['parent_message_id'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => ['message' => $message]]);
    });

    Route::put('/{id}/read', function (\Illuminate\Http\Request $request, $id) {
        $message = \App\Models\Message::where('id', $id)
            ->where('recipient_id', $request->user()->id)
            ->firstOrFail();

        $message->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => ['message' => $message]]);
    });
});

// =================================================================
// SUPER ADMIN ROUTES
// =================================================================
// These routes are only accessible to users with super_admin role
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':super_admin'])
    ->prefix('super-admin')
    ->group(function () {

    // School Management
    Route::apiResource('schools', SchoolController::class);
    Route::post('schools/{id}/toggle-status', [SchoolController::class, 'toggleStatus']);

    // Module Management
    Route::apiResource('modules', ModuleController::class);
    Route::get('modules-stats', [ModuleController::class, 'stats']);

    // Plan Management
    Route::apiResource('plans', PlanController::class);
    Route::post('plans/{id}/toggle-status', [PlanController::class, 'toggleStatus']);
    Route::get('plans/{id}/modules', [PlanController::class, 'getModules']);
    Route::post('plans/{id}/modules', [PlanController::class, 'updateModules']);

    // Subscription Management
    Route::apiResource('subscriptions', SubscriptionController::class)->only(['index', 'show', 'update']);
    Route::post('subscriptions', [SubscriptionController::class, 'store']); // Override default store
    Route::post('subscriptions/{id}/sync-modules', [SubscriptionController::class, 'syncModules']);
    Route::post('subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel']);
    Route::post('subscriptions/{id}/reactivate', [SubscriptionController::class, 'reactivate']);
    Route::post('subscriptions/{id}/change-plan', [SubscriptionController::class, 'changePlan']);
    Route::post('subscriptions/{id}/modules', [SubscriptionController::class, 'updateModules']);
    Route::get('subscriptions/{id}/history', [SubscriptionController::class, 'history']);

    // Invoice Management
    Route::get('invoices/stats', [InvoiceController::class, 'stats']);
    Route::post('invoices/mark-overdue', [InvoiceController::class, 'markOverdue']);
    Route::apiResource('invoices', InvoiceController::class)->except(['destroy']);
    Route::post('invoices/{id}/record-payment', [InvoiceController::class, 'recordPayment']);
    Route::post('invoices/{id}/cancel', [InvoiceController::class, 'cancel']);

    // Dashboard Stats
    Route::get('stats', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'total_schools' => \App\Models\School::count(),
                'active_schools' => \App\Models\School::where('is_active', true)->count(),
                'total_users' => \App\Models\User::count(),
                'subscriptions' => [
                    'free' => \App\Models\Subscription::where('plan', 'free')->count(),
                    'basic' => \App\Models\Subscription::where('plan', 'basic')->count(),
                    'standard' => \App\Models\Subscription::where('plan', 'standard')->count(),
                    'premium' => \App\Models\Subscription::where('plan', 'premium')->count(),
                ],
                'revenue' => [
                    'monthly' => \App\Models\Subscription::where('billing_cycle', 'monthly')->sum('amount'),
                    'yearly' => \App\Models\Subscription::where('billing_cycle', 'yearly')->sum('amount'),
                ],
            ],
        ]);
    });
});
