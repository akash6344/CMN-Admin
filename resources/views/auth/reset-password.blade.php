<x-reset-shell title="Set a new password">
  <h1>Set a new password</h1>
  <p class="rp-sub">Choose a password with at least 8 characters.</p>
  @if ($errors->any())<div class="rp-msg rp-err" role="alert">{{ $errors->first() }}@if ($errors->has('email') && str_contains($errors->first('email'), 'expired')) <a href="{{ route('password.request') }}">Get a new link</a>@endif</div>@endif
  <form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
    <label for="password">New password</label>
    <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" autofocus>
    <label for="password_confirmation">Confirm new password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
    <button type="submit">Save new password</button>
  </form>
</x-reset-shell>
