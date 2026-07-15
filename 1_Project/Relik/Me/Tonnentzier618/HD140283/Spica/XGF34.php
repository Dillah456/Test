<?php
// ============================================================
//  NEL Dashboard — Net Emotional Load
//  Source: spica table (SQL) · oortmyid_e0
// ============================================================

$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_e0";

if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'get_spica') {
        $conn = new mysqli($db_server, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) { echo json_encode(['error' => $conn->connect_error]); exit; }
        $res = $conn->query("SELECT Registration, Senang, Sedih, Grief FROM spica ORDER BY Registration DESC LIMIT 10");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $conn->close();
        echo json_encode($rows ?: []);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NEL Dashboard — Spica</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg:       #0e0e10;
  --surface:  #16161a;
  --surface2: #1e1e24;
  --surface3: #26262e;
  --border:   rgba(255,255,255,0.07);
  --border2:  rgba(255,255,255,0.13);
  --text:     #e8e6e0;
  --text2:    #8a8880;
  --text3:    #54524e;

  --growth:   #4ade80;
  --growth-bg:#0d2b18;
  --stable:   #60a5fa;
  --stable-bg:#0d1e33;
  --critical: #facc15;
  --critical-bg:#2a2200;
  --deficit:  #fb923c;
  --deficit-bg:#2a1300;
  --drift:    #f87171;
  --drift-bg: #2a0a0a;
  --breakdown:#ff3b3b;
  --breakdown-bg:#1a0000;

  --r:    10px;
  --r-sm: 7px;
}

body {
  font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', ui-monospace, monospace;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  font-size: 13px;
  line-height: 1.5;
}

/* ── TOPBAR ── */
.topbar {
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  height: 52px;
  padding: 0 1.8rem;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 100;
}
.brand { display: flex; align-items: center; gap: 12px; }
.brand-mark {
  display: flex; align-items: center; justify-content: center;
  width: 28px; height: 28px; border-radius: 6px;
  background: linear-gradient(135deg, var(--drift) 0%, var(--breakdown) 100%);
  flex-shrink: 0;
}
.brand-mark svg { width: 14px; height: 14px; color: #fff; }
.brand-name { font-size: 14px; font-weight: 700; letter-spacing: 0.5px; color: var(--text); }
.brand-sub  { font-size: 10px; color: var(--text3); letter-spacing: 1px; text-transform: uppercase; }
.top-right  { display: flex; align-items: center; gap: 14px; }
.api-badge  {
  display: flex; align-items: center; gap: 6px;
  font-size: 10.5px; color: var(--text3); letter-spacing: 0.3px;
}
.dot { width: 6px; height: 6px; border-radius: 50%; background: #4ade80; }
.dot.off { background: var(--text3); }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.3} }
.dot:not(.off) { animation: pulse 2s infinite; }
.reload-btn {
  height: 28px; padding: 0 12px; font-size: 11px;
  font-family: inherit; border-radius: var(--r-sm);
  border: 1px solid var(--border2); background: var(--surface2);
  color: var(--text2); cursor: pointer; transition: all .13s;
  display: flex; align-items: center; gap: 5px;
}
.reload-btn:hover { background: var(--surface3); color: var(--text); }
.reload-btn svg { width: 12px; height: 12px; }

/* ── MAIN ── */
.main { max-width: 1060px; margin: 0 auto; padding: 1.8rem 1.5rem 4rem; }

/* ── PAGE HDR ── */
.page-hdr { margin-bottom: 1.6rem; }
.page-hdr h1 {
  font-size: 22px; font-weight: 700; letter-spacing: -0.5px;
  color: var(--text); line-height: 1.2;
}
.page-hdr h1 span { color: var(--drift); }
.page-hdr p { font-size: 11.5px; color: var(--text3); margin-top: 5px; letter-spacing: 0.2px; }

