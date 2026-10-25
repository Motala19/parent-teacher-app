<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0a1a42">
    <title>You're offline</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 2rem; text-align: center; color: #fff;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background:
                radial-gradient(520px 260px at 90% 0%, rgba(236,127,168,.35), transparent 70%),
                linear-gradient(145deg, #061231, #0f2557 60%, #1f4096);
        }
        .icon { display: inline-grid; place-items: center; height: 4.5rem; width: 4.5rem; margin-bottom: 1.25rem; border-radius: 1.4rem;
                background: linear-gradient(135deg, #c9485e, #ec7fa8); box-shadow: 0 14px 30px -12px rgba(201,72,94,.8); }
        h1 { margin: 0 0 .5rem; font-size: 1.6rem; }
        p { margin: 0 auto 1.5rem; max-width: 22rem; color: rgba(255,255,255,.75); line-height: 1.5; }
        button { padding: .8rem 1.6rem; border: 0; border-radius: .9rem; cursor: pointer; font: inherit; font-weight: 700; color: #fff;
                 background: linear-gradient(120deg, #c9485e, #ec7fa8); }
    </style>
</head>
<body>
    <main>
        <div class="icon">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/>
                <path d="M10.71 5.05A16 16 0 0 1 22.58 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/>
                <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>
            </svg>
        </div>
        <h1>You're offline</h1>
        <p>We can't reach the school right now. Check your data or Wi-Fi connection and try again.</p>
        <button onclick="location.reload()">Try again</button>
    </main>
</body>
</html>