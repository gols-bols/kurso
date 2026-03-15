<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Система заявок СПК' }}</title>
    <style>
        :root {
            --bg: #f4efe6;
            --panel: rgba(255, 252, 247, 0.92);
            --panel-strong: #fffaf2;
            --line: #dccdb4;
            --text: #2f261a;
            --muted: #72614b;
            --accent: #9c4f2d;
            --accent-dark: #74371d;
            --success-bg: #e6f3e8;
            --success-text: #27573a;
            --danger-bg: #fbe7e3;
            --danger-text: #8f3626;
            --shadow: 0 24px 60px rgba(72, 49, 23, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(201, 143, 94, 0.22), transparent 28%),
                radial-gradient(circle at bottom right, rgba(112, 143, 123, 0.18), transparent 24%),
                linear-gradient(180deg, #f7f2ea 0%, #efe4d3 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 48px;
        }

        .hero {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 24px;
            padding: 24px 28px;
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 28px;
            background: linear-gradient(135deg, rgba(255, 250, 242, 0.9), rgba(241, 229, 210, 0.82));
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: clamp(28px, 4vw, 42px);
            line-height: 1.05;
        }

        .hero p {
            margin: 0;
            max-width: 720px;
            color: var(--muted);
            line-height: 1.6;
        }

        .hero-badge {
            flex-shrink: 0;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(156, 79, 45, 0.1);
            color: var(--accent-dark);
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-size: 12px;
        }

        .panel {
            padding: 24px;
            border: 1px solid rgba(220, 205, 180, 0.9);
            border-radius: 24px;
            background: var(--panel);
            box-shadow: var(--shadow);
            backdrop-filter: blur(12px);
        }

        .stack {
            display: grid;
            gap: 18px;
        }

        .flash,
        .error-list {
            padding: 14px 16px;
            border-radius: 16px;
            font-size: 15px;
            line-height: 1.5;
        }

        .flash {
            background: var(--success-bg);
            color: var(--success-text);
            border: 1px solid rgba(39, 87, 58, 0.14);
        }

        .error-list {
            margin: 0;
            padding-left: 28px;
            background: var(--danger-bg);
            color: var(--danger-text);
            border: 1px solid rgba(143, 54, 38, 0.14);
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .toolbar-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .button,
        button {
            appearance: none;
            border: 0;
            border-radius: 14px;
            padding: 12px 18px;
            background: var(--accent);
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s ease, background 0.15s ease;
        }

        .button:hover,
        button:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
        }

        .button.secondary,
        .button-muted {
            background: rgba(114, 97, 75, 0.12);
            color: var(--text);
        }

        .grid {
            display: grid;
            gap: 18px;
        }

        .tickets-grid {
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }

        .ticket-card {
            padding: 20px;
            border-radius: 22px;
            background: var(--panel-strong);
            border: 1px solid rgba(220, 205, 180, 0.85);
            display: grid;
            gap: 14px;
        }

        .ticket-card h3 {
            margin: 0;
            font-size: 21px;
        }

        .ticket-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.55;
        }

        .meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(156, 79, 45, 0.1);
            color: var(--accent-dark);
            font-size: 13px;
            font-weight: 700;
        }

        .pill-muted {
            background: rgba(114, 97, 75, 0.12);
            color: var(--muted);
        }

        .empty {
            padding: 26px;
            border: 1px dashed var(--line);
            border-radius: 22px;
            text-align: center;
            color: var(--muted);
            background: rgba(255, 250, 242, 0.75);
        }

        .form-grid {
            display: grid;
            gap: 16px;
        }

        label {
            display: grid;
            gap: 8px;
            font-weight: 700;
        }

        .hint {
            font-size: 14px;
            font-weight: 400;
            color: var(--muted);
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid var(--line);
            background: #fffdf9;
            color: var(--text);
            font: inherit;
        }

        textarea {
            min-height: 180px;
            resize: vertical;
        }

        .footer-note {
            margin-top: 18px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .auth-shell {
            width: min(520px, calc(100% - 32px));
            margin: 0 auto;
            padding: 60px 0;
        }

        @media (max-width: 720px) {
            .page {
                width: min(100% - 20px, 1120px);
                padding-top: 20px;
            }

            .panel,
            .hero {
                padding: 20px;
                border-radius: 22px;
            }

            .hero {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
@yield('content')
</body>
</html>
