<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

final class SystemStatsController extends Controller
{
    public function index(Request $request)
    {
        $uptime        = $this->uptime();
        $loads         = sys_getloadavg();
        $cpuUsage      = $this->cpuUsage();
        $memory        = $this->memory();
        $disk          = $this->disk('/');
        $temperature   = $this->temperature(); // nullable
        $ips           = $this->ips();
        $architecture  = php_uname('m');
        $activeUsers   = $this->activeUsers();
        $processCount  = $this->processCount();
        $services      = $this->services();
        $topRam        = $this->topRam();
        $topCpu        = $this->topCpu();

        $servers = $this->servers($request);

        return view('system.index', [
            'uptime'        => $uptime,
            'loads'         => $loads,
            'cpuUsage'      => $cpuUsage,
            'ramUsed'       => $memory['used'],
            'ramTotal'      => $memory['total'],
            'ramPercent'    => $memory['percent'],
            'diskUsed'      => $disk['used'],
            'diskTotal'     => $disk['total'],
            'diskPercent'   => $disk['percent'],
            'temperature'   => $temperature,
            'privateIPs'    => implode(' ', $ips),
            'architecture'  => $architecture,
            'activeUsers'   => $activeUsers,
            'processCount'  => $processCount,
            'services'      => $services,
            'topRam'        => $topRam,
            'topCpu'        => $topCpu,
            'servers'       => $servers,
        ]);
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

        if (!is_readable('/proc/stat')) {
            return 0.0;
        }

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

        $prev = compact('total', 'idle');

        return $diffTotal > 0
            ? round((1 - ($diffIdle / $diffTotal)) * 100, 2)
            : 0.0;
    }

    private function memory(): array
    {
        if (!is_readable('/proc/meminfo')) {
            return ['total' => 0, 'used' => 0, 'percent' => 0];
        }

        $data = [];

        foreach (file('/proc/meminfo') as $line) {
            [$key, $value] = explode(':', $line);
            $data[$key] = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT) * 1024;
        }

        $total     = $data['MemTotal'] ?? 0;
        $available = $data['MemAvailable']
            ?? (($data['MemFree'] ?? 0) + ($data['Buffers'] ?? 0) + ($data['Cached'] ?? 0));

        $used    = max($total - $available, 0);
        $percent = $total > 0 ? round(($used / $total) * 100, 2) : 0;

        return compact('total', 'used', 'percent');
    }

    private function disk(string $path): array
    {
        $total = @disk_total_space($path) ?: 0;
        $free  = @disk_free_space($path) ?: 0;
        $used  = max($total - $free, 0);

        return [
            'total'   => $total,
            'used'    => $used,
            'percent' => $total > 0 ? round(($used / $total) * 100, 2) : 0,
        ];
    }

    private function temperature(): ?float
    {
        $zones = glob('/sys/class/thermal/thermal_zone*/temp') ?: [];

        foreach ($zones as $zone) {
            $value = (int) @file_get_contents($zone);
            if ($value > 0) {
                return round($value / 1000, 2);
            }
        }

        return null; // unsupported hardware
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

    private function services(): array
    {
        if (shell_exec('command -v systemctl')) {
            return array_filter(
                explode("\n", trim((string) shell_exec(
                    'systemctl list-units --type=service --state=running --no-pager'
                )))
            );
        }

        if (shell_exec('command -v service')) {
            return array_filter(
                explode("\n", trim((string) shell_exec('service --status-all')))
            );
        }

        return [];
    }

    private function topRam(): string
    {
        return (string) shell_exec('ps aux --sort=-%mem | head -n 5');
    }

    private function topCpu(): string
    {
        return (string) shell_exec('ps aux --sort=-%cpu | head -n 5');
    }

    private function servers(Request $request): array
    {
        $servers = [
            ['host' => 'localhost', 'port' => 21,   'name' => 'FTP'],
            ['host' => 'localhost', 'port' => 22,   'name' => 'SSH'],
            ['host' => 'google.com','port' => 80,   'name' => 'Internet'],
            ['host' => 'localhost', 'port' => 80,   'name' => 'Web'],
            ['host' => 'localhost', 'port' => 3306, 'name' => 'MySQL'],
        ];

        if ($request->filled('ports')) {
            foreach (explode(',', $request->ports) as $port) {
                $port = (int) trim($port);
                if ($port >= 1 && $port <= 65353) {
                    $servers[] = [
                        'host' => 'localhost',
                        'port' => $port,
                        'name' => 'Custom',
                    ];
                }
            }
        }

        foreach ($servers as &$server) {
            $socket = @fsockopen($server['host'], $server['port'], $e, $s, 1);
            $server['up'] = (bool) $socket;
            if ($socket) fclose($socket);
        }

        return $servers;
    }
}
