<?php
// ── Error config ─────────────────────────────────────────────────────────────
$error_code = isset($_GET['code']) ? intval($_GET['code']) : 404;
http_response_code($error_code);

$errors = [
    400 => ['title' => 'Bad Request',              'msg' => 'Permintaanmu agak membingungkan.'],
    401 => ['title' => 'Unauthorized',             'msg' => 'Kamu siapa? Coba login dulu ya, biar kami kenal.'],
    403 => ['title' => 'Forbidden',                'msg' => 'Hmm… sepertinya ini bukan kamarmu.'],
    404 => ['title' => 'Page Not Found',           'msg' => 'Sepertinya perjalananmu sedikit terlalu jauh.'],
    405 => ['title' => 'Method Not Allowed',       'msg' => 'Cara yang kamu pakai agak unik.'],
    408 => ['title' => 'Request Timeout',          'msg' => 'Kami sudah menunggu… Yuk coba lagi.'],
    429 => ['title' => 'Too Many Requests',        'msg' => 'Pelan-pelan dulu, biarkan aku napas sebentar.'],
    500 => ['title' => 'Internal Server Error',    'msg' => 'Lagi ada drama kecil di balik layar, sabar ya.'],
    502 => ['title' => 'Bad Gateway',              'msg' => 'Kami mencoba berbicara dengan server lain…'],
    503 => ['title' => 'Service Unavailable',      'msg' => 'Server istirahat sebentar, nanti balik lagi ya.'],
    504 => ['title' => 'Gateway Timeout',          'msg' => 'Kami sudah menunggu jawaban… tapi dia nggak kunjung datang.'],
    505 => ['title' => 'HTTP Version Not Supported','msg' => 'Versi yang kamu pakai agak jadul. Coba upgrade sedikit biar nyambung.'],
];

$info  = $errors[$error_code] ?? ['title' => 'Unknown Error', 'msg' => 'Terjadi kesalahan yang tidak diketahui.'];
$title = $info['title'];
$msg   = $info['msg'];

// ── GIF served as base64 inline — src tidak bisa dilihat/diakses langsung ────
$gif_path = dirname(__DIR__) . '/private/stickman.gif';

$gif_data_uri = '';
if (file_exists($gif_path) && is_readable($gif_path)) {
    $gif_raw      = file_get_contents($gif_path);
    $gif_base64   = base64_encode($gif_raw);
    $gif_data_uri = 'data:image/gif;base64,' . $gif_base64;
} else {
    $gif_data_uri = '';
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $error_code ?> — <?= htmlspecialchars($title) ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Space+Grotesk:wght@300;400;500;600&display=swap');

*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }

:root {
    --bg:       #ffffff;
    --panel:    #ffffff;
    --border:   #d8d8d0;
    --accent:   #111111;       /* hitam lebih pekat */
    --dim:      #555550;       /* label title — jauh lebih gelap */
    --muted:    #444440;       /* pesan — gelap & mudah dibaca */
    --wm:       #999990;       /* watermark — tetap subtle */
    --btn-dim:  #444440;       /* warna teks tombol secondary */
    --red:      #c42e18;
    --green:    #157a50;
}

