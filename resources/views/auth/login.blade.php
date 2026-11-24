<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CMNHousing Admin Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      /* One pixel of the 1600 x 884 design, scaled uniformly to fit the viewport. */
      --u: max(0.62px, min(100vw / 1600, 100vh / 884));

      --ink: #101215;
      --ink-soft: #1b1e23;
      --label-muted: #5f6771;
      --placeholder: #6b7280;
      --link-green: #0b7a3b;
      --switch-green: #0a9a3f;
      --outline-green: #2ca25f;
      --input-bg: #f2f6f9;
      --input-border: #c5ced6;
      --heading-green: #86ef8a;
      --page-pale: #e4f2f5;
    }

    * { box-sizing: border-box; }

    html, body {
      margin: 0;
      min-height: 100%;
    }

    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: "Roboto", system-ui, -apple-system, sans-serif;
      color: var(--ink);
      background: var(--page-pale) url("{{ asset('images/login-background.jpg') }}") center bottom / cover no-repeat;
    }

    .login-stage {
      display: grid;
      grid-template-columns: calc(834 * var(--u)) calc(678 * var(--u));
      align-items: center;
      width: calc(1600 * var(--u));
      min-height: calc(884 * var(--u));
      padding-top: calc(20 * var(--u));
    }

    .login-brand {
      align-self: start;
      margin-top: calc(9.5 * var(--u));
      margin-left: calc((1600 * var(--u) - 100vw) / 2);
      padding-left: calc(113 * var(--u));
      color: #fff;
    }

    .login-brand-logo {
      display: block;
      width: calc(326 * var(--u));
      height: auto;
      margin-left: calc(-4.4 * var(--u));
    }

    .login-brand-title {
      margin: calc(28.5 * var(--u)) 0 0;
      font-size: calc(51 * var(--u));
      font-weight: 700;
      line-height: calc(54 * var(--u));
    }

    .login-brand-title span {
      display: block;
      font-size: calc(56.5 * var(--u));
      color: var(--heading-green);
    }

    .login-brand-copy {
      margin: calc(15 * var(--u)) 0 0;
      font-size: calc(21 * var(--u));
      line-height: calc(30 * var(--u));
      color: #dde7e6;
    }

    .login-features {
      display: grid;
      gap: calc(7.7 * var(--u));
      margin: calc(24 * var(--u)) 0 0;
      padding: 0;
      list-style: none;
    }

    .login-feature {
      display: flex;
      align-items: center;
      gap: calc(17 * var(--u));
      font-size: calc(17.7 * var(--u));
      font-weight: 400;
      color: #f2f6f5;
    }

    .login-feature-icon {
      display: grid;
      place-items: center;
      flex-shrink: 0;
      width: calc(48 * var(--u));
      height: calc(44 * var(--u));
      border-radius: calc(11 * var(--u));
      background: rgba(22, 128, 92, 0.34);
    }

    .login-feature-icon svg {
      width: calc(30 * var(--u));
      height: calc(30 * var(--u));
      fill: #fff;
    }

    .login-panel {
      min-width: 0;
    }

    .login-card {
      width: 100%;
      padding: calc(18.8 * var(--u)) calc(48 * var(--u)) calc(35 * var(--u)) calc(51 * var(--u));
      border-radius: calc(28 * var(--u));
      background: linear-gradient(180deg, #fefefe 0%, #f6fbfe 100%);
      box-shadow:
        0 calc(24 * var(--u)) calc(38 * var(--u)) rgba(0, 130, 95, 0.32),
        0 0 calc(14 * var(--u)) rgba(20, 110, 95, 0.09);
    }

    .login-card-brand {
      margin-bottom: calc(26 * var(--u));
      text-align: center;
    }

    .login-card-logo {
      display: block;
      width: calc(311 * var(--u));
      height: auto;
      margin: 0 auto;
      position: relative;
      left: calc(-3 * var(--u));
    }

    .login-card-title {
      margin: 0 0 calc(19 * var(--u));
      font-size: calc(22 * var(--u));
      font-weight: 600;
      line-height: 1;
      letter-spacing: 0.5em;
      text-indent: 0.5em;
      text-transform: uppercase;
      color: var(--ink);
    }

    .secure-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: calc(8 * var(--u));
      width: calc(200 * var(--u));
      padding-right: calc(4 * var(--u));
      height: calc(36 * var(--u));
      border-radius: calc(12 * var(--u));
      background: linear-gradient(180deg, #05a02f 0%, #008c2c 55%, #0a7b30 100%);
      box-shadow:
        inset 0 calc(1 * var(--u)) 0 rgba(255, 255, 255, 0.22),
        0 calc(1 * var(--u)) calc(2 * var(--u)) rgba(0, 80, 30, 0.35);
      color: #d6f7a3;
      font-size: calc(17.8 * var(--u));
      font-weight: 600;
      text-transform: uppercase;
      vertical-align: top;
    }

    .secure-badge svg {
      width: calc(25 * var(--u));
      height: calc(25 * var(--u));
      stroke-width: 1.7;
    }

    .login-error {
      margin: 0 0 calc(14 * var(--u));
      padding: calc(10 * var(--u)) calc(14 * var(--u));
      border-radius: calc(10 * var(--u));
      background: #fef2f2;
      color: #b91c1c;
      font-size: calc(15 * var(--u));
    }

    .form-group + .form-group {
      margin-top: calc(26 * var(--u));
    }

    .form-label-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: calc(10 * var(--u));
      margin-bottom: calc(8 * var(--u));
    }

    .form-group label {
      display: block;
      font-size: calc(20 * var(--u));
      font-weight: 600;
      line-height: calc(24 * var(--u));
      color: var(--ink);
    }

    .form-group > label {
      margin-bottom: calc(8 * var(--u));
    }

    .forgot-link {
      color: var(--link-green);
      font-size: calc(17.8 * var(--u));
      font-weight: 600;
      text-decoration: none;
      white-space: nowrap;
    }

    .forgot-link:hover {
      text-decoration: underline;
    }

    .input-with-icon {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-with-icon .leading-icon,
    .input-with-icon .trailing-btn {
      position: absolute;
      display: grid;
      place-items: center;
      padding: 0;
      border: 0;
      background: none;
      color: var(--ink-soft);
    }

    .input-with-icon .leading-icon {
      left: calc(17 * var(--u));
      pointer-events: none;
    }

    .input-with-icon .leading-icon svg {
      width: calc(30 * var(--u));
      height: calc(30 * var(--u));
    }

    .input-with-icon .trailing-btn {
      right: calc(17.5 * var(--u));
      cursor: pointer;
    }

    .input-with-icon .trailing-btn svg {
      width: calc(29 * var(--u));
      height: calc(29 * var(--u));
    }

    .input-with-icon .trailing-btn svg.is-hidden {
      display: none;
    }

    .input-with-icon .form-control {
      width: 100%;
      height: calc(52 * var(--u));
      padding: 0 calc(16 * var(--u)) 0 calc(60.4 * var(--u));
      border: calc(1.6 * var(--u)) solid var(--input-border);
      border-radius: calc(13 * var(--u));
      background: var(--input-bg);
      box-shadow: inset 0 calc(1 * var(--u)) calc(2 * var(--u)) rgba(15, 23, 42, 0.04);
      color: var(--ink);
      font-family: inherit;
      font-size: calc(18.3 * var(--u));
    }

    .input-with-icon #password {
      padding-right: calc(58 * var(--u));
    }

    .input-with-icon .form-control::placeholder {
      color: var(--placeholder);
      opacity: 1;
    }

    .input-with-icon .form-control:focus {
      outline: none;
      border-color: var(--switch-green);
      background: #fff;
      box-shadow: 0 0 0 calc(3 * var(--u)) rgba(10, 154, 63, 0.14);
    }

    .input-with-icon .form-control:-webkit-autofill,
    .input-with-icon .form-control:-webkit-autofill:hover,
    .input-with-icon .form-control:-webkit-autofill:focus {
      -webkit-text-fill-color: var(--ink);
      -webkit-box-shadow: 0 0 0 100px var(--input-bg) inset;
      caret-color: var(--ink);
    }

    .login-options {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: calc(12 * var(--u));
      min-height: calc(27 * var(--u));
      margin: calc(22.5 * var(--u)) 0 0;
    }

    .remember-row,
    .otp-toggle {
      display: inline-flex;
      align-items: center;
      user-select: none;
    }

    .remember-row {
      gap: calc(11 * var(--u));
      color: var(--ink-soft);
      font-size: calc(18.3 * var(--u));
      font-weight: 500;
      cursor: pointer;
    }

    .remember-row input {
      appearance: none;
      display: grid;
      place-items: center;
      width: calc(24 * var(--u));
      height: calc(24 * var(--u));
      margin: 0;
      border: calc(2 * var(--u)) solid #2b3038;
      border-radius: calc(5 * var(--u));
      background: #fff;
      cursor: pointer;
    }

    .remember-row input:checked {
      border-color: var(--switch-green);
      background: var(--switch-green);
    }

    .remember-row input:checked::after {
      content: "";
      width: 28%;
      height: 55%;
      margin-top: -12%;
      border: solid #fff;
      border-width: 0 calc(2.4 * var(--u)) calc(2.4 * var(--u)) 0;
      transform: rotate(45deg);
    }

    .remember-row input:focus-visible,
    .otp-toggle input:focus-visible + .otp-switch {
      outline: 2px solid var(--switch-green);
      outline-offset: 2px;
    }

    .otp-toggle {
      position: relative;
      gap: calc(5 * var(--u));
      color: var(--ink-soft);
      font-size: calc(16.4 * var(--u));
      font-weight: 500;
    }

    .otp-toggle > svg {
      width: calc(26 * var(--u));
      height: calc(26 * var(--u));
      flex-shrink: 0;
    }

    .otp-toggle input {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }

    .otp-switch {
      position: relative;
      flex-shrink: 0;
      width: calc(47 * var(--u));
      height: calc(27 * var(--u));
      margin-left: calc(10 * var(--u));
      border-radius: 999px;
      background: #cbd5e1;
    }

    .otp-switch::after {
      content: "";
      position: absolute;
      top: calc(2 * var(--u));
      left: calc(2 * var(--u));
      width: calc(23 * var(--u));
      height: calc(23 * var(--u));
      border-radius: 50%;
      background: #fff;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.25);
    }

    .otp-toggle input:checked + .otp-switch {
      background: var(--switch-green);
    }

    .otp-toggle input:checked + .otp-switch::after {
      transform: translateX(calc(20 * var(--u)));
    }

    .login-submit,
    .login-otp-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      border-radius: 999px;
      font-family: inherit;
      cursor: pointer;
    }

    .login-submit {
      gap: calc(9 * var(--u));
      height: calc(61 * var(--u));
      margin-top: calc(31.5 * var(--u));
      border: 0;
      background: linear-gradient(180deg, #06613a 0%, #04472c 52%, #022a1c 92%, #011c10 100%);
      color: #fff;
      font-size: calc(23.3 * var(--u));
      font-weight: 600;
    }

    .login-submit:hover {
      filter: brightness(1.12);
    }

    .login-submit svg {
      width: calc(28 * var(--u));
      height: calc(28 * var(--u));
    }

    .login-divider {
      display: flex;
      align-items: center;
      gap: calc(15 * var(--u));
      margin-top: calc(18 * var(--u));
      color: #5b6470;
      font-size: calc(17 * var(--u));
      line-height: calc(20 * var(--u));
    }

    .login-divider::before,
    .login-divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background: #dfe3e8;
    }

    .login-otp-btn {
      gap: calc(10.4 * var(--u));
      height: calc(52 * var(--u));
      margin-top: calc(19 * var(--u));
      border: calc(1.6 * var(--u)) solid var(--outline-green);
      background: transparent;
      color: #0a6b33;
      font-size: calc(19.3 * var(--u));
      font-weight: 600;
    }

    .login-otp-btn:hover {
      background: rgba(44, 162, 95, 0.06);
    }

    .login-otp-btn svg {
      width: calc(25 * var(--u));
      height: calc(27 * var(--u));
      color: #12873f;
    }

    .login-card-footer {
      display: flex;
      align-items: center;
      justify-content: center;
      margin: calc(33 * var(--u)) 0 0;
      color: var(--label-muted);
      font-size: calc(15.9 * var(--u));
      line-height: calc(20 * var(--u));
    }

    .login-card-footer span {
      display: inline-flex;
      align-items: center;
      gap: calc(5.8 * var(--u));
    }

    .login-card-footer span:last-child {
      gap: calc(2 * var(--u));
    }

    .login-card-footer i {
      display: block;
      width: 1px;
      height: calc(20 * var(--u));
      margin: 0 calc(19.75 * var(--u)) 0 calc(22 * var(--u));
      background: #8a929c;
    }

    .login-card-footer svg {
      width: calc(22 * var(--u));
      height: calc(22 * var(--u));
      margin-block: calc(-1 * var(--u));
      flex-shrink: 0;
      color: #3c424a;
    }

    @media (max-width: 1024px) {
      :root {
        --u: 0.9px;
      }

      body {
        display: block;
        background: var(--page-pale);
      }

      .login-stage {
        display: block;
        width: 100%;
        min-height: 100vh;
        padding: 0 0 24px;
      }

      .login-brand {
        height: 300px;
        margin: 0;
        padding: 16px 16px 0;
        background: url("{{ asset('images/login-background.jpg') }}") left bottom / max(650px, 100%) auto no-repeat;
      }

      .login-brand-logo {
        width: 190px;
        margin-left: -8px;
      }

      .login-brand-title {
        margin-top: 6px;
        font-size: 26px;
        line-height: 31px;
      }

      .login-brand-title span {
        font-size: 29px;
      }

      .login-brand-copy,
      .login-features {
        display: none;
      }

      .login-panel {
        width: calc(100% - 32px);
        max-width: 560px;
        margin: -56px auto 0;
      }

      .login-card {
        position: relative;
        padding: 16px 20px 24px;
        border-radius: 22px;
      }

      .login-options {
        flex-direction: column;
        align-items: stretch;
        gap: 14px;
      }

      .otp-toggle {
        justify-content: space-between;
      }

      .otp-toggle > span:first-of-type {
        flex: 1;
      }

      .login-card-footer {
        flex-direction: column;
        gap: 6px;
        text-align: center;
      }

      .login-card-footer i {
        display: none;
      }
    }
  </style>
