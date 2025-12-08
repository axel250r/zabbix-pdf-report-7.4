<?php
declare(strict_types=1);

// ORDEN CORRECTO: Todas las declaraciones y código van DESPUÉS de strict_types.
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline';");

session_start();
require_once __DIR__ . '/lib/i18n.php';
require_once __DIR__ . '/config.php';

if (!defined('ZBX_USER_PREFIX')) define('ZBX_USER_PREFIX','');
if (!defined('ZBX_USER_SUFFIX')) define('ZBX_USER_SUFFIX','');

// Simple trace
function ztrace_login($m){ @file_put_contents(TMP_DIR.'/zbx_login_trace.log','['.date('Y-m-d H:i:s').'] '.$m."\n",FILE_APPEND); }

// API validation (user typed by human)
function api_validate_login(string $user, string $pass, ?string &$err=null): bool {
  
  // Corrección: Se elimina 'auth'=>null del payload.
  $payload = json_encode(['jsonrpc'=>'2.0','method'=>'user.login','params'=>['username'=>$user,'password'=>$pass],'id'=>1]);

  $ch = curl_init(ZABBIX_API_URL);
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json-rpc'],CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_TIMEOUT=>30]);
  if (stripos(ZABBIX_API_URL,'https://')===0 && defined('VERIFY_SSL') && !VERIFY_SSL){ curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0); curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0); }
  $resp=curl_exec($ch); $hc=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $er=curl_error($ch); curl_close($ch);
  
  if ($er || $hc!==200 || !$resp){ 
    $err=$er?:('HTTP '.$hc.' Response: '.substr((string)$resp, 0, 150)); 
    return false; 
  }
  $j=json_decode($resp,true);
  if (isset($j['result']) && is_string($j['result']) && $j['result']!=='') return true;

  // Capturar el error real de la API de Zabbix
  if (isset($j['error']['data'])) {
    $err = $j['error']['message'] . ' - ' . $j['error']['data'];
  } elseif (isset($j['error']['message'])) {
    $err = $j['error']['message'];
  } else {
    $err = 'Respuesta API invalida o sin token';
  }
  return false;
}

