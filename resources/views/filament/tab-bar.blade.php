@php
    $items = collect(filament()->getNavigation())
        ->flatMap(fn ($group) => $group->getItems())
        ->filter(fn ($item) => $item->isVisible() && ! $item->shouldOpenUrlInNewTab());
@endphp

@if ($items->isNotEmpty())
    <nav class="mario-tabbar" aria-label="Admin">
        @foreach ($items as $item)
            <a class="mario-tab" href="{{ $item->getUrl() }}" @if ($item->isActive()) aria-current="page" @endif>
                @if ($icon = ($item->isActive() ? $item->getActiveIcon() : null) ?? $item->getIcon())
                    {{ \Filament\Support\generate_icon_html($icon) }}
                @endif
                <span>{{ $item->getLabel() }}</span>
            </a>
        @endforeach
    </nav>
@endif