</head>
<body>
  <div class="login-stage">
    <aside class="login-brand">
      <img class="login-brand-logo" src="{{ asset('images/cmn-housing-logo-light.png') }}" alt="CMN Housing">
      <h1 class="login-brand-title">CMNHousing <span>Admin Portal</span></h1>
      <p class="login-brand-copy">Manage properties, users, listings, payments,<br>legal checks and more &mdash; all in one place.</p>
      <ul class="login-features">
        <li class="login-feature">
          <span class="login-feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="3" y="13" width="5" height="8" rx="1"></rect><rect x="9.5" y="8" width="5" height="13" rx="1"></rect><rect x="16" y="3" width="5" height="18" rx="1"></rect></svg>
          </span>
          Property &amp; Listing Management
        </li>
        <li class="login-feature">
          <span class="login-feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"></path></svg>
          </span>
          Users &amp; CRM
        </li>
        <li class="login-feature">
          <span class="login-feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M12 1 3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"></path></svg>
          </span>
          Legal SafeCheck &amp; Verification
        </li>
        <li class="login-feature">
          <span class="login-feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><ellipse cx="15.5" cy="5" rx="6.5" ry="2.7"></ellipse><path d="M9 7.2c0 1.5 2.9 2.7 6.5 2.7S22 8.7 22 7.2v1.7c0 1.5-2.9 2.7-6.5 2.7S9 10.4 9 8.9z"></path><path d="M9 10.9c1.1.8 3.4 1.7 6.5 1.7S21 11.7 22 10.9v1.7c0 1.5-2.9 2.7-6.5 2.7-.3 0-.7 0-1-.1z"></path><ellipse cx="8.5" cy="12.5" rx="6.5" ry="2.7"></ellipse><path d="M2 14.7c0 1.5 2.9 2.7 6.5 2.7s6.5-1.2 6.5-2.7v1.7c0 1.5-2.9 2.7-6.5 2.7S2 17.9 2 16.4z"></path><path d="M2 18.4c0 1.5 2.9 2.7 6.5 2.7s6.5-1.2 6.5-2.7v1.7c0 1.5-2.9 2.7-6.5 2.7S2 21.6 2 20.1z"></path></svg>
          </span>
          Payments &amp; Premium Plans
        </li>
        <li class="login-feature">
          <span class="login-feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"></path></svg>
          </span>
          System &amp; Business Operations
        </li>
      </ul>
    </aside>

    <section class="login-panel">
      <main class="login-card">
        <div class="login-card-brand">
          <img class="login-card-logo" src="{{ asset('images/cmn-housing-logo.png') }}" alt="CMN Housing">
          <p class="login-card-title">Admin Portal</p>
          <span class="secure-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>
            Secure Access
          </span>
        </div>

        @if (session('status'))
          <div class="login-error" role="status" style="background:#ecfdf5;border-color:#a7f3d0;color:#065f46">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
          <div class="login-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" novalidate>
          @csrf

          <div class="form-group">
            <label for="login">Mobile Number / Email</label>
            <div class="input-with-icon">
              <span class="leading-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"><circle cx="12" cy="7.5" r="4.5"></circle><path d="M4 21.5v-.8a5.7 5.7 0 0 1 5.7-5.7h4.6a5.7 5.7 0 0 1 5.7 5.7v.8z"></path></svg>
              </span>
              <input
                class="form-control"
                type="text"
                id="login"
                name="login"
                value="{{ old('login') }}"
                placeholder="Enter mobile number or email"
                autocomplete="username"
                autocapitalize="none"
                spellcheck="false"
                required
              >
            </div>
          </div>

          <div class="form-group">
            <div class="form-label-row">
              <label for="password">Password</label>
              <a class="forgot-link" href="{{ route('password.request') }}">Forgot Password?</a>
            </div>
            <div class="input-with-icon">
              <span class="leading-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"><rect x="4" y="10.5" width="16" height="11.5" rx="2"></rect><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"></path><circle cx="12" cy="16.2" r="1.1" fill="currentColor" stroke="none"></circle></svg>
              </span>
              <input
                class="form-control"
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                required
              >
              <button class="trailing-btn" type="button" id="togglePassword" aria-label="Show password" aria-pressed="false">
                <svg id="eyeClosed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle><line x1="5.5" y1="5" x2="18.5" y2="19"></line></svg>
                <svg id="eyeOpen" class="is-hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </div>

          <div class="login-options">
            <label class="remember-row">
              <input type="checkbox" name="remember" value="1">
              Remember me
            </label>
          </div>

          <button class="login-submit" type="submit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 8V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2v-3"></path><path d="M3 12h12"></path><path d="m11 8 4 4-4 4"></path></svg>
            Sign In
          </button>
        </form>


        <p class="login-card-footer">
          <span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10.5" width="16" height="11.5" rx="2"></rect><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"></path><circle cx="12" cy="16.2" r="1.3" fill="currentColor" stroke="none"></circle></svg>
            Protected Administrator Access
          </span>
          <i aria-hidden="true"></i>
          <span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>
            Role-Based Secure Login
          </span>
        </p>
      </main>
    </section>
  </div>

  <script>
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');

    togglePassword.addEventListener('click', () => {
      const showing = passwordInput.type === 'text';
      passwordInput.type = showing ? 'password' : 'text';
      eyeOpen.classList.toggle('is-hidden', showing);
      eyeClosed.classList.toggle('is-hidden', !showing);
      togglePassword.setAttribute('aria-pressed', showing ? 'false' : 'true');
      togglePassword.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
  </script>
</body>
</html>
