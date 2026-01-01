<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartidaDenuncia;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartidaDenunciaController extends Controller
{
    public function index(Request $request): View
    {
        $denuncias = PartidaDenuncia::query()
            ->with(['partida.liga', 'partida.mandante', 'partida.visitante', 'user'])
            ->latest()
            ->paginate(20);

        return view('admin.partida_denuncias.index', [
            'denuncias' => $denuncias,
        ]);
    }
}
