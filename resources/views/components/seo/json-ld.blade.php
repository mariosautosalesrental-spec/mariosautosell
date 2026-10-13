@props(['data'])

<script type="application/ld+json">{!! \App\Support\StructuredData::json($data) !!}</script>
