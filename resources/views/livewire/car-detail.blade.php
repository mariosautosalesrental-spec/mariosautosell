<div>
    @if ($car)
        {{-- Native <dialog>: focus is trapped inside, Esc closes it and the focus returns to the card that opened it. --}}
        <dialog class="car-dialog" aria-labelledby="dTitle" wire:key="car-dialog-{{ $car->id }}"
                x-init="$el.showModal()"
                x-on:close="$wire.close()"
                x-on:click="if ($event.target === $el) $el.close()">
            <button class="close" type="button" id="dClose" aria-label="Close" x-on:click="$el.closest('dialog').close()">
                <x-ui.icon name="close" :stroke="2.5" />
            </button>
            <x-car-detail :car="$car" modal />
        </dialog>
    @endif
</div>
