<div class="flex gap-0 overflow-hidden" style="min-height: 280px; max-height: 420px;">

    {{-- ── Sidebar de filtros ── --}}
    <div class="w-56 flex-shrink-0 flex flex-col gap-5 p-5 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">

        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                Facultad
            </label>
            <select
                wire:model.live="facultadId"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500"
            >
                <option value="">Todas</option>
                @foreach ($facultades as $f)
                    <option value="{{ $f->id }}">{{ $f->display_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                Tipo de conserje
            </label>
            <select
                wire:model.live="tipoConserje"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500"
            >
                <option value="">Seleccionar...</option>
                @foreach ($tipoOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold {{ $facultadId ? 'text-gray-500 dark:text-gray-400' : 'text-gray-300 dark:text-gray-600' }} uppercase tracking-wide">
                Conserje
            </label>
            <select
                wire:model.live="conserjeId"
                @if(!$facultadId) disabled @endif
                class="w-full rounded-lg text-sm shadow-sm
                    {{ $facultadId
                        ? 'border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:ring-primary-500 focus:border-primary-500'
                        : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 cursor-not-allowed opacity-60' }}"
            >
                <option value="">{{ $facultadId ? 'Todos' : 'Selecciona una facultad primero' }}</option>
                @foreach ($conserjes as $c)
                    <option value="{{ $c->id }}">{{ $c->getDisplayNameWithConserjeType() }}</option>
                @endforeach
            </select>
        </div>

        @if ($facultadId || $tipoConserje || $conserjeId)
            <button
                wire:click="resetFilters"
                class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
                <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                Limpiar filtros
            </button>
        @endif

        <div class="mt-auto pt-3 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                {{ $locations->count() }} {{ $locations->count() === 1 ? 'ubicación' : 'ubicaciones' }}
            </p>
        </div>

    </div>

    {{-- ── Tabla ── --}}
    <div class="flex-1 overflow-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Ubicación
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Limpiezas
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Días activos
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Última limpieza
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
                @forelse ($locations as $i => $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">
                            {{ $row->location }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full text-xs font-bold
                                {{ $i === 0 ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $row->total }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">
                            {{ $row->cleaning_days }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $row->last_cleaning_date
                                ? \Carbon\Carbon::parse($row->last_cleaning_date)->format('d/m/Y')
                                : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                            Sin resultados para los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
