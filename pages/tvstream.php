<?php
/**
 * tvstream.php — HLS.js Proxy Player
 * Untuk channel yang streamingnya berformat M3U8 (HLS)
 * Usage: tvstream.php?ch=tvri
 *
 * Letakkan file ini di folder yang SAMA dengan tvdigital.php
 * Contoh: /public_html/tvstream.php
 */

// Daftar channel HLS yang didukung
$streams = [
  'tvri' => [
    'name'    => 'TVRI Nasional',
    'url'     => 'https://ott-balancer.tvri.go.id/live/eds/Nasional/hls/Nasional.m3u8',
    'referer' => 'https://klik.tvri.go.id/',
  ],
  'tvrisport' => [
    'name'    => 'TVRI Sport HD',
    'url'     => 'https://ott-balancer.tvri.go.id/live/eds/Sport/hls/Sport.m3u8',
    'referer' => 'https://klik.tvri.go.id/',
  ],
];

$ch   = strtolower(trim($_GET['ch'] ?? ''));
$data = $streams[$ch] ?? null;

// Izinkan di-embed dari domain manapun
header('X-Frame-Options: ALLOWALL');
header('Content-Security-Policy: frame-ancestors *');
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=UTF-8');

if (!$data) {
  http_response_code(404);
  echo '<!DOCTYPE html><html><body style="background:#000;color:#888;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0"><p>Channel tidak ditemukan.</p></body></html>';
  exit;
}

$name    = htmlspecialchars($data['name']);
$m3u8   = htmlspecialchars($data['url']);
$referer = htmlspecialchars($data['referer']);
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $name ?> — Live</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;height:100%;background:#000;overflow:hidden;}
video{width:100%;height:100%;display:block;object-fit:contain;background:#000;}
#err{
  display:none;position:absolute;inset:0;
  background:#060402;color:#5a4530;
  font-family:'Cinzel',Georgia,serif;font-size:11px;letter-spacing:.15em;
  text-transform:uppercase;
  flex-direction:column;align-items:center;justify-content:center;gap:12px;
}
#err.show{display:flex;}
#err p{color:#3a2a18;font-size:9px;letter-spacing:.1em;text-align:center;line-height:1.8;}
#load{
  position:absolute;inset:0;background:#030200;
  display:flex;align-items:center;justify-content:center;
}
#load-dot{
  width:8px;height:8px;border-radius:50%;
  background:#c9a23a;opacity:.4;
  animation:ld 1.2s ease-in-out infinite;
}
@keyframes ld{0%,100%{transform:scale(1);opacity:.4}50%{transform:scale(1.6);opacity:.9}}
</style>
</head>
<body>
<div id="load"><div id="load-dot"></div></div>
<div id="err">
  <span>⚡ Stream Tidak Dapat Dimuat</span>
  <p>
    <?= $name ?> membutuhkan akses langsung<br>
    ke server TVRI. Coba buka:<br>
    <a href="https://klik.tvri.go.id" target="_blank"
       style="color:#c9a23a;margin-top:4px;display:inline-block">
      klik.tvri.go.id
    </a>
  </p>
</div>
<video id="v" autoplay muted playsinline controls></video>

<!-- HLS.js — library open-source untuk play M3U8 di browser -->
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.15/dist/hls.min.js"></script>
<script>
(function(){
  var src = "<?= $m3u8 ?>";
  var v   = document.getElementById('v');
  var load= document.getElementById('load');
  var err = document.getElementById('err');

  function onReady(){
    load.style.display='none';
    v.play().catch(function(){
      // autoplay diblokir browser → mute lalu play
      v.muted=true; v.play();
    });
  }
  function onError(){
    load.style.display='none';
    err.classList.add('show');
  }

  if(Hls.isSupported()){
    var hls = new Hls({
      maxBufferLength: 15,
      maxBufferSize: 2*1000*1000,
      // TVRI kadang butuh warmup
      manifestLoadingTimeOut: 20000,
      levelLoadingTimeOut: 20000,
    });
    hls.loadSource(src);
    hls.attachMedia(v);
    hls.on(Hls.Events.MANIFEST_PARSED, onReady);
    hls.on(Hls.Events.ERROR, function(_,d){
      if(d.fatal) onError();
    });
  } else if(v.canPlayType('application/vnd.apple.mpegurl')){
    // Safari / iOS native HLS
    v.src = src;
    v.addEventListener('canplay', onReady);
    v.addEventListener('error',   onError);
  } else {
    onError();
  }
})();
</script>
</body>
</html>
