<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappConnection;
use App\Services\EvolutionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsappConnectionController extends Controller
{
    public function index(): View
    {
        return view('admin.whatsapp.index', [
            'connection' => WhatsappConnection::first(),
        ]);
    }

    public function createInstance(Request $request, EvolutionService $evolutionService): RedirectResponse
    {
        $instanceName = 'liga-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(6));
        $result = $evolutionService->createInstance($instanceName);

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        $connection = WhatsappConnection::first();

        $payload = [
            'instance_name' => $instanceName,
            'status' => 'created',
            'qr_code' => null,
            'connected_at' => null,
            'last_status_at' => now(),
            'created_by' => $request->user()?->id,
        ];

        if ($connection) {
            $connection->update($payload);
        } else {
            $connection = WhatsappConnection::create($payload);
        }

        return back()->with('success', 'Instancia criada. Gere o QR Code para conectar.');
    }

    public function connect(WhatsappConnection $connection, EvolutionService $evolutionService): RedirectResponse
    {
        $result = $evolutionService->connectInstance($connection->instance_name);

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        $qrCode = Arr::get($result, 'base64')
            ?? Arr::get($result, 'qrcode.base64')
            ?? Arr::get($result, 'qrcode')
            ?? Arr::get($result, 'qr')
            ?? Arr::get($result, 'instance.qrCode')
            ?? Arr::get($result, 'instance.qr_code');

        if (! $qrCode) {
            return back()->with('error', 'QR Code nao foi retornado pela Evolution.');
        }

        $connection->update([
            'qr_code' => trim((string) $qrCode),
            'status' => 'qr',
            'last_status_at' => now(),
        ]);

        return back()->with('success', 'QR Code gerado. Escaneie no WhatsApp.');
    }

    public function refreshStatus(WhatsappConnection $connection, EvolutionService $evolutionService): RedirectResponse
    {
        $result = $evolutionService->fetchConnectionState($connection->instance_name);

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        $state = $this->normalizeConnectionState($result);
        $isConnected = in_array($state, ['open', 'connected', 'online'], true);

        $connection->update([
            'status' => $state,
            'connected_at' => $isConnected ? now() : null,
            'qr_code' => $isConnected ? null : $connection->qr_code,
            'last_status_at' => now(),
        ]);

        return back()->with('success', 'Status atualizado.');
    }

    public function restart(WhatsappConnection $connection, EvolutionService $evolutionService): RedirectResponse
    {
        $result = $evolutionService->restartInstance($connection->instance_name);

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        $connection->update([
            'status' => 'restarting',
            'last_status_at' => now(),
        ]);

        return back()->with('success', 'Instancia reiniciada. Aguarde alguns instantes.');
    }

    public function logout(WhatsappConnection $connection, EvolutionService $evolutionService): RedirectResponse
    {
        $result = $evolutionService->logoutInstance($connection->instance_name);

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        $connection->update([
            'status' => 'disconnected',
            'qr_code' => null,
            'connected_at' => null,
            'last_status_at' => now(),
        ]);

        return back()->with('success', 'Instancia desconectada.');
    }

    private function normalizeConnectionState(array $payload): ?string
    {
        $candidates = [
            $payload['instance']['state'] ?? null,
            $payload['instance']['connectionState'] ?? null,
            $payload['state'] ?? null,
            $payload['connectionState'] ?? null,
            $payload['status'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }
            $value = strtolower(trim($candidate));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
