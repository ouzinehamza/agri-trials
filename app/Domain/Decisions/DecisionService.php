<?php

namespace App\Domain\Decisions;

/**
 * Pure, deterministic scorecard computation (SPEC §7). Given a trial's measures and the admin's
 * per-measure weights, it recomputes the deviation, per-measure score, and weighted total — so the
 * snapshot stored with a verdict is authoritative (never trusts client-computed numbers).
 */
class DecisionService
{
    /** @param array<string, mixed> $m */
    public static function rowScore(array $m): float
    {
        if (($m['essai']??null)===null||($m['temoin']??null)===null)return 0.0;
        $temoin = (float) ($m['temoin'] ?? 0);
        if ($temoin == 0.0) {
            return 50.0;
        }
        $dev = ((float) $m['essai'] - $temoin) / $temoin;
        if (in_array(($m['dir'] ?? 'up'),['down','lower'],true)) {
            $dev = -$dev;
        }
        if (($m['dir']??'up')==='neutral')return 50.0;

        return max(0.0, min(100.0, 50.0 + $dev * 100 * 2.5));
    }

    /** @param array<string, mixed> $m */
    public static function devPct(array $m): float
    {
        $temoin = (float) ($m['temoin'] ?? 0);
        if ($temoin == 0.0) {
            return 0.0;
        }
        $d = (((float) $m['essai'] - $temoin) / $temoin) * 100;

        return in_array(($m['dir'] ?? 'up'),['down','lower'],true) ? -$d : $d;
    }

    /**
     * @param  array<int, array<string, mixed>>  $measures
     * @param  array<string, int|float>  $weights  code => weight
     * @return array{score:int, rows:array<int, array<string, mixed>>, weights:array<string, int|float>}
     */
    public static function scorecard(array $measures, array $weights): array
    {
        $rows = [];
        $accum = 0.0;
        $totalWeight = 0.0;
        $usedWeights = [];

        foreach ($measures as $m) {
            $code = $m['code'];
            $weight = (float) ($weights[$code] ?? $m['weight'] ?? 0);
            $usedWeights[$code] = $weight;
            $rowScore = self::rowScore($m);

            $rows[] = [
                'code' => $code,
                'label' => $m['label'] ?? $code,
                'unit' => $m['unit'] ?? '',
                'essai' => $m['essai'] ?? null,
                'temoin' => $m['temoin'] ?? null,
                'dev_pct' => round(self::devPct($m), 1),
                'row_score' => round($rowScore),
                'weight' => $weight,
                'control_label' => $m['control_label'] ?? 'Témoin',
                'beats_control' => $m['beats_control'] ?? null,
                'aggregation' => $m['aggregation'] ?? 'mean',
            ];

            $accum += $rowScore * $weight;
            $totalWeight += $weight;
        }

        $score = $totalWeight > 0 ? (int) round($accum / $totalWeight) : 0;

        return ['score' => $score, 'rows' => $rows, 'weights' => $usedWeights];
    }
}