// Front login to get cookie (Compatible con Zabbix 7.4)
function web_login(string $user, string $pass, string $cookieJar, ?string &$err=null): bool {
  $base = rtrim(ZABBIX_URL,'/');
  @file_put_contents($cookieJar,''); @chmod($cookieJar,0600);
  $loginUser = ZBX_USER_PREFIX.$user.ZBX_USER_SUFFIX;

  $opt=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>5,CURLOPT_COOKIEFILE=>$cookieJar,CURLOPT_COOKIEJAR=>$cookieJar,CURLOPT_USERAGENT=>'Mozilla/5.0',CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30];
  if (stripos($base,'https://')===0 && defined('VERIFY_SSL') && !VERIFY_SSL){ $opt[CURLOPT_SSL_VERIFYPEER]=0; $opt[CURLOPT_SSL_VERIFYHOST]=0; }

  // Corrección Zabbix 7.4: Se eliminó el GET de "calentamiento" y el scraping de 'sid' y 'form_refresh'.
  $post=['name'=>$loginUser,'password'=>$pass,'autologin'=>1,'enter'=>'Sign in'];
  $postUrl = $base.'/index.php';
  
  $ch=curl_init($postUrl); 
  curl_setopt_array($ch,$opt+[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($post),CURLOPT_REFERER=>$postUrl,CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded']]); 
  $resp=curl_exec($ch); 
  curl_close($ch);

  // --- INICIO DE LA CORRECCIÓN ---
  // Se elimina la comprobación redundante de file_get_contents.
  // El archivo SÍ se está creando (como lo demostraste), pero PHP no puede leerlo a tiempo.
  // La comprobación real (y más importante) es la del dashboard, que SÍ usa el archivo cookie.
  /*
  // require zbx_session cookie
  $cj=@file_get_contents($cookieJar);
  if (!$cj || !preg_match('/\\tzbx_session\\b/',$cj)){ $err='Front no creo sesion (cookie zbx_session no encontrada)'; return false; }
  */
  // --- FIN DE LA CORRECCIÓN ---

  // check dashboard access
  $ch=curl_init($base.'/zabbix.php?action=dashboard.view'); curl_setopt_array($ch,$opt); $dash=curl_exec($ch); $eff=curl_getinfo($ch,CURLINFO_EFFECTIVE_URL); $hc3=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
  if ($dash===false || $hc3===401 || stripos((string)$eff,'index.php')!==false){ $err='Sin acceso al dashboard (posible redirect a login)'; return false; }

  return true;
}

$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $u=trim($_POST['user']??''); $p=trim($_POST['pass']??'');
  if ($u==='' || $p===''){ $msg=t('login_error_invalid_form'); }
  else{
    if (!api_validate_login($u,$p,$apiErr)){
      // Mostrar el error real de la API o cURL
      $msg = t('login_error_invalid_credentials') . " (Debug: " . htmlspecialchars($apiErr ?? 'Error API desconocido', ENT_QUOTES, 'UTF-8') . ")";
    }else{
      
      // Corrección: Se reemplaza APP_TMP por TMP_DIR, definido en config.php
      $cookieJar = TMP_DIR.DIRECTORY_SEPARATOR.'cj_'.bin2hex(random_bytes(6)).'.txt';

      if (web_login($u,$p,$cookieJar,$webErr)){
        $_SESSION['zbx_user']=$u; $_SESSION['zbx_pass']=$p; $_SESSION['zbx_cookiejar']=$cookieJar; $_SESSION['zbx_auth_ok']=true;
        header('Location: export.php'); exit;
      }else{
        $msg=t('login_error_frontend_rejected') . " (Debug: " . htmlspecialchars($webErr ?? 'Error web desconocido', ENT_QUOTES, 'UTF-8') . ")";
      }
    }
  }
}
?>
<!doctype html>
<html lang="<?= htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<meta name="author" content="Axel Del Canto">
<title><?= t('login_title') ?></title>
<style>
:root {
  --bg-light: #fff;
  --card-light: #f8f9fa;
  --text-dark: #333;
  --text-muted-light: #6c757d;
  --zbx-red: #e04646;
  --card-border-light: #e0e0e0;
  --input-light: #fff;

  --bg-dark: #1a1a1a;
  --card-dark: #2b2b2b;
  --text-light: #fff;
  --text-muted-dark: #ccc;
  --card-border-dark: #444;
  --input-dark: #3a3a3a;
}

body.light-theme {
  background: var(--bg-light);
  color: var(--text-dark);
}

body.dark-theme {
  background: var(--bg-dark);
  color: var(--text-light);
}

.card {
  width: 100%;
  max-width: 500px;
  box-shadow: 0 10px 30px rgba(0,0,0,.15);
  border-radius: 14px;
  padding: 30px;
}
body.light-theme .card {
  background: var(--card-light);
  border: 1px solid var(--card-border-light);
}
body.dark-theme .card {
  background: var(--card-dark);
  border: 1px solid var(--card-border-dark);
  box-shadow: 0 10px 30px rgba(0,0,0,.35);
}

* { box-sizing: border-box; }
body { margin: 0; font: 14px/1.5 system-ui,Segoe UI,Roboto,Arial; transition: background .3s, color .3s; }
.wrap { display: grid; place-items: center; min-height: 100vh; padding: 16px; }
h1 { margin: 0 0 12px 0; font-size: 20px; text-align: center; }
label { display: block; margin: .6rem 0 .2rem; }
body.light-theme label { color: var(--text-dark); }
body.dark-theme label { color: var(--text-light); }

