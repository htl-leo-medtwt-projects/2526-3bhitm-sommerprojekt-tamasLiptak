<?php
header('Content-Type: application/json');

$battletag  = 'mugrootbeer#2789'; // change to your actual battletag
$playerId   = str_replace('#', '-', $battletag);

$statsUrl   = "https://overfast-api.tekrop.fr/players/{$playerId}/stats/career?gamemode=quickplay";
$statsJson  = @file_get_contents($statsUrl);
$stats      = $statsJson ? json_decode($statsJson, true) : null;

$summaryUrl  = "https://overfast-api.tekrop.fr/players/{$playerId}/summary";
$summaryJson = @file_get_contents($summaryUrl);
$summary     = $summaryJson ? json_decode($summaryJson, true) : null;

echo json_encode([
    'stats_one_hero' => $stats['tracer'] ?? 'missing',
    'stats_all_heroes' => $stats['all-heroes'] ?? null,
    'stats_raw_first_hero'   => $stats['heroes'][0] ?? 'NO HEROES KEY - top level keys: ' . implode(', ', array_keys($stats ?? [])),
    'stats_top_keys'         => array_keys($stats ?? []),
    'summary_top_keys'       => array_keys($summary ?? []),
    'summary_endorsement'    => $summary['endorsement'] ?? 'MISSING',
    'summary_competitive_pc' => $summary['competitive']['pc'] ?? 'MISSING',
    'stats_raw_url'          => $statsUrl,

], JSON_PRETTY_PRINT);

