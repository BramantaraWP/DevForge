<?php
/* =========================
   BEEROXY — One‑File PHP Web Proxy
   Author: midnight experiment mode
   ========================= */

error_reporting(0);
set_time_limit(0);

/* ---------- CONFIG ---------- */
$PROXY_API = "https://api.nekolabs.web.id/tls/free-proxy";
$SEARCH_ENGINE = "https://www.startpage.com/sp/search";

/* ---------- UTIL ---------- */
function getProxy(){
    global $PROXY_API;
    $json = @file_get_contents($PROXY_API);
    if(!$json) return null;
    $data = json_decode($json,true);
    if(!$data || !$data['success']) return null;

    foreach($data['result'] as $p){
        if($p['anonymity'] !== 'transparent'){
            return $p['ip'].":".$p['port'];
        }
    }
    return null;
}

function proxy_url($url){
    return $_SERVER['PHP_SELF']."?u=".urlencode($url);
}

function abs_url($base,$rel){
    if(!$rel) return $base;
    if(parse_url($rel,PHP_URL_SCHEME)) return $rel;
    if($rel[0]==='/') return $base.$rel;
    return rtrim($base,'/').'/'.$rel;
}

/* ---------- UI MODE ---------- */
if(!isset($_GET['u']) && !isset($_GET['q'])){
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Beeroxy Secure Web Proxy</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{
    margin:0;
    height:100vh;
    background:radial-gradient(circle at top,#1e293b,#020617);
    color:#e5e7eb;
    font-family:system-ui;
    display:flex;
    justify-content:center;
    align-items:center;
}
.card{
    width:420px;
    background:#020617;
    border:1px solid #1e293b;
    border-radius:14px;
    padding:22px;
    box-shadow:0 0 40px rgba(0,0,0,.4);
}
h1{
    margin:0 0 8px;
    text-align:center;
}
p{
    margin:0 0 16px;
    text-align:center;
    color:#94a3b8;
    font-size:14px;
}
input{
    width:100%;
    padding:14px;
    border-radius:10px;
    border:none;
    margin-bottom:12px;
    font-size:15px;
    outline:none;
}
button{
    width:100%;
    padding:14px;
    border-radius:10px;
    border:none;
    background:#2563eb;
    color:#fff;
    font-size:15px;
    cursor:pointer;
}
button:hover{opacity:.9}
.footer{
    margin-top:12px;
    font-size:12px;
    color:#64748b;
    text-align:center;
}
</style>
</head>
<body>
<div class="card">
    <h1> Beeroxy</h1>
    <p>Secure • Anonymous • One‑File Proxy</p>

    <!-- URL -->
    <form method="get">
        <input name="u" placeholder="Paste URL (https://example.com)">
        <button>Open via Proxy</button>
    </form>

    <!-- SEARCH -->
    <form method="get">
        <input name="q" placeholder="Search keyword (Startpage)">
        <button>Search Privately</button>
    </form>

    <div class="footer">
        Traffic routed via public proxy • Experiment only
    </div>
</div>
</body>
</html>
<?php
exit;
}

/* ---------- SEARCH MODE ---------- */
if(isset($_GET['q'])){
    $q = trim($_GET['q']);
    if($q){
        $url = $SEARCH_ENGINE."?query=".urlencode($q);
        header("Location: ".proxy_url($url));
        exit;
    }
}

/* ---------- FETCH MODE ---------- */
$url = urldecode($_GET['u']);
if(!preg_match('#^https?://#',$url)){
    $url = 'https://'.$url;
}

$parsed = parse_url($url);
$base = $parsed['scheme'].'://'.$parsed['host'];

$proxy = getProxy();

$ch = curl_init($url);
curl_setopt_array($ch,[
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0 Beeroxy',
    CURLOPT_TIMEOUT => 25
]);

if($proxy){
    curl_setopt($ch,CURLOPT_PROXY,$proxy);
}

$html = curl_exec($ch);
$type = curl_getinfo($ch,CURLINFO_CONTENT_TYPE);
curl_close($ch);

/* ---------- REWRITE ---------- */
if($html && strpos($type,'text/html')!==false){

    // rewrite links
    $html = preg_replace_callback(
        '#(href|src|action)=["\'](.*?)["\']#i',
        function($m) use ($base){
            $abs = abs_url($base,$m[2]);
            return $m[1].'="'.proxy_url($abs).'"';
        },
        $html
    );

    // inject control bar + JS interceptor
    $inject = <<<HTML
<style>
#beeroxy-bar{
    position:fixed;
    top:0;left:0;right:0;
    height:42px;
    background:#020617;
    border-bottom:1px solid #1e293b;
    z-index:999999;
    display:flex;
    align-items:center;
    padding:0 10px;
    gap:8px;
}
#beeroxy-bar input{
    flex:1;
    padding:6px 10px;
    border-radius:8px;
    border:none;
}
#beeroxy-bar button{
    padding:6px 12px;
    border-radius:8px;
    border:none;
    background:#2563eb;
    color:#fff;
}
body{margin-top:42px!important}
</style>

<div id="beeroxy-bar">
    <strong>Beeroxy</strong>
    <input id="bx-url" placeholder="URL or keyword">
    <button onclick="go()">Go</button>
</div>

<script>
(function(){
    const wrap = u=>{
        if(!u) return u;
        if(u.startsWith('http')) return '?u='+encodeURIComponent(u);
        return '?u='+encodeURIComponent(location.origin+u);
    };

    // intercept fetch
    const _fetch = window.fetch;
    window.fetch = (u,o)=>_fetch(wrap(u),o);

    // intercept xhr
    const open = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(m,u){
        return open.call(this,m,wrap(u),true);
    };

    window.go = ()=>{
        const v = document.getElementById('bx-url').value.trim();
        if(!v) return;
        if(v.includes('.') || v.startsWith('http')){
            location.href='?u='+encodeURIComponent(v);
        }else{
            location.href='?u='+encodeURIComponent('{$SEARCH_ENGINE}?query='+encodeURIComponent(v));
        }
    };
})();
</script>
HTML;

    $html = str_ireplace('</head>',$inject.'</head>',$html);
}

/* ---------- OUTPUT ---------- */
header("Content-Type: ".$type);
echo $html ?: "Beeroxy failed. Refresh to rotate proxy.";
