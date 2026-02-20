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
        return array_merge(parent::share($request), [
            // THIS IS THE BRIDGE: It grabs Laravel's session errors and sends them to Vue
            'errors' => function () use ($request) {
                if ($request->session()->has('errors')) {
                    $errors = $request->session()->get('errors')->getBag('default')->getMessages();
                    // Simplify the error arrays into single strings for Vue
                    return collect($errors)->map(function ($error) {
                        return $error[0];
                    })->toArray();
                }
                return (object) [];
            },
        ]);
    }
}
