{{-- Falls back to the default store info so the error pages still render when the database is down. --}}
@php($store = rescue(fn () => \App\Models\Setting::current(), fn () => new \App\Models\Setting(\App\Models\Setting::defaults()), false))

<header class="site">
    <div class="wrap">
        <a class="logo" href="{{ url('/') }}">
            <img src="{{ asset('images/logo.png') }}" alt="Mario's Auto Sales &amp; Rentals" width="560" height="236">
        </a>
        <div class="hinfo">
            <div class="hitem">
                <x-ui.icon name="pin" />
                <span><b>{{ $store->street }}</b><small>{{ $store->cityLine() }}</small></span>
            </div>
            <div class="hitem">
                <x-ui.icon name="clock" />
                <span><b>Mon–Fri {{ $store->hours_mon_fri }}</b><small>Sat {{ $store->hours_sat }} · Sun {{ $store->hours_sun }}</small></span>
            </div>
            <div class="hitem hphone">
                <x-ui.icon name="phone" />
                <span><small>Call us</small><b>{{ $store->phone }}</b></span>
            </div>
        </div>
    </div>
</header>
