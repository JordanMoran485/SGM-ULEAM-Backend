<div class="sgm-loading-zone" wire:target="{{ $loadingTarget }}">
    <div class="sgm-loading-zone__overlay" wire:loading.delay.flex wire:target="{{ $loadingTarget }}">
        <div class="sgm-loading-zone__panel" role="status" aria-live="polite">
            <div class="sgm-loading-zone__pulse" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <div class="sgm-loading-zone__copy">
                <strong>Actualizando datos</strong>
                <span>Aplicando filtros en esta seccion</span>
            </div>
        </div>
    </div>

    <div class="sgm-loading-zone__content" wire:loading.delay.class="sgm-loading-zone__content--loading" wire:target="{{ $loadingTarget }}">
        {{ $widgetsContent }}
    </div>
</div>
