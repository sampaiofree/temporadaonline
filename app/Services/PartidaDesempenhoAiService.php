<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PartidaDesempenhoAiService
{
    private const TIMEOUT_SECONDS = 60;

    public function analyzeMatch(
        UploadedFile $mandanteImage,
        UploadedFile $visitanteImage,
        array $mandanteRoster,
        array $visitanteRoster,
    ): array {
        $mandante = $this->analyzeTeam($mandanteImage, $mandanteRoster);
        $visitante = $this->analyzeTeam($visitanteImage, $visitanteRoster);

        return [
            'mandante' => $mandante,
            'visitante' => $visitante,
        ];
    }

    private function analyzeTeam(UploadedFile $image, array $roster): array
    {
        $apiKey = config('services.openai.key');
        if (! $apiKey) {
            throw new RuntimeException('OPENAI_API_KEY não configurada.');
        }

        $payload = [
            'model' => config('services.openai.model', 'gpt-4o-mini'),
            'temperature' => 0,
            'text' => [
                'format' => $this->responseFormat(),
            ],
            'input' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $this->buildPrompt($roster),
                        ],
                        [
                            'type' => 'input_image',
                            'image_url' => $this->toDataUrl($image),
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withToken($apiKey)
            ->timeout(self::TIMEOUT_SECONDS)
            ->post(rtrim(config('services.openai.base_url'), '/').'/responses', $payload);

        if (! $response->successful()) {
            Log::warning('OpenAI response error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Falha ao analisar a imagem.');
        }

        $data = $this->extractJson($response->json());

        return [
            'entries' => $this->sanitizeEntries($data['entries'] ?? [], $roster),
            'unknown_players' => $this->sanitizeUnknown($data['unknown_players'] ?? []),
        ];
    }

    private function buildPrompt(array $roster): string
    {
        $playersJson = json_encode($roster, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Você está analisando UMA imagem de "Desempenho individual" de um time.

Extraia os dados por jogador (NF, G, AST) e faça o match com a lista de jogadores abaixo.

Regras:
- Use APENAS jogadores presentes na lista fornecida.
- Seja permissivo com o match de nomes (abreviações, iniciais, sobrenome isolado, diferenças de acento/pontuação).
- Se houver múltiplos candidatos, escolha o mais provável e indique confidence mais baixa.
- Só coloque em unknown_players quando não houver candidato plausível.
- Ignore linhas com NF/nota "ND" ou vazia.
- Notas podem ter vírgula (ex: 6,6). Converta para ponto (6.6).
- G e AST são inteiros. Se estiver em branco, use 0.

Lista de jogadores (use o id para o match):
{$playersJson}
PROMPT;
    }

    private function responseFormat(): array
    {
        return [
            'type' => 'json_schema',
            'name' => 'team_performance',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'entries' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'player_id' => ['type' => 'integer'],
                                'nota' => ['type' => 'number'],
                                'gols' => ['type' => 'integer'],
                                'assistencias' => ['type' => 'integer'],
                                'name_in_image' => ['type' => 'string'],
                                'confidence' => ['type' => 'number'],
                            ],
                            'required' => ['player_id', 'nota', 'gols', 'assistencias', 'name_in_image', 'confidence'],
                            'additionalProperties' => false,
                        ],
                    ],
                    'unknown_players' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                ],
                'required' => ['entries', 'unknown_players'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ];
    }

    private function toDataUrl(UploadedFile $image): string
    {
        $content = file_get_contents($image->getRealPath());
        if ($content === false) {
            throw new RuntimeException('Não foi possível ler a imagem enviada.');
        }

        $mime = $image->getMimeType() ?: 'image/jpeg';
        $base64 = base64_encode($content);

        return "data:{$mime};base64,{$base64}";
    }

    private function extractJson(array $response): array
    {
        $text = $response['output_text'] ?? null;

        if (! $text && isset($response['output']) && is_array($response['output'])) {
            foreach ($response['output'] as $output) {
                foreach ($output['content'] ?? [] as $content) {
                    $type = $content['type'] ?? '';
                    if (in_array($type, ['output_text', 'text'], true) && ! empty($content['text'])) {
                        $text = $content['text'];
                        break 2;
                    }
                }
            }
        }

        if (! $text) {
            throw new RuntimeException('Resposta inválida da OpenAI.');
        }

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Não foi possível interpretar a resposta da OpenAI.');
        }

        return $decoded;
    }

    private function sanitizeEntries(array $entries, array $roster): array
    {
        $rosterIds = array_column($roster, 'id');
        $allowed = array_flip($rosterIds);
        $sanitized = [];

        foreach ($entries as $entry) {
            $playerId = (int) ($entry['player_id'] ?? 0);
            if (! $playerId || ! isset($allowed[$playerId])) {
                continue;
            }

            $notaRaw = $entry['nota'] ?? null;
            $nota = $notaRaw !== null ? (float) str_replace(',', '.', (string) $notaRaw) : null;
            if ($nota === null || $nota < 0 || $nota > 10) {
                continue;
            }

            $gols = max(0, (int) ($entry['gols'] ?? 0));
            $assistencias = max(0, (int) ($entry['assistencias'] ?? 0));

            $sanitized[] = [
                'player_id' => $playerId,
                'nota' => $nota,
                'gols' => $gols,
                'assistencias' => $assistencias,
                'name_in_image' => $entry['name_in_image'] ?? null,
                'confidence' => isset($entry['confidence']) ? (float) $entry['confidence'] : null,
            ];
        }

        return $sanitized;
    }

    private function sanitizeUnknown(array $unknown): array
    {
        $names = [];

        foreach ($unknown as $name) {
            $value = trim((string) $name);
            if ($value === '') {
                continue;
            }
            $names[] = $value;
        }

        return array_values(array_unique($names));
    }
}
