@php
    use App\Models\CarPhoto;
    use App\Support\Format;
    use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

    $data = $this->data ?? [];
    $first = collect($data['photos'] ?? [])->first();

    $cover = null;
    if ($first instanceof TemporaryUploadedFile) {
        try {
            $cover = $first->isPreviewable() ? $first->temporaryUrl() : null;
        } catch (\Throwable) {
            $cover = null;
        }
    } elseif (is_string($first) && $first !== '') {
        $cover = CarPhoto::disk()->url($first);
    }

    $title = trim((string) ($data['title'] ?? '')) ?: '2019 Jeep Compass';
    $trim = trim((string) ($data['trim'] ?? ''));
    $price = filled($data['price'] ?? null) ? Format::price((int) $data['price']) : '$0';
@endphp

<div class="lg:sticky lg:top-20">
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <p class="px-4 pt-3 font-[Barlow_Condensed] text-xs font-bold uppercase tracking-[0.16em] text-gray-500">Preview on the site</p>
        <div class="relative mt-3 aspect-[4/3] bg-gray-100">
            @if ($cover)
                <img src="{{ $cover }}" alt="Cover photo preview" class="h-full w-full object-cover">
            @else
                <div class="flex h-full items-center justify-center text-sm font-semibold text-gray-500">No photo yet</div>
            @endif
            @if (! empty($data['price_drop']))
                <span class="absolute left-3 top-3 rounded-full bg-primary-600 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-white">Price drop</span>
            @endif
        </div>
        <div class="flex flex-col gap-1 px-4 pb-4 pt-3">
            <p class="font-[Barlow_Condensed] text-2xl font-bold uppercase leading-none text-gray-950">{{ $title }}</p>
            @if ($trim !== '')
                <p class="text-sm text-gray-500">{{ $trim }}</p>
            @endif
            <p class="mt-1 font-[Barlow_Condensed] text-3xl font-extrabold leading-none text-gray-950">{{ $price }}</p>
            <p class="text-xs text-gray-500">incl. dealer fees</p>
        </div>
    </div>
</div>
