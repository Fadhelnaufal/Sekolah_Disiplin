<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use App\Models\School;
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
                'user' => $request->user(),
                'roles' => $request->user() ? $request->user()->getRoleNames() : [],
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'school' => [
                'name' => rescue(fn () => School::value('name'), 'SMP Malang', false) ?: 'SMP Malang',
                'academic_year' => rescue(fn () => AcademicYear::whereIn('status', ['Active', 'active'])->value('name'), '2026/2027', false) ?: '2026/2027',
                'application_name' => rescue(fn () => School::value('application_name'), 'Sekolah Disiplin', false) ?: 'Sekolah Disiplin',
                'logo_path' => rescue(fn () => School::value('logo_path'), null, false),
            ],
        ];
    }
}
