<div wire:key="system-monitor-root" wire:poll.visible>
    <div class="flex flex-col gap-4">

        <div class="grid md:grid-cols-3 gap-4">
            <!-- Ports -->
            <div class="rounded-xl border p-4">
                <label class="text-sm font-medium">Check Custom Ports</label>
                <input wire:model.lazy="ports"
                       class="mt-2 w-full rounded border px-3 py-2"
                       placeholder="22,80,443">
            </div>

            <!-- Services -->
            <div class="rounded-xl border p-4">
                <table class="w-full text-sm">
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

            <!-- System Info -->
            <div class="rounded-xl border p-4 space-y-2 text-sm">
                <div><strong>Uptime:</strong> {{ $uptime }}</div>
                <div><strong>Architecture:</strong> {{ $architecture }}</div>
                <div><strong>IP:</strong> {!! nl2br(e(str_replace(' ', "\n", $privateIPs))) !!}</div>
                <div><strong>Users:</strong> {{ $activeUsers }}</div>
                <div><strong>Processes:</strong> {{ $processCount }}</div>

                <div>
                    <strong>Disk:</strong> {{ $this->bytes($diskUsed) }} / {{ $this->bytes($diskTotal) }}
                    <div class="h-2 bg-slate-200 rounded">
                        <div class="h-2 rounded {{ $this->colour($diskPercent) }}"
                             style="width: {{ $diskPercent }}%"></div>
                    </div>
                </div>

                <div>
                    <strong>Memory:</strong> {{ $this->bytes($ramUsed) }} / {{ $this->bytes($ramTotal) }}
                    <div class="h-2 bg-slate-200 rounded">
                        <div class="h-2 rounded {{ $this->colour($ramPercent) }}"
                             style="width: {{ $ramPercent }}%"></div>
                    </div>
                </div>

                <div>
                    <strong>CPU:</strong> {{ $cpuUsage }}%
                    <div class="h-2 bg-slate-200 rounded">
                        <div class="h-2 rounded {{ $this->colour($cpuUsage) }}"
                             style="width: {{ $cpuUsage }}%"></div>
                    </div>
                </div>

                @if($temperature !== null)
                    <div><strong>Temperature:</strong> {{ $temperature }} °C</div>
                @endif
            </div>
        </div>

        <div class="rounded-xl border p-4 overflow-x-auto">
            <h2 class="font-semibold mb-2">Top RAM Processes</h2>
            <pre class="bg-slate-900 text-slate-100 p-3 rounded text-xs">{{ $topRam }}</pre>

            <h2 class="font-semibold mt-4 mb-2">Top CPU Processes</h2>
            <pre class="bg-slate-900 text-slate-100 p-3 rounded text-xs">{{ $topCpu }}</pre>
        </div>

    </div>
</div>
