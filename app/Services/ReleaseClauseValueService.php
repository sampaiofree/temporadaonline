<?php

namespace App\Services;

class ReleaseClauseValueService
{
    public function resolve(?int $entryValueEur, ?int $originalValueEur, ?float $multaMultiplicador = null): int
    {
        $entry = max(0, (int) ($entryValueEur ?? 0));
        $original = max(0, (int) ($originalValueEur ?? 0));
        $multiplier = $this->normalizeMultiplier($multaMultiplicador);

        if ($entry === $original) {
            return max(0, (int) round($entry * $multiplier));
        }

        return $entry;
    }

    private function normalizeMultiplier(?float $multaMultiplicador): float
    {
        if (! is_numeric($multaMultiplicador)) {
            return 1.0;
        }

        $value = (float) $multaMultiplicador;

        return $value > 0 ? $value : 1.0;
    }
}

