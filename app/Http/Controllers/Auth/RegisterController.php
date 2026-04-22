<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Schema;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $request->merge([
            'email' => $request->filled('email') ? trim((string) $request->email) : null,
            'university_id' => $request->filled('university_id') ? trim((string) $request->university_id) : null,
            'phone_number' => $request->filled('phone_number') ? trim((string) $request->phone_number) : null,
            'supervisor_code' => $request->filled('supervisor_code') ? trim((string) $request->supervisor_code) : null,
        ]);

        $rules = [
            'role' => 'required|in:student,supervisor,company,admin',
            'name' => 'required|string|max:255',
            'password' => 'required|confirmed|min:6',
            'phone_number' => 'required|string|min:9',
            'email' => 'nullable|email|unique:users,email',
        ];

        if ($request->role === 'student') {
            $rules['university_id'] = 'required|string|max:255|unique:users,university_id';
            $rules['supervisor_code'] = 'required|exists:users,supervisor_code';
        }

        if ($request->role === 'supervisor') {
            $rules['university_id'] = 'required|string|max:255|unique:users,university_id';
        }

        if ($request->role === 'company') {
            $rules['email'] = 'required|email|unique:users,email';
            $rules['company_name'] = 'required|string|max:255';
            $rules['company_address'] = 'required|string|max:255';
        }

        if ($request->role === 'admin') {
            $rules['email'] = 'required|email|unique:users,email';
        }

        $request->validate($rules);

        $supervisorCode = null;

        if ($request->role === 'supervisor') {
            $supervisorCode = $this->generateSupervisorCode();
        }

        if ($request->role === 'student') {
            $supervisor = User::where('supervisor_code', $request->supervisor_code)
                ->where('role', 'supervisor')
                ->first();

            if (!$supervisor) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => 'كود المشرف غير صحيح',
                        'errors' => [
                            'supervisor_code' => ['كود المشرف غير صحيح'],
                        ],
                    ], 422);
                }

                return back()->withErrors([
                    'supervisor_code' => 'كود المشرف غير صحيح'
                ])->withInput();
            }
        }

        $user = User::create([
            'role' => $request->role,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'university_id' => $request->university_id,
            'supervisor_code' => $request->role === 'supervisor'
                ? $supervisorCode
                : $request->supervisor_code,
            'company_name' => $request->company_name,
            'company_website' => $request->company_website,
            'company_address' => $request->company_address,
            'status' => $request->role === 'admin' ? 'active' : 'pending',
        ]);

        if ($user->role === 'admin' && $user->status === 'active') {
            Auth::login($user);
            $request->session()->regenerate();
            $this->logRegisterNotification($user->id);

            if ($request->expectsJson() || $request->ajax()) {
                $request->session()->flash('success', 'تم إنشاء حساب الأدمن وتسجيل الدخول بنجاح');

                return response()->json([
                    'status' => 'success',
                    'message' => 'تم إنشاء حساب الأدمن وتسجيل الدخول بنجاح',
                    'redirect' => route('admin.dashboard'),
                ]);
            }

            return redirect()->route('admin.dashboard')
                ->with('success', 'تم إنشاء حساب الأدمن وتسجيل الدخول بنجاح');
        }

        $successMessage = $user->role === 'student'
            ? 'تم إنشاء الحساب بنجاح، بانتظار موافقة المشرف'
            : 'تم إنشاء الحساب بنجاح، بانتظار موافقة الأدمن';

        $successMessage = $user->role === 'student'
            ? 'تم إنشاء حسابك بنجاح، والآن قيد انتظار قبول المشرف.'
            : 'تم إنشاء الحساب بنجاح، والآن قيد انتظار قبول الأدمن.';

        if ($request->expectsJson() || $request->ajax()) {
            $request->session()->flash('success', $successMessage);

            return response()->json([
                'status' => 'success',
                'message' => $successMessage,
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login')
            ->with('success', $successMessage);
    }

    private function generateSupervisorCode(): string
    {
        do {
            $code = 'SUP-' . strtoupper(Str::random(6));
        } while (User::where('supervisor_code', $code)->exists());

        return $code;
    }

    private function logRegisterNotification(int $userId): void
    {
        try {
            if (! Schema::hasTable('user_notifications')) {
                return;
            }

            UserNotification::create([
                'user_id' => $userId,
                'title' => 'create new profile Successfully',
                'description' => 'تم انشاء حسابك بنجاح.',
                'type' => 'success',
                'meta' => ['category' => 'auth'],
            ]);
        } catch (\Throwable) {
        }
    }
}
