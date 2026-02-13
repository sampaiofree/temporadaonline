<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegacyController extends Controller
{
    public function index(Request $request): View
    {
        return view('legacy.index', [
            'legacyConfig' => [
                'profileSettingsUrl' => route('legacy.profile.settings'),
                'profileUpdateUrl' => route('legacy.profile.update'),
                'profileDisponibilidadesSyncUrl' => route('legacy.profile.disponibilidades.sync'),
                'logoutUrl' => route('legacy.logout'),
                'userId' => $request->user()?->id,
            ],
        ]);
    }
}
