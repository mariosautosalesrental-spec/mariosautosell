<footer class="site">
    <div class="wrap">
        <img class="flogo" src="{{ asset('images/logo.png') }}" alt="Mario's Auto Sales &amp; Rentals" width="560" height="236">
        <p>Advertised prices include all dealer fees and exclude sales tax, title and registration. Vehicles are subject to prior sale. Please confirm all details with us before purchase.</p>
        <nav aria-label="Legal">
            <a href="{{ route('legal.privacy') }}">Privacy Policy</a>
            <a href="{{ route('legal.terms') }}">Terms</a>
            <a href="{{ route('legal.accessibility') }}">Accessibility</a>
        </nav>
        <span>© {{ now()->year }} Mario's Auto Sales &amp; Rentals · Dracut, MA</span>
    </div>
</footer>
