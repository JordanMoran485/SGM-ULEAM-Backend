<div class="mb-4">
    <p class="text-sm font-medium text-gray-700 mb-2">Plantillas que se aplicarán:</p>

    @if($templates->isEmpty())
        <p class="text-sm text-gray-400 italic">No hay plantillas configuradas.</p>
    @else
        <ul class="divide-y divide-gray-200">
            @foreach($templates as $template)
                <li class="flex items-center justify-between py-2 text-sm">
                    <span class="font-medium text-gray-800">
                        {{ $template->user?->getDisplayNameWithConserjeType() ?? '—' }}
                    </span>
                    <span class="text-gray-400 text-xs">
                        {{ $template->taskTemplateItems->count() }}
                        {{ $template->taskTemplateItems->count() === 1 ? 'tarea' : 'tareas' }}
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