body {
    background: var(--bg);
    color: var(--accent);
    font-family: 'Space Grotesk', sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

/* subtle dot grid */
body::after {
    content: '';
    position: fixed; inset: 0; pointer-events: none; z-index: 0;
    background-image: radial-gradient(circle, rgba(0,0,0,0.07) 1px, transparent 1px);
    background-size: 28px 28px;
}

/* scanline overlay */
body::before {
    content: '';
    position: fixed; inset: 0; pointer-events: none; z-index: 10;
    background: repeating-linear-gradient(
        0deg,
        transparent, transparent 3px,
        rgba(0,0,0,0.010) 3px, rgba(0,0,0,0.010) 4px
    );
}

/* ── Main wrap ── */
.wrap {
    position: relative; z-index: 1;
    display: flex; flex-direction: column;
    align-items: center; gap: 1.3rem;
    padding: 2.5rem 2rem 3rem;
}

/* ── GIF stage ── */
.stage {
    width: 180px; height: 180px;
    background: var(--panel);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
}
.stage img {
    width: 100%; height: 100%;
    object-fit: contain; display: block;
    -webkit-user-drag: none;
    user-select: none;
    pointer-events: none;
}
.stage canvas { display: block; }

/* ── Glitch error code ── */
.code {
    font-family: 'Share Tech Mono', monospace;
    font-size: clamp(5rem, 14vw, 8rem);
    line-height: 1; letter-spacing: -2px;
    color: var(--accent);
    position: relative;
}
.code::before {
    content: attr(d); position: absolute; inset: 0;
    color: var(--red);
    clip-path: polygon(0 0,100% 0,100% 45%,0 45%);
    transform: translate(-3px,2px); opacity: .45;
    animation: g1 4s infinite;
}
.code::after {
    content: attr(d); position: absolute; inset: 0;
    color: var(--green);
    clip-path: polygon(0 55%,100% 55%,100% 100%,0 100%);
    transform: translate(3px,-2px); opacity: .35;
    animation: g2 4s infinite;
}
@keyframes g1 {
    0%,88%,100%{ transform:translate(-3px,2px) }
    90%{ transform:translate(5px,-3px) skewX(6deg) }
    93%{ transform:translate(-2px,2px) }
    96%{ transform:translate(3px,2px) skewX(-3deg) }
}
@keyframes g2 {
    0%,88%,100%{ transform:translate(3px,-2px) }
    90%{ transform:translate(-5px,3px) skewX(-6deg) }
    93%{ transform:translate(2px,-2px) }
    96%{ transform:translate(-3px,-2px) skewX(3deg) }
}

/* ── Watermark elegan ── */
.watermark {
    display: flex;
    align-items: center;
    gap: 0;
    margin-top: -.6rem;
}
.watermark-line {
    height: 1px;
    width: 40px;
    background: var(--border);
    flex-shrink: 0;
}
.watermark-text {
    font-family: 'Share Tech Mono', monospace;
    font-size: .60rem;
    letter-spacing: .20em;
    text-transform: lowercase;
    color: var(--wm);
    padding: .28rem 1rem;
    position: relative;
    white-space: nowrap;
}
.watermark-text::before {
    content: '';
    position: absolute;
    inset: 0;
    border: 0.5px solid var(--border);
    border-radius: 999px;
}

/* ── Labels ── */
.etitle {
    font-size: .76rem;
    font-weight: 600;
    letter-spacing: .26em;
    text-transform: uppercase;
    color: var(--dim);       /* #555550 — kontras jelas */
    margin-top: -.2rem;
}

.emsg {
    font-size: .85rem;
    font-weight: 400;        /* naik dari 300 agar lebih terbaca */
    color: var(--muted);     /* #444440 — kontras kuat */
    text-align: center;
    max-width: 320px;
    line-height: 1.85;
    border-left: 2px solid var(--border);
    padding-left: 1rem;
}

/* ── Action buttons ── */
.actions {
    display: flex; gap: .8rem; flex-wrap: wrap; justify-content: center;
    margin-top: .2rem;
}
.btn {
    font-family: 'Share Tech Mono', monospace;
    font-size: .72rem; letter-spacing: .14em; text-transform: uppercase;
    text-decoration: none; padding: .6rem 1.4rem;
    border: 1px solid var(--border); background: transparent;
    color: var(--btn-dim);   /* #444440 — terbaca tanpa hover */
    cursor: pointer; transition: all .2s;
    position: relative; overflow: hidden;
}
.btn::before {
    content: ''; position: absolute; inset: 0;
    background: var(--accent);
    transform: translateX(-105%);
    transition: transform .2s ease; z-index: -1;
}
.btn:hover { color: var(--bg); border-color: var(--accent); }
.btn:hover::before { transform: translateX(0); }


/* ── Status bar ── */
.bar {
    position: fixed; bottom: 0; left: 0; right: 0;
    font-family: 'Share Tech Mono', monospace; font-size: .66rem;
    color: var(--dim);
    background: var(--panel);
    border-top: 0.5px solid var(--border);
    padding: .4rem 1.2rem;
    display: flex; justify-content: space-between; z-index: 5;
}
.dot {
    display: inline-block; width: 6px; height: 6px;
    border-radius: 50%; background: var(--red);
    margin-right: 6px; animation: blink 1.2s infinite;
}
@keyframes blink { 0%,100%{ opacity:1 } 50%{ opacity:.15 } }
</style>
</head>
<body>

<div class="wrap">

    <!-- ── Stickman GIF ── -->
    <div class="stage" id="stage">
        <?php if ($gif_data_uri): ?>
            <img
                src="<?= $gif_data_uri ?>"
                alt=""
                draggable="false"
                oncontextmenu="return false;"
            >
        <?php else: ?>
            <canvas id="sc" width="180" height="180"></canvas>
        <?php endif; ?>
    </div>

    <!-- ── Glitch error code ── -->
    <div class="code" d="<?= $error_code ?>"><?= $error_code ?></div>

    <!-- ── Watermark elegan ── -->
    <div class="watermark">
        <span class="watermark-line"></span>
        <span class="watermark-text">parokitulungagung.org</span>
        <span class="watermark-line"></span>
    </div>

    <div class="etitle"><?= htmlspecialchars($title) ?></div>
    <p class="emsg"><?= htmlspecialchars($msg) ?></p>

    <div class="actions">
        <a href="/" class="btn">Ke Beranda</a>
    </div>

</div>

<!-- ── Status bar ── -->
<div class="bar">
    <span><span class="dot"></span>HTTP <?= $error_code ?> — <?= htmlspecialchars($title) ?></span>
    <span id="ts"></span>
</div>

<script>
// ── Disable semua cara melihat/menyimpan gambar ──────────────────────────────
(function(){
    document.addEventListener('contextmenu', function(e){ e.preventDefault(); });
    document.addEventListener('dragstart',   function(e){ e.preventDefault(); });
    document.addEventListener('keydown', function(e){
        const k = e.key;
        if(
            (e.ctrlKey && ['s','u','S','U'].includes(k)) ||
            (e.ctrlKey && e.shiftKey && ['i','I','j','J','c','C'].includes(k)) ||
            k === 'F12'
        ){ e.preventDefault(); }
    });
    document.addEventListener('selectstart', function(e){ e.preventDefault(); });
})();

// ── Fallback stickman canvas (jika GIF tidak ditemukan) ─────────────────────
(function(){
    const cv = document.getElementById('sc');
    if(!cv) return;

    const ctx = cv.getContext('2d');
    const W=180, H=180, GY=148;
    const TAU=Math.PI*2;
    const COL='#111111';

    function seg(x0,y0,x1,y1,w,a){
        ctx.save(); ctx.globalAlpha=a??1;
        ctx.strokeStyle=COL; ctx.lineWidth=w;
        ctx.lineCap='round'; ctx.lineJoin='round';
        ctx.beginPath(); ctx.moveTo(x0,y0); ctx.lineTo(x1,y1);
        ctx.stroke(); ctx.restore();
    }
    function dot(x,y,r,a){
        ctx.save(); ctx.globalAlpha=a??1;
        ctx.fillStyle=COL; ctx.beginPath();
        ctx.arc(x,y,r,0,TAU); ctx.fill(); ctx.restore();
    }
    function ik(rx,ry,tx,ty,l1,l2,flip){
        const dx=tx-rx,dy=ty-ry;
        let d=Math.min(Math.hypot(dx,dy),l1+l2-0.5);
        const a1=Math.atan2(dy,dx);
        const cosA=Math.max(-1,Math.min(1,(l1*l1+d*d-l2*l2)/(2*l1*d)));
        const a2=Math.acos(cosA);
        const ang=flip?a1+a2:a1-a2;
        return [rx+Math.cos(ang)*l1,ry+Math.sin(ang)*l1];
    }

    const HR=8,TL=26,UL=22,FL=19,UA=15,FA=12,FT=8,LW=3.5;
    let posX=-30, t0=null;
    const SPD=0.9, CYCLE=900;

    function eio(t){ return t<.5?2*t*t:-1+(4-2*t)*t; }

    function buildFrame(phase){
        const a=phase*TAU, S=Math.sin;
        const bob=-Math.abs(S(a))*3+1;
        const hx=posX, hy=GY-UL-FL+bob;
        const tilt=0.06;
        const cx=hx+Math.sin(tilt)*TL, cy=hy-Math.cos(tilt)*TL;
        const nx=cx+Math.sin(tilt)*6, ny=cy-Math.cos(tilt)*6;
        const HW=6,SW=8;
        const lhx=hx-HW,lhy=hy,rhx=hx+HW,rhy=hy;
        const lsx=cx-SW,lsy=cy+2,rsx=cx+SW,rsy=cy+2;

        function footPos(ph,hipX){
            const inSwing=ph>0.5;
            let fx,fy,angle;
            if(inSwing){
                const t=(ph-.5)/.5,e=eio(t);
                fx=hipX+(-20+e*40);
                fy=GY-Math.sin(t*Math.PI)*14;
                angle=(1-t)*0.3;
            } else {
                const t=ph/.5;
                fx=hipX+(20-t*40);
                fy=GY; angle=t*-0.2;
            }
            return {fx,fy,angle};
        }

        const rF=footPos(phase,rhx), lF=footPos((phase+.5)%1,lhx);
        const [rkx,rky]=ik(rhx,rhy,rF.fx,rF.fy,UL,FL,false);
        const [lkx,lky]=ik(lhx,lhy,lF.fx,lF.fy,UL,FL,false);

        function fp(f){
            return {
                hx:f.fx-Math.cos(f.angle)*2, hy:f.fy+1,
                ax:f.fx, ay:f.fy,
                tx:f.fx+Math.cos(f.angle)*FT, ty:f.fy-Math.sin(f.angle)*1.5
            };
        }

        const armSwing=0.38,eb=0.5;
        const rAA=(lF.fx-rhx)/40*armSwing;
        const lAA=(rF.fx-lhx)/40*armSwing;
        return {
            hx,hy,cx,cy,nx,ny,lhx,lhy,rhx,rhy,lsx,lsy,rsx,rsy,
            lkx,lky,lF,lFP:fp(lF),
            rkx,rky,rF,rFP:fp(rF),
            rex:rsx+S(rAA)*UA,     rey:rsy+Math.cos(rAA)*UA,
            rwx:rsx+S(rAA)*UA+S(rAA+eb)*FA, rwy:rsy+Math.cos(rAA)*UA+Math.cos(rAA+eb)*FA,
            lex:lsx+S(lAA)*UA,     ley:lsy+Math.cos(lAA)*UA,
            lwx:lsx+S(lAA)*UA+S(lAA+eb)*FA, lwy:lsy+Math.cos(lAA)*UA+Math.cos(lAA+eb)*FA,
            rightFront:S(a)>0
        };
    }

    function drawFoot(fp,a){
        ctx.save(); ctx.globalAlpha=a;
        ctx.strokeStyle=COL; ctx.lineWidth=LW*.8;
        ctx.lineCap='round'; ctx.lineJoin='round';
        ctx.beginPath(); ctx.moveTo(fp.hx,fp.hy);
        ctx.lineTo(fp.ax,fp.ay); ctx.lineTo(fp.tx,fp.ty);
        ctx.stroke(); ctx.restore();
    }

    function draw(now){
        if(!t0) t0=now;
        const phase=((now-t0)%CYCLE)/CYCLE;
        posX+=SPD; if(posX>W+40) posX=-40;
        ctx.clearRect(0,0,W,H);
        const f=buildFrame(phase);
        const FA_=1.0,BA=0.28;
        const rA=f.rightFront?FA_:BA,lA=f.rightFront?BA:FA_;

        if(f.rightFront){
            seg(f.lsx,f.lsy,f.lex,f.ley,LW*.85,lA); seg(f.lex,f.ley,f.lwx,f.lwy,LW*.78,lA);
            seg(f.lhx,f.lhy,f.lkx,f.lky,LW+.5,lA); seg(f.lkx,f.lky,f.lF.fx,f.lF.fy,LW*.95,lA);
            drawFoot(f.lFP,lA);
        } else {
            seg(f.rsx,f.rsy,f.rex,f.rey,LW*.85,rA); seg(f.rex,f.rey,f.rwx,f.rwy,LW*.78,rA);
            seg(f.rhx,f.rhy,f.rkx,f.rky,LW+.5,rA); seg(f.rkx,f.rky,f.rF.fx,f.rF.fy,LW*.95,rA);
            drawFoot(f.rFP,rA);
        }

        seg(f.lhx,f.lhy,f.rhx,f.rhy,LW*.7,.75);
        seg(f.hx,f.hy,f.cx,f.cy,LW+.5,.95);
        seg(f.lsx,f.lsy,f.rsx,f.rsy,LW*.78,.88);
        seg(f.cx,f.cy,f.nx,f.ny,LW*.72,.90);
        dot(f.nx,f.ny-HR-1,HR,1);

        if(f.rightFront){
            seg(f.rsx,f.rsy,f.rex,f.rey,LW*.9,rA); seg(f.rex,f.rey,f.rwx,f.rwy,LW*.82,rA);
            seg(f.rhx,f.rhy,f.rkx,f.rky,LW+1,rA); seg(f.rkx,f.rky,f.rF.fx,f.rF.fy,LW,rA);
            drawFoot(f.rFP,rA);
        } else {
            seg(f.lsx,f.lsy,f.lex,f.ley,LW*.9,lA); seg(f.lex,f.ley,f.lwx,f.lwy,LW*.82,lA);
            seg(f.lhx,f.lhy,f.lkx,f.lky,LW+1,lA); seg(f.lkx,f.lky,f.lF.fx,f.lF.fy,LW,lA);
            drawFoot(f.lFP,lA);
        }

        requestAnimationFrame(draw);
    }
    requestAnimationFrame(draw);
})();

// ── Clock ────────────────────────────────────────────────────────────────────
(function tick(){
    const d=new Date(),p=n=>String(n).padStart(2,'0');
    document.getElementById('ts').textContent=p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds());
    setTimeout(tick,1000);
})();
</script>
</body>
</html>