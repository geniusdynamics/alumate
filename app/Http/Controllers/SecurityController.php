<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
use App\Models\DataAccessLog;
use App\Models\FailedLoginAttempt;
use App\Models\SessionSecurity;
use App\Models\TwoFactorAuth;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SecurityController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
        $this->middleware(['auth', 'role:super-admin'])->except(['twoFactorSetup', 'twoFactorVerify', 'twoFactorDisable']);
    }

    public function dashboard()
    {
        $data = $this->securityService->getSecurityDashboardData();
        
        return Inertia::render('Security/Dashboard', [
            'securityData' => $data,
        ]);
    }

    public function events(Request $request)
    {
        $query = SecurityEvent::with(['user', 'resolvedBy'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('event_type', $request->type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('resolved')) {
            $query->where('resolved', $request->boolean('resolved'));
        }

        $events = $query->paginate(20);

        return Inertia::render('Security/Events', [
            'events' => $events,
            'filters' => $request->only(['type', 'severity', 'resolved']),
        ]);
    }

    public function resolveEvent(Request $request, SecurityEvent $event)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $event->resolve(Auth::id(), $request->notes);

        return back()->with('success', 'Security event resolved successfully.');
    }

    public function dataAccessLogs(Request $request)
    {
        $query = DataAccessLog::with('user')
            ->orderBy('created_at', 'desc');

        if ($request->filled('resource_type')) {
            $query->where('resource_type', $request->resource_type);
        }

        if ($request->filled('access_type')) {
            $query->where('access_type', $request->access_type);
        }

        if ($request->filled('authorized')) {
            $query->where('authorized', $request->boolean('authorized'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->paginate(50);

        return Inertia::render('Security/DataAccessLogs', [
            'logs' => $logs,
            'filters' => $request->only(['resource_type', 'access_type', 'authorized', 'user_id']),
        ]);
    }

    public function failedLogins(Request $request)
    {
        $query = FailedLoginAttempt::orderBy('last_attempt_at', 'desc');

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        if ($request->filled('blocked')) {
            if ($request->boolean('blocked')) {
                $query->blocked();
            } else {
                $query->whereNull('blocked_until');
            }
        }

        $attempts = $query->paginate(20);

        return Inertia::render('Security/FailedLogins', [
            'attempts' => $attempts,
            'filters' => $request->only(['email', 'blocked']),
        ]);
    }

    public function unblockIp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'ip_address' => 'required|ip',
        ]);

        FailedLoginAttempt::where('email', $request->email)
            ->where('ip_address', $request->ip_address)
            ->update(['blocked_until' => null, 'attempts' => 0]);

        return back()->with('success', 'IP address unblocked successfully.');
    }

    public function activeSessions(Request $request)
    {
        $query = SessionSecurity::with('user')
            ->active()
            ->orderBy('last_activity', 'desc');

        if ($request->filled('suspicious')) {
            $query->where('is_suspicious', $request->boolean('suspicious'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $sessions = $query->paginate(20);

        return Inertia::render('Security/ActiveSessions', [
            'sessions' => $sessions,
            'filters' => $request->only(['suspicious', 'user_id']),
        ]);
    }

    public function terminateSession(Request $request, SessionSecurity $session)
    {
        $session->update(['expires_at' => now()]);

        $this->securityService->logSecurityEvent(
            'session_terminated',
            SecurityEvent::SEVERITY_MEDIUM,
            "Session terminated by admin for user: {$session->user->email}",
            [
                'session_id' => $session->session_id,
                'terminated_by' => Auth::id(),
            ]
        );

        return back()->with('success', 'Session terminated successfully.');
    }

    public function securityReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->securityService->generateSecurityReport(
            $request->start_date,
            $request->end_date
        );

        return Inertia::render('Security/Report', [
            'report' => $report,
        ]);
    }

    // Two-Factor Authentication methods
    public function twoFactorSetup()
    {
        $user = Auth::user();
        $twoFactor = TwoFactorAuth::where('user_id', $user->id)->first();

        if (!$twoFactor || !$twoFactor->enabled) {
            $twoFactor = $this->securityService->enableTwoFactorAuth($user);
        }

        return Inertia::render('Security/TwoFactorSetup', [
            'qrCodeUrl' => $twoFactor->getQrCodeUrl(),
            'recoveryCodes' => $twoFactor->recovery_codes,
            'enabled' => $twoFactor->enabled,
        ]);
    }

    public function twoFactorVerify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        
        if ($this->securityService->verifyTwoFactorCode($user, $request->code)) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid code'], 422);
    }

    public function twoFactorDisable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Invalid password']);
        }

        $this->securityService->disableTwoFactorAuth($user);

        return back()->with('success', 'Two-factor authentication disabled successfully.');
    }

    public function systemHealth()
    {
        // This would integrate with your monitoring system
        $health = [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'queue' => $this->checkQueueHealth(),
        ];

        return Inertia::render('Security/SystemHealth', [
            'health' => $health,
        ]);
    }

    private function checkDatabaseHealth()
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    private function checkCacheHealth()
    {
        try {
            \Cache::put('health_check', 'test', 60);
            $value = \Cache::get('health_check');
            return $value === 'test' 
                ? ['status' => 'healthy', 'message' => 'Cache is working properly']
                : ['status' => 'warning', 'message' => 'Cache read/write issue'];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Cache error: ' . $e->getMessage()];
        }
    }

    private function checkStorageHealth()
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            \Storage::put($testFile, 'test');
            $content = \Storage::get($testFile);
            \Storage::delete($testFile);
            
            return $content === 'test'
                ? ['status' => 'healthy', 'message' => 'Storage is working properly']
                : ['status' => 'warning', 'message' => 'Storage read/write issue'];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Storage error: ' . $e->getMessage()];
        }
    }

    private function checkQueueHealth()
    {
        try {
            // This is a simplified check - in production you'd want more comprehensive queue monitoring
            $queueSize = \Queue::size();
            return ['status' => 'healthy', 'message' => "Queue size: {$queueSize}"];
        } catch (\Exception $e) {
            return ['status' => 'warning', 'message' => 'Queue monitoring unavailable: ' . $e->getMessage()];
        }
    }
}