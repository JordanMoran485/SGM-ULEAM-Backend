@php
    use Filament\Widgets\View\Components\ChartWidgetComponent;
    use Illuminate\View\ComponentAttributeBag;

    $heading  = $this->getHeading();
    $type     = $this->getType();
    $maxHeight = $this->getMaxHeight();
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section :heading="$heading">

        @if (method_exists($this, 'getFiltersSchema'))
            <x-slot name="afterHeader">
                <div class="w-80">
                    {{ $this->getFiltersSchema() }}
                </div>
            </x-slot>
        @endif

        <div>
            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                data-chart-type="{{ $type }}"
                x-data="chart({
                    cachedData: @js($this->getCachedData()),
                    maxHeight: @js($maxHeight),
                    options: @js($this->getOptions()),
                    type: @js($type),
                })"
                {{
                    (new ComponentAttributeBag)
                        ->class([
                            'fi-wi-chart-canvas-ctn',
                            'fi-wi-chart-canvas-ctn-no-aspect-ratio' => filled($maxHeight),
                        ])
                }}
            >
                <canvas
                    x-ref="canvas"
                    @if ($maxHeight) style="max-height: {{ $maxHeight }}" @endif
                ></canvas>

                <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                <span x-ref="borderColorElement"     class="fi-wi-chart-border-color"></span>
                <span x-ref="gridColorElement"       class="fi-wi-chart-grid-color"></span>
                <span x-ref="textColorElement"       class="fi-wi-chart-text-color"></span>
            </div>
        </div>

    </x-filament::section>
</x-filament-widgets::widget>
