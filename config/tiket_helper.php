<?php

function golLabel(string $g): string {
    $map = [
        'gol_1'  => 'Gol I — Sepeda',
        'gol_2'  => 'Gol II — Motor <500cc',
        'gol_3'  => 'Gol III — Motor >500cc',
        'gol_4a' => 'Gol IVA — Mobil Penumpang',
        'gol_4b' => 'Gol IVB — Mobil Barang',
        'gol_5a' => 'Gol VA — Bus Sedang',
        'gol_5b' => 'Gol VB — Truk Sedang',
        'gol_6a' => 'Gol VIA — Bus Besar',
        'gol_6b' => 'Gol VIB — Truk Besar',
        'gol_7'  => 'Gol VII — Tronton 10–12m',
        'gol_8'  => 'Gol VIII — Tronton 12–16m',
        'gol_9'  => 'Gol IX — Tronton >16m',
    ];
    return $map[$g] ?? $g;
}

function isTiketKendaraan(array $t): bool {
    return strtolower(trim((string)($t['jenis_pengguna'] ?? ''))) === 'kendaraan';
}

function getGolonganTiket(array $t): string {
    $gol = trim((string)($t['golongan'] ?? ''));
    if ($gol === '') {
        $gol = trim((string)($t['kendaraan'] ?? ''));
    }
    return $gol !== '' ? golLabel($gol) : '-';
}

function getPlatTiket(array $t): string {
    $plat = strtoupper(trim((string)($t['plat'] ?? '')));
    return $plat !== '' ? $plat : '-';
}
