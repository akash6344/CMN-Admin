<x-reset-shell title="Forgot password">
  <h1>Forgot your password?</h1>
  <p class="rp-sub">Enter your account email and we’ll send you a link to set a new password.</p>
  @if (session('status'))<div class="rp-msg rp-ok" role="status">{{ session('status') }}</div>@endif
  @error('email')<div class="rp-msg rp-err" role="alert">{{ $message }}</div>@enderror
  <form method="POST" action="{{ route('password.email') }}">
    @csrf
    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@example.com">
    <button type="submit">Email me a reset link</button>
  </form>
</x-reset-shell>
