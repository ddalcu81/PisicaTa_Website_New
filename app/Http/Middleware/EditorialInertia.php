<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class EditorialInertia extends Middleware
{
    protected $rootView = 'editorial';

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'user' => fn () => $request->user()?->only('name', 'email'),
            'message' => fn () => $request->session()->get('message'),
        ]);
    }
}