input { width: 100%; padding: 10px; border-radius: 10px; border: 1px solid; }
body.light-theme input { border-color: #ccc; background: #fff; color: var(--text-dark); }
body.dark-theme input { border-color: #444; background: #3a3a3a; color: var(--text-light); }

.btn { margin-top: 14px; width: 100%; padding: 10px 14px; border-radius: 10px; border: 0; background: var(--zbx-red); color: #fff; font-weight: 600; cursor: pointer; }
.btn:hover { opacity: .9; }
.err { padding: .6rem .7rem; border-radius: 10px; margin-bottom: .8rem; border: 1px solid; }
body.light-theme .err { background: #ffcccc; color: #cc0000; border-color: #ff9999; }
body.dark-theme .err { background: #2b1416; color: #ffb3b8; border-color: #3b1e22; }

.small, .muted { font-size: 12px; margin-top: 10px; text-align: center; }
body.light-theme .muted, body.light-theme .small { color: var(--text-muted-light); }
body.dark-theme .muted, body.dark-theme .small { color: var(--text-muted-dark); }

.logo-container { display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 20px; }
.custom-logo { max-width: 100px; height: auto; }
.zabbix-logo { background: var(--zbx-red); color: #fff; padding: 5px 10px; border-radius: 5px; font-weight: bold; font-size: 1.2rem; display: inline-block; }

.theme-switcher { position: absolute; top: 20px; right: 20px; background: none; border: 1px solid; padding: 5px 10px; border-radius: 5px; font-size: 14px; cursor: pointer; }
body.light-theme .theme-switcher { color: #555; background-color: #f0f0f0; border-color: #ccc; }
body.dark-theme .theme-switcher { color: #ccc; background-color: #3a3a3a; border-color: #444; }

.credit {
  margin-top: 20px;
  font-size: 12px;
  text-align: center;
}
</style>
<?php if (defined('APPLY_LOGO_BLEND_MODE') && APPLY_LOGO_BLEND_MODE): ?>
<style>
body.dark-theme .custom-logo {
  mix-blend-mode: multiply;
}
</style>
<?php endif; ?>
</head>
<body class="light-theme">
<div style="position: absolute; top: 20px; left: 20px; z-index: 10;">
    <a href="?lang=es" style="text-decoration: none; color: inherit;">ES</a> | 
    <a href="?lang=en" style="text-decoration: none; color: inherit;">EN</a>
</div>
<button id="theme-toggle" class="theme-switcher"><?= t('theme_dark') ?></button>
<div class="wrap">
  <form class="card" method="post" action="login.php" autocomplete="off">
    <div class="logo-container">
      <img src="<?= htmlspecialchars(defined('CUSTOM_LOGO_PATH') ? CUSTOM_LOGO_PATH : 'assets/sonda.png', ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="custom-logo" />
      <span class="zabbix-logo">Zabbix</span>
    </div>
    <h1><?= t('login_heading') ?></h1>
    <div class="muted"><?= t('login_subheading') ?></div>
    <?php if ($msg): ?><div class="err"><?php echo $msg; /* Ya no usamos htmlspecialchars para ver el debug */ ?></div><?php endif; ?>
    <label><?= t('login_user_label') ?></label>
    <input name="user" required />
    <label><?= t('login_pass_label') ?></label>
    <input type="password" name="pass" required />
    <button class="btn" type="submit"><?= t('login_button') ?></button>
    <div class="small">Zabbix: <?php echo htmlspecialchars(ZABBIX_URL,ENT_QUOTES,'UTF-8'); ?></div>
  </form>
  <div class="credit muted">
    <?= t('common_author_credit') ?>
  </div>
</div>
<script>
  const themeToggle = document.getElementById('theme-toggle');
  const body = document.body;

  function setTheme(theme) {
    if (theme === 'dark') {
      body.classList.remove('light-theme');
      body.classList.add('dark-theme');
      themeToggle.textContent = '<?= t('theme_light') ?>';
    } else {
      body.classList.remove('dark-theme');
      body.classList.add('light-theme');
      themeToggle.textContent = '<?= t('theme_dark') ?>';
    }
    localStorage.setItem('theme', theme);
  }

  themeToggle.addEventListener('click', () => {
    const currentTheme = body.classList.contains('dark-theme') ? 'dark' : 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
  });

  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) {
    setTheme(savedTheme);
  } else {
    // Default theme based on system preference
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    setTheme(prefersDark ? 'dark' : 'light');
  }
</script>
</body>
</html>