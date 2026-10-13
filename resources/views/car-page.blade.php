<x-layouts.app :title="$car->fullName().' for sale in Dracut, MA'" :description="$description">
    <x-slot:head>
        <x-seo.json-ld :data="\App\Support\StructuredData::car($car)" />
    </x-slot:head>

    <div class="wrap car-page">
        <a class="back" href="{{ url('/') }}#inventory"><x-ui.icon name="chevron-left" :size="18" /> Back to all cars</a>
        <div class="car-page-card">
            <x-car-detail :car="$car" />
        </div>
    </div>
</x-layouts.app>
