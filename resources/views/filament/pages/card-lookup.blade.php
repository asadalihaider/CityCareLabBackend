<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="searchCard">
            {{ $this->form }}
        </form>

        @if($physicalCard)
            {{ $this->infolist }}
        @endif
    </div>
</x-filament-panels::page>
