<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authorize {{ $client->name }} · {{ config('app.name') }}</title>
    <style>
        :root { --bg: #f4f4f5; --card: #fff; --fg: #18181b; --muted: #71717a; --border: #e4e4e7; --primary: #d97706; --primary-fg: #fff; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #09090b; --card: #18181b; --fg: #fafafa; --muted: #a1a1aa; --border: #27272a; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; background: var(--bg); color: var(--fg); font: 15px/1.5 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        .card { width: 100%; max-width: 420px; background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgb(0 0 0 / .06); }
        .logo { width: 48px; height: 48px; margin: 0 auto 16px; display: block; border-radius: 8px; object-fit: contain; }
        h1 { font-size: 20px; margin: 0 0 6px; text-align: center; }
        p { margin: 0; }
        .muted { color: var(--muted); font-size: 14px; text-align: center; }
        .box { margin-top: 20px; border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; font-size: 14px; }
        .box strong { display: block; }
        ul { margin: 8px 0 0; padding-left: 18px; color: var(--muted); }
        .redirect { margin-top: 8px; font-size: 12px; word-break: break-all; }
        .actions { display: flex; gap: 12px; margin-top: 24px; }
        .actions form { flex: 1; }
        button { width: 100%; height: 40px; border-radius: 8px; font: inherit; font-weight: 600; cursor: pointer; border: 1px solid var(--border); background: transparent; color: var(--fg); }
        button.primary { background: var(--primary); border-color: var(--primary); color: var(--primary-fg); }
        button:disabled { opacity: .6; cursor: default; }
    </style>
</head>
<body>
<main class="card">
    @if ($client->logo_uri ?? null)
        <img class="logo" src="{{ $client->logo_uri }}" alt="">
    @endif

    <h1>Authorize {{ $client->name }}</h1>
    <p class="muted">This AI agent wants read-only access to {{ config('app.name') }} analytics data (visitors, sessions, events).</p>
    @if ($redirectUri ?? null)
        <p class="muted redirect">Redirects to {{ $redirectUri }}</p>
    @endif

    <div class="box">
        <span class="muted" style="text-align:left;display:block">Signed in as</span>
        <strong>{{ $user->email }}</strong>
        @if (count($scopes) > 0)
            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="actions">
        <form method="POST" action="{{ route('passport.authorizations.deny') }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state" value="">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit">Cancel</button>
        </form>
        <form method="POST" action="{{ route('passport.authorizations.approve') }}" onsubmit="this.querySelector('button').disabled = true">
            @csrf
            <input type="hidden" name="state" value="">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="primary">Authorize</button>
        </form>
    </div>
</main>
</body>
</html>
