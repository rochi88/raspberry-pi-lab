<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;

final class SystemMonitor extends Component
{
    public string $ports = '';

    public function render()
    {
        $memory      = $this->memory();
        $disk        = $this->disk('/');
        $servers     = $this->servers();

        return view('livewire.system-monitor', [
            'uptime'        => $this->uptime(),
            'loads'         => sys_getloadavg(),
            'cpuUsage'      => $this->cpuUsage(),
            'ramUsed'       => $memory['used'],
            'ramTotal'      => $memory['total'],
            'ramPercent'    => $memory['percent'],
            'diskUsed'      => $disk['used'],
            'diskTotal'     => $disk['total'],
            'diskPercent'   => $disk['percent'],
            'temperature'   => $this->temperature(),
            'privateIPs'    => implode(' ', $this->ips()),
            'architecture'  => php_uname('m'),
            'activeUsers'   => $this->activeUsers(),
            'processCount'  => $this->processCount(),
            'topRam'        => $this->topRam(),
            'topCpu'        => $this->topCpu(),
            'servers'       => $servers,
        ]);
    }

    /* -----------------------------
     |  Formatting helpers
     | ----------------------------- */

    public function bytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = (int) floor(log($bytes, 1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }

    public function colour(float $value, float $max = 100): string
    {
        $p = ($value / $max) * 100;

        return match (true) {
            $p < 50 => 'bg-emerald-500',
            $p < 65 => 'bg-sky-500',
            $p < 80 => 'bg-amber-500',
            default => 'bg-red-500',
        };
    }

    /* -----------------------------
     |  System probes (universal)
     | ----------------------------- */

    private function uptime(): string
    {
        if (is_readable('/proc/uptime')) {
            $seconds = (int) explode(' ', trim(file_get_contents('/proc/uptime')))[0];
            return gmdate('j \d\a\y\s H:i:s', $seconds);
        }

        return trim((string) shell_exec('uptime -p'));
    }

    private function cpuUsage(): float
    {
        static $prev = null;

        if (!is_readable('/proc/stat')) return 0.0;

        $line  = explode("\n", trim(file_get_contents('/proc/stat')))[0];
        $parts = array_values(array_filter(explode(' ', $line)));

        $total = array_sum(array_slice($parts, 1));
        $idle  = $parts[4];

        if ($prev === null) {
            $prev = compact('total', 'idle');
            usleep(200000);
            return $this->cpuUsage();
        }

        $diffTotal = $total - $prev['total'];
        $diffIdle  = $idle  - $prev['idle'];
        $prev      = compact('total', 'idle');

        return $diffTotal > 0
            ? round((1 - ($diffIdle / $diffTotal)) * 100, 2)
            : 0.0;
    }

    private function memory(): array
    {
        $data = [];

        foreach (file('/proc/meminfo') as $line) {
            [$k, $v] = explode(':', $line);
            $data[$k] = (int) filter_var($v, FILTER_SANITIZE_NUMBER_INT) * 1024;
        }

        $total = $data['MemTotal'];
        $avail = $data['MemAvailable']
            ?? ($data['MemFree'] + $data['Buffers'] + $data['Cached']);

        $used = $total - $avail;

        return [
            'total'   => $total,
            'used'    => $used,
            'percent' => round(($used / $total) * 100, 2),
        ];
    }

    private function disk(string $path): array
    {
        $total = disk_total_space($path);
        $free  = disk_free_space($path);
        $used  = $total - $free;

        return [
            'total'   => $total,
            'used'    => $used,
            'percent' => round(($used / $total) * 100, 2),
        ];
    }

    private function temperature(): ?float
    {
        foreach (glob('/sys/class/thermal/thermal_zone*/temp') ?: [] as $zone) {
            $val = (int) @file_get_contents($zone);
            if ($val > 0) return round($val / 1000, 2);
        }
        return null;
    }

    private function ips(): array
    {
        $ips = [];
        foreach (net_get_interfaces() as $iface) {
            foreach ($iface['unicast'] ?? [] as $addr) {
                if ($addr['family'] === AF_INET && empty($addr['internal'])) {
                    $ips[] = $addr['address'];
                }
            }
        }
        return array_unique($ips);
    }

    private function activeUsers(): int
    {
        return (int) trim((string) shell_exec('who | wc -l'));
    }

    private function processCount(): int
    {
        return (int) trim((string) shell_exec('ps aux | wc -l'));
    }

    private function topRam(): string
    {
        return (string) shell_exec('ps aux --sort=-%mem | head -n 5');
    }

    private function topCpu(): string
    {
        return (string) shell_exec('ps aux --sort=-%cpu | head -n 5');
    }

    private function servers(): array
    {
        $servers = [
            ['host' => 'localhost', 'port' => 21, 'name' => 'FTP'],
            ['host' => 'localhost', 'port' => 22, 'name' => 'SSH'],
            ['host' => 'google.com','port' => 80, 'name' => 'Internet'],
            ['host' => 'localhost', 'port' => 80, 'name' => 'Web'],
            ['host' => 'localhost', 'port' => 3306,'name' => 'MySQL'],
        ];

        foreach (array_filter(explode(',', $this->ports)) as $port) {
            $port = (int) trim($port);
            if ($port >= 1 && $port <= 65353) {
                $servers[] = ['host' => 'localhost', 'port' => $port, 'name' => 'Custom'];
            }
        }

        foreach ($servers as &$s) {
            $sock = @fsockopen($s['host'], $s['port'], $e, $er, 1);
            $s['up'] = (bool) $sock;
            if ($sock) fclose($sock);
        }

        return $servers;
    }
}
