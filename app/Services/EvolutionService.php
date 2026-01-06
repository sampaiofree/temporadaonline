<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class EvolutionService
{
    public function createInstance(string $instanceName): array|string
    {
        $url = rtrim((string) config('services.evolution.url'), '/') . '/instance/create';
        $apiKey = (string) config('services.evolution.key');
        $integration = (string) config('services.evolution.integration', 'WHATSAPP-BAILEYS');

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])->post($url, [
                'instanceName' => $instanceName,
                'integration' => $integration,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EvolutionService: falha ao criar instancia', [
                'instance' => $instanceName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EvolutionService: erro ao criar instancia', [
                'instance' => $instanceName,
                'exception' => $e->getMessage(),
            ]);
        }

        return "Erro ao criar instancia {$instanceName}";
    }

    public function connectInstance(string $instanceName): array|string
    {
        $url = rtrim((string) config('services.evolution.url'), '/') . "/instance/connect/{$instanceName}";
        $apiKey = (string) config('services.evolution.key');

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EvolutionService: falha ao conectar instancia', [
                'instance' => $instanceName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EvolutionService: erro ao conectar instancia', [
                'instance' => $instanceName,
                'exception' => $e->getMessage(),
            ]);
        }

        return "Erro ao conectar instancia {$instanceName}";
    }

    public function fetchConnectionState(string $instanceName): array|string
    {
        $url = rtrim((string) config('services.evolution.url'), '/') . "/instance/connectionState/{$instanceName}";
        $apiKey = (string) config('services.evolution.key');

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EvolutionService: falha ao buscar status', [
                'instance' => $instanceName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EvolutionService: erro ao buscar status', [
                'instance' => $instanceName,
                'exception' => $e->getMessage(),
            ]);
        }

        return "Erro ao buscar status da instancia {$instanceName}";
    }

    public function restartInstance(string $instanceName): array|string
    {
        $url = rtrim((string) config('services.evolution.url'), '/') . "/instance/restart/{$instanceName}";
        $apiKey = (string) config('services.evolution.key');

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])->post($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EvolutionService: falha ao reiniciar instancia', [
                'instance' => $instanceName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EvolutionService: erro ao reiniciar instancia', [
                'instance' => $instanceName,
                'exception' => $e->getMessage(),
            ]);
        }

        return "Erro ao reiniciar instancia {$instanceName}";
    }

    public function logoutInstance(string $instanceName): array|string
    {
        $url = rtrim((string) config('services.evolution.url'), '/') . "/instance/logout/{$instanceName}";
        $apiKey = (string) config('services.evolution.key');

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])->delete($url);

            if ($response->successful() || $response->notFound()) {
                return $response->json() ?: [
                    'status' => $response->notFound() ? 'not_found' : 'success',
                    'instance' => $instanceName,
                ];
            }

            Log::error('EvolutionService: falha ao desconectar instancia', [
                'instance' => $instanceName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EvolutionService: erro ao desconectar instancia', [
                'instance' => $instanceName,
                'exception' => $e->getMessage(),
            ]);
        }

        return "Erro ao desconectar instancia {$instanceName}";
    }

    public function listGroups(string $instanceName): array
    {
        $url = rtrim((string) config('services.evolution.url'), '/') . "/group/fetchAllGroups/{$instanceName}";
        $apiKey = (string) config('services.evolution.key');

        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->get($url, [
                'getParticipants' => 'false',
            ]);

            if ($response->successful()) {
                $payload = $response->json();
                $groups = $payload['groups'] ?? $payload['data'] ?? Arr::get($payload, 'response.groups');
                if (! $groups) {
                    $groups = Arr::get($payload, 'instance.groups');
                }

                if (is_array($groups)) {
                    return $groups;
                }

                if (is_array($payload) && array_is_list($payload)) {
                    return $payload;
                }

                return [];
            }

            Log::error('EvolutionService: falha ao listar grupos', [
                'instance' => $instanceName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EvolutionService: erro ao listar grupos', [
                'instance' => $instanceName,
                'exception' => $e->getMessage(),
            ]);
        }

        return [];
    }

    public function fetchInviteCode(string $instanceName, string $groupJid): array|string
    {
        $url = rtrim((string) config('services.evolution.url'), '/') . "/group/inviteCode/{$instanceName}";
        $apiKey = (string) config('services.evolution.key');

        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->get($url, [
                'groupJid' => $groupJid,
            ]);

            Log::info('EvolutionService: inviteCode response', [
                'instance' => $instanceName,
                'groupJid' => $groupJid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EvolutionService: falha ao buscar inviteCode', [
                'instance' => $instanceName,
                'groupJid' => $groupJid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EvolutionService: erro ao buscar inviteCode', [
                'instance' => $instanceName,
                'groupJid' => $groupJid,
                'exception' => $e->getMessage(),
            ]);
        }

        return "Erro ao buscar inviteCode para {$groupJid}";
    }

    public function sendTextMessage(string $jid, string $message, string $instanceName): array|string
    {
        $parts = $this->splitMessage($message);
        $results = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $results[] = $this->sendTextSegment($jid, $part, $instanceName);
        }

        if (empty($results)) {
            return "Mensagem vazia, nada enviado para {$jid}";
        }

        return count($results) === 1 ? $results[0] : $results;
    }

    private function sendTextSegment(string $jid, string $message, string $instanceName): array|string
    {
        $url = rtrim((string) config('services.evolution.url'), '/') . "/message/sendText/{$instanceName}";
        $apiKey = (string) config('services.evolution.key');

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])->post($url, [
                'number' => $jid,
                'text' => $message,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EvolutionService: falha ao enviar mensagem', [
                'jid' => $jid,
                'instance' => $instanceName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EvolutionService: erro ao enviar mensagem', [
                'jid' => $jid,
                'instance' => $instanceName,
                'exception' => $e->getMessage(),
            ]);
        }

        return "Erro ao enviar mensagem para {$jid}";
    }

    private function splitMessage(string $message, int $limit = 800): array
    {
        $message = str_replace(["\\r\\n", "\\r", "\\n"], "\n", $message);
        $message = trim(str_replace(["\r\n", "\r"], "\n", $message));
        if ($message === '') {
            return [''];
        }

        if (mb_strlen($message) <= $limit) {
            return [$message];
        }

        $paragraphs = preg_split('/\n\s*\n/', $message) ?: [$message];
        $results = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            if (mb_strlen($paragraph) > $limit) {
                $results = array_merge($results, $this->splitLongParagraph($paragraph, $limit));
            } else {
                $results[] = $paragraph;
            }
        }

        return $results ?: [$message];
    }

    private function splitLongParagraph(string $paragraph, int $limit): array
    {
        $words = preg_split('/\s+/', $paragraph) ?: [];
        $chunks = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;

            if (mb_strlen($word) > $limit) {
                $chunks = array_merge($chunks, $this->splitLongWord($word, $limit));
                $line = '';
                continue;
            }

            if (mb_strlen($candidate) > $limit) {
                if ($line !== '') {
                    $chunks[] = $line;
                }
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '') {
            $chunks[] = $line;
        }

        return $chunks;
    }

    private function splitLongWord(string $word, int $limit): array
    {
        $pieces = [];
        $length = mb_strlen($word);

        for ($i = 0; $i < $length; $i += $limit) {
            $pieces[] = mb_substr($word, $i, $limit);
        }

        return $pieces;
    }
}
