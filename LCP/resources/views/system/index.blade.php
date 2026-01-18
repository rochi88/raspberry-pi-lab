<x-layouts::app :title="__('System Monitor')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <form method="GET" class="mb-6">
        <label class="block text-sm font-medium">Check Custom Ports</label>
        <input
            name="ports"
            value="{{ request('ports') }}"
            class="mt-1 w-full rounded border px-3 py-2"
            placeholder="22,80,443"
        >
        <button class="mt-3 px-4 py-2 bg-blue-600 text-white rounded">
            Check
        </button>
    </form>
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <table class="w-full text-sm border mb-8">
        <thead class="bg-slate-800 text-white">
        <tr>
            <th class="p-2">Service</th>
            <th class="p-2 text-center">Port</th>
            <th class="p-2 text-center">Status</th>
        </tr>
        </thead>
        <tbody>
        @foreach($servers as $s)
            <tr class="border-t">
                <td class="p-2">{{ $s['name'] }}</td>
                <td class="p-2 text-center">{{ $s['port'] }}</td>
                <td class="p-2 text-center">
                    <span class="{{ $s['up'] ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $s['up'] ? 'UP' : 'DOWN' }}
                    </span>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <h2 class="text-xl font-semibold mb-4">System Info</h2>

    <div class="space-y-3 text-sm">
        <div><strong>Uptime:</strong> {{ $uptime }}</div>
        <div><strong>Architecture:</strong> {{ $architecture }}</div>
        <div><strong>IP:</strong> {!! nl2br(e(str_replace(' ', "\n", $privateIPs))) !!}</div>
        <div><strong>Users:</strong> {{ $activeUsers }}</div>
        <div><strong>Processes:</strong> {{ $processCount }}</div>

        <div>
            <strong>Disk:</strong> {{ bytes($diskUsed) }} / {{ bytes($diskTotal) }}
            <div class="h-2 bg-slate-200 rounded">
                <div class="h-2 rounded {{ colour($diskPercent) }}"
                     style="width: {{ $diskPercent }}%"></div>
            </div>
        </div>

        <div>
            <strong>Memory:</strong> {{ bytes($ramUsed) }} / {{ bytes($ramTotal) }}
            <div class="h-2 bg-slate-200 rounded">
                <div class="h-2 rounded {{ colour($ramPercent) }}"
                     style="width: {{ $ramPercent }}%"></div>
            </div>
        </div>

        <div>
            <strong>CPU:</strong> {{ $cpuUsage }}%
            <div class="h-2 bg-slate-200 rounded">
                <div class="h-2 rounded {{ colour($cpuUsage) }}"
                     style="width: {{ $cpuUsage }}%"></div>
                     @if($temperature !== null)
  <div>Temperature: {{ $temperature }} °C</div>
@endif
            </div>
        </div>
    </div>
            </div>
        </div>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <h2 class="text-xl font-semibold mt-8">Top RAM Processes</h2>
    <pre class="bg-slate-900 text-slate-100 p-3 rounded mt-2 text-xs">{{ $topRam }}</pre>

    <h2 class="text-xl font-semibold mt-6">Top CPU Processes</h2>
    <pre class="bg-slate-900 text-slate-100 p-3 rounded mt-2 text-xs">{{ $topCpu }}</pre>
        </div>
    </div>
</x-layouts::app>
