<?php
// ============================================================
// PERINGATAN: File ini hanya untuk penggunaan sementara!
// Hapus file ini segera setelah selesai mengganti password.
// JANGAN upload ke server produksi secara permanen.
// ============================================================

$hash = '';
$error = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password      = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($password)) {
        $error = 'Password tidak boleh kosong.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Password Hash — Admin Tool</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&family=Sora:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0d0f14;
            --surface:   #161a23;
            --border:    #252b38;
            --accent:    #f5a623;
            --accent2:   #e8562a;
            --text:      #e8eaf0;
            --muted:     #6b7280;
            --success:   #22c55e;
            --error:     #ef4444;
            --mono:      'JetBrains Mono', monospace;
            --sans:      'Sora', sans-serif;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: var(--sans);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        /* Noise overlay */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
        }

        .warning-banner {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: linear-gradient(90deg, var(--accent2), var(--accent));
            color: #0d0f14;
            text-align: center;
            padding: 0.5rem 1rem;
            font-family: var(--mono);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            z-index: 100;
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 520px;
            margin-top: 2rem;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 0 60px rgba(245,166,35,0.06), 0 20px 60px rgba(0,0,0,0.4);
        }

        .header {
            margin-bottom: 2rem;
        }

        .badge {
            display: inline-block;
            background: rgba(245,166,35,0.12);
            border: 1px solid rgba(245,166,35,0.3);
            color: var(--accent);
            font-family: var(--mono);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
            line-height: 1.3;
        }

        h1 span {
            color: var(--accent);
        }

        .subtitle {
            margin-top: 0.4rem;
            color: var(--muted);
            font-size: 0.85rem;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 0.5rem;
            font-family: var(--mono);
        }

        .input-wrap {
            position: relative;
        }

        input[type="password"], input[type="text"] {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: var(--mono);
            font-size: 0.95rem;
            padding: 0.75rem 3rem 0.75rem 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        input[type="password"]:focus, input[type="text"]:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245,166,35,0.1);
        }

        .toggle-eye {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted);
            padding: 0.25rem;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }

        .toggle-eye:hover { color: var(--accent); }

        .btn {
            width: 100%;
            padding: 0.85rem 1.5rem;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #0d0f14;
            font-family: var(--sans);
            font-size: 0.95rem;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s;
            letter-spacing: 0.03em;
            margin-top: 0.5rem;
        }

        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }

        .alert {
            padding: 0.85rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
        }

        .alert-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
        }

        .result-box {
            margin-top: 1.75rem;
            background: var(--bg);
            border: 1px solid var(--success);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 0 30px rgba(34,197,94,0.08);
        }

        .result-label {
            font-family: var(--mono);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--success);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .result-label::before {
            content: '';
            display: inline-block;
            width: 6px; height: 6px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .hash-value {
            font-family: var(--mono);
            font-size: 0.78rem;
            color: var(--text);
            word-break: break-all;
            line-height: 1.7;
            background: rgba(255,255,255,0.03);
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .copy-btn {
            width: 100%;
            margin-top: 0.75rem;
            padding: 0.6rem 1rem;
            background: rgba(34,197,94,0.12);
            border: 1px solid rgba(34,197,94,0.3);
            color: var(--success);
            font-family: var(--mono);
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            letter-spacing: 0.04em;
        }

        .copy-btn:hover {
            background: rgba(34,197,94,0.2);
        }

        .instruction {
            margin-top: 1.25rem;
            background: rgba(245,166,35,0.06);
            border: 1px solid rgba(245,166,35,0.2);
            border-radius: 10px;
            padding: 1rem;
        }

        .instruction p {
            font-size: 0.8rem;
            color: #c8a96e;
            line-height: 1.65;
        }

        .instruction code {
            font-family: var(--mono);
            background: rgba(245,166,35,0.12);
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
            font-size: 0.75rem;
            color: var(--accent);
        }

        .footer-note {
            text-align: center;
            margin-top: 1.5rem;
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--muted);
            letter-spacing: 0.05em;
        }

        .strength-bar {
            height: 3px;
            background: var(--border);
            border-radius: 99px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            border-radius: 99px;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }
    </style>
</head>
<body>

<div class="warning-banner">
    ⚠ Tool Sementara — Hapus file ini setelah selesai! Jangan dibiarkan di server produksi.
</div>

<div class="container">
    <div class="card">
        <div class="header">
            <div class="badge">Admin Utility</div>
            <h1>Generate <span>Bcrypt</span> Hash</h1>
            <p class="subtitle">Masukkan password baru untuk mendapatkan hash yang bisa dipasang di <code style="font-family:monospace;font-size:0.8em;color:#f5a623">index.php</code>.</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <span>⚠</span> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label>Password Baru</label>
                <div class="input-wrap">
                    <input type="password" id="pw" name="password" placeholder="Masukkan password baru..." required>
                    <button type="button" class="toggle-eye" onclick="toggleVis('pw', this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
            </div>

            <div class="form-group">
                <label>Konfirmasi Password</label>
                <div class="input-wrap">
                    <input type="password" id="pw2" name="password_confirm" placeholder="Ulangi password..." required>
                    <button type="button" class="toggle-eye" onclick="toggleVis('pw2', this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn">Generate Hash →</button>
        </form>

        <?php if ($hash): ?>
        <div class="result-box">
            <div class="result-label">Hash Berhasil Dibuat</div>
            <div class="hash-value" id="hash-output"><?= htmlspecialchars($hash) ?></div>
            <button class="copy-btn" onclick="copyHash()">[ Salin Hash ]</button>
        </div>

        <div class="instruction">
            <p>Buka file <code>index.php</code> dan ganti baris:</p>
            <p style="margin-top:0.5rem"><code>define('ADMIN_PASSWORD_HASH', '...');</code></p>
            <p style="margin-top:0.5rem">Ganti nilai string di dalamnya dengan hash di atas, lalu simpan. Password baru langsung aktif.</p>
        </div>
        <?php endif; ?>
    </div>

    <p class="footer-note">bcrypt · cost=10 · PHP password_hash()</p>
</div>

<script>
function toggleVis(id, btn) {
    const el = document.getElementById(id);
    const isPass = el.type === 'password';
    el.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass
        ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
        : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
}

function copyHash() {
    const text = document.getElementById('hash-output').innerText;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector('.copy-btn');
        btn.textContent = '[ ✓ Tersalin! ]';
        setTimeout(() => btn.textContent = '[ Salin Hash ]', 2000);
    });
}

// Password strength indicator
document.getElementById('pw').addEventListener('input', function() {
    const val = this.value;
    const fill = document.getElementById('strength-fill');
    let score = 0;
    if (val.length >= 8) score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const colors = ['#ef4444','#f97316','#eab308','#84cc16','#22c55e'];
    const widths = ['20%','40%','60%','80%','100%'];
    fill.style.width  = score ? widths[score-1] : '0%';
    fill.style.background = score ? colors[score-1] : 'transparent';
});
</script>

</body>
</html>