/* ── SUSTAINED WARN ── */
.sustained-warn {
  background: #1a0505; border: 1px solid #5a1010;
  border-radius: var(--r); padding: 12px 16px;
  display: none; align-items: flex-start; gap: 12px;
  margin-bottom: 16px;
}
.sustained-warn.show { display: flex; }
.sustained-warn svg { width: 16px; height: 16px; color: var(--breakdown); flex-shrink: 0; margin-top: 2px; }
.sustained-warn strong { display: block; font-size: 12px; color: #ffdddd; margin-bottom: 3px; }
.sustained-warn span { font-size: 11.5px; color: #ff9999; line-height: 1.55; }

/* ── SUMMARY CARDS ── */
.summary-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 10px;
  margin-bottom: 18px;
}
.sc {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r);
  padding: 14px 15px 12px;
}
.sc-label { font-size: 9.5px; color: var(--text3); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 7px; display: block; }
.sc-val   { font-size: 28px; font-weight: 700; line-height: 1; }
.sc-sub   { font-size: 10px; margin-top: 5px; font-weight: 600; }

/* ── RANGE SELECTOR ── */
.range-row {
  display: flex; gap: 5px; margin-bottom: 16px; align-items: center; flex-wrap: wrap;
}
.range-row span { font-size: 10px; color: var(--text3); text-transform: uppercase; letter-spacing: 0.8px; margin-right: 4px; }
.range-btn {
  height: 28px; padding: 0 12px; font-size: 11px; font-family: inherit;
  border-radius: var(--r-sm); border: 1px solid var(--border2);
  background: var(--surface2); color: var(--text2);
  cursor: pointer; transition: all .13s;
}
.range-btn.active { background: var(--surface3); color: var(--text); border-color: var(--border2); }
.range-btn:hover:not(.active) { background: var(--surface3); color: var(--text); }

/* ── GRID ── */
.dash-grid { display: grid; grid-template-columns: 1fr 340px; gap: 14px; margin-bottom: 14px; }
@media (max-width: 800px) { .dash-grid { grid-template-columns: 1fr; } }

/* ── PANEL ── */
.panel {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r);
  padding: 1.1rem 1.2rem;
}
.panel-title {
  font-size: 9.5px; text-transform: uppercase; letter-spacing: 1px;
  color: var(--text3); margin-bottom: 14px;
  display: flex; align-items: center; gap: 8px;
}
.panel-title .pill {
  font-size: 9px; background: var(--surface3); color: var(--text2);
  border-radius: 20px; padding: 2px 8px; letter-spacing: 0.3px;
  text-transform: none;
}

/* ── CARD ROWS (entry list) ── */
.entry-list { display: flex; flex-direction: column; gap: 6px; }
.entry-card {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  padding: 10px 12px;
  display: grid;
  grid-template-columns: 90px 1fr auto;
  align-items: center;
  gap: 10px;
  transition: border-color .15s;
}
.entry-card:hover { border-color: var(--border2); }
.entry-card.is-latest { border-color: var(--text3); }
.entry-reg  { font-size: 10px; color: var(--text3); line-height: 1.4; }
.entry-reg strong { display: block; font-size: 11px; color: var(--text2); }
.entry-scores {
  display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
}
.score-item { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.score-item .si-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text3); }
.score-item .si-val   { font-size: 15px; font-weight: 700; line-height: 1; }
.net-badge {
  display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0;
}
.net-num {
  font-size: 18px; font-weight: 700; line-height: 1;
  font-variant-numeric: tabular-nums;
}
.state-pill {
  font-size: 8.5px; font-weight: 700; padding: 2px 8px;
  border-radius: 20px; white-space: nowrap; letter-spacing: 0.3px;
  text-transform: uppercase;
}

/* ── BAR CHART ── */
.chart-wrap { }
.chart-title { font-size: 9.5px; text-transform: uppercase; letter-spacing: 1px; color: var(--text3); margin-bottom: 12px; }
.bar-row    { display: flex; align-items: center; gap: 8px; margin-bottom: 7px; }
.bar-idx    { width: 18px; font-size: 9.5px; color: var(--text3); text-align: right; flex-shrink: 0; }
.bar-track  { flex: 1; height: 18px; background: var(--surface2); border-radius: 4px; position: relative; overflow: hidden; }
.bar-fill   { position: absolute; top: 0; bottom: 0; border-radius: 4px; min-width: 2px; transition: width .45s ease; }
.bar-zero   { position: absolute; left: 50%; top: 0; bottom: 0; width: 1px; background: var(--border2); }
.bar-val    { width: 34px; font-size: 10.5px; text-align: right; flex-shrink: 0; font-variant-numeric: tabular-nums; }

