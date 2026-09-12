@if (config('services.turnstile.enabled') && config('services.turnstile.site_key'))
    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
