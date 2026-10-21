@if (\App\Rules\Turnstile::activo() && config('services.turnstile.site_key'))
  @once
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
  @endonce
  <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="es" data-theme="auto"></div>
  @error('cf-turnstile-response')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
@endif
