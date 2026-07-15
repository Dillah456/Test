
<?php
// ============================================================
//  Dashboard Oortmyid — Single File PHP
//  Formalhault (SQL) + Name-List (XANO API) + Spica NEL
// ============================================================

$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_e0";

// ---- Handle AJAX requests ----
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'get_formalhault') {
        $conn = new mysqli($db_server, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            echo json_encode(['error' => $conn->connect_error]);
            exit;
        }
        $res = $conn->query("SELECT Nama, Jenis, RegNo, Saldo, Hutang, Modal FROM formalhault");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $conn->close();
        echo json_encode($rows);
        exit;
    }

    if ($_GET['action'] === 'get_sirius') {
        $conn = new mysqli($db_server, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) { echo json_encode(['error'=>$conn->connect_error]); exit; }
        $res = $conn->query("SELECT * FROM sirius LIMIT 1");
        $row = $res->fetch_assoc();
        $conn->close();
        echo json_encode($row ?: []);
        exit;
    }

    // Spica: ambil 10 transaksi terakhir (bukan LIMIT 1)
    if ($_GET['action'] === 'get_spica') {
        $conn = new mysqli($db_server, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) { echo json_encode(['error'=>$conn->connect_error]); exit; }
        $res = $conn->query("SELECT Registration, Senang, Sedih, Grief FROM spica ORDER BY Registration DESC LIMIT 10");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $conn->close();
        echo json_encode($rows ?: []);
        exit;
    }

    if ($_GET['action'] === 'proxy_namelist') {
        $method = $_SERVER['REQUEST_METHOD'];
        $url    = 'https://x8ki-letl-twmt.n7.xano.io/api:ZBg3zfDx/name_list';
        $opts   = ['http' => ['method' => $method, 'header' => 'Content-Type: application/json', 'ignore_errors' => true]];
        if ($method === 'POST') {
            $body = file_get_contents('php://input');
            $opts['http']['content'] = $body;
        }
        $ctx  = stream_context_create($opts);
        $resp = file_get_contents($url, false, $ctx);
        echo $resp ?: json_encode(['error' => 'Gagal terhubung ke XANO']);
        exit;
    }

    if ($_GET['action'] === 'proxy_hunt') {
        $url  = 'https://x8ki-letl-twmt.n7.xano.io/api:X6h8irt0/hunt';
        $ctx  = stream_context_create(['http'=>['method'=>'GET','ignore_errors'=>true]]);
        $resp = file_get_contents($url, false, $ctx);
        echo $resp ?: json_encode(['error'=>'Gagal']);
        exit;
    }

    if ($_GET['action'] === 'proxy_kwitansi') {
        $url  = 'https://x8ki-letl-twmt.n7.xano.io/api:ZBg3zfDx/kwitansi';
        $ctx  = stream_context_create(['http'=>['method'=>'GET','ignore_errors'=>true]]);
        $resp = file_get_contents($url, false, $ctx);
        echo $resp ?: json_encode(['error'=>'Gagal']);
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
<title>Dashboard Oortmyid</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg: #f5f4f0;
  --surface: #ffffff;
  --surface2: #f0eeea;
  --border: rgba(0,0,0,0.12);
  --border2: rgba(0,0,0,0.22);
  --text: #1a1a18;
  --text2: #6b6a66;
  --text3: #9e9d99;
  --blue: #185FA5;
  --blue-light: #E6F1FB;
  --blue-mid: #85B7EB;
  --blue-dark: #042C53;
  --green: #3B6D11;
  --green-light: #EAF3DE;
  --red: #A32D2D;
  --red-light: #FCEBEB;
  --amber-light: #FAEEDA;
  --amber-dark: #633806;
  --radius: 10px;
  --radius-sm: 7px;

  /* NEL state colors */
  --nel-growth:    #3B6D11;
  --nel-stable:    #185FA5;
  --nel-critical:  #7a6500;
  --nel-deficit:   #c06a00;
  --nel-drift:     #8B2020;
  --nel-breakdown: #5a0a0a;

  --nel-growth-bg:    #EAF3DE;
  --nel-stable-bg:    #E6F1FB;
  --nel-critical-bg:  #FEF9D7;
  --nel-deficit-bg:   #FAEEDA;
  --nel-drift-bg:     #FCEBEB;
  --nel-breakdown-bg: #2d0808;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
}

