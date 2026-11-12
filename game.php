<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../app/Core/Session.php';
require_once __DIR__ . '/../app/Core/DemoBalance.php';

$slug = $_GET['slug'] ?? '';
$user = \Session::user();

if ($user) {
    \DemoBalance::ensure((int)$user['id']);
}

$supported = ['roulette', 'dice', 'barbut', 'slot', 'blackjack', 'mines', 'plinko', 'crash'];
$isSupported = in_array($slug, $supported, true);
?>
<!DOCTYPE html>
<html lang="<?= e(\Lang::current()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(ucfirst($slug)) ?> — <?= e(\Settings::get('site_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/app.css">
<style>
  .game-layout { display: flex; height: calc(100vh - 73px); overflow: hidden; }
  .game-canvas-area { flex: 1; position: relative; display: flex; justify-content: center; align-items: center; background: radial-gradient(circle at center, #1a1a20 0%, #050506 100%); }
  .game-panel { width: 400px; max-width: 45vw; background: #0a0a0c; border-left: 1px solid rgba(212, 175, 55, 0.15); padding: 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; }
  
  .panel-title { font-family: 'Montserrat', sans-serif; font-size: 18px; font-weight: 800; color: var(--gold-light); margin-bottom: 10px; text-align: center; text-transform: uppercase; letter-spacing: 1px; }
  
  .top-hud { position: absolute; top: 20px; left: 20px; right: 20px; display: flex; justify-content: space-between; pointer-events: none; z-index: 10; }
  .hud-pill { background: rgba(10,10,12,0.85); border: 1px solid var(--border-gold); padding: 10px 24px; border-radius: 100px; font-weight: 700; color: var(--gold-light); backdrop-filter: blur(10px); font-family: var(--font-body); }
  
  .game-box { background: linear-gradient(160deg, #1a1a20, #0a0a0c); border: 1px solid rgba(212,175,55,0.2); border-radius: 24px; padding: 32px; width: 100%; max-width: 500px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.8); }
  .game-box h2 { color: var(--gold-light); margin-bottom: 20px; font-size: 24px; font-family: var(--font-heading); text-transform: uppercase; }
  
  .input-group { margin-bottom: 20px; text-align: left; }
  .input-group label { display: block; margin-bottom: 8px; font-size: 13px; color: var(--text-muted); font-family: var(--font-body); }
  .input-group input, .input-group select { width: 100%; padding: 14px; background: #050506; border: 1px solid rgba(212,175,55,0.2); border-radius: 100px; color: #fff; font-size: 16px; font-family: var(--font-body); }
  .input-group input:focus, .input-group select:focus { outline: none; border-color: var(--gold); }
  
  .spin-btn { width: 100%; padding: 18px; background: linear-gradient(135deg, var(--gold-light), var(--gold)); color: #000; border: none; border-radius: 100px; font-weight: 800; font-size: 18px; cursor: pointer; box-shadow: 0 5px 20px rgba(212,175,55,0.4); transition: 0.2s; font-family: var(--font-body); text-transform: uppercase; letter-spacing: 1px; }
  .spin-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(212,175,55,0.6); }
  .spin-btn:disabled { opacity: 0.5; cursor: not-allowed; }
  
  .chips-row { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
  .p-chip { width: 45px; height: 45px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: 700; cursor: pointer; border: 2px dashed rgba(255,255,255,0.3); transition: 0.2s; font-family: var(--font-body); }
  .p-chip:hover { transform: scale(1.1); }
  .p-chip.active { border: 2px solid var(--gold-light); box-shadow: 0 0 15px var(--gold); transform: scale(1.1); }
  .p-chip[data-val="1"] { background: #ddd; color: #000; }
  .p-chip[data-val="5"] { background: #e74c3c; color: #fff; }
  .p-chip[data-val="10"] { background: #3498db; color: #fff; }
  .p-chip[data-val="100"] { background: #2ecc71; color: #fff; }
  .p-chip[data-val="500"] { background: #9b59b6; color: #fff; }

  /* Rulet Masası */
  .is-roulette .game-panel { width: 560px; max-width: 52vw; }
  .roulette-board-wrap { position: relative; width: 100%; padding-top: 35%; }
  .roulette-board { position: absolute; inset: 0; display: grid; grid-template-columns: 8% repeat(12, 1fr) 8%; grid-template-rows: repeat(3, 1fr) 17% 17%; gap: 3px; background: #0f5f3f; padding: 6px; border-radius: 10px; border: 2px solid var(--gold-dark, #8a6d1f); box-shadow: inset 0 0 30px rgba(0,0,0,.45); }
  .roulette-board.locked { pointer-events: none; opacity: .85; }
  .r-cell { background: #111; color: #fff; display: flex; justify-content: center; align-items: center; font-weight: 700; font-size: 13px; border-radius: 4px; cursor: pointer; border: 1px solid rgba(255,255,255,.06); position: relative; font-family: var(--font-body); user-select: none; }
  .r-cell:hover { border-color: var(--gold-light); }
  .r-cell.red { background: #a3281e; }
  .r-cell.green { background: #1f7a3d; }
  .r-cell.outside, .r-cell.col-bet, .r-cell.dozen { background: #14301f; font-size: 11px; letter-spacing: .04em; border-color: rgba(212,175,55,.25); }
  .r-cell.win { box-shadow: 0 0 0 2px #f0c850, 0 0 14px 3px rgba(240,200,80,.8); z-index: 3; }

  /* Para koyma noktaları (split / sokak / köşe / altılı / sıfır) */
  .spot { position: absolute; z-index: 6; cursor: pointer; display: flex; align-items: center; justify-content: center; }
  .spot::before { content: ''; width: 9px; height: 9px; border-radius: 50%; border: 1.5px dashed rgba(240,200,80,0); transition: .12s; }
  .spot:hover::before { width: 17px; height: 17px; border-color: #f0c850; background: rgba(240,200,80,.28); }
  .split-h { width: 14px; height: 62%; right: -9px; top: 19%; }
  .split-v { width: 62%; height: 14px; left: 19%; bottom: -9px; }
  .split-0 { width: 14px; height: 62%; left: -9px; top: 19%; }
  .corner { width: 16px; height: 16px; right: -10px; bottom: -10px; z-index: 7; }
  .street { width: 62%; height: 16px; left: 19%; bottom: -10px; }
  .street::before { width: 12px; height: 12px; border-color: rgba(240,200,80,.4); }
  .six-line { width: 18px; height: 18px; right: -11px; bottom: -11px; z-index: 8; }

  /* Masadaki jetonlar */
  .chip-mark { position: absolute; left: 50%; top: 50%; transform: translate(-50%,-50%); min-width: 26px; height: 26px; padding: 0 4px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font: 800 10px/1 var(--font-body, Inter, sans-serif); color: #fff; text-shadow: 0 1px 1px rgba(0,0,0,.6); background: var(--c, #ddd); border: 2px dashed rgba(255,255,255,.85); box-shadow: 0 0 0 2px var(--c, #ddd), 0 3px 7px rgba(0,0,0,.75), inset 0 0 7px rgba(0,0,0,.35); pointer-events: none; z-index: 12; }
  .chip-mark.c1 { --c: #d8d8d8; color: #111; text-shadow: none; }
  .chip-mark.c5 { --c: #e74c3c; } .chip-mark.c10 { --c: #3498db; } .chip-mark.c100 { --c: #2ecc71; } .chip-mark.c500 { --c: #9b59b6; }

  #wheel3d { position: absolute; inset: 0; }
  #wheel3d canvas { display: block; width: 100%; height: 100%; }
  #hash { position: absolute; left: 16px; bottom: 12px; font: 11px ui-monospace, monospace; color: #8f8460; max-width: 70%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; z-index: 10; }
  #error { position: absolute; left: 50%; bottom: 40px; transform: translateX(-50%); max-width: 90%; background: #7a0d12; color: #fff; border: 1px solid #ff5a5f; padding: 10px 16px; border-radius: 10px; font-weight: 600; display: none; z-index: 12; }
  @media (max-width: 1000px) { .is-roulette .game-panel { width: 100%; max-width: none; } }

  /* Mines */
  .mine-cell { aspect-ratio: 1; background: #0f1419; border: 2px solid #25303c; border-radius: 8px; display: flex; justify-content: center; align-items: center; font-size: 30px; cursor: pointer; transition: 0.2s; user-select: none; }
  .mine-cell:hover:not(.revealed) { border-color: #f0b90b; transform: scale(1.05); }
  .mine-cell.revealed { background: #1a2a1f; cursor: default; transform: scale(0.95); }
  .mine-cell.mine { background: #5a1a1a; border-color: #e74c3c; }
  .mine-cell.gem { background: #1a3a2a; border-color: #2ecc71; }

  /* Plinko */
  .plinko-canvas { width: 100%; height: 400px; background: #0a0a0a; border-radius: 12px; border: 2px solid #333; display: block; }

  /* Blackjack */
  .bj-table { background: #0b3a1a; border-radius: 16px; padding: 20px; border: 4px solid var(--gold-dark); box-shadow: 0 15px 50px rgba(0,0,0,0.9); text-align: center; width: 100%; max-width: 600px;}
  .bj-hand { display: flex; justify-content: center; gap: 8px; margin: 15px 0; min-height: 100px;}
  .bj-card { width: 70px; height: 100px; background: white; border-radius: 8px; display: flex; flex-direction: column; justify-content: space-between; padding: 8px; font-weight: bold; box-shadow: 0 5px 15px rgba(0,0,0,0.5); position: relative;}
  .bj-card.red { color: #e74c3c; }
  .bj-card.black { color: #000; }
  .bj-card.hidden { background: linear-gradient(135deg, #1a212b, #0f1419); color: transparent; border: 2px solid #6b5420;}
  .bj-card .rank { font-size: 22px; line-height: 1;}
  .bj-card .suit { font-size: 30px; line-height: 1; text-align: right;}
  .bj-score { font-size: 22px; font-weight: bold; color: #f0b90b;}
  .bj-msg { font-size: 22px; font-weight: bold; height: 40px; margin: 15px 0;}
  .bj-actions { display: flex; gap: 10px; justify-content: center; margin-top: 15px;}
  .bj-actions button { padding: 12px 24px; font-weight: bold; font-size: 16px;}

  /* Slot */
  .slot-machine { background: #1a212b; border-radius: 16px; padding: 30px; border: 4px solid var(--gold); box-shadow: 0 15px 50px rgba(0,0,0,0.9), inset 0 0 30px rgba(0,0,0,0.5); text-align: center;}
  .slot-reels { display: flex; justify-content: center; gap: 10px; background: #0a0a0a; padding: 15px; border-radius: 12px; border: 3px solid #333; margin-bottom: 20px;}
  .reel { width: 100px; height: 100px; background: white; border-radius: 10px; display: flex; justify-content: center; align-items: center; font-size: 60px; box-shadow: inset 0 0 20px rgba(0,0,0,0.3); overflow: hidden;}
  .reel.spinning { animation: spin 0.1s linear infinite;}
  @keyframes spin { 0% { transform: translateY(-10px); } 100% { transform: translateY(10px); } }
  .slot-msg { font-size: 22px; font-weight: bold; height: 40px; margin-bottom: 15px;}

  @media (max-width: 1000px) { .game-layout { flex-direction: column; height: auto; overflow: visible; } .game-panel { width: 100%; max-width: none; border-left: none; border-top: 1px solid rgba(212,175,55,0.15); } .game-canvas-area { min-height: 400px; } .roulette-board-wrap { padding-top: 46%; } }
</style>
</head>
<body>
<?php include __DIR__ . '/_header.php'; ?>

<?php if (!$isSupported): ?>
  <div class="container"><div class="alert error">Bu oyun henüz aktif değil.</div></div>
<?php elseif (!$user): ?>
  <div class="container"><div class="alert error">Oynamak için <a href="/login.php" style="color:var(--gold)">giriş yap</a>.</div></div>
<?php else: ?>

  <!-- RULET -->
  <?php if ($slug === 'roulette'): ?>
  <div class="game-layout is-roulette">
    <div class="game-canvas-area">
      <div class="top-hud">
        <div class="hud-pill">Bakiye: <span id="balance">0.00</span></div>
        <div class="hud-pill" id="result">Bahislerinizi yapın</div>
      </div>
      <div id="wheel3d"></div>
      <div id="hash"></div>
      <div id="error"></div>
    </div>
    <aside class="game-panel">
      <div class="panel-title">Rulet Bahisleri</div>
      <div class="chips-row" id="chips">
        <div class="p-chip active" data-val="1">1</div>
        <div class="p-chip" data-val="5">5</div>
        <div class="p-chip" data-val="10">10</div>
        <div class="p-chip" data-val="100">100</div>
        <div class="p-chip" data-val="500">500</div>
      </div>
      <div class="roulette-board-wrap">
        <div class="roulette-board" id="board">
          <div class="r-cell green" data-bet="straight_0" style="grid-row: 1 / span 3; grid-column: 1;">0</div>
          <div id="number-cells" style="display: contents;"></div>
          <div class="r-cell col-bet" data-bet="column_3" style="grid-column: 14; grid-row: 1;">2:1</div>
          <div class="r-cell col-bet" data-bet="column_2" style="grid-column: 14; grid-row: 2;">2:1</div>
          <div class="r-cell col-bet" data-bet="column_1" style="grid-column: 14; grid-row: 3;">2:1</div>
          <div class="r-cell dozen" data-bet="dozen_1" style="grid-column: 2 / span 4; grid-row: 4;">1st 12</div>
          <div class="r-cell dozen" data-bet="dozen_2" style="grid-column: 6 / span 4; grid-row: 4;">2nd 12</div>
          <div class="r-cell dozen" data-bet="dozen_3" style="grid-column: 10 / span 4; grid-row: 4;">3rd 12</div>
          <div class="r-cell outside" data-bet="low" style="grid-column: 2 / span 2; grid-row: 5;">1-18</div>
          <div class="r-cell outside" data-bet="even" style="grid-column: 4 / span 2; grid-row: 5;">ÇİFT</div>
          <div class="r-cell red" data-bet="red" style="grid-column: 6 / span 2; grid-row: 5;">KIRMIZI</div>
          <div class="r-cell black" data-bet="black" style="grid-column: 8 / span 2; grid-row: 5;">SİYAH</div>
          <div class="r-cell outside" data-bet="odd" style="grid-column: 10 / span 2; grid-row: 5;">TEK</div>
          <div class="r-cell outside" data-bet="high" style="grid-column: 12 / span 2; grid-row: 5;">19-36</div>
        </div>
      </div>
      <div style="display:flex; justify-content:space-between; color:var(--gold-light); font-weight:bold; padding:10px 0; font-family:var(--font-body);">
        <span>Toplam Bahis:</span><span id="totalAmt">0</span>
      </div>
      <button class="spin-btn" id="spin">ÇEVİR</button>
      <button id="clear" style="width:100%; padding:12px; background:none; border:1px solid rgba(212,175,55,0.3); color:var(--text-muted); border-radius:100px; cursor:pointer; margin-top:10px; font-family:var(--font-body);">Temizle</button>
    </aside>
  </div>
  <script type="importmap">
  { "imports": { "three": "https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js", "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/" } }
  </script>
  <script> window.RED_NUMBERS = [1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36]; window.API_URL = '/api/roulette.php'; </script>
  <script type="module" src="/assets/js/roulette.js"></script>
  <?php endif; ?>

  <!-- DICE -->
  <?php if ($slug === 'dice'): ?>
  <div class="game-layout">
    <div class="game-canvas-area">
      <div class="game-box">
        <h2>🎲 Zar Oyunu</h2>
        <div style="font-size:60px; font-weight:900; font-family:monospace; margin:20px 0; color:var(--gold-light);" id="dice-result-val">50.00</div>
        <div id="dice-message" style="height:30px; font-weight:bold; margin-bottom:20px; font-family:var(--font-body);"></div>
      </div>
    </div>
    <aside class="game-panel">
      <div style="background:#0f5f3f; padding:20px; border-radius:16px; color:#fff; text-align:center;"><div style="font-size:20px; font-weight:bold; font-family:var(--font-body);">Bakiye: <span id="dice-balance">0.00</span></div></div>
      <div class="input-group"><label>Hedef Sayı</label><input type="range" id="dice-target-slider" min="2" max="98" value="50" style="width:100%;"><div style="text-align:center; font-weight:bold; margin-top:5px; color:var(--gold-light);" id="dice-target-val">50</div></div>
      <div style="display:flex; gap:10px; margin-bottom:20px;"><button id="dice-mode-under" class="btn-pill btn-gold" style="flex:1;">ALT</button><button id="dice-mode-over" class="btn-pill btn-outline" style="flex:1;">ÜST</button></div>
      <div class="input-group"><label>Bahis Miktarı</label><input type="number" id="dice-bet-input" value="1" step="0.1"></div>
      <button class="spin-btn" id="dice-play-btn">ZAR AT</button>
    </aside>
  </div>
  <script>
  window.addEventListener('load', () => {
      const $ = id => document.getElementById(id); const API = '/api/roulette.php'; if (!$('dice-play-btn')) return;
      let balance = 0, busy = false, betAmount = 1, target = 50, mode = 'under';
      async function api(body) { const r = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) }); const j = await r.json(); if (!r.ok || j.error) throw new Error(j.error || 'Hata'); return j; }
      function setBalance(v) { if (v == null) return; balance = Number(v); $('dice-balance').textContent = balance.toLocaleString('tr-TR', {minimumFractionDigits: 2}); }
      async function loadBalance() { try { setBalance((await api({ action: 'balance' })).balance); } catch (e) {} }
      $('dice-bet-input').addEventListener('input', (e) => { betAmount = Math.max(0.1, parseFloat(e.target.value) || 0); });
      $('dice-target-slider').addEventListener('input', (e) => { target = parseInt(e.target.value); $('dice-target-val').textContent = target; });
      $('dice-mode-under').onclick = () => { mode = 'under'; $('dice-mode-under').className='btn-pill btn-gold'; $('dice-mode-over').className='btn-pill btn-outline'; };
      $('dice-mode-over').onclick = () => { mode = 'over'; $('dice-mode-over').className='btn-pill btn-gold'; $('dice-mode-under').className='btn-pill btn-outline'; };
      async function play() { if (busy || betAmount <= 0) return; if (betAmount > balance) return alert('Yetersiz bakiye!'); busy = true; $('dice-play-btn').disabled = true; try { const start = await api({ action: 'dice_start', bet: betAmount, target: target, mode: mode }); const finish = await api({ action: 'dice_finish', round_id: start.round_id }); $('dice-result-val').textContent = finish.roll.toFixed(2); setBalance(finish.balance); $('dice-message').textContent = finish.payout > 0 ? `🎉 Kazandın! ${finish.payout.toFixed(2)}` : `Kaybettin.`; $('dice-message').style.color = finish.payout > 0 ? '#2ecc71' : '#e74c3c'; } catch (e) { alert(e.message); } finally { busy = false; $('dice-play-btn').disabled = false; } }
      $('dice-play-btn').onclick = play; loadBalance();
  });
  </script>
  <?php endif; ?>

  <!-- CRASH -->
  <?php if ($slug === 'crash'): ?>
  <div class="game-layout">
    <div class="game-canvas-area">
      <div class="game-box">
        <h2>🚀 Crash</h2>
        <div style="font-size:72px; font-weight:900; font-family:monospace; margin:20px 0; color:var(--gold-light);" id="crash-multiplier">1.00x</div>
        <div id="crash-message" style="height:30px; font-weight:bold; font-size:18px; font-family:var(--font-body);"></div>
        <canvas id="crash-canvas" width="500" height="250" style="width:100%; background:#050506; border-radius:16px; margin-top:16px; border:1px solid var(--border-subtle);"></canvas>
      </div>
    </div>
    <aside class="game-panel">
      <div style="background:#0f5f3f; padding:20px; border-radius:16px; color:#fff; text-align:center;"><div style="font-size:20px; font-weight:bold; font-family:var(--font-body);">Bakiye: <span id="crash-balance">0.00</span></div></div>
      <div class="input-group"><label>Bahis Miktarı</label><input type="number" id="crash-bet-input" value="1" step="0.1"></div>
      <button class="spin-btn" id="crash-play-btn">BAŞLAT</button>
      <button class="spin-btn" id="crash-cashout-btn" style="display:none; background:#2ecc71; color:#fff;">PARAYI AL</button>
    </aside>
  </div>
  <script>
  window.addEventListener('load', () => {
      const $ = id => document.getElementById(id); const API = '/api/roulette.php'; if (!$('crash-play-btn')) return;
      let balance = 0, busy = false, betAmount = 1, gameActive = false, crashPoint = 0, multiplier = 1, startTime = 0, animFrame = null;
      const canvas = $('crash-canvas'); const ctx = canvas.getContext('2d');
      const W = canvas.width, H = canvas.height;
      
      async function api(body) { const r = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) }); const j = await r.json(); if (!r.ok || j.error) throw new Error(j.error || 'Hata'); return j; }
      function setBalance(v) { if (v == null) return; balance = Number(v); $('crash-balance').textContent = balance.toLocaleString('tr-TR', {minimumFractionDigits: 2}); }
      async function loadBalance() { try { setBalance((await api({ action: 'balance' })).balance); } catch (e) {} }
      
      function drawGraph() {
          ctx.clearRect(0, 0, W, H);
          ctx.strokeStyle = '#1a1a1a'; ctx.lineWidth = 1;
          for (let i = 0; i <= 10; i++) { ctx.beginPath(); ctx.moveTo(0, i * H / 10); ctx.lineTo(W, i * H / 10); ctx.stroke(); ctx.beginPath(); ctx.moveTo(i * W / 10, 0); ctx.lineTo(i * W / 10, H); ctx.stroke(); }
          if (!gameActive) return;
          ctx.beginPath();
          ctx.strokeStyle = '#f0b90b';
          ctx.lineWidth = 3;
          ctx.shadowColor = 'rgba(240,185,11,0.5)';
          ctx.shadowBlur = 10;
          const maxX = W - 40; const maxY = H - 40;
          const maxMult = Math.max(2, multiplier * 1.2);
          for (let x = 0; x <= maxX; x += 5) {
              const t = x / maxX;
              const mult = 1 + (multiplier - 1) * Math.pow(t, 2);
              const y = maxY - ((mult - 1) / (maxMult - 1)) * maxY;
              if (x === 0) ctx.moveTo(40 + x, y); else ctx.lineTo(40 + x, y);
          }
          ctx.stroke();
          ctx.shadowBlur = 0;
      }
      
      function updateMultiplier() {
          if (!gameActive) return;
          const elapsed = (performance.now() - startTime) / 1000;
          multiplier = 1 + Math.pow(elapsed, 1.5) * 0.5;
          if (multiplier >= crashPoint) {
              multiplier = crashPoint;
              gameActive = false;
              $('crash-multiplier').textContent = multiplier.toFixed(2) + 'x';
              $('crash-multiplier').style.color = '#e74c3c';
              $('crash-message').textContent = '💥 PATLADI!';
              $('crash-message').style.color = '#e74c3c';
              $('crash-cashout-btn').style.display = 'none';
              $('crash-play-btn').style.display = 'block';
              $('crash-play-btn').disabled = false;
              busy = false;
              drawGraph();
              return;
          }
          $('crash-multiplier').textContent = multiplier.toFixed(2) + 'x';
          drawGraph();
          animFrame = requestAnimationFrame(updateMultiplier);
      }
      
      $('crash-play-btn').onclick = async () => {
          if (busy || betAmount <= 0) return;
          if (betAmount > balance) return alert('Yetersiz bakiye!');
          busy = true; $('crash-play-btn').disabled = true;
          try {
              const start = await api({ action: 'crash_start', bet: betAmount });
              crashPoint = start.crash_point;
              multiplier = 1.00;
              gameActive = true;
              startTime = performance.now();
              $('crash-multiplier').textContent = '1.00x';
              $('crash-multiplier').style.color = 'var(--gold-light)';
              $('crash-message').textContent = '';
              $('crash-play-btn').style.display = 'none';
              $('crash-cashout-btn').style.display = 'block';
              $('crash-cashout-btn').disabled = false;
              updateMultiplier();
          } catch (e) { alert(e.message); busy = false; $('crash-play-btn').disabled = false; }
      };
      
      $('crash-cashout-btn').onclick = async () => {
          if (!gameActive) return;
          gameActive = false;
          cancelAnimationFrame(animFrame);
          try {
              const res = await api({ action: 'crash_cashout', round_id: 0, multiplier: multiplier });
              setBalance(res.balance);
              $('crash-message').textContent = `🎉 ${multiplier.toFixed(2)}x — ${res.payout.toFixed(2)} USD`;
              $('crash-message').style.color = '#2ecc71';
          } catch (e) { alert(e.message); }
          $('crash-cashout-btn').style.display = 'none';
          $('crash-play-btn').style.display = 'block';
          $('crash-play-btn').disabled = false;
          busy = false;
          drawGraph();
      };
      
      $('crash-bet-input').addEventListener('input', (e) => { betAmount = Math.max(0.1, parseFloat(e.target.value) || 0); });
      drawGraph(); loadBalance();
  });
  </script>
  <?php endif; ?>

  <!-- BARBUT -->
  <?php if ($slug === 'barbut'): ?>
  <div class="game-layout">
    <div class="game-canvas-area"><div class="game-box"><h2>🎲 Barbut</h2><div style="display:flex; justify-content:center; gap:20px; margin:30px 0;"><div id="die1" style="width:80px;height:80px;background:#fff;border-radius:12px;display:flex;justify-content:center;align-items:center;font-size:40px;font-weight:bold;color:#000;box-shadow:0 10px 20px rgba(0,0,0,0.5);">?</div><div id="die2" style="width:80px;height:80px;background:#fff;border-radius:12px;display:flex;justify-content:center;align-items:center;font-size:40px;font-weight:bold;color:#000;box-shadow:0 10px 20px rgba(0,0,0,0.5);">?</div></div><div id="barbut-message" style="height:30px;font-weight:bold;font-size:18px;font-family:var(--font-body);"></div></div></div>
    <aside class="game-panel">
      <div style="background:#0f5f3f; padding:20px; border-radius:16px; color:#fff; text-align:center;"><div style="font-size:20px; font-weight:bold; font-family:var(--font-body);">Bakiye: <span id="barbut-balance">0.00</span></div></div>
      <div class="input-group"><label>Bahis Miktarı</label><input type="number" id="barbut-bet-input" value="1" step="0.1"></div>
      <button class="spin-btn" id="barbut-play-btn">ZAR AT</button>
    </aside>
  </div>
  <script>
  window.addEventListener('load', () => {
      const $ = id => document.getElementById(id); const API = '/api/roulette.php'; if (!$('barbut-play-btn')) return;
      let balance = 0, busy = false, betAmount = 1;
      async function api(body) { const r = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) }); const j = await r.json(); if (!r.ok || j.error) throw new Error(j.error || 'Hata'); return j; }
      function setBalance(v) { if (v == null) return; balance = Number(v); $('barbut-balance').textContent = balance.toLocaleString('tr-TR', {minimumFractionDigits: 2}); }
      async function loadBalance() { try { setBalance((await api({ action: 'balance' })).balance); } catch (e) {} }
      $('barbut-bet-input').addEventListener('input', (e) => { betAmount = Math.max(0.1, parseFloat(e.target.value) || 0); });
      async function play() { if (busy || betAmount <= 0) return; if (betAmount > balance) return alert('Yetersiz bakiye!'); busy = true; $('barbut-play-btn').disabled = true; try { const start = await api({ action: 'barbut_start', bet: betAmount }); const finish = await api({ action: 'barbut_finish', round_id: start.round_id }); $('die1').textContent = finish.die1; $('die2').textContent = finish.die2; $('barbut-message').textContent = `Toplam: ${finish.total} — ${finish.msg}`; $('barbut-message').style.color = finish.payout > 0 ? '#2ecc71' : '#e74c3c'; setBalance(finish.balance); } catch (e) { alert(e.message); } finally { busy = false; $('barbut-play-btn').disabled = false; } }
      $('barbut-play-btn').onclick = play; loadBalance();
  });
  </script>
  <?php endif; ?>

  <!-- SLOT -->
  <?php if ($slug === 'slot'): ?>
  <div class="game-layout">
    <div class="game-canvas-area">
      <div class="game-box" style="border:4px solid var(--gold); box-shadow:0 0 50px var(--gold-glow);">
        <h2>🍒 Meyve Slot 🍋</h2>
        <div style="display:flex; justify-content:center; gap:10px; margin:30px 0; background:#000; padding:20px; border-radius:16px;">
          <div id="reel1" style="width:100px;height:100px;background:#fff;border-radius:12px;display:flex;justify-content:center;align-items:center;font-size:60px;">🍒</div>
          <div id="reel2" style="width:100px;height:100px;background:#fff;border-radius:12px;display:flex;justify-content:center;align-items:center;font-size:60px;">🍋</div>
          <div id="reel3" style="width:100px;height:100px;background:#fff;border-radius:12px;display:flex;justify-content:center;align-items:center;font-size:60px;">🍊</div>
        </div>
        <div id="slot-message" style="height:30px; font-weight:bold; font-size:20px; color:var(--gold-light);"></div>
      </div>
    </div>
    <aside class="game-panel">
      <div style="background:#0f5f3f; padding:20px; border-radius:16px; color:#fff; text-align:center;"><div style="font-size:20px; font-weight:bold; font-family:var(--font-body);">Bakiye: <span id="slot-balance">0.00</span></div></div>
      <div class="input-group"><label>Bahis Miktarı</label><input type="number" id="slot-bet-input" value="1" step="0.1"></div>
      <button class="spin-btn" id="slot-play-btn">ÇEVİR</button>
    </aside>
  </div>
  <script>
  window.addEventListener('load', () => {
      const $ = id => document.getElementById(id); const API = '/api/roulette.php'; if (!$('slot-play-btn')) return;
      let balance = 0, busy = false, betAmount = 1;
      const SYMBOLS = ['🍒', '🍋', '🍊', '🍇', '🔔', '⭐', '7️⃣']; const reels = [$('reel1'), $('reel2'), $('reel3')];
      async function api(body) { const r = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) }); const j = await r.json(); if (!r.ok || j.error) throw new Error(j.error || 'Hata'); return j; }
      function setBalance(v) { if (v == null) return; balance = Number(v); $('slot-balance').textContent = balance.toLocaleString('tr-TR', {minimumFractionDigits: 2}); }
      async function loadBalance() { try { setBalance((await api({ action: 'balance' })).balance); } catch (e) {} }
      $('slot-bet-input').addEventListener('input', (e) => { betAmount = Math.max(0.1, parseFloat(e.target.value) || 0); });
      async function play() { if (busy || betAmount <= 0) return; if (betAmount > balance) return alert('Yetersiz bakiye!'); busy = true; $('slot-play-btn').disabled = true; $('slot-message').textContent = ''; try { const start = await api({ action: 'slot_start', bet: betAmount }); const finish = await api({ action: 'slot_finish', round_id: start.round_id }); reels[0].textContent = SYMBOLS[finish.reels[0]]; setTimeout(() => reels[1].textContent = SYMBOLS[finish.reels[1]], 300); setTimeout(() => reels[2].textContent = SYMBOLS[finish.reels[2]], 600); setTimeout(() => { $('slot-message').textContent = finish.payout > 0 ? `🎉 ${finish.msg} — ${finish.payout.toFixed(2)}` : `😢 ${finish.msg}`; $('slot-message').style.color = finish.payout > 0 ? '#2ecc71' : '#e74c3c'; setBalance(finish.balance); busy = false; $('slot-play-btn').disabled = false; }, 900); } catch (e) { alert(e.message); busy = false; $('slot-play-btn').disabled = false; } }
      $('slot-play-btn').onclick = play; loadBalance();
  });
  </script>
  <?php endif; ?>

  <!-- MINES -->
  <?php if ($slug === 'mines'): ?>
  <div class="game-layout">
    <div class="game-canvas-area">
      <div class="game-box" style="max-width:500px;">
        <h2>💣 Mines</h2>
        <div style="display:flex; justify-content:space-between; margin-bottom:15px; font-size:16px; color:var(--gold-light); font-weight:bold; font-family:var(--font-body);">
            <span>Sonraki Çarpan: <span id="mines-next-multi">1.00x</span></span>
            <span>Mevcut Kazanç: <span id="mines-current-win">0.00</span> USD</span>
        </div>
        <div id="mines-grid" style="display:grid; grid-template-columns:repeat(5, 1fr); gap:8px; margin-bottom:20px;"></div>
        <div id="mines-message" style="height:30px; font-weight:bold; font-size:18px; font-family:var(--font-body);"></div>
      </div>
    </div>
    <aside class="game-panel">
      <div style="background:#0f5f3f; padding:20px; border-radius:16px; color:#fff; text-align:center;"><div style="font-size:20px; font-weight:bold; font-family:var(--font-body);">Bakiye: <span id="mines-balance">0.00</span></div></div>
      <div id="mines-bet-area">
        <div class="input-group"><label>Bahis Miktarı</label><input type="number" id="mines-bet-input" value="1" step="0.1"></div>
        <div class="input-group"><label>Mayın Sayısı</label><select id="mines-count"><option value="1">1 Mayın (25x)</option><option value="3" selected>3 Mayın (8.3x)</option><option value="5">5 Mayın (5x)</option><option value="10">10 Mayın (2.5x)</option></select></div>
        <button class="spin-btn" id="mines-start-btn">OYUNA BAŞLA</button>
      </div>
      <div id="mines-action-area" style="display:none;">
        <button class="spin-btn" id="mines-cashout-btn" style="background:#2ecc71; color:#000;">PARAYI AL</button>
      </div>
    </aside>
  </div>
  <script>
  window.addEventListener('load', () => {
      const $ = id => document.getElementById(id); const API = '/api/roulette.php'; if (!$('mines-start-btn')) return;
      let balance = 0, busy = false, roundId = null, betAmount = 1, mineCount = 3, revealedCount = 0, totalCells = 25;
      async function api(body) { const r = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) }); const j = await r.json(); if (!r.ok || j.error) throw new Error(j.error || 'Hata'); return j; }
      function setBalance(v) { if (v == null) return; balance = Number(v); $('mines-balance').textContent = balance.toLocaleString('tr-TR', {minimumFractionDigits: 2}); }
      async function loadBalance() { try { setBalance((await api({ action: 'balance' })).balance); } catch (e) {} }
      function buildGrid() { const grid = $('mines-grid'); grid.innerHTML = ''; for (let i = 0; i < 25; i++) { const cell = document.createElement('div'); cell.className = 'mine-cell'; cell.dataset.index = i; cell.textContent = ''; cell.onclick = () => revealCell(i); grid.appendChild(cell); } }
      function updateMultiplier() { let safeCells = totalCells - mineCount; let currentMultiplier = 1; for (let i = 0; i < revealedCount; i++) { currentMultiplier *= (totalCells - i) / (safeCells - i); } currentMultiplier *= 0.99; $('mines-next-multi').textContent = currentMultiplier.toFixed(2) + 'x'; $('mines-current-win').textContent = (betAmount * currentMultiplier).toFixed(2); }
      async function startGame() { if (busy) return; betAmount = Math.max(0.1, parseFloat($('mines-bet-input').value) || 0); mineCount = parseInt($('mines-count').value); if (betAmount > balance) return alert('Yetersiz bakiye!'); busy = true; $('mines-start-btn').disabled = true; $('mines-message').textContent = 'Oyun başladı!'; $('mines-bet-area').style.display = 'none'; $('mines-action-area').style.display = 'block'; revealedCount = 0; buildGrid(); try { const start = await api({ action: 'mines_start', bet: betAmount, mines: mineCount }); roundId = start.round_id; updateMultiplier(); busy = false; } catch (e) { alert(e.message); resetGame(); } }
      async function revealCell(index) { if (busy || !roundId) return; const cell = document.querySelector(`.mine-cell[data-index="${index}"]`); if (!cell || cell.classList.contains('revealed')) return; busy = true; try { const res = await api({ action: 'mines_reveal', round_id: roundId, index: index }); cell.classList.add('revealed'); if (res.mine) { cell.classList.add('mine'); cell.textContent = '💣'; $('mines-message').textContent = '💥 MAYINA BASTIN!'; $('mines-message').style.color = '#e74c3c'; setBalance(res.balance); if (res.all_mines) { res.all_mines.forEach(idx => { const c = document.querySelector(`.mine-cell[data-index="${idx}"]`); if (c && !c.classList.contains('revealed')) { c.classList.add('revealed', 'mine'); c.textContent = '💣'; } }); } resetGame(); } else { cell.classList.add('gem'); cell.textContent = '💎'; revealedCount++; updateMultiplier(); if (revealedCount === totalCells - mineCount) { await cashout(); } else { busy = false; } } } catch (e) { alert(e.message); busy = false; } }
      async function cashout() { if (busy || !roundId) return; busy = true; try { const res = await api({ action: 'mines_cashout', round_id: roundId }); $('mines-message').textContent = `🎉 Kazandın! ${res.payout.toFixed(2)} USD`; $('mines-message').style.color = '#2ecc71'; setBalance(res.balance); if (res.all_mines) { res.all_mines.forEach(idx => { const c = document.querySelector(`.mine-cell[data-index="${idx}"]`); if (c && !c.classList.contains('revealed')) { c.classList.add('revealed', 'mine'); c.textContent = '💣'; } }); } resetGame(); } catch (e) { alert(e.message); busy = false; } }
      function resetGame() { roundId = null; busy = false; revealedCount = 0; $('mines-start-btn').disabled = false; $('mines-bet-area').style.display = 'block'; $('mines-action-area').style.display = 'none'; $('mines-next-multi').textContent = '1.00x'; $('mines-current-win').textContent = '0.00'; }
      $('mines-start-btn').onclick = startGame; $('mines-cashout-btn').onclick = cashout;
      buildGrid(); loadBalance();
  });
  </script>
  <?php endif; ?>

  <!-- PLINKO -->
  <?php if ($slug === 'plinko'): ?>
  <div class="game-layout">
    <div class="game-canvas-area">
      <div class="game-box" style="max-width:500px;">
        <h2>📍 Plinko</h2>
        <canvas id="plinko-canvas" width="400" height="400" style="width:100%; background:#0a0a0a; border-radius:12px; border:2px solid #333; display:block;"></canvas>
        <div id="plinko-message" style="height:30px; font-weight:bold; font-size:18px; margin:15px 0; font-family:var(--font-body);"></div>
      </div>
    </div>
    <aside class="game-panel">
      <div style="background:#0f5f3f; padding:20px; border-radius:16px; color:#fff; text-align:center;"><div style="font-size:20px; font-weight:bold; font-family:var(--font-body);">Bakiye: <span id="plinko-balance">0.00</span></div></div>
      <div class="input-group"><label>Bahis Miktarı</label><input type="number" id="plinko-bet-input" value="1" step="0.1"></div>
      <div class="input-group"><label>Risk</label><select id="plinko-risk"><option value="low">Düşük</option><option value="medium" selected>Orta</option><option value="high">Yüksek</option></select></div>
      <button class="spin-btn" id="plinko-play-btn">TOPU AT</button>
    </aside>
  </div>
  <script>
  window.addEventListener('load', () => {
      const $ = id => document.getElementById(id); const API = '/api/roulette.php'; if (!$('plinko-play-btn')) return;
      let balance = 0, busy = false, betAmount = 1;
      const canvas = $('plinko-canvas'); const ctx = canvas.getContext('2d');
      const W = canvas.width, H = canvas.height;
      const ROWS = 8; const COLS = 8; const PEG_RADIUS = 4; const BALL_RADIUS = 8; const BUCKET_HEIGHT = 30; const PADDING = 40;
      const MULTIPLIERS = { 'low': [3, 1.5, 1.2, 1.1, 1.1, 1.2, 1.5, 3], 'medium': [5, 2, 1.5, 1.2, 1.2, 1.5, 2, 5], 'high': [10, 3, 1.5, 0.5, 0.5, 1.5, 3, 10] };
      const pegs = []; const pegSpacingX = (W - PADDING * 2) / (COLS - 1); const pegSpacingY = (H - PADDING * 2 - BUCKET_HEIGHT) / ROWS;
      for (let row = 0; row < ROWS; row++) { const rowCols = row + 2; const startX = (W - (rowCols - 1) * pegSpacingX) / 2; for (let col = 0; col < rowCols; col++) { pegs.push({ x: startX + col * pegSpacingX, y: PADDING + row * pegSpacingY }); } }
      const buckets = []; const bucketWidth = (W - PADDING * 2) / COLS; for (let i = 0; i < COLS; i++) { buckets.push({ x: PADDING + i * bucketWidth + bucketWidth / 2, y: H - BUCKET_HEIGHT / 2, width: bucketWidth }); }
      let ball = null; let animFrame = null;
      function drawScene() { ctx.clearRect(0, 0, W, H); ctx.fillStyle = '#6b5420'; pegs.forEach(p => { ctx.beginPath(); ctx.arc(p.x, p.y, PEG_RADIUS, 0, Math.PI * 2); ctx.fill(); ctx.strokeStyle = '#ffd700'; ctx.lineWidth = 1; ctx.stroke(); }); const risk = $('plinko-risk').value; const mults = MULTIPLIERS[risk]; for (let i = 0; i < COLS; i++) { const b = buckets[i]; const m = mults[i]; let color = '#e74c3c'; if (m >= 10) color = '#f1c40f'; else if (m >= 5) color = '#e67e22'; else if (m >= 3) color = '#2ecc71'; else if (m >= 2) color = '#3498db'; else if (m >= 1.5) color = '#9b59b6'; else if (m >= 1) color = '#34495e'; ctx.fillStyle = color; ctx.fillRect(b.x - b.width / 2 + 2, b.y - BUCKET_HEIGHT / 2, b.width - 4, BUCKET_HEIGHT); ctx.strokeStyle = '#000'; ctx.lineWidth = 1; ctx.strokeRect(b.x - b.width / 2 + 2, b.y - BUCKET_HEIGHT / 2, b.width - 4, BUCKET_HEIGHT); ctx.fillStyle = '#fff'; ctx.font = 'bold 12px Arial'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText(m + 'x', b.x, b.y); } if (ball) { ctx.beginPath(); ctx.arc(ball.x, ball.y, BALL_RADIUS, 0, Math.PI * 2); let g = ctx.createRadialGradient(ball.x - 3, ball.y - 3, 1, ball.x, ball.y, BALL_RADIUS); g.addColorStop(0, '#fff'); g.addColorStop(0.6, '#f0b90b'); g.addColorStop(1, '#b8860b'); ctx.fillStyle = g; ctx.fill(); ctx.strokeStyle = '#fff'; ctx.lineWidth = 1; ctx.stroke(); } }
      function animate() { if (!ball) return; ball.vy += 0.15; ball.y += ball.vy; ball.x += ball.vx; pegs.forEach(p => { const dx = ball.x - p.x; const dy = ball.y - p.y; const dist = Math.sqrt(dx * dx + dy * dy); if (dist < PEG_RADIUS + BALL_RADIUS) { const nx = dx / dist; const ny = dy / dist; const dot = ball.vx * nx + ball.vy * ny; ball.vx = ball.vx - 2 * dot * nx + (Math.random() - 0.5) * 1.5; ball.vy = ball.vy - 2 * dot * ny + 1; ball.x = p.x + nx * (PEG_RADIUS + BALL_RADIUS + 0.1); ball.y = p.y + ny * (PEG_RADIUS + BALL_RADIUS + 0.1); } }); if (ball.x < PADDING + BALL_RADIUS) { ball.x = PADDING + BALL_RADIUS; ball.vx = Math.abs(ball.vx) * 0.8; } if (ball.x > W - PADDING - BALL_RADIUS) { ball.x = W - PADDING - BALL_RADIUS; ball.vx = -Math.abs(ball.vx) * 0.8; } if (ball.y + BALL_RADIUS >= H - BUCKET_HEIGHT) { for (let i = 0; i < COLS; i++) { const b = buckets[i]; if (ball.x > b.x - b.width / 2 && ball.x < b.x + b.width / 2) { finishBall(i); return; } } finishBall(Math.floor(Math.random() * COLS)); return; } drawScene(); animFrame = requestAnimationFrame(animate); }
      async function finishBall(bucketIndex) { cancelAnimationFrame(animFrame); ball = null; drawScene(); try { const res = await api({ action: 'plinko_finish', round_id: window.plinkoRoundId, bucket: bucketIndex }); const mult = res.multiplier; const payout = res.payout; if (payout > 0) { $('plinko-message').textContent = `🎉 ${mult}x — ${payout.toFixed(2)} USD`; $('plinko-message').style.color = '#2ecc71'; } else { $('plinko-message').textContent = `${mult}x — Kaybettin.`; $('plinko-message').style.color = '#e74c3c'; } setBalance(res.balance); } catch (e) { alert(e.message); } finally { busy = false; $('plinko-play-btn').disabled = false; } }
      async function api(body) { const r = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) }); const j = await r.json(); if (!r.ok || j.error) throw new Error(j.error || 'Hata'); return j; }
      function setBalance(v) { if (v == null) return; balance = Number(v); $('plinko-balance').textContent = balance.toLocaleString('tr-TR', {minimumFractionDigits: 2}); }
      async function loadBalance() { try { setBalance((await api({ action: 'balance' })).balance); } catch (e) {} }
      $('plinko-bet-input').addEventListener('input', (e) => { betAmount = Math.max(0.1, parseFloat(e.target.value) || 0); });
      $('plinko-risk').addEventListener('change', drawScene);
      async function play() { if (busy || betAmount <= 0) return; if (betAmount > balance) return alert('Yetersiz bakiye!'); busy = true; $('plinko-play-btn').disabled = true; try { const risk = $('plinko-risk').value; const start = await api({ action: 'plinko_start', bet: betAmount, risk: risk }); window.plinkoRoundId = start.round_id; ball = { x: W / 2, y: 20, vx: (Math.random() - 0.5) * 2, vy: 0 }; animate(); } catch (e) { alert(e.message); busy = false; $('plinko-play-btn').disabled = false; } }
      $('plinko-play-btn').onclick = play; drawScene(); loadBalance();
  });
  </script>
  <?php endif; ?>

  <!-- BLACKJACK -->
  <?php if ($slug === 'blackjack'): ?>
  <div class="game-layout">
    <div class="game-canvas-area">
      <div class="game-box" style="background: #0b3a1a; max-width:600px;">
        <h2>🃏 Blackjack (21)</h2>
        <div style="text-align: left; padding: 0 20px;">
          <div style="color: #ccc; font-size: 14px; margin-bottom: 4px;">Krupiye: <span id="bj-dealer-score" class="bj-score"></span></div>
          <div id="bj-dealer-hand" class="bj-hand"></div>
          <div style="color: #ccc; font-size: 14px; margin-bottom: 4px;">Oyuncu: <span id="bj-player-score" class="bj-score"></span></div>
          <div id="bj-player-hand" class="bj-hand"></div>
        </div>
        <div id="bj-message" class="bj-msg"></div>
        <div id="bj-action-area" style="display: none; gap:10px; justify-content:center;">
          <button id="bj-hit-btn" class="spin-btn" style="background: #2ecc71; color: #000; width:45%;">KART ÇEK</button>
          <button id="bj-stand-btn" class="spin-btn" style="background: #e74c3c; color: #fff; width:45%;">DUR</button>
        </div>
      </div>
    </div>
    <aside class="game-panel">
      <div style="background:#0f5f3f; padding:20px; border-radius:16px; color:#fff; text-align:center;"><div style="font-size:20px; font-weight:bold; font-family:var(--font-body);">Bakiye: <span id="bj-balance">0.00</span></div></div>
      <div id="bj-bet-area">
        <div class="input-group"><label>Bahis Miktarı</label><input type="number" id="bj-bet-input" value="1" step="0.1"></div>
        <button class="spin-btn" id="bj-start-btn">OYUNA BAŞLA</button>
      </div>
    </aside>
  </div>
  <script>
  window.addEventListener('load', () => {
      const $ = id => document.getElementById(id); const API = '/api/roulette.php'; if (!$('bj-start-btn')) return;
      let balance = 0, busy = false, betAmount = 1, roundId = null;
      async function api(body) { const r = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) }); const j = await r.json(); if (!r.ok || j.error) throw new Error(j.error || 'Hata'); return j; }
      function setBalance(v) { if (v == null) return; balance = Number(v); $('bj-balance').textContent = balance.toLocaleString('tr-TR', {minimumFractionDigits: 2}); }
      async function loadBalance() { try { setBalance((await api({ action: 'balance' })).balance); } catch (e) {} }
      $('bj-bet-input').addEventListener('input', (e) => { betAmount = Math.max(0.1, parseFloat(e.target.value) || 0); });
      function renderCard(card, hidden = false) { const div = document.createElement('div'); div.className = 'bj-card ' + (hidden ? 'hidden' : (['♥','♦'].includes(card.suit) ? 'red' : 'black')); if (!hidden) { div.innerHTML = `<div class="rank">${card.rank}</div><div class="suit">${card.suit}</div>`; } return div; }
      function renderHands(dealerHand, playerHand, dealerHidden = true) { const dContainer = $('bj-dealer-hand'); dContainer.innerHTML = ''; dealerHand.forEach((c, i) => { dContainer.appendChild(renderCard(c, dealerHidden && i === 1)); }); const pContainer = $('bj-player-hand'); pContainer.innerHTML = ''; playerHand.forEach(c => pContainer.appendChild(renderCard(c))); }
      function calcScore(hand) { let score = 0, aces = 0; for (const c of hand) { if (c.rank === 'A') { aces++; score += 11; } else if (['J','Q','K'].includes(c.rank)) score += 10; else score += parseInt(c.rank); } while (score > 21 && aces > 0) { score -= 10; aces--; } return score; }
      async function startGame() { if (busy || betAmount <= 0) return; if (betAmount > balance) return alert('Yetersiz bakiye!'); busy = true; $('bj-start-btn').disabled = true; $('bj-message').textContent = ''; try { const start = await api({ action: 'blackjack_start', bet: betAmount }); roundId = start.round_id; renderHands(start.dealer, start.player, true); $('bj-dealer-score').textContent = calcScore([start.dealer[0]]) + ' + ?'; $('bj-player-score').textContent = calcScore(start.player); $('bj-bet-area').style.display = 'none'; $('bj-action-area').style.display = 'flex'; if (calcScore(start.player) === 21) { await stand(); } else { busy = false; } } catch (e) { alert(e.message); busy = false; $('bj-start-btn').disabled = false; } }
      async function hit() { if (busy) return; busy = true; try { const res = await api({ action: 'blackjack_hit', round_id: roundId }); renderHands(res.dealer, res.player, true); $('bj-player-score').textContent = calcScore(res.player); if (res.bust) { await finishGame(res); } else { busy = false; } } catch (e) { alert(e.message); busy = false; } }
      async function stand() { if (busy) return; busy = true; try { const res = await api({ action: 'blackjack_stand', round_id: roundId }); renderHands(res.dealer, res.player, false); $('bj-dealer-score').textContent = calcScore(res.dealer); $('bj-player-score').textContent = calcScore(res.player); await finishGame(res); } catch (e) { alert(e.message); busy = false; } }
      async function finishGame(res) { $('bj-action-area').style.display = 'none'; $('bj-bet-area').style.display = 'block'; $('bj-start-btn').disabled = false; if (res.result === 'win') { $('bj-message').textContent = `🎉 Kazandın! ${res.payout.toFixed(2)} USD`; $('bj-message').style.color = '#2ecc71'; } else if (res.result === 'push') { $('bj-message').textContent = `🤝 Berabere! ${res.payout.toFixed(2)} USD iade`; $('bj-message').style.color = '#f0b90b'; } else { $('bj-message').textContent = `😢 Kaybettin.`; $('bj-message').style.color = '#e74c3c'; } if (res.bust) $('bj-message').textContent += ' (Bust)'; setBalance(res.balance); busy = false; }
      $('bj-start-btn').onclick = startGame; $('bj-hit-btn').onclick = hit; $('bj-stand-btn').onclick = stand; loadBalance();
  });
  </script>
  <?php endif; ?>

<?php endif; ?>
</body>
</html>