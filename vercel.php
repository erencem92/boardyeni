<?php
// Vercel PHP Runtime Test Sayfası
header('Content-Type: text/html; charset=utf-8');

$phpVersion = phpversion();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor';
$serverName = $_SERVER['SERVER_NAME'] ?? 'Bilinmiyor';
$requestUri = $_SERVER['REQUEST_URI'] ?? 'Bilinmiyor';
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? 'Bilinmiyor';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'EVET (HTTPS)' : 'HAYIR (HTTP)';
$vercelEnv = getenv('VERCEL_ENV') ?: 'Tanımsız (yerel veya Vercel değil)';
$vercelUrl = getenv('VERCEL_URL') ?: 'Tanımsız';
$vercelRegion = getenv('VERCEL_REGION') ?: 'Tanımsız';

// Test: Matematiksel işlem (PHP çalışıyor mu?)
$mathTest = 0;
for ($i = 1; $i <= 1000; $i++) $mathTest += $i;

// Test: Dosya sistemi (yazma izni var mı?)
$tmpFile = sys_get_temp_dir() . '/vercel_test_' . uniqid();
$writeTest = @file_put_contents($tmpFile, 'test');
$readTest = $writeTest ? @file_get_contents($tmpFile) : false;
@unlink($tmpFile);

// Test: JSON
$jsonTest = json_encode(['ok' => true, 'time' => date('Y-m-d H:i:s')]);

// Test: Tarih/Saat
$now = date('Y-m-d H:i:s');
$timezone = date_default_timezone_get();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vercel PHP Test</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: system-ui, -apple-system, sans-serif; background: #0a0a0c; color: #f5f0e1; padding: 24px; margin: 0; }
  .container { max-width: 720px; margin: 0 auto; }
  h1 { color: #f0d67a; font-size: 26px; margin-bottom: 4px; }
  .subtitle { color: #8b95a1; margin-bottom: 24px; font-size: 14px; }
  .status { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; font-size: 18px; text-align: center; }
  .status.ok { background: rgba(46,204,113,0.15); border: 2px solid #2ecc71; color: #2ecc71; }
  .status.fail { background: rgba(231,76,60,0.15); border: 2px solid #e74c3c; color: #e74c3c; }
  table { width: 100%; border-collapse: collapse; background: #131318; border-radius: 12px; overflow: hidden; border: 1px solid #25303c; }
  th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #25303c; font-size: 14px; }
  th { background: #1a1a20; color: #8b95a1; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; }
  td:first-child { color: #8b95a1; width: 40%; }
  td:last-child { color: #fff; font-family: monospace; font-size: 13px; word-break: break-all; }
  tr:last-child td { border-bottom: none; }
  .ok-text { color: #2ecc71 !important; font-weight: 700; }
  .fail-text { color: #e74c3c !important; font-weight: 700; }
  .note { margin-top: 20px; padding: 14px 18px; background: rgba(240,185,11,0.1); border: 1px solid rgba(240,185,11,0.3); border-radius: 10px; font-size: 13px; color: #f0d67a; line-height: 1.6; }
</style>
</head>
<body>
<div class="container">
  <h1>🚀 Vercel PHP Runtime Test</h1>
  <p class="subtitle">Bu sayfa, Vercel'in PHP çalıştırıp çalıştıramadığını test eder.</p>

  <?php if ($phpVersion): ?>
    <div class="status ok">✅ PHP ÇALIŞIYOR! (v<?= htmlspecialchars($phpVersion) ?>)</div>
  <?php else: ?>
    <div class="status fail">❌ PHP ÇALIŞMIYOR!</div>
  <?php endif; ?>

  <table>
    <tr><th>Test</th><th>Sonuç</th></tr>
    <tr>
      <td>PHP Sürümü</td>
      <td class="ok-text"><?= htmlspecialchars($phpVersion) ?></td>
    </tr>
    <tr>
      <td>Sunucu Yazılımı</td>
      <td><?= htmlspecialchars($serverSoftware) ?></td>
    </tr>
    <tr>
      <td>Sunucu Adı</td>
      <td><?= htmlspecialchars($serverName) ?></td>
    </tr>
    <tr>
      <td>İstek URL</td>
      <td><?= htmlspecialchars($requestUri) ?></td>
    </tr>
    <tr>
      <td>Belge Kök Dizini</td>
      <td><?= htmlspecialchars($documentRoot) ?></td>
    </tr>
    <tr>
      <td>HTTPS Durumu</td>
      <td class="<?= str_contains($isHttps, 'EVET') ? 'ok-text' : '' ?>"><?= htmlspecialchars($isHttps) ?></td>
    </tr>
    <tr>
      <td>Vercel Ortamı</td>
      <td><?= htmlspecialchars($vercelEnv) ?></td>
    </tr>
    <tr>
      <td>Vercel URL</td>
      <td><?= htmlspecialchars($vercelUrl) ?></td>
    </tr>
    <tr>
      <td>Vercel Bölgesi</td>
      <td><?= htmlspecialchars($vercelRegion) ?></td>
    </tr>
    <tr>
      <td>Matematik Testi (1..1000)</td>
      <td class="<?= $mathTest === 500500 ? 'ok-text' : 'fail-text' ?>">
        <?= $mathTest ?> (beklenen: 500500)
      </td>
    </tr>
    <tr>
      <td>Dosya Yazma Testi</td>
      <td class="<?= $writeTest !== false ? 'ok-text' : 'fail-text' ?>">
        <?= $writeTest !== false ? '✅ Başarılı' : '❌ Başarısız (salt okunur dosya sistemi)' ?>
      </td>
    </tr>
    <tr>
      <td>Dosya Okuma Testi</td>
      <td class="<?= $readTest === 'test' ? 'ok-text' : 'fail-text' ?>">
        <?= $readTest === 'test' ? '✅ Başarılı' : '❌ Başarısız' ?>
      </td>
    </tr>
    <tr>
      <td>JSON Testi</td>
      <td class="<?= $jsonTest !== false ? 'ok-text' : 'fail-text' ?>">
        <?= htmlspecialchars($jsonTest) ?>
      </td>
    </tr>
    <tr>
      <td>Sunucu Zamanı</td>
      <td><?= htmlspecialchars($now) ?></td>
    </tr>
    <tr>
      <td>Saat Dilimi</td>
      <td><?= htmlspecialchars($timezone) ?></td>
    </tr>
  </table>

  <div class="note">
    <strong>📌 Not:</strong> Bu sayfa, Vercel'in PHP'yi çalıştırıp çalıştıramadığını gösterir. 
    Eğer "PHP ÇALIŞIYOR" yazıyorsa, Vercel'de PHP runtime'ı başarıyla kurulmuş demektir. 
    Eğer "PHP ÇALIŞMIYOR" veya sayfa boş geliyorsa, <code>vercel.json</code> dosyanı kontrol etmen gerekir.
  </div>
</div>
</body>
</html>