/* ── TREND LINE ── */
.trend-panel { margin-bottom: 14px; }
.trend-canvas-wrap { position: relative; }
canvas#trendCanvas { display: block; width: 100%; }

/* ── LEGEND ── */
.legend-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 6px; margin-top: 14px;
}
.leg-item { display: flex; align-items: center; gap: 7px; font-size: 10.5px; color: var(--text2); }
.leg-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

/* ── ACCORDION ── */
.acc { border: 1px solid var(--border); border-radius: var(--r); overflow: hidden; margin-top: 16px; }
.acc-hdr {
  width: 100%; display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px; background: var(--surface2);
  font-size: 9.5px; text-transform: uppercase; letter-spacing: 1px; color: var(--text3);
  border: none; cursor: pointer; font-family: inherit; transition: background .13s;
  text-align: left;
}
.acc-hdr:hover { background: var(--surface3); }
.acc-hdr svg { width: 13px; height: 13px; transition: transform .22s ease; flex-shrink: 0; }
.acc-hdr.open svg { transform: rotate(180deg); }
.acc-body { display: none; padding: 16px; border-top: 1px solid var(--border); }
.acc-body.open { display: block; }

.state-block { margin-bottom: 14px; }
.state-block:last-child { margin-bottom: 0; }
.sbh { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.sbh-bar  { width: 3px; border-radius: 2px; align-self: stretch; min-height: 18px; flex-shrink: 0; }
.sbh-title { font-size: 12px; font-weight: 700; }
.sbh-range { font-size: 9.5px; color: var(--text3); margin-left: auto; background: var(--surface3); padding: 2px 8px; border-radius: 4px; }
.sb-desc  { font-size: 11.5px; color: var(--text2); line-height: 1.65; padding-left: 11px; }
.sb-tags  { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; padding-left: 11px; }
.sb-tag   { font-size: 9.5px; padding: 2px 8px; border-radius: 20px; background: var(--surface3); color: var(--text2); border: 1px solid var(--border); }
.state-divider { height: 1px; background: var(--border); margin: 12px 0; }
.sus-note {
  margin-top: 12px; padding: 12px 14px;
  background: #1a0505; border: 1px solid #5a1010;
  border-radius: var(--r-sm); font-size: 11.5px; color: #ff9999; line-height: 1.65;
}
.sus-note strong { display: block; color: #ffdddd; margin-bottom: 4px; font-size: 12px; }

/* ── LOADING ── */
.loading {
  padding: 2.5rem 0; text-align: center; color: var(--text3); font-size: 12px;
  display: flex; align-items: center; justify-content: center; gap: 10px;
}
.spinner {
  width: 16px; height: 16px; border: 2px solid var(--border2);
  border-top-color: var(--drift); border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── DIVIDER ── */
hr { border: none; border-top: 1px solid var(--border); margin: 14px 0; }
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <div class="brand">
    <div class="brand-mark">
      <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M7 2v4l2.5 1.5M2 7a5 5 0 1010 0A5 5 0 002 7z"/>
      </svg>
    </div>
    <div>
      <div class="brand-name">NEL Dashboard</div>
      <div class="brand-sub">Net Emotional Load · Spica</div>
    </div>
  </div>
  <div class="top-right">
    <div class="api-badge">
      <span class="dot off" id="apiDot"></span>
      <span id="apiStatus">Menghubungkan…</span>
    </div>
    <button class="reload-btn" id="reloadBtn">
      <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M12 7A5 5 0 112 7M12 7V3.5M12 7H8.5"/>
      </svg>
      Muat Ulang
    </button>
  </div>
</div>

<!-- MAIN -->
<div class="main">

  <div class="page-hdr">
    <h1>Net Emotional <span>Load</span></h1>
    <p>Analisis beban emosional dari 10 transaksi terakhir · tabel spica · oortmyid_e0</p>
  </div>

  <!-- Sustained warning -->
  <div class="sustained-warn" id="sustainedWarn">
    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6">
      <path d="M8 2L14 13H2L8 2z"/><path d="M8 6v3M8 11v.5"/>
    </svg>
    <div>
      <strong>⚠ Sustained Depressive State Terdeteksi</strong>
      <span id="sustainedDesc"></span>
    </div>
  </div>

  <!-- Summary cards -->
  <div class="summary-row" id="summaryRow">
    <div class="loading" style="grid-column:1/-1"><div class="spinner"></div> Memuat…</div>
  </div>

  <!-- Range selector -->
  <div class="range-row">
    <span>Tampilkan</span>
    <button class="range-btn active" data-n="10">10 terakhir</button>
    <button class="range-btn" data-n="7">7 terakhir</button>
    <button class="range-btn" data-n="5">5 terakhir</button>
    <button class="range-btn" data-n="3">3 terakhir</button>
  </div>

  <!-- Trend line canvas -->
  <div class="panel trend-panel" id="trendPanel" style="margin-bottom:14px;">
    <div class="panel-title">Tren Net Emosional <span class="pill">Kronologis</span></div>
    <div class="trend-canvas-wrap">
      <canvas id="trendCanvas" height="100"></canvas>
    </div>
  </div>

  <!-- Main grid: entry list + bar chart -->
  <div class="dash-grid">

    <!-- Entry list -->
    <div class="panel">
      <div class="panel-title">Rincian Transaksi <span class="pill">Senang − (Sedih + Grief)</span></div>
      <div class="entry-list" id="entryList">
        <div class="loading"><div class="spinner"></div> Memuat…</div>
      </div>
    </div>

    <!-- Bar chart -->
    <div class="panel">
      <div class="panel-title">Distribusi Net <span class="pill">Relatif</span></div>
      <div class="chart-wrap" id="barChart">
        <div class="loading"><div class="spinner"></div></div>
      </div>
      <hr>
      <!-- Legend -->
      <div class="legend-grid">
        <div class="leg-item"><span class="leg-dot" style="background:var(--growth)"></span>Recovery / Growth (&gt;3)</div>
        <div class="leg-item"><span class="leg-dot" style="background:var(--stable)"></span>Stable Vulnerable (0–3)</div>
        <div class="leg-item"><span class="leg-dot" style="background:var(--critical)"></span>Critical Equilibrium (=0)</div>
        <div class="leg-item"><span class="leg-dot" style="background:var(--deficit)"></span>Emotional Deficit (−3..−1)</div>
        <div class="leg-item"><span class="leg-dot" style="background:var(--drift)"></span>Depressive Drift (−7..−4)</div>
        <div class="leg-item"><span class="leg-dot" style="background:var(--breakdown)"></span>Breakdown Risk (&lt;−7)</div>
      </div>
    </div>

  </div>

  <!-- Accordion: state explanations -->
  <div class="acc">
    <button class="acc-hdr" id="accBtn">
      Penjelasan Threshold State NEL
      <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M2.5 5l4.5 4 4.5-4"/>
      </svg>
    </button>
    <div class="acc-body" id="accBody">

      <div class="state-block">
        <div class="sbh"><div class="sbh-bar" style="background:var(--growth)"></div><span class="sbh-title" style="color:var(--growth)">Recovery / Growth</span><span class="sbh-range">Net &gt; 3</span></div>
        <div class="sb-desc">Surplus energi emosional cukup. Buffer memadai untuk menghadapi tekanan tanpa langsung collapse. Individu dalam kapasitas penuh.</div>
        <div class="sb-tags"><span class="sb-tag">Motivasi aktif</span><span class="sb-tag">Resiliensi tinggi</span><span class="sb-tag">Kapasitas sosial tersedia</span><span class="sb-tag">Fleksibel terhadap perubahan</span></div>
      </div>
      <div class="state-divider"></div>

      <div class="state-block">
        <div class="sbh"><div class="sbh-bar" style="background:var(--stable)"></div><span class="sbh-title" style="color:var(--stable)">Stable but Vulnerable</span><span class="sbh-range">0 &lt; Net ≤ 3</span></div>
        <div class="sb-desc">Masih positif, tapi marginnya tipis. Fungsi berjalan normal tanpa cadangan. Satu trigger eksternal bisa langsung geser ke negatif.</div>
        <div class="sb-tags"><span class="sb-tag">Tetap fungsional</span><span class="sb-tag">Mudah drop jika ada trigger</span><span class="sb-tag">Butuh kondisi stabil</span></div>
      </div>
      <div class="state-divider"></div>

      <div class="state-block">
        <div class="sbh"><div class="sbh-bar" style="background:var(--critical)"></div><span class="sbh-title" style="color:var(--critical)">Critical Equilibrium</span><span class="sbh-range">Net = 0</span></div>
        <div class="sb-desc">Impas — tidak ada surplus, tidak ada defisit. Berbahaya karena terlihat "aman" tapi tidak ada buffer: trigger kecil langsung mendorong ke zona negatif.</div>
        <div class="sb-tags"><span class="sb-tag">Tidak ada cadangan energi</span><span class="sb-tag">Ekuilibrium semu</span><span class="sb-tag">Rentan trigger kecil</span></div>
      </div>
      <div class="state-divider"></div>

      <div class="state-block">
        <div class="sbh"><div class="sbh-bar" style="background:var(--deficit)"></div><span class="sbh-title" style="color:var(--deficit)">Emotional Deficit</span><span class="sbh-range">−3 ≤ Net &lt; 0</span></div>
        <div class="sb-desc">Defisit ringan. Erosion emosional mulai terjadi, belum klinis tapi penurunan afektif sudah terasa. Fase awal withdrawal.</div>
        <div class="sb-tags"><span class="sb-tag">Kelelahan emosional</span><span class="sb-tag">Withdrawal kecil-kecilan</span><span class="sb-tag">Overthinking meningkat</span></div>
      </div>
      <div class="state-divider"></div>

      <div class="state-block">
        <div class="sbh"><div class="sbh-bar" style="background:var(--drift)"></div><span class="sbh-title" style="color:var(--drift)">Depressive Drift</span><span class="sbh-range">−7 ≤ Net &lt; −4</span></div>
        <div class="sb-desc">Fase geser ke bawah. Belum collapse, tapi trajektori mengarah ke deteriorasi. Makna dan kesenangan menurun signifikan, isolasi meningkat. Berbahaya justru karena masih "bisa jalan".</div>
        <div class="sb-tags"><span class="sb-tag">Makna hidup menurun</span><span class="sb-tag">Anhedonia parsial</span><span class="sb-tag">Isolasi sosial meningkat</span><span class="sb-tag">Arah ke bawah</span></div>
      </div>
      <div class="state-divider"></div>

      <div class="state-block">
        <div class="sbh"><div class="sbh-bar" style="background:var(--breakdown)"></div><span class="sbh-title" style="color:var(--breakdown)">Depressive State / Breakdown Risk</span><span class="sbh-range">Net &lt; −7</span></div>
        <div class="sb-desc">Red zone. Bukan diagnosis klinis, tapi warning operasional serius. Energi mental drop berat, distress dominan atas semua fungsi lain. Fungsi harian terganggu.</div>
        <div class="sb-tags"><span class="sb-tag">Energi mental sangat rendah</span><span class="sb-tag">Hopeless patterning</span><span class="sb-tag">Distress dominan</span><span class="sb-tag">Fungsi harian terganggu</span></div>
      </div>

      <div class="sus-note">
        <strong>⚠ Sustained Depressive State — Tentang Durasi</strong>
        Jika Net &lt; −4 selama 3+ event berurutan, sistem menandai <em>Sustained Depressive State</em>. Ini lebih berbahaya dari satu-kali breakdown berat: depresi bukan hanya soal intensitas, tapi <strong>durasi</strong>. Negatif sekali belum tentu bahaya — negatif sedang tapi terus-menerus justru lebih merusak karena erosion berlangsung tanpa pemulihan.
      </div>

    </div>
  </div>

</div><!-- /main -->

<script>
// ── data & state ──────────────────────────────────────────
let rawData = [];   // newest-first from DB
let visN    = 10;   // how many to show

// ── helpers ──────────────────────────────────────────────
function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function calcNEL(r) {
  const senang = parseFloat(r.Senang ?? r.senang ?? 0);
  const sedih  = parseFloat(r.Sedih  ?? r.sedih  ?? 0);
  const grief  = parseFloat(r.Grief  ?? r.grief  ?? 0);
  return { senang, sedih, grief, net: senang - sedih - grief };
}
function getState(net) {
  if (net > 3)   return { label:'Recovery / Growth',           short:'Growth',    cls:'growth',    color:'var(--growth)' };
  if (net > 0)   return { label:'Stable but Vulnerable',       short:'Stable',    cls:'stable',    color:'var(--stable)' };
  if (net === 0) return { label:'Critical Equilibrium',        short:'Critical',  cls:'critical',  color:'var(--critical)' };
  if (net >= -3) return { label:'Emotional Deficit',           short:'Deficit',   cls:'deficit',   color:'var(--deficit)' };
  if (net >= -7) return { label:'Depressive Drift',            short:'Drift',     cls:'drift',     color:'var(--drift)' };
  return               { label:'Breakdown Risk',               short:'Breakdown', cls:'breakdown', color:'var(--breakdown)' };
}
function fmtReg(s) {
  if (!s) return '—';
  const d = new Date(s);
  if (isNaN(d)) return String(s);
  return d.toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'}) +
         '<br>' + d.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
}
function setStatus(live) {
  document.getElementById('apiDot').className    = 'dot' + (live ? '' : ' off');
  document.getElementById('apiStatus').textContent = live ? 'API Live' : 'Demo Mode';
}

// ── sustained check ───────────────────────────────────────
function checkSustained(chronoRows) {
  let streak = 0, max = 0;
  chronoRows.forEach(r => {
    if (calcNEL(r).net < -4) { streak++; if (streak > max) max = streak; }
    else streak = 0;
  });
  return { triggered: max >= 3, streak: max };
}

// ── render summary cards ──────────────────────────────────
function renderSummary(chrono) {
  const nets    = chrono.map(r => calcNEL(r).net);
  const avg     = nets.reduce((a,b)=>a+b,0) / nets.length;
  const mn      = Math.min(...nets);
  const mx      = Math.max(...nets);
  const latest  = getState(nets[nets.length-1]);
  const overall = getState(avg);
  const sus     = checkSustained(chrono);
  const trend   = nets.length > 1
    ? (nets[nets.length-1] - nets[0] > 0 ? '↑ Membaik' : nets[nets.length-1] - nets[0] < 0 ? '↓ Memburuk' : '→ Stabil')
    : '—';

  // Sustained warn
  const sw = document.getElementById('sustainedWarn');
  if (sus.triggered) {
    sw.classList.add('show');
    document.getElementById('sustainedDesc').textContent =
      `Net < −4 terdeteksi ${sus.streak} event berurutan. Intensitas rendah + durasi panjang = risiko lebih tinggi dari satu-kali breakdown.`;
  } else {
    sw.classList.remove('show');
  }

  document.getElementById('summaryRow').innerHTML = `
    <div class="sc">
      <span class="sc-label">Rata-rata Net</span>
      <div class="sc-val" style="color:${overall.color}">${avg.toFixed(1)}</div>
      <div class="sc-sub" style="color:${overall.color}">${overall.short}</div>
    </div>
    <div class="sc">
      <span class="sc-label">State Terkini</span>
      <div class="sc-val" style="font-size:16px;margin-top:4px;color:${latest.color}">${latest.label}</div>
      <div class="sc-sub" style="color:${latest.color}">Transaksi terakhir</div>
    </div>
    <div class="sc">
      <span class="sc-label">Min / Max</span>
      <div class="sc-val" style="color:var(--drift)">${mn}</div>
      <div class="sc-sub" style="color:var(--growth)">/ ${mx} tertinggi</div>
    </div>
    <div class="sc">
      <span class="sc-label">Arah Tren</span>
      <div class="sc-val" style="font-size:18px;margin-top:4px;color:var(--text)">${trend}</div>
      <div class="sc-sub" style="color:var(--text3)">Oldest → latest</div>
    </div>
    ${sus.triggered ? `
    <div class="sc" style="background:var(--breakdown-bg);border-color:#5a1010;">
      <span class="sc-label" style="color:#ff9999">Sustained</span>
      <div class="sc-val" style="color:var(--breakdown)">${sus.streak}×</div>
      <div class="sc-sub" style="color:#ff9999">Net &lt; −4 berurutan</div>
    </div>` : ''}
  `;
}

// ── render entry list ─────────────────────────────────────
function renderEntryList(rows) {
  // rows: newest first — display that way
  const el = document.getElementById('entryList');
  if (!rows.length) { el.innerHTML = '<div style="color:var(--text3);font-size:12px;padding:12px 0;text-align:center;">Tidak ada data</div>'; return; }
  let h = '';
  rows.forEach((r, i) => {
    const { senang, sedih, grief, net } = calcNEL(r);
    const state = getState(net);
    const sign  = net > 0 ? '+' : '';
    const regStr = r.Registration ?? r.id ?? (i+1);
    h += `
    <div class="entry-card${i === 0 ? ' is-latest' : ''}">
      <div class="entry-reg">
        <strong>#${rows.length - i}</strong>
        ${fmtReg(regStr)}
      </div>
      <div class="entry-scores">
        <div class="score-item">
          <span class="si-label">Senang</span>
          <span class="si-val" style="color:var(--growth)">${senang}</span>
        </div>
        <div class="score-item">
          <span class="si-label">Sedih</span>
          <span class="si-val" style="color:var(--drift)">${sedih}</span>
        </div>
        <div class="score-item">
          <span class="si-label">Grief</span>
          <span class="si-val" style="color:var(--breakdown)">${grief}</span>
        </div>
      </div>
      <div class="net-badge">
        <span class="net-num" style="color:${state.color}">${sign}${net}</span>
        <span class="state-pill" style="background:${state.color}22;color:${state.color}">${state.short}</span>
      </div>
    </div>`;
  });
  el.innerHTML = h;
}

// ── render bar chart ──────────────────────────────────────
function renderBarChart(chrono) {
  const nets   = chrono.map(r => calcNEL(r).net);
  const absMax = Math.max(...nets.map(Math.abs), 1);
  let h = `<div class="chart-title">Net per Event (kronologis)</div>`;
  nets.forEach((net, i) => {
    const state = getState(net);
    const pct   = Math.abs(net) / absMax * 48;
    const left  = net < 0 ? (50 - pct) : 50;
    const sign  = net > 0 ? '+' : '';
    h += `
    <div class="bar-row">
      <span class="bar-idx">${i+1}</span>
      <div class="bar-track">
        <div class="bar-zero"></div>
        <div class="bar-fill" style="left:${left}%;width:${pct}%;background:${state.color};opacity:0.85;"></div>
      </div>
      <span class="bar-val" style="color:${state.color}">${sign}${net}</span>
    </div>`;
  });
  document.getElementById('barChart').innerHTML = h;
}

// ── render trend line (canvas) ────────────────────────────
function renderTrend(chrono) {
  const canvas = document.getElementById('trendCanvas');
  const dpr    = window.devicePixelRatio || 1;
  const w      = canvas.offsetWidth || 600;
  const h      = 100;
  canvas.width  = w * dpr;
  canvas.height = h * dpr;
  const ctx = canvas.getContext('2d');
  ctx.scale(dpr, dpr);

  const nets   = chrono.map(r => calcNEL(r).net);
  if (nets.length < 2) return;

  const pad    = { t: 14, b: 14, l: 10, r: 10 };
  const cw     = w - pad.l - pad.r;
  const ch     = h - pad.t - pad.b;
  const mn     = Math.min(...nets, -1);
  const mx     = Math.max(...nets, 1);
  const range  = mx - mn || 1;
  const midY   = pad.t + ch * (mx / range);

  const xOf = i => pad.l + (i / (nets.length - 1)) * cw;
  const yOf = v => pad.t + ch * (1 - (v - mn) / range);

  // Zero line
  ctx.beginPath();
  ctx.strokeStyle = 'rgba(255,255,255,0.10)';
  ctx.lineWidth   = 1;
  ctx.setLineDash([4, 4]);
  ctx.moveTo(pad.l, midY);
  ctx.lineTo(pad.l + cw, midY);
  ctx.stroke();
  ctx.setLineDash([]);

  // Gradient fill under line
  const grad = ctx.createLinearGradient(0, pad.t, 0, pad.t + ch);
  grad.addColorStop(0,   'rgba(248,113,113,0.25)');
  grad.addColorStop(0.5, 'rgba(248,113,113,0.05)');
  grad.addColorStop(1,   'rgba(74,222,128,0.15)');
  ctx.beginPath();
  ctx.moveTo(xOf(0), yOf(nets[0]));
  for (let i = 1; i < nets.length; i++) ctx.lineTo(xOf(i), yOf(nets[i]));
  ctx.lineTo(xOf(nets.length - 1), midY);
  ctx.lineTo(xOf(0), midY);
  ctx.closePath();
  ctx.fillStyle = grad;
  ctx.fill();

  // Line
  ctx.beginPath();
  ctx.strokeStyle = '#f87171';
  ctx.lineWidth   = 2;
  ctx.lineJoin    = 'round';
  ctx.moveTo(xOf(0), yOf(nets[0]));
  for (let i = 1; i < nets.length; i++) ctx.lineTo(xOf(i), yOf(nets[i]));
  ctx.stroke();

  // Dots
  nets.forEach((v, i) => {
    const state = getState(v);
    const style = getComputedStyle(document.documentElement);
    ctx.beginPath();
    ctx.arc(xOf(i), yOf(v), 4, 0, Math.PI * 2);
    ctx.fillStyle   = state.color.startsWith('var') ? '#f87171' : state.color;
    ctx.strokeStyle = '#0e0e10';
    ctx.lineWidth   = 1.5;
    ctx.fill();
    ctx.stroke();
    // label
    ctx.fillStyle  = 'rgba(255,255,255,0.45)';
    ctx.font       = '9px monospace';
    ctx.textAlign  = 'center';
    ctx.fillText((v > 0 ? '+' : '') + v, xOf(i), yOf(v) - 8);
  });
}

// ── main render ───────────────────────────────────────────
function renderAll() {
  const slice  = rawData.slice(0, visN);           // newest-first
  const chrono = [...slice].reverse();             // oldest-first for charts/trend

  renderSummary(chrono);
  renderEntryList(slice);   // keep newest-first for list (most recent on top)
  renderBarChart(chrono);
  renderTrend(chrono);
}

// ── load data ─────────────────────────────────────────────
async function load() {
  ['summaryRow','entryList','barChart'].forEach(id => {
    document.getElementById(id).innerHTML =
      '<div class="loading"><div class="spinner"></div> Memuat…</div>';
  });
  try {
    const r = await fetch('?action=get_spica');
    const d = await r.json();
    if (!Array.isArray(d) || !d.length) throw new Error('empty');
    rawData = d;
    setStatus(true);
  } catch {
    rawData = [
      {Registration:'2025-07-10 08:30:00', Senang:7, Sedih:1, Grief:0},
      {Registration:'2025-07-08 14:15:00', Senang:5, Sedih:3, Grief:1},
      {Registration:'2025-07-06 09:00:00', Senang:3, Sedih:3, Grief:0},
      {Registration:'2025-07-04 11:45:00', Senang:2, Sedih:4, Grief:2},
      {Registration:'2025-07-02 16:20:00', Senang:1, Sedih:5, Grief:3},
      {Registration:'2025-06-30 10:00:00', Senang:0, Sedih:6, Grief:2},
      {Registration:'2025-06-28 13:30:00', Senang:1, Sedih:5, Grief:3},
      {Registration:'2025-06-26 08:00:00', Senang:2, Sedih:4, Grief:2},
      {Registration:'2025-06-24 15:10:00', Senang:4, Sedih:2, Grief:1},
      {Registration:'2025-06-22 09:45:00', Senang:6, Sedih:1, Grief:0},
    ];
    setStatus(false);
  }
  renderAll();
}

// ── range buttons ─────────────────────────────────────────
document.querySelectorAll('.range-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.range-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    visN = parseInt(this.dataset.n);
    renderAll();
  });
});

// ── reload ────────────────────────────────────────────────
document.getElementById('reloadBtn').addEventListener('click', load);

// ── accordion ─────────────────────────────────────────────
document.getElementById('accBtn').addEventListener('click', function() {
  this.classList.toggle('open');
  document.getElementById('accBody').classList.toggle('open');
});

// ── resize redraw ─────────────────────────────────────────
window.addEventListener('resize', () => { if (rawData.length) renderTrend([...rawData.slice(0,visN)].reverse()); });

// ── init ──────────────────────────────────────────────────
load();
</script>
</body>
</html>