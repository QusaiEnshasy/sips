<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    private function profilePayload(User $user): array
    {
        $role = (string) $user->role;

        $editable = [];
        if (in_array($role, ['student', 'supervisor'], true)) {
            $editable = ['name', 'phone_number'];
        } elseif ($role === 'company') {
            $editable = ['name', 'phone_number', 'company_name', 'company_address', 'company_website'];
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'phone_number' => $user->phone_number,
                'company_name' => $user->company_name,
                'company_address' => $user->company_address,
                'company_website' => $user->company_website,
                'status' => $user->status,
                'supervisor_code' => $user->supervisor_code,
                'university_id' => $user->university_id,
                'student_id' => $user->student_id ?? null,
            ],
            'editable_fields' => $editable,
        ];
    }

    public function edit(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $this->profilePayload($user),
            ]);
        }

        return view('spa');
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->role === 'admin') {
            return response()->json(['status' => 'error', 'message' => 'ملف الأدمن للعرض فقط.'], 422);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ];

        if ($user->role === 'company') {
            $rules['company_name'] = ['nullable', 'string', 'max:255'];
            $rules['company_address'] = ['nullable', 'string', 'max:255'];
            $rules['company_website'] = ['nullable', 'url', 'max:255'];
        }

        $validated = $request->validate($rules);

        if ($user->role !== 'company') {
            unset($validated['company_name'], $validated['company_address'], $validated['company_website']);
        }

        $user->update([
            'name' => trim((string) ($validated['name'] ?? $user->name)),
            'phone_number' => $validated['phone_number'] ? trim((string) $validated['phone_number']) : null,
            'company_name' => array_key_exists('company_name', $validated)
                ? ($validated['company_name'] ? trim((string) $validated['company_name']) : null)
                : $user->company_name,
            'company_address' => array_key_exists('company_address', $validated)
                ? ($validated['company_address'] ? trim((string) $validated['company_address']) : null)
                : $user->company_address,
            'company_website' => array_key_exists('company_website', $validated)
                ? ($validated['company_website'] ? trim((string) $validated['company_website']) : null)
                : $user->company_website,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث الملف الشخصي بنجاح.',
            'data' => $this->profilePayload($user->fresh()),
        ]);
    }

    public function changePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! in_array((string) $user->role, ['student', 'supervisor', 'company'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'هذا النوع من الحسابات لا يمكنه تغيير كلمة المرور من هذه الشاشة.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'different:current_password'],
            'new_password_confirmation' => ['nullable', 'string'],
            'confirm_password' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $currentPassword = (string) $request->input('current_password');
        $newPassword = (string) $request->input('new_password');
        $confirm = (string) ($request->input('new_password_confirmation') ?: $request->input('confirm_password'));

        if ($confirm === '' || ! hash_equals($newPassword, $confirm)) {
            return response()->json(['status' => 'error', 'message' => 'Password confirmation does not match.'], 422);
        }

        if (! Hash::check($currentPassword, (string) $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'كلمة المرور الحالية غير صحيحة.'], 422);
        }

        $user->update(['password' => Hash::make($newPassword)]);

        $this->notifications->notifyUser(
            userId: $user->id,
            title: 'Password Changed',
            description: 'تم تغيير كلمة المرور بنجاح.',
            type: 'success',
            meta: ['category' => 'auth']
        );

        return response()->json(['status' => 'success', 'message' => 'تم تحديث كلمة المرور بنجاح.']);
    }

    public function destroy(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! in_array((string) $user->role, ['student', 'supervisor', 'company'], true)) {
            return response()->json(['status' => 'error', 'message' => 'هذا النوع من الحسابات لا يمكن حذفه.'], 422);
        }

        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $user->delete();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['status' => 'success', 'message' => 'تم حذف الحساب بنجاح.']);
    }
}
