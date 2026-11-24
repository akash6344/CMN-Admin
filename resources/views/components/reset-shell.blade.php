@props(['title'])
{{-- Standalone card used by the forgot / reset password pages. --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $title }} · CMNHousing Admin</title>
  <style>
    :root{--accent:#0a7b30;--ink:#0f172a;--muted:#64748b;--line:#e2e8f0}
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px 16px;background:#f1f5f9;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:var(--ink)}
    .rp-card{width:100%;max-width:420px;background:#fff;border:1px solid var(--line);border-radius:16px;padding:32px 28px;box-shadow:0 10px 30px rgba(15,23,42,.06)}
    .rp-brand{display:flex;align-items:center;gap:10px;margin-bottom:22px;font-weight:700;font-size:15px}
    .rp-mark{width:34px;height:34px;border-radius:9px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800}
    h1{font-size:22px;margin:0 0 6px}
    .rp-sub{margin:0 0 22px;color:var(--muted);font-size:14px;line-height:1.5}
    label{display:block;font-size:13px;font-weight:600;margin:0 0 6px}
    input{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:10px;font:inherit;font-size:14.5px;margin-bottom:16px}
    input:focus{outline:2px solid color-mix(in srgb,var(--accent) 35%,transparent);border-color:var(--accent)}
    button{width:100%;padding:12px;border:0;border-radius:10px;background:var(--accent);color:#fff;font:inherit;font-weight:700;font-size:15px;cursor:pointer}
    button:hover{filter:brightness(.93)}
    .rp-msg{padding:11px 13px;border-radius:10px;font-size:13.5px;line-height:1.5;margin-bottom:16px}
    .rp-ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
    .rp-err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
    .rp-back{display:block;text-align:center;margin-top:18px;font-size:14px;color:var(--accent);font-weight:600;text-decoration:none}
    .rp-hint{font-size:12.5px;color:var(--muted);margin:-10px 0 16px}
  </style>
</head>
<body>
  <main class="rp-card">
    <div class="rp-brand"><span class="rp-mark">C</span>CMNHousing Admin</div>
    {{ $slot }}
    <a class="rp-back" href="{{ route('login') }}">← Back to sign in</a>
  </main>
</body>
</html>
