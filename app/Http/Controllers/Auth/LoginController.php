<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function login(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim((string) $request->identifier);
        $password = (string) $request->password;
        $throttleKey = $this->throttleKey($request, $identifier);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->with('error', "تم حظر المحاولة مؤقتًا. أعد المحاولة بعد {$seconds} ثانية.")
                ->withInput($request->except('password'));
        }

        if ($adminResponse = $this->attemptBootstrapAdminLogin($request, $identifier, $password, $throttleKey)) {
            return $adminResponse;
        }

        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'university_id';
        $user = User::where($field, $identifier)->first();

        if (! $user) {
            return back()->with('error', 'هذا الحساب غير موجود أو تم حذفه.')->withInput();
        }

        if (! Hash::check($password, (string) $user->password)) {
            return back()->with('error', 'كلمة المرور غير صحيحة.')->withInput();
        }

        if ((string) $user->status !== 'active') {
            return back()->with('error', $this->inactiveAccountMessage($user))->withInput();
        }

        Auth::login($user);
        $request->session()->regenerate();
        $this->logLoginNotification($user->id);

        $redirect = match ((string) $user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'student' => redirect($this->studentRedirectPath($user)),
            'supervisor' => redirect()->route('supervisor.dashboard'),
            'company' => redirect()->route('company.dashboard'),
            default => tap(redirect()->route('login')->with('error', 'نوع المستخدم غير مسموح.'), function () {
                Auth::logout();
            }),
        };

        if ($postLoginWarning = $this->postLoginWarning($user)) {
            $redirect->with('warning', $postLoginWarning);
        }

        return $redirect;
    }

    private function attemptBootstrapAdminLogin(Request $request, string $identifier, string $password, string $throttleKey)
    {
        $adminIdentifier = trim((string) env('LOCAL_ADMIN_IDENTIFIER', ''));
        $adminPassword = (string) env('LOCAL_ADMIN_PASSWORD', '');

        if (! App::isLocal() || $adminIdentifier === '' || $adminPassword === '') {
            return null;
        }

        if ($identifier !== $adminIdentifier || ! hash_equals($adminPassword, $password)) {
            return null;
        }

        $admin = User::firstOrCreate(
            ['email' => $adminIdentifier],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'password' => Hash::make($adminPassword),
                'status' => 'active',
            ]
        );

        if (! Hash::check($adminPassword, (string) $admin->password)) {
            $admin->password = Hash::make($adminPassword);
            $admin->status = 'active';
            $admin->save();
        }

        Auth::login($admin);
        $request->session()->regenerate();
        RateLimiter::clear($throttleKey);
        $this->logLoginNotification($admin->id);

        return redirect()->route('admin.dashboard');
    }

    private function throttleKey(Request $request, string $identifier): string
    {
        return Str::lower($identifier) . '|' . $request->ip();
    }

    private function logLoginNotification(int $userId): void
    {
        $this->notifications->notifyUser(
            userId: $userId,
            title: 'Login Successful',
            description: 'تم تسجيل الدخول بنجاح.',
            type: 'success',
            meta: ['category' => 'auth']
        );
    }

    private function studentRedirectPath(User $user): string
    {
        if ($user->is_in_jisr) {
            return route('student.jisr');
        }

        if ($user->skill_test_required && ! $user->skill_test_passed) {
            return route('student.skill-test');
        }

        return route('student.dashboard');
    }

    private function inactiveAccountMessage(User $user): string
    {
        if ((string) $user->status === 'pending') {
            return 'حسابك ما زال قيد انتظار الاعتماد.';
        }

        if ((string) $user->status === 'rejected') {
            $meta = UserNotification::query()
                ->where('user_id', $user->id)
                ->whereIn('type', ['error', 'danger', 'warning'])
                ->latest()
                ->value('meta');

            $reason = is_array($meta) ? ($meta['reason'] ?? null) : null;

            if ($reason) {
                return 'تم رفض حسابك، والسبب هو: ' . $reason;
            }

            return 'تم رفض حسابك. يرجى مراجعة المشرف لمعرفة السبب.';
        }

        return 'حسابك غير نشط حاليًا.';
    }

    private function postLoginWarning(User $user): ?string
    {
        $notification = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['error', 'danger', 'warning'])
            ->latest()
            ->first();

        $reason = $notification?->meta['reason'] ?? null;
        $category = $notification?->meta['category'] ?? null;

        if (! $reason || $category !== 'application') {
            return null;
        }

        return 'يوجد رفض سابق لطلبك. السبب: ' . $reason;
    }
}