/* TOPBAR */
.topbar {
  background: var(--surface);
  border-bottom: 0.5px solid var(--border);
  padding: 0 2rem;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 50;
}
.brand { display: flex; align-items: center; gap: 10px; }
.brand svg { width: 20px; height: 20px; color: var(--blue); flex-shrink: 0; }
.brand h1 { font-size: 15px; font-weight: 600; letter-spacing: -0.3px; }
.brand .v { font-size: 11px; color: var(--text2); background: var(--surface2); border: 0.5px solid var(--border); border-radius: 20px; padding: 3px 9px; }
.status { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text2); }
.dot { width: 7px; height: 7px; border-radius: 50%; background: #639922; flex-shrink: 0; }
.dot.off { background: var(--text3); }

/* MAIN */
.main { max-width: 1100px; margin: 0 auto; padding: 1.5rem; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
@media (max-width: 720px) { .grid2 { grid-template-columns: 1fr; } }

/* PANEL */
.panel {
  background: var(--surface);
  border: 0.5px solid var(--border);
  border-radius: var(--radius);
  padding: 1.1rem 1.3rem;
}
.panel-title {
  font-size: 10.5px;
  font-weight: 600;
  color: var(--text2);
  text-transform: uppercase;
  letter-spacing: 0.7px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.pill {
  font-size: 10px;
  background: var(--blue-light);
  color: var(--blue);
  border-radius: 20px;
  padding: 2px 8px;
  text-transform: none;
  letter-spacing: 0;
  font-weight: 500;
}
.divider { height: 0.5px; background: var(--border); margin: 12px 0; }

/* METRICS */
.metric-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-bottom: 12px; }
.metric { background: var(--surface2); border-radius: var(--radius-sm); padding: 10px 10px 8px; }
.metric label { font-size: 10.5px; color: var(--text2); display: block; margin-bottom: 4px; }
.metric .val { font-size: 21px; font-weight: 600; color: var(--text); line-height: 1; }
.metric .sub { font-size: 10px; color: var(--text3); margin-top: 2px; }

/* CHIPS */
.filter-label { font-size: 10.5px; color: var(--text2); margin-bottom: 6px; font-weight: 500; }
.chips { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 10px; }
.chip {
  font-size: 11px; padding: 4px 11px; border-radius: 20px;
  border: 0.5px solid var(--border2); background: var(--surface2);
  color: var(--text2); cursor: pointer; transition: all 0.13s; user-select: none;
}
.chip.active { background: var(--blue-light); border-color: var(--blue-mid); color: var(--blue-dark); }
.chip:hover:not(.active) { background: #e8e6e2; }

/* TABLE */
.tbl-wrap { overflow-x: auto; }
table { width: 100%; font-size: 12px; border-collapse: collapse; }
thead th { color: var(--text2); font-weight: 600; text-align: left; padding: 5px 7px; border-bottom: 0.5px solid var(--border); font-size: 10.5px; white-space: nowrap; }
tbody td { padding: 6px 7px; color: var(--text); border-bottom: 0.5px solid var(--border); }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: var(--surface2); }
.muted { color: var(--text3) !important; }
.num { text-align: right; font-variant-numeric: tabular-nums; }

/* SEARCH & BUTTONS */
.search-row { display: flex; gap: 7px; margin-bottom: 10px; }
.search-row input {
  flex: 1; height: 34px; font-size: 12.5px; padding: 0 11px;
  border-radius: var(--radius-sm); border: 0.5px solid var(--border2);
  background: var(--surface2); color: var(--text); outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.search-row input:focus { border-color: var(--blue-mid); box-shadow: 0 0 0 2px rgba(55,138,221,0.13); }
.btn {
  height: 34px; padding: 0 13px; font-size: 12px; border-radius: var(--radius-sm);
  border: 0.5px solid var(--border2); background: var(--surface);
  color: var(--text); cursor: pointer; transition: background 0.13s;
}
.btn:hover { background: var(--surface2); }
.btn.primary { background: var(--blue); color: #fff; border-color: var(--blue); }
.btn.primary:hover { background: #0C447C; }

/* FULL DIVE BUTTON */
.fd-btn {
  width: 100%; padding: 14px; background: var(--blue-dark); color: var(--blue-light);
  border: none; border-radius: var(--radius); font-size: 14px; font-weight: 600;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  gap: 9px; letter-spacing: 0.2px; transition: background 0.18s; margin-top: 14px;
}
.fd-btn:hover { background: #0c3460; }
.fd-btn svg { width: 18px; height: 18px; flex-shrink: 0; }

/* NEL PANEL */
.nel-panel { display: none; background: var(--surface); border: 0.5px solid var(--border); border-radius: var(--radius); padding: 1.2rem 1.4rem; margin-top: 14px; }
.nel-panel.open { display: block; }

/* NEL SUMMARY BAR */
.nel-summary {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 10px;
  margin-bottom: 14px;
}
.nel-summary-card {
  border-radius: var(--radius-sm);
  padding: 11px 13px;
  border: 0.5px solid var(--border);
}
.nel-summary-card .nsc-label { font-size: 10px; color: var(--text3); margin-bottom: 3px; }
.nel-summary-card .nsc-val   { font-size: 22px; font-weight: 700; line-height: 1; }
.nel-summary-card .nsc-sub   { font-size: 10.5px; margin-top: 3px; font-weight: 500; }

/* STATE BADGE */
.state-badge {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 10.5px; font-weight: 600; padding: 3px 10px; border-radius: 20px;
  white-space: nowrap;
}
.state-badge .sb-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

/* State color helpers (applied via JS) */
.s-growth    { background: var(--nel-growth-bg);    color: var(--nel-growth); }
.s-stable    { background: var(--nel-stable-bg);    color: var(--nel-stable); }
.s-critical  { background: var(--nel-critical-bg);  color: var(--nel-critical); }
.s-deficit   { background: var(--nel-deficit-bg);   color: var(--nel-deficit); }
.s-drift     { background: var(--nel-drift-bg);     color: var(--nel-drift); }
.s-breakdown { background: var(--nel-breakdown-bg); color: #ffaaaa; }

/* SUSTAINED WARNING */
.sustained-warn {
  background: #2d0808; color: #ffaaaa;
  border: 0.5px solid #8b2020;
  border-radius: var(--radius-sm);
  padding: 10px 14px;
  font-size: 12px;
  display: flex; align-items: flex-start; gap: 9px;
  margin-bottom: 12px;
}
.sustained-warn svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }
.sustained-warn strong { display: block; font-size: 12.5px; margin-bottom: 2px; }

/* NEL TABLE overrides */
.nel-tbl thead th { background: var(--surface2); }
.nel-tbl td.net-pos { color: var(--nel-growth); font-weight: 600; }
.nel-tbl td.net-zero { color: var(--nel-critical); font-weight: 600; }
.nel-tbl td.net-neg { color: var(--nel-drift); font-weight: 600; }
.nel-tbl td.net-breakdown { color: #A32D2D; font-weight: 700; }

/* BAR CHART */
.nel-chart { margin-top: 14px; }
.nel-chart-title { font-size: 10.5px; color: var(--text2); font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; }
.bar-row { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; font-size: 11px; }
.bar-row .bar-label { width: 22px; color: var(--text3); text-align: right; flex-shrink: 0; font-size: 10px; }
.bar-row .bar-track { flex: 1; height: 14px; background: var(--surface2); border-radius: 3px; overflow: hidden; position: relative; }
.bar-row .bar-fill { height: 100%; border-radius: 3px; transition: width 0.4s ease; min-width: 2px; }
.bar-row .bar-num { width: 30px; text-align: right; font-size: 10.5px; color: var(--text2); font-variant-numeric: tabular-nums; }
.zero-line { position: absolute; left: 50%; top: 0; bottom: 0; width: 1px; background: var(--border2); }

/* LEGEND */
.nel-legend { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 10px; }
.nel-legend-item { display: flex; align-items: center; gap: 5px; font-size: 10.5px; color: var(--text2); }
.legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

.empty { color: var(--text3); font-size: 12px; padding: 12px 0; text-align: center; }
.loading-txt { color: var(--text3); font-size: 12px; padding: 8px 0; }

/* ACCORDION STATE EXPLANATION */
.acc-wrap { margin-top: 14px; border: 0.5px solid var(--border); border-radius: var(--radius); overflow: hidden; }
.acc-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px; background: var(--surface2);
  cursor: pointer; user-select: none;
  font-size: 10.5px; font-weight: 600; color: var(--text2);
  text-transform: uppercase; letter-spacing: 0.7px;
  border: none; width: 100%; text-align: left;
  transition: background 0.13s;
}
.acc-header:hover { background: #e8e6e2; }
.acc-header .acc-arrow {
  width: 14px; height: 14px; transition: transform 0.22s ease;
  flex-shrink: 0; color: var(--text3);
}
.acc-header.open .acc-arrow { transform: rotate(180deg); }
.acc-body {
  display: none; padding: 14px;
  border-top: 0.5px solid var(--border);
  background: var(--surface);
}
.acc-body.open { display: block; }

.state-block { margin-bottom: 12px; }
.state-block:last-child { margin-bottom: 0; }
.state-block-header {
  display: flex; align-items: center; gap: 8px; margin-bottom: 6px;
}
.state-block-bar {
  width: 3px; border-radius: 2px; align-self: stretch; flex-shrink: 0; min-height: 16px;
}
.state-block-title { font-size: 12px; font-weight: 600; }
.state-block-range { font-size: 10px; color: var(--text3); font-family: monospace; margin-left: auto; }
.state-block-desc { font-size: 11.5px; color: var(--text2); line-height: 1.6; padding-left: 11px; }
.state-block-ciri { margin-top: 4px; display: flex; flex-wrap: wrap; gap: 5px; padding-left: 11px; }
.ciri-tag {
  font-size: 10px; padding: 2px 8px; border-radius: 20px;
  background: var(--surface2); color: var(--text2);
  border: 0.5px solid var(--border);
}
.state-divider { height: 0.5px; background: var(--border); margin: 10px 0; }

.sustained-note {
  margin-top: 10px; padding: 10px 13px;
  background: #1a0505; border: 0.5px solid #5a1010;
  border-radius: var(--radius-sm); font-size: 11.5px; color: #ffbbbb; line-height: 1.6;
}
.sustained-note strong { display: block; font-size: 12px; color: #ffdddd; margin-bottom: 4px; }

/* TAGS */
.tag { display: inline-block; font-size: 10px; padding: 2px 8px; border-radius: 20px; }
.tag-blue { background: var(--blue-light); color: var(--blue-dark); }
.tag-green { background: var(--green-light); color: var(--green); }
.tag-amber { background: var(--amber-light); color: var(--amber-dark); }

/* MODAL */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 200; align-items: center; justify-content: center; }
.modal-overlay.open { display: flex; }
.modal { background: var(--surface); border-radius: var(--radius); border: 0.5px solid var(--border); padding: 1.5rem 1.6rem; width: 360px; max-width: 95vw; box-shadow: 0 8px 40px rgba(0,0,0,0.18); }
.modal h2 { font-size: 15px; font-weight: 600; margin-bottom: 1rem; }
.modal label { font-size: 12px; color: var(--text2); display: block; margin-bottom: 3px; margin-top: 12px; }
.modal input { width: 100%; height: 36px; font-size: 13px; padding: 0 11px; border-radius: var(--radius-sm); border: 0.5px solid var(--border2); background: var(--surface2); color: var(--text); outline: none; }
.modal input:focus { border-color: var(--blue-mid); box-shadow: 0 0 0 2px rgba(55,138,221,0.13); }
.modal-btns { display: flex; gap: 8px; margin-top: 1.25rem; justify-content: flex-end; }
.modal-msg { font-size: 12px; margin-top: 8px; min-height: 18px; }
.ok  { color: var(--green); }
.err { color: var(--red); }
</style>
</head>
<body>

<div class="topbar">
  <div class="brand">
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
      <rect x="3" y="3" width="6" height="6" rx="1.5"/>
      <rect x="11" y="3" width="6" height="6" rx="1.5"/>
      <rect x="3" y="11" width="6" height="6" rx="1.5"/>
      <rect x="11" y="11" width="6" height="6" rx="1.5"/>
    </svg>
    <h1>Oortmyid Dashboard</h1>
    <span class="v">v1.0</span>
  </div>
  <div class="status">
    <span class="dot off" id="apiDot"></span>
    <span id="apiStatus">Menghubungkan...</span>
  </div>
</div>

<div class="main">
  <div class="grid2">

    <!-- FORMALHAULT -->
    <div class="panel">
      <div class="panel-title">Formalhault <span class="pill">SQL · oortmyid_e0</span></div>
      <div class="metric-row">
        <div class="metric"><label>Saldo</label><div class="val" id="fhSaldo">—</div><div class="sub">total</div></div>
        <div class="metric"><label>Hutang</label><div class="val" id="fhHutang">—</div><div class="sub">total</div></div>
        <div class="metric"><label>Modal</label><div class="val" id="fhModal">—</div><div class="sub">total</div></div>
      </div>
      <div class="divider"></div>
      <div class="filter-label">Filter entitas</div>
      <div class="chips" id="fhChips">
        <div class="chip active" data-key="Nama">Nama</div>
        <div class="chip" data-key="Jenis">Jenis</div>
        <div class="chip" data-key="RegNo">RegNo</div>
        <div class="chip" data-key="Saldo">Saldo</div>
        <div class="chip" data-key="Hutang">Hutang</div>
        <div class="chip" data-key="Modal">Modal</div>
      </div>
      <div class="tbl-wrap" id="fhWrap"><div class="loading-txt">Memuat data SQL...</div></div>
    </div>

    <!-- NAME-LIST -->
    <div class="panel">
      <div class="panel-title">Name-List <span class="pill">XANO API</span></div>
      <div class="search-row">
        <input type="text" id="nlSearch" placeholder="Cari nama..." />
        <button class="btn primary" id="addBtn">+ Tambah</button>
      </div>
      <div class="tbl-wrap" id="nlWrap"><div class="loading-txt">Memuat data XANO...</div></div>
    </div>

  </div>

  <!-- FULL DIVE / NEL BUTTON -->
  <button class="fd-btn" id="fdBtn">
    <svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8">
      <path d="M9 2C5.13 2 2 5.13 2 9s3.13 7 7 7 7-3.13 7-7-3.13-7-7-7z"/>
      <path d="M9 6v3.5l2.5 1.5"/>
      <path d="M4.5 4.5l1.2 1.2M13.5 13.5l-1.2-1.2M13.5 4.5l-1.2 1.2M4.5 13.5l1.2-1.2"/>
    </svg>
    <span id="fdLabel">Net Emotional Load — Analisis Spica</span>
  </button>

  <!-- NEL PANEL -->
  <div class="nel-panel" id="nelPanel">
    <div class="panel-title">
      Net Emotional Load
      <span class="pill">Spica · 10 Transaksi Terakhir</span>
    </div>

    <!-- Sustained warning (shown if triggered) -->
    <div class="sustained-warn" id="sustainedWarn" style="display:none;">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6">
        <path d="M8 2L14 13H2L8 2z"/><path d="M8 7v3M8 11.5v.5"/>
      </svg>
      <div>
        <strong>⚠ Sustained Depressive State Terdeteksi</strong>
        <span id="sustainedDesc"></span>
      </div>
    </div>

    <!-- Summary cards -->
    <div class="nel-summary" id="nelSummary"></div>

    <!-- NEL table -->
    <div class="tbl-wrap">
      <table class="nel-tbl">
        <thead>
          <tr>
            <th>#</th>
            <th>Registration</th>
            <th style="text-align:right">Senang</th>
            <th style="text-align:right">Sedih</th>
            <th style="text-align:right">Grief</th>
            <th style="text-align:right">Net</th>
            <th>State</th>
          </tr>
        </thead>
        <tbody id="nelTbody">
          <tr><td colspan="7" class="loading-txt" style="padding:12px 7px;">Memuat data Spica...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Bar chart -->
    <div class="nel-chart" id="nelChart"></div>

    <!-- Legend -->
    <div class="nel-legend">
      <div class="nel-legend-item"><span class="legend-dot" style="background:var(--nel-growth)"></span>Recovery/Growth (Net&gt;3)</div>
      <div class="nel-legend-item"><span class="legend-dot" style="background:var(--nel-stable)"></span>Stable Vulnerable (0&lt;Net≤3)</div>
      <div class="nel-legend-item"><span class="legend-dot" style="background:var(--nel-critical)"></span>Critical Equilibrium (Net=0)</div>
      <div class="nel-legend-item"><span class="legend-dot" style="background:var(--nel-deficit)"></span>Emotional Deficit (-3 s/d -1)</div>
      <div class="nel-legend-item"><span class="legend-dot" style="background:var(--nel-drift)"></span>Depressive Drift (-7 s/d -4)</div>
      <div class="nel-legend-item"><span class="legend-dot" style="background:#A32D2D"></span>Breakdown Risk (&lt;-7)</div>
    </div>

    <!-- ACCORDION: Penjelasan State -->
    <div class="acc-wrap" style="margin-top:16px;">
      <button class="acc-header" id="accStateBtn">
        Penjelasan Threshold State NEL
        <svg class="acc-arrow" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M2.5 5l4.5 4 4.5-4"/>
        </svg>
      </button>
      <div class="acc-body" id="accStateBody">

        <!-- Growth -->
        <div class="state-block">
          <div class="state-block-header">
            <div class="state-block-bar" style="background:var(--nel-growth)"></div>
            <span class="state-block-title" style="color:var(--nel-growth)">Recovery / Growth</span>
            <span class="state-block-range">Net &gt; 3</span>
          </div>
          <div class="state-block-desc">
            Surplus energi emosional cukup. Individu memiliki buffer yang memadai untuk menghadapi tekanan tanpa langsung collapse.
          </div>
          <div class="state-block-ciri">
            <span class="ciri-tag">Motivasi hidup aktif</span>
            <span class="ciri-tag">Fleksibel terhadap perubahan</span>
            <span class="ciri-tag">Kapasitas sosial tersedia</span>
            <span class="ciri-tag">Resiliensi tinggi</span>
          </div>
        </div>
        <div class="state-divider"></div>

        <!-- Stable -->
        <div class="state-block">
          <div class="state-block-header">
            <div class="state-block-bar" style="background:var(--nel-stable)"></div>
            <span class="state-block-title" style="color:var(--nel-stable)">Stable but Vulnerable</span>
            <span class="state-block-range">0 &lt; Net ≤ 3</span>
          </div>
          <div class="state-block-desc">
            Masih positif, tapi marginnya tipis. Fungsi berjalan normal, namun tidak ada buffer cadangan. Area "fungsi normal tapi rapuh" — satu trigger eksternal bisa langsung menggeser ke negatif.
          </div>
          <div class="state-block-ciri">
            <span class="ciri-tag">Tetap fungsional</span>
            <span class="ciri-tag">Mudah drop jika ada trigger baru</span>
            <span class="ciri-tag">Butuh kondisi stabil</span>
          </div>
        </div>
        <div class="state-divider"></div>

        <!-- Critical -->
        <div class="state-block">
          <div class="state-block-header">
            <div class="state-block-bar" style="background:var(--nel-critical)"></div>
            <span class="state-block-title" style="color:var(--nel-critical)">Critical Equilibrium</span>
            <span class="state-block-range">Net = 0</span>
          </div>
          <div class="state-block-desc">
            Impas — tidak ada surplus, tidak ada defisit. Berbahaya justru karena terlihat "aman". Tidak ada buffer sama sekali: satu trigger kecil langsung mendorong ke zona negatif.
          </div>
          <div class="state-block-ciri">
            <span class="ciri-tag">Tidak ada cadangan energi</span>
            <span class="ciri-tag">Ekuilibrium semu</span>
            <span class="ciri-tag">Rentan trigger kecil sekalipun</span>
          </div>
        </div>
        <div class="state-divider"></div>

        <!-- Deficit -->
        <div class="state-block">
          <div class="state-block-header">
            <div class="state-block-bar" style="background:var(--nel-deficit)"></div>
            <span class="state-block-title" style="color:var(--nel-deficit)">Emotional Deficit</span>
            <span class="state-block-range">-3 ≤ Net &lt; 0</span>
          </div>
          <div class="state-block-desc">
            Defisit ringan. Erosion emosional mulai terjadi, belum mencapai level depresi klinis tapi penurunan afektif sudah terasa. Fase awal withdrawal.
          </div>
          <div class="state-block-ciri">
            <span class="ciri-tag">Kelelahan emosional</span>
            <span class="ciri-tag">Withdrawal kecil-kecilan</span>
            <span class="ciri-tag">Overthinking meningkat</span>
            <span class="ciri-tag">Belum depresi, tapi erosion dimulai</span>
          </div>
        </div>
        <div class="state-divider"></div>

        <!-- Drift -->
        <div class="state-block">
          <div class="state-block-header">
            <div class="state-block-bar" style="background:var(--nel-drift)"></div>
            <span class="state-block-title" style="color:var(--nel-drift)">Depressive Drift</span>
            <span class="state-block-range">-7 ≤ Net &lt; -4</span>
          </div>
          <div class="state-block-desc">
            Fase geser ke bawah. Belum collapse, tapi trajektori mengarah ke deteriorasi. Makna dan kesenangan menurun signifikan, isolasi meningkat. Zona ini berbahaya justru karena masih "bisa jalan" — sehingga sering tidak ditangani.
          </div>
          <div class="state-block-ciri">
            <span class="ciri-tag">Makna hidup menurun</span>
            <span class="ciri-tag">Anhedonia parsial</span>
            <span class="ciri-tag">Isolasi sosial meningkat</span>
            <span class="ciri-tag">Panic lebih mudah aktif</span>
            <span class="ciri-tag">Arah ke bawah</span>
          </div>
        </div>
        <div class="state-divider"></div>

        <!-- Breakdown -->
        <div class="state-block">
          <div class="state-block-header">
            <div class="state-block-bar" style="background:#A32D2D"></div>
            <span class="state-block-title" style="color:#A32D2D">Depressive State / Breakdown Risk</span>
            <span class="state-block-range">Net &lt; -7</span>
          </div>
          <div class="state-block-desc">
            Red zone. Bukan diagnosis klinis, tapi warning operasional serius. Energi mental drop berat, distress dominan atas semua fungsi lain. Fungsi harian terganggu.
          </div>
          <div class="state-block-ciri">
            <span class="ciri-tag">Energi mental sangat rendah</span>
            <span class="ciri-tag">Hopeless patterning</span>
            <span class="ciri-tag">Distress dominan</span>
            <span class="ciri-tag">Fungsi harian terganggu</span>
          </div>
        </div>

        <!-- Sustained note -->
        <div class="sustained-note">
          <strong>⚠ Sustained Depressive State — Tentang Durasi</strong>
          Jika Net &lt; -4 selama 3+ event berurutan, sistem menandai <em>Sustained Depressive State</em>. Ini lebih berbahaya dari satu-kali breakdown berat: depresi bukan hanya soal intensitas, tapi <strong>durasi</strong>. Negatif sekali belum tentu bahaya — negatif sedang tapi terus-menerus justru lebih merusak karena erosion berlangsung tanpa pemulihan.
        </div>

      </div>
    </div>

  </div>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <h2>Tambah Name-List</h2>
    <label>Nama</label>
    <input type="text" id="newNama" placeholder="Masukkan nama..." />
    <div class="modal-btns">
      <button class="btn" id="cancelBtn">Batal</button>
      <button class="btn primary" id="saveBtn">Simpan</button>
    </div>
    <div class="modal-msg" id="modalMsg"></div>
  </div>
</div>

<script>
// ======================== STATE ========================
let nlData = [];
let fhData = [];
let activeFilters = ['Nama'];
let nelLoaded = false, nelOpen = false;
const NUM_FIELDS = ['Saldo','Hutang','Modal'];

// ======================== UTILS ========================
function fmt(n) {
  n = +n;
  if (isNaN(n)) return '—';
  if (n >= 1e6) return (n/1e6).toFixed(1)+'M';
  if (n >= 1e3) return (n/1e3).toFixed(0)+'K';
  return n.toLocaleString('id-ID');
}
function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function setStatus(ok) {
  document.getElementById('apiDot').className = 'dot'+(ok?'':' off');
  document.getElementById('apiStatus').textContent = ok ? 'API terhubung' : 'Demo mode';
}

// ======================== NEL LOGIC ========================
function calcNEL(row) {
  const senang = parseFloat(row.Senang ?? row.senang ?? 0);
  const sedih  = parseFloat(row.Sedih  ?? row.sedih  ?? 0);
  const grief  = parseFloat(row.Grief  ?? row.grief  ?? 0);
  const net    = senang - (sedih + grief);
  return { senang, sedih, grief, net };
}

function getState(net) {
  if (net > 3)  return { label: 'Recovery / Growth',        cls: 's-growth',    color: 'var(--nel-growth)',   short: 'Growth' };
  if (net > 0)  return { label: 'Stable but Vulnerable',    cls: 's-stable',    color: 'var(--nel-stable)',   short: 'Stable' };
  if (net === 0) return { label: 'Critical Equilibrium',    cls: 's-critical',  color: 'var(--nel-critical)', short: 'Critical' };
  if (net >= -3) return { label: 'Emotional Deficit',       cls: 's-deficit',   color: 'var(--nel-deficit)',  short: 'Deficit' };
  if (net >= -7) return { label: 'Depressive Drift',        cls: 's-drift',     color: 'var(--nel-drift)',    short: 'Drift' };
  return           { label: 'Depressive State / Breakdown', cls: 's-breakdown', color: '#A32D2D',             short: 'Breakdown' };
}

function checkSustained(rows) {
  // IF Net < -4 for 3+ consecutive events → Sustained Depressive State
  let streak = 0, maxStreak = 0, streakEnd = -1;
  rows.forEach((r, i) => {
    const { net } = calcNEL(r);
    if (net < -4) {
      streak++;
      if (streak > maxStreak) { maxStreak = streak; streakEnd = i; }
    } else {
      streak = 0;
    }
  });
  return maxStreak >= 3 ? { triggered: true, streak: maxStreak, streakEnd } : { triggered: false };
}

function netClass(net) {
  if (net > 0)  return 'net-pos';
  if (net === 0) return 'net-zero';
  if (net >= -7) return 'net-neg';
  return 'net-breakdown';
}

// ======================== NEL RENDER ========================
function renderNEL(rows) {
  // --- rows: newest first (ORDER BY id DESC from PHP) ---
  // Reverse so oldest → newest for streak check (chronological)
  const chrono = [...rows].reverse();

  // Sustained check
  const sus = checkSustained(chrono);
  const susEl = document.getElementById('sustainedWarn');
  const susDesc = document.getElementById('sustainedDesc');
  if (sus.triggered) {
    susEl.style.display = 'flex';
    susDesc.textContent = `Net < -4 terdeteksi ${sus.streak} event berurutan. Intensitas rendah + durasi panjang = risiko lebih tinggi dari satu-kali breakdown.`;
  } else {
    susEl.style.display = 'none';
  }

  // Summary
  const nets = chrono.map(r => calcNEL(r).net);
  const avgNet = nets.reduce((a,b)=>a+b,0) / nets.length;
  const minNet = Math.min(...nets);
  const maxNet = Math.max(...nets);
  const latestState = getState(nets[nets.length-1]);
  const overallState = getState(avgNet);

  const sumEl = document.getElementById('nelSummary');
  sumEl.innerHTML = `
    <div class="nel-summary-card" style="background:var(--surface2);">
      <div class="nsc-label">Rata-rata Net</div>
      <div class="nsc-val" style="color:${overallState.color}">${avgNet.toFixed(1)}</div>
      <div class="nsc-sub" style="color:${overallState.color}">${overallState.short}</div>
    </div>
    <div class="nel-summary-card" style="background:var(--surface2);">
      <div class="nsc-label">State Terkini</div>
      <div class="nsc-val" style="font-size:13px;margin-top:4px;color:${latestState.color}">${latestState.short}</div>
      <div class="nsc-sub" style="color:${latestState.color}">Transaksi terakhir</div>
    </div>
    <div class="nel-summary-card" style="background:var(--surface2);">
      <div class="nsc-label">Terendah / Tertinggi</div>
      <div class="nsc-val" style="font-size:16px;color:var(--nel-drift)">${minNet.toFixed(0)}</div>
      <div class="nsc-sub" style="color:var(--nel-growth)">/ ${maxNet.toFixed(0)} tertinggi</div>
    </div>
    ${sus.triggered ? `
    <div class="nel-summary-card" style="background:#2d0808;border-color:#8b2020;">
      <div class="nsc-label" style="color:#ff9999">Sustained</div>
      <div class="nsc-val" style="color:#ffaaaa;font-size:16px">${sus.streak}× berurutan</div>
      <div class="nsc-sub" style="color:#ff9999">Net &lt; -4</div>
    </div>` : ''}
  `;

  // Table — display newest first (rows already newest first from DB)
  const tbody = document.getElementById('nelTbody');
  let html = '';
  rows.forEach((r, i) => {
    const { senang, sedih, grief, net } = calcNEL(r);
    const state = getState(net);
    const ref = r.Registration ?? r.id ?? (i+1);
    html += `
      <tr>
        <td class="muted">${i+1}</td>
        <td style="font-size:11px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${esc(String(ref))}</td>
        <td class="num" style="color:var(--nel-growth)">${senang}</td>
        <td class="num" style="color:var(--nel-drift)">${sedih}</td>
        <td class="num" style="color:var(--nel-breakdown)">${grief}</td>
        <td class="num ${netClass(net)}">${net > 0 ? '+' : ''}${net}</td>
        <td><span class="state-badge ${state.cls}"><span class="sb-dot" style="background:${state.color}"></span>${state.label}</span></td>
      </tr>`;
  });
  tbody.innerHTML = html;

  // Bar chart
  renderNELChart(chrono);
}

function renderNELChart(chrono) {
  const absMax = Math.max(...chrono.map(r => Math.abs(calcNEL(r).net)), 1);
  let h = `<div class="nel-chart-title">Net per Transaksi (kronologis)</div>`;
  chrono.forEach((r, i) => {
    const { net } = calcNEL(r);
    const state = getState(net);
    const pct   = Math.abs(net) / absMax * 50; // 50% = half of track = max
    const isNeg = net < 0;
    // Bar starts from center: positive goes right, negative goes left
    const leftPct  = isNeg ? (50 - pct) : 50;
    const widthPct = pct;
    h += `
      <div class="bar-row">
        <span class="bar-label">${i+1}</span>
        <div class="bar-track">
          <div class="zero-line"></div>
          <div class="bar-fill" style="
            position:absolute;
            left:${leftPct}%;
            width:${widthPct}%;
            background:${state.color};
            opacity:0.85;
            top:0; bottom:0;
          "></div>
        </div>
        <span class="bar-num" style="color:${state.color}">${net>0?'+':''}${net}</span>
      </div>`;
  });
  document.getElementById('nelChart').innerHTML = h;
}

// ======================== LOAD NEL ========================
async function loadNEL() {
  try {
    const r = await fetch('?action=get_spica');
    const d = await r.json();
    if (!Array.isArray(d) || !d.length) throw new Error('empty');
    renderNEL(d);
  } catch {
    // Demo fallback: 10 rows with variety
    const demo = [
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
    renderNEL(demo);
  }
}

// ======================== FORMALHAULT ========================
async function loadFH() {
  try {
    const r = await fetch('?action=get_formalhault');
    const d = await r.json();
    if (d.error) throw new Error(d.error);
    fhData = d;
  } catch(e) {
    fhData = [
      {Nama:'Arjuna',Jenis:1,RegNo:1001,Saldo:1500000,Hutang:200000,Modal:3000000},
      {Nama:'Sinta', Jenis:2,RegNo:1002,Saldo:800000, Hutang:50000, Modal:1200000},
      {Nama:'Gatot', Jenis:1,RegNo:1003,Saldo:2200000,Hutang:0,     Modal:5000000},
    ];
  }
  const totS = fhData.reduce((a,r)=>a+(+r.Saldo),0);
  const totH = fhData.reduce((a,r)=>a+(+r.Hutang),0);
  const totM = fhData.reduce((a,r)=>a+(+r.Modal),0);
  document.getElementById('fhSaldo').textContent  = fmt(totS);
  document.getElementById('fhHutang').textContent = fmt(totH);
  document.getElementById('fhModal').textContent  = fmt(totM);
  renderFH();
}

function renderFH() {
  const w = document.getElementById('fhWrap');
  if (!activeFilters.length) { w.innerHTML = '<div class="empty">Pilih filter untuk melihat data</div>'; return; }
  let h = '<table><thead><tr>' + activeFilters.map(f=>`<th>${esc(f)}</th>`).join('') + '</tr></thead><tbody>';
  fhData.forEach(r => {
    h += '<tr>' + activeFilters.map(f => {
      const isNum = NUM_FIELDS.includes(f);
      return `<td class="${isNum?'num':''}">${isNum ? fmt(r[f]) : esc(r[f])}</td>`;
    }).join('') + '</tr>';
  });
  h += '</tbody></table>';
  w.innerHTML = h;
}

document.getElementById('fhChips').addEventListener('click', e => {
  const chip = e.target.closest('.chip');
  if (!chip) return;
  const key = chip.dataset.key;
  if (chip.classList.contains('active')) {
    chip.classList.remove('active');
    activeFilters = activeFilters.filter(f=>f!==key);
  } else {
    chip.classList.add('active');
    activeFilters.push(key);
  }
  renderFH();
});

// ======================== NAME-LIST ========================
async function loadNL() {
  try {
    const r = await fetch('?action=proxy_namelist');
    const d = await r.json();
    if (d.error) throw new Error(d.error);
    nlData = Array.isArray(d) ? d : [];
    setStatus(true);
  } catch(e) {
    nlData = [{id:1,Nama:'Arjuna'},{id:2,Nama:'Sinta'},{id:3,Nama:'Bima'}];
    setStatus(false);
  }
  renderNL(nlData);
}

function renderNL(data) {
  const w = document.getElementById('nlWrap');
  if (!data.length) { w.innerHTML = '<div class="empty">Tidak ada data</div>'; return; }
  let h = '<table><thead><tr><th>#</th><th>Nama</th><th>ID</th></tr></thead><tbody>';
  data.forEach((r,i) => {
    h += `<tr><td class="muted">${i+1}</td><td>${esc(r.Nama||r.nama||'—')}</td><td class="muted">${esc(r.id??'—')}</td></tr>`;
  });
  w.innerHTML = h + '</tbody></table>';
}

document.getElementById('nlSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  renderNL(nlData.filter(r=>(r.Nama||r.nama||'').toLowerCase().includes(q)));
});

// ======================== MODAL ========================
function openModal()  { document.getElementById('modalOverlay').classList.add('open'); document.getElementById('newNama').focus(); }
function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  document.getElementById('newNama').value='';
  const m = document.getElementById('modalMsg');
  m.textContent=''; m.className='modal-msg';
}

document.getElementById('addBtn').addEventListener('click', openModal);
document.getElementById('cancelBtn').addEventListener('click', closeModal);
document.getElementById('modalOverlay').addEventListener('click', e => { if (e.target===e.currentTarget) closeModal(); });

document.getElementById('saveBtn').addEventListener('click', async () => {
  const nama = document.getElementById('newNama').value.trim();
  const msg  = document.getElementById('modalMsg');
  if (!nama) { msg.textContent='Nama tidak boleh kosong.'; msg.className='modal-msg err'; return; }
  const btn = document.getElementById('saveBtn');
  btn.textContent='Menyimpan...'; btn.disabled=true;
  try {
    const r = await fetch('?action=proxy_namelist', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({Nama: nama})
    });
    const d = await r.json();
    if (d.error) throw new Error(d.error);
    nlData.unshift(d);
    msg.textContent='Berhasil ditambahkan!'; msg.className='modal-msg ok';
  } catch {
    nlData.unshift({id:Date.now(), Nama:nama});
    msg.textContent='Tersimpan (demo mode).'; msg.className='modal-msg ok';
  }
  renderNL(nlData);
  btn.textContent='Simpan'; btn.disabled=false;
  setTimeout(closeModal, 900);
});

// ======================== NEL TOGGLE ========================
document.getElementById('fdBtn').addEventListener('click', () => {
  nelOpen = !nelOpen;
  document.getElementById('nelPanel').classList.toggle('open', nelOpen);
  document.getElementById('fdLabel').textContent = nelOpen
    ? 'Tutup Analisis NEL'
    : 'Net Emotional Load — Analisis Spica';
  if (nelOpen && !nelLoaded) { nelLoaded = true; loadNEL(); }
});

// ======================== KEYBOARD ========================
document.addEventListener('keydown', e => {
  if (e.key==='Escape') closeModal();
  if (e.key==='Enter' && document.getElementById('modalOverlay').classList.contains('open'))
    document.getElementById('saveBtn').click();
});

// ======================== ACCORDION ========================
document.getElementById('accStateBtn').addEventListener('click', function() {
  this.classList.toggle('open');
  document.getElementById('accStateBody').classList.toggle('open');
});

// ======================== INIT ========================
loadFH();
loadNL();
</script>
</body>
</html>