<?php
// ============================================================
//  Dashboard Oortmyid v3
//  Formalhault (SQL) + Name-List (XANO API) + Asosiasi JSON
//  Menu: Dashboard Saldo | Asosiasi Memori
// ============================================================

$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_e0";

if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'get_formalhault') {
        $conn = new mysqli($db_server, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) { echo json_encode(['error' => $conn->connect_error]); exit; }
        $res = $conn->query("SELECT Nama, Jenis, RegNo, Saldo, Hutang, Modal FROM formalhault");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $conn->close();
        echo json_encode($rows);
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

    // ── Asosiasi: read from JSON file ────────────────────────
    if ($_GET['action'] === 'get_asos') {
        $file = __DIR__ . '/asosiasi_oortmyid.json';
        if (!file_exists($file)) { echo json_encode([]); exit; }
        $data = json_decode(file_get_contents($file), true);
        echo json_encode($data ?: []);
        exit;
    }

    // ── Asosiasi: save entire object to JSON file ─────────────
    if ($_GET['action'] === 'save_asos') {
        $file  = __DIR__ . '/asosiasi_oortmyid.json';
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { echo json_encode(['error' => 'Invalid JSON']); exit; }
        $ok = file_put_contents($file, json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode($ok !== false ? ['ok' => true] : ['error' => 'Gagal menulis file']);
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
  --border: rgba(0,0,0,0.10);
  --border2: rgba(0,0,0,0.20);
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
  --violet: #5B3FA6;
  --violet-light: #EDE9FB;
  --radius: 10px;
  --radius-sm: 7px;
  --nav-h: 56px;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
}

/* ── TOPBAR ── */
.topbar {
  background: var(--surface);
  border-bottom: 0.5px solid var(--border);
  padding: 0 1.6rem;
  height: var(--nav-h);
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
  gap: 1rem;
}

.brand { display: flex; align-items: center; gap: 9px; flex-shrink: 0; }
.brand-icon {
  width: 28px; height: 28px;
  background: var(--blue-dark);
  border-radius: 7px;
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 3px; padding: 6px;
}
.brand-icon span { background: rgba(255,255,255,0.7); border-radius: 1.5px; }
.brand-icon span:last-child { background: rgba(255,255,255,0.25); }
.brand h1 { font-size: 14px; font-weight: 700; letter-spacing: -0.3px; }
.brand .v { font-size: 10px; color: var(--text3); background: var(--surface2); border: 0.5px solid var(--border2); border-radius: 20px; padding: 2px 8px; }

/* ── NAV MENU ── */
.nav-menu {
  display: flex; align-items: center; gap: 2px;
  background: var(--surface2);
  border: 0.5px solid var(--border2);
  border-radius: 8px;
  padding: 3px;
}
.nav-item {
  display: flex; align-items: center; gap: 6px;
  padding: 6px 14px; border-radius: 6px;
  font-size: 12.5px; font-weight: 600; color: var(--text2);
  cursor: pointer; border: none; background: none;
  transition: background 0.13s, color 0.13s;
  white-space: nowrap;
}
.nav-item svg { width: 14px; height: 14px; flex-shrink: 0; opacity: 0.65; transition: opacity 0.13s; }
.nav-item:hover:not(.active) { background: rgba(0,0,0,0.04); color: var(--text); }
.nav-item.active { background: var(--surface); color: var(--text); box-shadow: 0 1px 4px rgba(0,0,0,0.10); }
.nav-item.active svg { opacity: 1; }

/* ── STATUS ── */
.status { display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--text2); flex-shrink: 0; }
.dot { width: 6px; height: 6px; border-radius: 50%; background: #52a126; animation: pulse 2.5s infinite; }
.dot.off { background: var(--text3); animation: none; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.35} }

/* ── PAGE ROUTING ── */
.page { display: none; }
.page.active { display: block; }

/* ── MAIN ── */
.main { max-width: 1140px; margin: 0 auto; padding: 1.5rem; }

/* ── PAGE HEADER ── */
.page-hdr {
  display: flex; align-items: center; gap: 12px;
  margin-bottom: 18px; padding-bottom: 14px;
  border-bottom: 0.5px solid var(--border2);
}
.page-hdr-icon {
  width: 36px; height: 36px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.page-hdr-icon.blue   { background: var(--blue-light);   color: var(--blue); }
.page-hdr-icon.violet { background: var(--violet-light); color: var(--violet); }
.page-hdr-icon svg { width: 16px; height: 16px; }
.page-hdr-text h2 { font-size: 16px; font-weight: 700; letter-spacing: -0.3px; }
.page-hdr-text p  { font-size: 12px; color: var(--text2); margin-top: 2px; }
.page-hdr-actions { margin-left: auto; display: flex; gap: 7px; align-items: center; }

/* ── PANEL ── */
.panel {
  background: var(--surface);
  border: 0.5px solid var(--border);
  border-radius: var(--radius);
  padding: 1.15rem 1.3rem;
}
.panel-title {
  font-size: 10.5px; font-weight: 700; color: var(--text2);
  text-transform: uppercase; letter-spacing: 0.7px;
  margin-bottom: 12px;
  display: flex; align-items: center; gap: 6px;
}

/* ── PILLS ── */
.pill { font-size: 9.5px; border-radius: 20px; padding: 2px 8px; font-weight: 600; }
.pill-blue   { background: var(--blue-light);   color: var(--blue); }
.pill-green  { background: var(--green-light);  color: var(--green); }
.pill-violet { background: var(--violet-light); color: var(--violet); }

/* ── GRID ── */
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
@media (max-width: 720px) { .grid2 { grid-template-columns: 1fr; } }

/* ── METRICS ── */
.metric-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-bottom: 12px; }
.metric { background: var(--surface2); border-radius: var(--radius-sm); padding: 10px 10px 8px; }
.metric label { font-size: 9.5px; color: var(--text2); display: block; margin-bottom: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.metric .val  { font-size: 18px; font-weight: 700; color: var(--text); line-height: 1; }
.metric .sub  { font-size: 9.5px; color: var(--text3); margin-top: 3px; }

/* ── STAT ROW ── */
.stat-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
.stat-mini {
  background: var(--surface2); border-radius: var(--radius-sm);
  padding: 9px 14px; display: flex; flex-direction: column; gap: 2px;
  flex: 1; min-width: 80px;
}
.stat-mini .sm-label { font-size: 9px; color: var(--text3); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-mini .sm-val   { font-size: 20px; font-weight: 700; color: var(--text); }

/* ── CHIPS ── */
.filter-label { font-size: 9.5px; color: var(--text2); margin-bottom: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.chips { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 10px; }
.chip {
  font-size: 11px; padding: 3px 10px; border-radius: 20px;
  border: 0.5px solid var(--border2); background: var(--surface2);
  color: var(--text2); cursor: pointer; transition: all 0.12s; user-select: none;
}
.chip.active { background: var(--blue-light); border-color: var(--blue-mid); color: var(--blue-dark); font-weight: 600; }
.chip:hover:not(.active) { background: #e8e6e2; }

/* ── TABLE ── */
.tbl-wrap { overflow-x: auto; }
table { width: 100%; font-size: 12px; border-collapse: collapse; }
thead th { color: var(--text2); font-weight: 600; text-align: left; padding: 5px 8px; border-bottom: 0.5px solid var(--border2); font-size: 10.5px; white-space: nowrap; }
tbody td { padding: 7px 8px; color: var(--text); border-bottom: 0.5px solid var(--border); vertical-align: middle; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: var(--surface2); }
.muted { color: var(--text3) !important; }
.num { text-align: right; font-variant-numeric: tabular-nums; }

/* ── SOURCE BADGE ── */
.src-badge {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: 9px; font-weight: 700; padding: 2px 7px;
  border-radius: 20px; white-space: nowrap; letter-spacing: 0.3px;
}
.src-sql { background: var(--blue-light);  color: var(--blue); }
.src-api { background: var(--green-light); color: var(--green); }

/* ── ASOSIASI CELLS ── */
.asos-val   { font-style: italic; color: var(--violet); }
.asos-empty { color: var(--text3); font-size: 11px; }

/* ── ACTION BTNS in table ── */
.act-btn {
  background: none; border: none; cursor: pointer;
  padding: 3px 5px; border-radius: 4px;
  font-size: 11px; color: var(--text3);
  transition: color 0.12s, background 0.12s; line-height: 1;
}
.act-btn.edit:hover { color: var(--violet); background: var(--violet-light); }
.act-btn.del:hover  { color: var(--red);    background: var(--red-light); }

/* ── SEARCH ROW & BUTTONS ── */
.row-flex { display: flex; gap: 7px; align-items: center; margin-bottom: 10px; flex-wrap: wrap; }
.row-flex input {
  flex: 1; min-width: 140px; height: 34px; font-size: 12.5px; padding: 0 11px;
  border-radius: var(--radius-sm); border: 0.5px solid var(--border2);
  background: var(--surface2); color: var(--text); outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  font-family: inherit;
}
.row-flex input:focus { border-color: var(--blue-mid); box-shadow: 0 0 0 2.5px rgba(24,95,165,0.10); }
.btn {
  height: 34px; padding: 0 14px; font-size: 12px; border-radius: var(--radius-sm);
  border: 0.5px solid var(--border2); background: var(--surface);
  color: var(--text); cursor: pointer; transition: background 0.12s;
  white-space: nowrap; font-weight: 500; font-family: inherit;
}
.btn:hover { background: var(--surface2); }
.btn.primary { background: var(--blue);   color: #fff; border-color: var(--blue); }
.btn.primary:hover { background: #0c4479; }
.btn.violet  { background: var(--violet); color: #fff; border-color: var(--violet); }
.btn.violet:hover  { background: #47318a; }
.btn.ghost   { background: transparent; border-color: var(--border2); color: var(--text2); }
.btn.ghost:hover { background: var(--surface2); color: var(--text); }
.btn.sm { height: 28px; padding: 0 10px; font-size: 11px; }

/* ── DIVIDER ── */
.divider { height: 0.5px; background: var(--border); margin: 12px 0; }

/* ── FILTER TABS ── */
.ftabs { display: flex; gap: 2px; margin-bottom: 12px; border-bottom: 0.5px solid var(--border); }
.ftab {
  font-size: 11.5px; font-weight: 600; padding: 6px 13px;
  background: none; border: none; cursor: pointer; color: var(--text2);
  border-bottom: 2px solid transparent; margin-bottom: -0.5px;
  transition: color 0.12s, border-color 0.12s; font-family: inherit;
}
.ftab.active { color: var(--violet); border-bottom-color: var(--violet); }
.ftab:hover:not(.active) { color: var(--text); }
.ftab-pane { display: none; }
.ftab-pane.active { display: block; }

/* ── JSON VIEWER ── */
.json-block {
  background: #181825;
  border-radius: var(--radius-sm);
  border: 0.5px solid rgba(255,255,255,0.07);
  padding: 12px 14px;
  font-size: 11.5px;
  font-family: 'Courier New', monospace;
  color: #cdd6f4;
  max-height: 210px;
  overflow-y: auto;
  line-height: 1.65;
  margin-top: 8px;
}
.jk { color: #89b4fa; }
.js { color: #a6e3a1; }
.jx { color: #6c7086; font-style: italic; }

/* ── MODAL ── */
.modal-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(10,10,14,0.45); z-index: 300;
  align-items: center; justify-content: center;
  backdrop-filter: blur(2px);
}
.modal-overlay.open { display: flex; }
.modal {
  background: var(--surface);
  border-radius: 12px; border: 0.5px solid var(--border);
  padding: 1.6rem 1.8rem; width: 440px; max-width: 95vw;
  box-shadow: 0 12px 48px rgba(0,0,0,0.18);
  animation: slideUp 0.18s ease;
}
@keyframes slideUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
.modal h2       { font-size: 15px; font-weight: 700; margin-bottom: 3px; }
.modal .modal-sub { font-size: 12px; color: var(--text2); margin-bottom: 1.3rem; line-height: 1.5; }
.modal label {
  font-size: 10px; color: var(--text2); display: block;
  margin-bottom: 4px; margin-top: 13px;
  font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
}
.modal input, .modal select, .modal textarea {
  width: 100%; font-size: 13px; padding: 8px 11px;
  border-radius: var(--radius-sm); border: 0.5px solid var(--border2);
  background: var(--surface2); color: var(--text); outline: none;
  font-family: inherit;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.modal input:focus, .modal select:focus, .modal textarea:focus {
  border-color: var(--violet); box-shadow: 0 0 0 2.5px rgba(91,63,166,0.12);
}
.modal textarea { min-height: 68px; resize: vertical; line-height: 1.5; }
.modal-btns { display: flex; gap: 8px; margin-top: 1.3rem; justify-content: flex-end; }
.modal-msg  { font-size: 11.5px; margin-top: 8px; min-height: 18px; }
.ok  { color: var(--green); }
.err { color: var(--red); }

/* ── MISC ── */
.empty       { color: var(--text3); font-size: 12px; padding: 16px 0; text-align: center; }
.loading-txt { color: var(--text3); font-size: 12px; padding: 10px 0; }
</style>
</head>
<body>

<!-- ══════════════════════ TOPBAR ══════════════════════ -->
<div class="topbar">

  <div class="brand">
    <div class="brand-icon">
      <span></span><span></span>
      <span></span><span></span>
    </div>
    <h1>Oortmyid</h1>
    <span class="v">v3.0</span>
  </div>

  <nav class="nav-menu">
    <button class="nav-item active" data-page="page-saldo">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7">
        <rect x="2" y="5" width="12" height="8" rx="1.5"/>
        <path d="M5 5V4a1 1 0 011-1h4a1 1 0 011 1v1"/>
        <path d="M6 9h4M8 7v4"/>
      </svg>
      Dashboard Saldo
    </button>
    <button class="nav-item" data-page="page-asosiasi">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7">
        <circle cx="4" cy="8" r="2"/>
        <circle cx="12" cy="4" r="2"/>
        <circle cx="12" cy="12" r="2"/>
        <path d="M5.9 7.1L10.1 4.9M5.9 8.9L10.1 11.1"/>
      </svg>
      Asosiasi Memori
    </button>
  </nav>

  <div class="status">
    <span class="dot off" id="apiDot"></span>
    <span id="apiStatus">Menghubungkan…</span>
  </div>

</div>

<!-- ══════════════════════ PAGE: DASHBOARD SALDO ══════════════════════ -->
<div class="page active" id="page-saldo">
<div class="main">

  <div class="page-hdr">
    <div class="page-hdr-icon blue">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7">
        <rect x="2" y="5" width="12" height="8" rx="1.5"/>
        <path d="M5 5V4a1 1 0 011-1h4a1 1 0 011 1v1"/>
      </svg>
    </div>
    <div class="page-hdr-text">
      <h2>Dashboard Saldo</h2>
      <p>Ringkasan entitas dari Formalhault SQL &amp; XANO API</p>
    </div>
  </div>

  <div class="grid2">

    <!-- Formalhault SQL -->
    <div class="panel">
      <div class="panel-title">
        Formalhault
        <span class="pill pill-blue">SQL · oortmyid_e0</span>
      </div>
      <div class="metric-row">
        <div class="metric"><label>Saldo</label><div class="val" id="fhSaldo">—</div><div class="sub">total</div></div>
        <div class="metric"><label>Hutang</label><div class="val" id="fhHutang">—</div><div class="sub">total</div></div>
        <div class="metric"><label>Modal</label><div class="val" id="fhModal">—</div><div class="sub">total</div></div>
      </div>
      <div class="divider"></div>
      <div class="filter-label">Filter kolom</div>
      <div class="chips" id="fhChips">
        <div class="chip active" data-key="Nama">Nama</div>
        <div class="chip active" data-key="RegNo">RegNo</div>
        <div class="chip" data-key="Jenis">Jenis</div>
        <div class="chip" data-key="Saldo">Saldo</div>
        <div class="chip" data-key="Hutang">Hutang</div>
        <div class="chip" data-key="Modal">Modal</div>
      </div>
      <div class="tbl-wrap" id="fhWrap"><div class="loading-txt">Memuat data SQL…</div></div>
    </div>

    <!-- Name-List XANO -->
    <div class="panel">
      <div class="panel-title">
        Name-List
        <span class="pill pill-green">XANO API</span>
      </div>
      <div class="row-flex">
        <input type="text" id="nlSearch" placeholder="Cari nama…" />
        <button class="btn primary sm" id="addNlBtn">+ Tambah</button>
      </div>
      <div class="tbl-wrap" id="nlWrap"><div class="loading-txt">Memuat data XANO…</div></div>
    </div>

  </div>
</div>
</div>

<!-- ══════════════════════ PAGE: ASOSIASI MEMORI ══════════════════════ -->
<div class="page" id="page-asosiasi">
<div class="main">

  <div class="page-hdr">
    <div class="page-hdr-icon violet">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7">
        <circle cx="4" cy="8" r="2"/>
        <circle cx="12" cy="4" r="2"/>
        <circle cx="12" cy="12" r="2"/>
        <path d="M5.9 7.1L10.1 4.9M5.9 8.9L10.1 11.1"/>
      </svg>
    </div>
    <div class="page-hdr-text">
      <h2>Asosiasi Memori</h2>
      <p>Registri entitas gabungan SQL &amp; API — asosiasikan ke memori</p>
    </div>
    <div class="page-hdr-actions">
      <button class="btn ghost sm" id="exportJsonBtn">↓ Ekspor JSON</button>
      <button class="btn violet sm" id="openAsosModal">+ Asosiasi Baru</button>
    </div>
  </div>

  <div class="panel">

    <!-- Stats -->
    <div class="stat-row">
      <div class="stat-mini"><span class="sm-label">Total Entitas</span><span class="sm-val" id="statTotal">—</span></div>
      <div class="stat-mini"><span class="sm-label">Terasosiasi</span><span class="sm-val" id="statAsos">—</span></div>
      <div class="stat-mini"><span class="sm-label">Belum Terhubung</span><span class="sm-val" id="statUnlinked">—</span></div>
      <div class="stat-mini"><span class="sm-label">Entri JSON</span><span class="sm-val" id="statJson">—</span></div>
    </div>

    <!-- Search + clear -->
    <div class="row-flex">
      <input type="text" id="asosSearch" placeholder="Cari RegNo, Nama, atau Asosiasi…" />
      <button class="btn ghost sm" id="clearJsonBtn">Hapus Semua Asosiasi</button>
    </div>

    <!-- Filter tabs -->
    <div class="ftabs">
      <button class="ftab active" data-ftab="all">Semua</button>
      <button class="ftab" data-ftab="asos">Terasosiasi</button>
      <button class="ftab" data-ftab="unlinked">Belum Terhubung</button>
    </div>

    <div class="ftab-pane active" id="ftab-all"><div class="tbl-wrap" id="regAll"><div class="loading-txt">Memuat…</div></div></div>
    <div class="ftab-pane" id="ftab-asos"><div class="tbl-wrap" id="regAsos"></div></div>
    <div class="ftab-pane" id="ftab-unlinked"><div class="tbl-wrap" id="regUnlinked"></div></div>

    <!-- JSON Viewer -->
    <div class="divider"></div>
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <span style="font-size:10px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:0.7px;">JSON Tersimpan</span>
      <span id="jsonCount" style="font-size:10.5px;color:var(--text3);"></span>
    </div>
    <div class="json-block" id="jsonViewer"><span class="jx">{ }</span></div>

  </div>
</div>
</div>

<!-- ══════════════════════ MODAL: TAMBAH NAME-LIST ══════════════════════ -->
<div class="modal-overlay" id="nlModalOverlay">
  <div class="modal">
    <h2>Tambah ke Name-List</h2>
    <p class="modal-sub">Data akan disimpan ke XANO API (name_list)</p>
    <label>Nama Entitas</label>
    <input type="text" id="newNlNama" placeholder="Nama…" />
    <div class="modal-msg" id="nlModalMsg"></div>
    <div class="modal-btns">
      <button class="btn" id="nlCancelBtn">Batal</button>
      <button class="btn primary" id="nlSaveBtn">Simpan</button>
    </div>
  </div>
</div>

<!-- ══════════════════════ MODAL: ASOSIASI ══════════════════════ -->
<div class="modal-overlay" id="asosModalOverlay">
  <div class="modal">
    <h2 id="asosModalTitle">Tambah Asosiasi</h2>
    <p class="modal-sub" id="asosModalSub">Pilih entitas dari daftar gabungan SQL &amp; API, lalu masukkan asosiasi memori</p>

    <label>Entitas (SQL &amp; API — gabungan)</label>
    <select id="asosEntitySel">
      <option value="">— Pilih entitas —</option>
    </select>

    <label>RegNo</label>
    <input type="text" id="asosRegNo" placeholder="Otomatis dari pilihan di atas" />

    <label>Nama</label>
    <input type="text" id="asosNama" placeholder="Otomatis dari pilihan di atas" />

    <label>Asosiasi Memori</label>
    <input type="text" id="asosAsosiasi" placeholder="mis. Musim Gugur 2021, Cermin Pecah, Rasa Garam…" />

    <label>Catatan (opsional)</label>
    <textarea id="asosCatatan" placeholder="Konteks atau deskripsi tambahan…"></textarea>

    <div class="modal-msg" id="asosModalMsg"></div>
    <div class="modal-btns">
      <button class="btn" id="asosCancelBtn">Batal</button>
      <button class="btn violet" id="asonSaveBtn">Simpan Asosiasi</button>
    </div>
  </div>
</div>

<script>
// ── helpers ──────────────────────────────────────────────
function esc(s) {
  if (s == null) return '—';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmt(n) {
  const v = Number(n);
  return isNaN(v) ? '—' : v.toLocaleString('id-ID');
}
function setStatus(ok) {
  document.getElementById('apiDot').className = 'dot' + (ok ? '' : ' off');
  document.getElementById('apiStatus').textContent = ok ? 'API Terhubung' : 'Demo Mode';
}

const NUM_FIELDS = ['Saldo','Hutang','Modal','RegNo'];

// ── state ────────────────────────────────────────────────
let fhData   = [];
let nlData   = [];
let asosData = {};
let activeFilters = ['Nama','RegNo'];
let editingKey = null;

// ── entity list: SQL + API merged, no name duplicates ────
function buildEntityList() {
  const list = [];
  const seenNames = new Set();
  fhData.forEach(r => {
    const name = r.Nama || '—';
    seenNames.add(name.toLowerCase());
    list.push({ key: 'sql_' + r.RegNo, regNo: String(r.RegNo), nama: name, sumber: 'SQL' });
  });
  nlData.forEach(r => {
    const name = r.Nama || r.nama || '—';
    if (seenNames.has(name.toLowerCase())) return;
    list.push({ key: 'api_' + r.id, regNo: r.id != null ? String(r.id) : null, nama: name, sumber: 'API' });
  });
  return list;
}

// ── server JSON read/write ────────────────────────────────
async function saveAsos() {
  try {
    await fetch('?action=save_asos', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(asosData)
    });
  } catch (e) { console.error('saveAsos failed', e); }
}

async function loadAsosFromServer() {
  try {
    const r = await fetch('?action=get_asos');
    const d = await r.json();
    asosData = (d && typeof d === 'object' && !Array.isArray(d)) ? d : {};
  } catch { asosData = {}; }
}

// ── populate entity selector ─────────────────────────────
function populateEntitySel() {
  const sel  = document.getElementById('asosEntitySel');
  const prev = sel.value;
  sel.innerHTML = '<option value="">— Pilih entitas —</option>';
  buildEntityList().forEach(e => {
    const opt = document.createElement('option');
    opt.value = e.key;
    opt.textContent = `[${e.sumber}]  ${e.nama}${e.regNo ? '  ·  RegNo ' + e.regNo : ''}`;
    sel.appendChild(opt);
  });
  if (prev) sel.value = prev;
}

// ── JSON viewer ───────────────────────────────────────────
function renderJsonViewer() {
  const el  = document.getElementById('jsonViewer');
  const cnt = document.getElementById('jsonCount');
  const entries = Object.entries(asosData);
  cnt.textContent = entries.length ? entries.length + ' entri' : '';
  if (!entries.length) { el.innerHTML = '<span class="jx">{ }</span>'; return; }
  let h = '{\n';
  entries.forEach(([key, val], i) => {
    h += `  <span class="jk">"${esc(key)}"</span>: {\n`;
    h += `    <span class="jk">"Nama"</span>: <span class="js">"${esc(val.Nama)}"</span>,\n`;
    h += `    <span class="jk">"Sumber"</span>: <span class="js">"${esc(val.Sumber)}"</span>,\n`;
    h += `    <span class="jk">"Asosiasi"</span>: <span class="js">"${esc(val.Asosiasi)}"</span>`;
    if (val.Catatan) h += `,\n    <span class="jk">"Catatan"</span>: <span class="js">"${esc(val.Catatan)}"</span>`;
    h += `\n  }` + (i < entries.length - 1 ? ',' : '') + '\n';
  });
  h += '}';
  el.innerHTML = h;
}

// ── registri table ────────────────────────────────────────
function buildRows(filterMode, searchQ) {
  const q = (searchQ || '').toLowerCase();
  return buildEntityList()
    .map(e => {
      const a = asosData[e.key];
      return { ...e, asosiasi: a ? a.Asosiasi : null, catatan: a ? a.Catatan : null, linked: !!a };
    })
    .filter(r => {
      if (filterMode === 'asos'     && !r.linked) return false;
      if (filterMode === 'unlinked' &&  r.linked) return false;
      if (q) return (r.regNo||'').includes(q) ||
                    r.nama.toLowerCase().includes(q) ||
                    (r.asosiasi||'').toLowerCase().includes(q);
      return true;
    });
}

function srcBadge(s) {
  return `<span class="src-badge src-${s.toLowerCase()}">${s}</span>`;
}

function tableHtml(rows) {
  if (!rows.length) return '<div class="empty">Tidak ada data</div>';
  let h = `<table><thead><tr>
    <th style="width:28px">#</th>
    <th>RegNo</th><th>Nama</th><th>Sumber</th>
    <th>Asosiasi Memori</th><th>Catatan</th>
    <th style="width:52px"></th>
  </tr></thead><tbody>`;
  rows.forEach((r, i) => {
    const asosCell = r.asosiasi
      ? `<span class="asos-val">${esc(r.asosiasi)}</span>`
      : `<span class="asos-empty">—</span>`;
    const catCell = r.catatan
      ? `<span style="font-size:11px;color:var(--text2)">${esc(r.catatan)}</span>`
      : `<span class="muted" style="font-size:11px;">—</span>`;
    const editBtn = `<button class="act-btn edit" onclick="openEditAsos('${esc(r.key)}')" title="Edit">✎</button>`;
    const delBtn  = r.linked
      ? `<button class="act-btn del" onclick="deleteAsos('${esc(r.key)}')" title="Hapus asosiasi">✕</button>`
      : '';
    h += `<tr>
      <td class="muted">${i+1}</td>
      <td class="num" style="color:var(--blue);font-weight:600;">${esc(r.regNo)}</td>
      <td style="font-weight:600;font-style:italic;">${esc(r.nama)}</td>
      <td>${srcBadge(r.sumber)}</td>
      <td>${asosCell}</td>
      <td>${catCell}</td>
      <td style="white-space:nowrap;">${editBtn}${delBtn}</td>
    </tr>`;
  });
  return h + '</tbody></table>';
}

function renderRegistri() {
  const q      = document.getElementById('asosSearch').value;
  const all    = buildRows('all',      q);
  const asos   = buildRows('asos',     q);
  const unlnk  = buildRows('unlinked', q);

  document.getElementById('statTotal').textContent    = all.length;
  document.getElementById('statAsos').textContent     = asos.length;
  document.getElementById('statUnlinked').textContent = unlnk.length;
  document.getElementById('statJson').textContent     = Object.keys(asosData).length;

  document.getElementById('regAll').innerHTML      = tableHtml(all);
  document.getElementById('regAsos').innerHTML     = tableHtml(asos);
  document.getElementById('regUnlinked').innerHTML = tableHtml(unlnk);

  renderJsonViewer();
}

// ── filter tabs ───────────────────────────────────────────
document.querySelectorAll('.ftab').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.ftab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.ftab-pane').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('ftab-' + this.dataset.ftab).classList.add('active');
  });
});
document.getElementById('asosSearch').addEventListener('input', renderRegistri);

// ── nav menu ──────────────────────────────────────────────
document.querySelectorAll('.nav-item').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    document.getElementById(this.dataset.page).classList.add('active');
  });
});

// ── formalhault SQL ───────────────────────────────────────
async function loadFH() {
  try {
    const r = await fetch('?action=get_formalhault');
    const d = await r.json();
    if (d.error) throw new Error(d.error);
    fhData = d;
  } catch {
    fhData = [
      {Nama:'Arjuna',   Jenis:1, RegNo:1001, Saldo:1500000, Hutang:200000, Modal:3000000},
      {Nama:'Sinta',    Jenis:2, RegNo:1002, Saldo:800000,  Hutang:50000,  Modal:1200000},
      {Nama:'Gatot',    Jenis:1, RegNo:1003, Saldo:2200000, Hutang:0,      Modal:5000000},
      {Nama:'Karna',    Jenis:1, RegNo:1004, Saldo:950000,  Hutang:100000, Modal:2500000},
      {Nama:'Drupadi',  Jenis:2, RegNo:1005, Saldo:600000,  Hutang:0,      Modal:900000},
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
  chip.classList.toggle('active');
  activeFilters = [...document.querySelectorAll('#fhChips .chip.active')].map(c => c.dataset.key);
  renderFH();
});

// ── name-list XANO ────────────────────────────────────────
async function loadNL() {
  try {
    const r = await fetch('?action=proxy_namelist');
    const d = await r.json();
    if (d.error) throw new Error(d.error);
    nlData = Array.isArray(d) ? d : [];
    setStatus(true);
  } catch {
    nlData = [
      {id:2001, Nama:'Bima'},
      {id:2002, Nama:'Yudistira'},
      {id:2003, Nama:'Nakula'},
    ];
    setStatus(false);
  }
  renderNL(nlData);
  populateEntitySel();
  renderRegistri();
}

function renderNL(data) {
  const w = document.getElementById('nlWrap');
  if (!data.length) { w.innerHTML = '<div class="empty">Tidak ada data</div>'; return; }
  let h = '<table><thead><tr><th>#</th><th>id</th><th>Nama</th></tr></thead><tbody>';
  data.forEach((r,i) => {
    h += `<tr><td class="muted">${i+1}</td><td class="num muted">${esc(r.id??'—')}</td><td>${esc(r.Nama||r.nama||'—')}</td></tr>`;
  });
  w.innerHTML = h + '</tbody></table>';
}

document.getElementById('nlSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  renderNL(nlData.filter(r=>(r.Nama||r.nama||'').toLowerCase().includes(q)));
});

// ── modal: name-list ──────────────────────────────────────
document.getElementById('addNlBtn').addEventListener('click', () => {
  document.getElementById('nlModalOverlay').classList.add('open');
  setTimeout(() => document.getElementById('newNlNama').focus(), 60);
});
document.getElementById('nlCancelBtn').addEventListener('click', closeNlModal);
document.getElementById('nlModalOverlay').addEventListener('click', e => { if(e.target===e.currentTarget) closeNlModal(); });

function closeNlModal() {
  document.getElementById('nlModalOverlay').classList.remove('open');
  document.getElementById('newNlNama').value = '';
  const m = document.getElementById('nlModalMsg');
  m.textContent=''; m.className='modal-msg';
}

document.getElementById('nlSaveBtn').addEventListener('click', async () => {
  const nama = document.getElementById('newNlNama').value.trim();
  const msg  = document.getElementById('nlModalMsg');
  if (!nama) { msg.textContent='Nama tidak boleh kosong.'; msg.className='modal-msg err'; return; }
  const btn = document.getElementById('nlSaveBtn');
  btn.textContent='Menyimpan…'; btn.disabled=true;
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
    nlData.unshift({id: Date.now(), Nama: nama});
    msg.textContent='Tersimpan (demo mode).'; msg.className='modal-msg ok';
  }
  renderNL(nlData);
  populateEntitySel();
  renderRegistri();
  btn.textContent='Simpan'; btn.disabled=false;
  setTimeout(closeNlModal, 900);
});

// ── modal: asosiasi ───────────────────────────────────────
document.getElementById('openAsosModal').addEventListener('click', () => openAsosModal());
document.getElementById('asosCancelBtn').addEventListener('click', closeAsosModal);
document.getElementById('asosModalOverlay').addEventListener('click', e => { if(e.target===e.currentTarget) closeAsosModal(); });

function openAsosModal(key = null) {
  editingKey = key;
  const msg = document.getElementById('asosModalMsg');
  msg.textContent = ''; msg.className = 'modal-msg';

  const sel = document.getElementById('asosEntitySel');
  if (key && asosData[key]) {
    // EDIT mode
    document.getElementById('asosModalTitle').textContent = 'Edit Asosiasi';
    document.getElementById('asosModalSub').textContent   = 'Mengedit asosiasi untuk: ' + (asosData[key].Nama || key);
    sel.value = ''; sel.disabled = true;
    document.getElementById('asosRegNo').value    = asosData[key].RegNo || '';
    document.getElementById('asosRegNo').readOnly = true;
    document.getElementById('asosNama').value     = asosData[key].Nama || '';
    document.getElementById('asosNama').readOnly  = true;
    document.getElementById('asosAsosiasi').value = asosData[key].Asosiasi || '';
    document.getElementById('asosCatatan').value  = asosData[key].Catatan  || '';
  } else {
    // ADD mode
    document.getElementById('asosModalTitle').textContent = 'Tambah Asosiasi';
    document.getElementById('asosModalSub').textContent   = 'Pilih entitas dari daftar gabungan SQL & API, lalu masukkan asosiasi memori';
    sel.value = ''; sel.disabled = false;
    document.getElementById('asosRegNo').value    = '';
    document.getElementById('asosRegNo').readOnly = false;
    document.getElementById('asosNama').value     = '';
    document.getElementById('asosNama').readOnly  = false;
    document.getElementById('asosAsosiasi').value = '';
    document.getElementById('asosCatatan').value  = '';
  }
  document.getElementById('asosModalOverlay').classList.add('open');
  setTimeout(() => document.getElementById('asosAsosiasi').focus(), 80);
}

function closeAsosModal() {
  document.getElementById('asosModalOverlay').classList.remove('open');
  editingKey = null;
}

// Auto-fill when entity selected
document.getElementById('asosEntitySel').addEventListener('change', function() {
  const key    = this.value;
  const entity = buildEntityList().find(e => e.key === key);
  if (entity) {
    document.getElementById('asosRegNo').value = entity.regNo || '';
    document.getElementById('asosNama').value  = entity.nama;
  }
});

document.getElementById('asonSaveBtn').addEventListener('click', async () => {
  const selKey   = document.getElementById('asosEntitySel').value;
  const regNo    = document.getElementById('asosRegNo').value.trim();
  const nama     = document.getElementById('asosNama').value.trim();
  const asosiasi = document.getElementById('asosAsosiasi').value.trim();
  const catatan  = document.getElementById('asosCatatan').value.trim();
  const msg = document.getElementById('asosModalMsg');

  if (!nama)     { msg.textContent='Nama tidak boleh kosong.';    msg.className='modal-msg err'; return; }
  if (!asosiasi) { msg.textContent='Asosiasi tidak boleh kosong.'; msg.className='modal-msg err'; return; }

  const entity   = buildEntityList().find(e => e.key === selKey);
  const storeKey = editingKey || (entity ? entity.key : 'manual_' + (regNo || nama.replace(/\s+/g,'_')));
  const sumber   = entity ? entity.sumber : (editingKey ? (asosData[editingKey]?.Sumber || 'Manual') : 'Manual');

  asosData[storeKey] = { RegNo: regNo, Nama: nama, Sumber: sumber, Asosiasi: asosiasi, Catatan: catatan || null };
  await saveAsos();
  renderRegistri();

  msg.textContent = editingKey ? 'Asosiasi diperbarui!' : 'Asosiasi disimpan!';
  msg.className = 'modal-msg ok';
  setTimeout(closeAsosModal, 750);
});

function openEditAsos(key)  { openAsosModal(key); }
async function deleteAsos(key) {
  const label = asosData[key] ? asosData[key].Nama : key;
  if (confirm(`Hapus asosiasi untuk "${label}"?`)) {
    delete asosData[key];
    await saveAsos();
    renderRegistri();
  }
}

// ── export JSON ───────────────────────────────────────────
document.getElementById('exportJsonBtn').addEventListener('click', () => {
  const blob = new Blob([JSON.stringify(asosData, null, 2)], {type: 'application/json'});
  const a    = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'asosiasi_oortmyid.json';
  a.click();
});

// ── clear all ─────────────────────────────────────────────
document.getElementById('clearJsonBtn').addEventListener('click', async () => {
  if (!Object.keys(asosData).length) return;
  if (confirm('Hapus semua data asosiasi? Tindakan ini tidak dapat dibatalkan.')) {
    asosData = {};
    await saveAsos();
    renderRegistri();
  }
});

// ── keyboard shortcuts ────────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeNlModal(); closeAsosModal(); }
  if (e.key === 'Enter') {
    if (document.getElementById('nlModalOverlay').classList.contains('open'))
      document.getElementById('nlSaveBtn').click();
    if (document.getElementById('asosModalOverlay').classList.contains('open'))
      document.getElementById('asonSaveBtn').click();
  }
});

// ── init ──────────────────────────────────────────────────
loadAsosFromServer().then(() => {
  renderRegistri();
  loadFH().then(() => loadNL());
});
</script>
</body>
</html>