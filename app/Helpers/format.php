<?php

if (!function_exists('formatRupiah')) {
    function formatRupiah($value, bool $withSymbol = true): string
    {
        return ($withSymbol ? 'Rp ' : '') . number_format((float) $value, 0, ',', '.');
    }
}

if (!function_exists('durationLabel')) {
    function durationLabel(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): string
    {
        $diff = $start->diff($end);
        $parts = [];
        
        if ($diff->d > 0) {
            $parts[] = $diff->d . ' hari';
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h . ' jam';
        }
        
        return implode(' ', $parts) ?: '0 jam';
    }
}
