<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AppAssetController extends Controller
{
    public function edit(): View
    {
        $assets = AppAsset::first();

        if (! $assets) {
            $assets = AppAsset::create([]);
        }

        return view('admin.app_assets.edit', [
            'assets' => $assets,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $assets = AppAsset::first();

        if (! $assets) {
            $assets = AppAsset::create([]);
        }

        $validated = $request->validate([
            'favicon' => 'nullable|mimes:ico,png|max:1024',
            'logo_padrao' => 'nullable|image:allow_svg|max:4096',
            'logo_dark' => 'nullable|image:allow_svg|max:4096',
            'imagem_campo' => 'nullable|image:allow_svg|max:4096',
            'background_app' => 'nullable|image:allow_svg|max:4096',
        ]);

        $data = [];

        foreach (['favicon', 'logo_padrao', 'logo_dark', 'imagem_campo', 'background_app'] as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $path = $file->store('app-assets', 'public');

                if ($assets->{$field}) {
                    Storage::disk('public')->delete($assets->{$field});
                }

                $data[$field] = $path;
            }
        }

        if ($data) {
            $assets->update($data);
        }

        return redirect()
            ->route('admin.app-assets.edit')
            ->with('success', 'Imagens atualizadas com sucesso.');
    }
}
