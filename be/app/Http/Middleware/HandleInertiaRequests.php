<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'nik' => $request->user()->nik,
                    'department' => $request->user()->department,
                    'branch' => $request->user()->branch,
                    'role_name' => $request->user()->role_name,
                    'avatar' => $request->user()->avatar,
                    'roles' => method_exists($request->user(), 'getRoleNames') ? $request->user()->getRoleNames() : [$request->user()->role_name],
                    'permissions' => method_exists($request->user(), 'getAllPermissions') ? $request->user()->getAllPermissions()->pluck('name') : [],
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'meta' => [
                'app_name' => 'BWS Learning Integrated System',
                'bank_code' => 'BWS-ID',
                'security_badge' => 'OJK & Bank Indonesia Compliant (POJK 11/2022)',
                'environment' => config('app.env'),
            ],
        ];
    }
}
