<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-5">
            <div class="space-y-1">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ $heading }}
                </h3>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $description }}
                </p>
            </div>

            @if ($hasData)
                <div class="overflow-x-auto">
                    <div class="min-w-max space-y-3">
                        <div
                            class="grid gap-2"
                            style="grid-template-columns: 220px repeat({{ count($dates) }}, minmax(44px, 44px)) 92px 92px;"
                        >
                            <div class="sticky left-0 z-10 bg-white text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                                Ubicacion
                            </div>

                            @foreach ($dates as $date)
                                <div class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                                    <div>{{ $date['label'] }}</div>
                                    <div class="text-[10px] uppercase">{{ $date['day_name'] }}</div>
                                </div>
                            @endforeach

                            <div class="text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Total
                            </div>

                            <div class="text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Dias
                            </div>

                            @foreach ($rows as $row)
                                <div class="sticky left-0 z-10 flex items-center rounded-lg bg-white pr-3 text-sm font-medium text-gray-800 dark:bg-gray-900 dark:text-gray-100">
                                    {{ $row['location'] }}
                                </div>

                                @foreach ($row['values'] as $index => $value)
                                    @php
                                        $opacity = $value > 0 ? 0.18 + (($value / $maxValue) * 0.72) : 0.06;
                                        $tooltip = $row['location'] . ' - ' . $dates[$index]['iso'] . ': ' . $value . ' limpieza(s)';
                                    @endphp

                                    <div
                                        class="flex h-11 items-center justify-center rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 dark:border-white/10 dark:text-gray-100"
                                        style="background-color: rgba(15, 118, 110, {{ number_format($opacity, 2, '.', '') }});"
                                        title="{{ $tooltip }}"
                                    >
                                        {{ $value ?: '' }}
                                    </div>
                                @endforeach

                                <div class="flex h-11 items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-800 dark:bg-white/5 dark:text-gray-100">
                                    {{ $row['total'] }}
                                </div>

                                <div class="flex h-11 items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-800 dark:bg-white/5 dark:text-gray-100">
                                    {{ $row['active_days'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                    <span>Menor actividad</span>

                    <div class="flex items-center gap-1">
                        @foreach ($legend as $step)
                            @php
                                $opacity = $step > 0 ? 0.18 + (($step / $maxValue) * 0.72) : 0.06;
                            @endphp

                            <span
                                class="flex h-6 w-8 items-center justify-center rounded-md border border-gray-200 text-[11px] font-semibold text-gray-700 dark:border-white/10 dark:text-gray-100"
                                style="background-color: rgba(15, 118, 110, {{ number_format($opacity, 2, '.', '') }});"
                            >
                                {{ $step }}
                            </span>
                        @endforeach
                    </div>

                    <span>Mayor actividad</span>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                    No hay limpiezas registradas para el periodo seleccionado.
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
