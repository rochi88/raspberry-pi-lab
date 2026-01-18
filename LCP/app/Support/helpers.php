<?php

declare(strict_types=1);

if (! function_exists('bytes')) {
    function bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }
}

if (! function_exists('colour')) {
    function colour(float $value, float $max = 100): string
    {
        $p = ($value / $max) * 100;

        return match (true) {
            $p < 50 => 'bg-emerald-500',
            $p < 60 => 'bg-sky-500',
            $p < 75 => 'bg-amber-500',
            default => 'bg-red-500',
        };
    }
}
