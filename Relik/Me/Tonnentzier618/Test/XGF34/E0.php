<?php
// ============================================================
//  Albiero-B v1.0
//  Formalhault (SQL) ↔ XANO API Sync Dashboard
//  Menu: Dashboard Saldo | Sinkronisasi
// ============================================================

$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_e0";

$XANO_FH   = 'https://x8ki-letl-twmt.n7.xano.io/api:ZBg3zfDx/formalhault';
$XANO_NL   = 'https://x8ki-letl-twmt.n7.xano.io/api:ZBg3zfDx/name_list';

// ── helper: XANO request ─────────────────────────────────
function xano_req($url, $method = 'GET', $body = null) {
    $opts = ['http' => [
        'method'        => $method,
        'header'        => 'Content-Type: application/json',
        'ignore_errors' => true,
    ]];
    if ($body !== null) $opts['http']['content'] = json_encode($body);
    $ctx  = stream_context_create($opts);
    $resp = file_get_contents($url, false, $ctx);
    return $resp ? json_decode($resp, true) : null;
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    // ── SQL: get formalhault ──────────────────────────────
    if ($_GET['action'] === 'get_fh_sql') {
        $conn = new mysqli($db_server, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) { echo json_encode(['error' => $conn->connect_error]); exit; }
        $res  = $conn->query("SELECT Nama, Jenis, RegNo, Saldo, Hutang, Modal FROM formalhault");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $conn->close();
        echo json_encode($rows);
        exit;
    }

    // ── XANO: get formalhault ─────────────────────────────
    if ($_GET['action'] === 'get_fh_xano') {
        $data = xano_req($XANO_FH);
        echo json_encode($data ?? ['error' => 'Gagal terhubung ke XANO']);
        exit;
    }

    // ── XANO: get name_list ───────────────────────────────
    if ($_GET['action'] === 'proxy_namelist') {
        $method = $_SERVER['REQUEST_METHOD'];
        $opts   = ['http' => ['method' => $method, 'header' => 'Content-Type: application/json', 'ignore_errors' => true]];
        if ($method === 'POST') {
            $body = file_get_contents('php://input');
            $opts['http']['content'] = $body;
        }
        $ctx  = stream_context_create($opts);
        $resp = file_get_contents($XANO_NL, false, $ctx);
        echo $resp ?: json_encode(['error' => 'Gagal terhubung ke XANO']);
        exit;
    }

    // ── SYNC: SQL → XANO (batch 10) ──────────────────────
    if ($_GET['action'] === 'sync_sql_to_xano') {
        $limit  = 10;
        $offset = max(0, intval($_GET['offset'] ?? 0));

        $conn = new mysqli($db_server, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) { echo json_encode(['error' => $conn->connect_error]); exit; }

        // total rows
        $cntRes = $conn->query("SELECT COUNT(*) AS cnt FROM formalhault");
        $total  = (int)$cntRes->fetch_assoc()['cnt'];

        $res = $conn->query("SELECT Nama, Jenis, RegNo, Saldo, Hutang, Modal FROM formalhault LIMIT $limit OFFSET $offset");
        $batch = [];
        while ($r = $res->fetch_assoc()) $batch[] = $r;
        $conn->close();

        $xanoRows    = xano_req($XANO_FH) ?? [];
        $xanoByRegNo = [];
        foreach ($xanoRows as $xr) {
            if (isset($xr['RegNo'])) $xanoByRegNo[$xr['RegNo']] = $xr;
        }

        $inserted = 0; $updated = 0; $errors = [];
        foreach ($batch as $row) {
            $rn = $row['RegNo'];
            if (isset($xanoByRegNo[$rn])) {
                $xid = $xanoByRegNo[$rn]['id'] ?? null;
                if ($xid) {
                    $r = xano_req($XANO_FH . '/' . $xid, 'PATCH', $row);
                    if (isset($r['error'])) $errors[] = "RegNo $rn: " . $r['error'];
                    else $updated++;
                }
            } else {
                $r = xano_req($XANO_FH, 'POST', $row);
                if (isset($r['error'])) $errors[] = "RegNo $rn: " . $r['error'];
                else $inserted++;
            }
        }

        $nextOffset = $offset + $limit;
        echo json_encode([
            'inserted'   => $inserted,
            'updated'    => $updated,
            'errors'     => $errors,
            'offset'     => $offset,
            'batch_size' => count($batch),
            'total'      => $total,
            'has_more'   => $nextOffset < $total,
            'next_offset'=> $nextOffset,
        ]);
        exit;
    }

    // ── SYNC: XANO → SQL (batch 10) ──────────────────────
    if ($_GET['action'] === 'sync_xano_to_sql') {
        $limit  = 10;
        $offset = max(0, intval($_GET['offset'] ?? 0));

        $allXano = xano_req($XANO_FH);
        if (!is_array($allXano)) { echo json_encode(['error' => 'Gagal mengambil data XANO']); exit; }

        $total = count($allXano);
        $batch = array_slice($allXano, $offset, $limit);

        $conn = new mysqli($db_server, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) { echo json_encode(['error' => $conn->connect_error]); exit; }

        $inserted = 0; $updated = 0; $errors = [];
        foreach ($batch as $xr) {
            $Nama   = $conn->real_escape_string($xr['Nama']  ?? '');
            $Jenis  = intval($xr['Jenis']  ?? 0);
            $RegNo  = intval($xr['RegNo']  ?? 0);
            $Saldo  = intval($xr['Saldo']  ?? 0);
            $Hutang = intval($xr['Hutang'] ?? 0);
            $Modal  = intval($xr['Modal']  ?? 0);

            $chk = $conn->query("SELECT RegNo FROM formalhault WHERE RegNo = $RegNo LIMIT 1");
            if ($chk && $chk->num_rows > 0) {
                $q = $conn->query("UPDATE formalhault SET Nama='$Nama', Jenis=$Jenis, Saldo=$Saldo, Hutang=$Hutang, Modal=$Modal WHERE RegNo=$RegNo");
                if ($q) $updated++; else $errors[] = "RegNo $RegNo: " . $conn->error;
            } else {
                $q = $conn->query("INSERT INTO formalhault (Nama,Jenis,RegNo,Saldo,Hutang,Modal) VALUES ('$Nama',$Jenis,$RegNo,$Saldo,$Hutang,$Modal)");
                if ($q) $inserted++; else $errors[] = "RegNo $RegNo: " . $conn->error;
            }
        }
        $conn->close();

        $nextOffset = $offset + $limit;
        echo json_encode([
            'inserted'    => $inserted,
            'updated'     => $updated,
            'errors'      => $errors,
            'offset'      => $offset,
            'batch_size'  => count($batch),
            'total'       => $total,
            'has_more'    => $nextOffset < $total,
            'next_offset' => $nextOffset,
        ]);
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
<title>Albiero-B · Oortmyid</title>
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
  --teal: #0e6f72;
  --teal-light: #d6f3f4;
  --radius: 10px;
  --radius-sm: 7px;
  --nav-h: 56px;
}

body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

/* ── TOPBAR ── */
.topbar {
  background: var(--surface); border-bottom: 0.5px solid var(--border);
  padding: 0 1.6rem; height: var(--nav-h);
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 100; gap: 1rem;
}
.brand { display: flex; align-items: center; gap: 9px; flex-shrink: 0; }
.brand-icon {
  width: 28px; height: 28px; background: var(--blue-dark); border-radius: 7px;
  display: grid; grid-template-columns: 1fr 1fr; gap: 3px; padding: 6px;
}
.brand-icon span { background: rgba(255,255,255,0.7); border-radius: 1.5px; }
.brand-icon span:last-child { background: rgba(255,255,255,0.25); }
.brand h1 { font-size: 14px; font-weight: 700; letter-spacing: -0.3px; }
.brand .v { font-size: 10px; color: var(--text3); background: var(--surface2); border: 0.5px solid var(--border2); border-radius: 20px; padding: 2px 8px; }

.nav-menu { display: flex; align-items: center; gap: 2px; background: var(--surface2); border: 0.5px solid var(--border2); border-radius: 8px; padding: 3px; }
.nav-item {
  display: flex; align-items: center; gap: 6px;
  padding: 6px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600;
  color: var(--text2); cursor: pointer; border: none; background: none;
  transition: background 0.13s, color 0.13s; white-space: nowrap;
}
.nav-item svg { width: 14px; height: 14px; flex-shrink: 0; opacity: 0.65; transition: opacity 0.13s; }
.nav-item:hover:not(.active) { background: rgba(0,0,0,0.04); color: var(--text); }
.nav-item.active { background: var(--surface); color: var(--text); box-shadow: 0 1px 4px rgba(0,0,0,0.10); }
.nav-item.active svg { opacity: 1; }

.status { display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--text2); flex-shrink: 0; }
.dot { width: 6px; height: 6px; border-radius: 50%; background: #52a126; animation: pulse 2.5s infinite; }
.dot.off { background: var(--text3); animation: none; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.35} }

/* ── PAGES ── */
.page { display: none; }
.page.active { display: block; }
.main { max-width: 1140px; margin: 0 auto; padding: 1.5rem; }

/* ── PAGE HEADER ── */
.page-hdr { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 0.5px solid var(--border2); }
.page-hdr-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.page-hdr-icon.blue  { background: var(--blue-light);  color: var(--blue); }
.page-hdr-icon.teal  { background: var(--teal-light);  color: var(--teal); }
.page-hdr-icon svg { width: 16px; height: 16px; }
.page-hdr-text h2 { font-size: 16px; font-weight: 700; letter-spacing: -0.3px; }
.page-hdr-text p  { font-size: 12px; color: var(--text2); margin-top: 2px; }
.page-hdr-actions { margin-left: auto; display: flex; gap: 7px; align-items: center; }

/* ── PANEL ── */
.panel { background: var(--surface); border: 0.5px solid var(--border); border-radius: var(--radius); padding: 1.15rem 1.3rem; }
.panel-title { font-size: 10.5px; font-weight: 700; color: var(--text2); text-transform: uppercase; letter-spacing: 0.7px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

.pill { font-size: 9.5px; border-radius: 20px; padding: 2px 8px; font-weight: 600; }
.pill-blue  { background: var(--blue-light);  color: var(--blue); }
.pill-green { background: var(--green-light); color: var(--green); }
.pill-teal  { background: var(--teal-light);  color: var(--teal); }

.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
@media (max-width: 720px) { .grid2 { grid-template-columns: 1fr; } }

/* ── METRICS ── */
.metric-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-bottom: 12px; }
.metric { background: var(--surface2); border-radius: var(--radius-sm); padding: 10px 10px 8px; }
.metric label { font-size: 9.5px; color: var(--text2); display: block; margin-bottom: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.metric .val  { font-size: 18px; font-weight: 700; color: var(--text); line-height: 1; }
.metric .sub  { font-size: 9.5px; color: var(--text3); margin-top: 3px; }

/* ── CHIPS ── */
.filter-label { font-size: 9.5px; color: var(--text2); margin-bottom: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.chips { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 10px; }
.chip { font-size: 11px; padding: 3px 10px; border-radius: 20px; border: 0.5px solid var(--border2); background: var(--surface2); color: var(--text2); cursor: pointer; transition: all 0.12s; user-select: none; }
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
.diff-add  td { background: #f0fce8 !important; }
.diff-mod  td { background: #fdf6e3 !important; }
.diff-same td { }

/* ── SEARCH + BUTTONS ── */
.row-flex { display: flex; gap: 7px; align-items: center; margin-bottom: 10px; flex-wrap: wrap; }
.row-flex input { flex: 1; min-width: 140px; height: 34px; font-size: 12.5px; padding: 0 11px; border-radius: var(--radius-sm); border: 0.5px solid var(--border2); background: var(--surface2); color: var(--text); outline: none; transition: border-color 0.15s; font-family: inherit; }
.row-flex input:focus { border-color: var(--blue-mid); box-shadow: 0 0 0 2.5px rgba(24,95,165,0.10); }
.btn { height: 34px; padding: 0 14px; font-size: 12px; border-radius: var(--radius-sm); border: 0.5px solid var(--border2); background: var(--surface); color: var(--text); cursor: pointer; transition: background 0.12s; white-space: nowrap; font-weight: 500; font-family: inherit; }
.btn:hover { background: var(--surface2); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn.primary { background: var(--blue);  color: #fff; border-color: var(--blue); }
.btn.primary:hover:not(:disabled) { background: #0c4479; }
.btn.teal    { background: var(--teal);  color: #fff; border-color: var(--teal); }
.btn.teal:hover:not(:disabled)    { background: #0a4f52; }
.btn.ghost   { background: transparent; border-color: var(--border2); color: var(--text2); }
.btn.ghost:hover { background: var(--surface2); color: var(--text); }
.btn.sm { height: 28px; padding: 0 10px; font-size: 11px; }

.divider { height: 0.5px; background: var(--border); margin: 12px 0; }

/* ── SYNC PANEL ── */
.sync-grid { display: grid; grid-template-columns: 1fr auto 1fr; gap: 10px; align-items: start; margin-bottom: 16px; }
@media (max-width: 700px) { .sync-grid { grid-template-columns: 1fr; } }
.sync-arrow { display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding-top: 32px; gap: 8px; }
.sync-arrow svg { width: 20px; height: 20px; color: var(--text3); }
.sync-source {
  background: var(--surface2); border: 0.5px solid var(--border2);
  border-radius: var(--radius); padding: 14px;
}
.sync-source h3 { font-size: 11px; font-weight: 700; color: var(--text2); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; }
.sync-source .count { font-size: 28px; font-weight: 700; color: var(--text); }
.sync-source .label { font-size: 10.5px; color: var(--text3); }

.log-box {
  background: #181825; border-radius: var(--radius-sm);
  border: 0.5px solid rgba(255,255,255,0.07);
  padding: 10px 13px; font-size: 11.5px;
  font-family: 'Courier New', monospace; color: #cdd6f4;
  max-height: 180px; overflow-y: auto; line-height: 1.65;
}
.log-ok   { color: #a6e3a1; }
.log-err  { color: #f38ba8; }
.log-info { color: #89b4fa; }
.log-warn { color: #f9e2af; }

/* ── DIFF TABLE ── */
.diff-legend { display: flex; gap: 10px; margin-bottom: 8px; flex-wrap: wrap; }
.diff-legend span { font-size: 10.5px; display: flex; align-items: center; gap: 4px; color: var(--text2); }
.legend-dot { width: 8px; height: 8px; border-radius: 2px; }
.legend-add  { background: #7ec850; }
.legend-mod  { background: #f0c050; }

.src-badge { display: inline-flex; align-items: center; gap: 3px; font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 20px; white-space: nowrap; letter-spacing: 0.3px; }
.src-sql { background: var(--blue-light);  color: var(--blue); }
.src-api { background: var(--green-light); color: var(--green); }

.stat-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
.stat-mini { background: var(--surface2); border-radius: var(--radius-sm); padding: 9px 14px; display: flex; flex-direction: column; gap: 2px; flex: 1; min-width: 80px; }
.stat-mini .sm-label { font-size: 9px; color: var(--text3); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-mini .sm-val   { font-size: 20px; font-weight: 700; color: var(--text); }

.empty { color: var(--text3); font-size: 12px; padding: 16px 0; text-align: center; }
.loading-txt { color: var(--text3); font-size: 12px; padding: 10px 0; }

/* ── MODAL: add name-list ── */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(10,10,14,0.45); z-index: 300; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
.modal-overlay.open { display: flex; }
.modal { background: var(--surface); border-radius: 12px; border: 0.5px solid var(--border); padding: 1.6rem 1.8rem; width: 400px; max-width: 95vw; box-shadow: 0 12px 48px rgba(0,0,0,0.18); animation: slideUp 0.18s ease; }
@keyframes slideUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
.modal h2 { font-size: 15px; font-weight: 700; margin-bottom: 3px; }
.modal .modal-sub { font-size: 12px; color: var(--text2); margin-bottom: 1.3rem; }
.modal label { font-size: 10px; color: var(--text2); display: block; margin-bottom: 4px; margin-top: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.modal input { width: 100%; font-size: 13px; padding: 8px 11px; border-radius: var(--radius-sm); border: 0.5px solid var(--border2); background: var(--surface2); color: var(--text); outline: none; font-family: inherit; transition: border-color 0.15s; }
.modal input:focus { border-color: var(--blue-mid); box-shadow: 0 0 0 2.5px rgba(24,95,165,0.10); }
.modal-btns { display: flex; gap: 8px; margin-top: 1.3rem; justify-content: flex-end; }
.modal-msg  { font-size: 11.5px; margin-top: 8px; min-height: 18px; }
.ok  { color: var(--green); }
.err { color: var(--red); }
</style>
</head>
<body>

<!-- ══ TOPBAR ══ -->
<div class="topbar">
  <div class="brand">
    <div class="brand-icon"><span></span><span></span><span></span><span></span></div>
    <h1>Oortmyid</h1>
    <span class="v">Albiero-B</span>
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
    <button class="nav-item" data-page="page-sync">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7">
        <path d="M3 8a5 5 0 0 1 9-3M13 8a5 5 0 0 1-9 3"/>
        <path d="M11 5l1-2 2 1M5 11l-1 2-2-1"/>
      </svg>
      Sinkronisasi
    </button>
  </nav>

  <div class="status">
    <span class="dot off" id="apiDot"></span>
    <span id="apiStatus">Menghubungkan…</span>
  </div>
</div>

<!-- ══ PAGE: DASHBOARD SALDO ══ -->
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
      <div class="panel-title">Formalhault <span class="pill pill-blue">SQL · oortmyid_e0</span></div>
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
      <div class="panel-title">Name-List <span class="pill pill-green">XANO API</span></div>
      <div class="row-flex">
        <input type="text" id="nlSearch" placeholder="Cari nama…" />
        <button class="btn primary sm" id="addNlBtn">+ Tambah</button>
      </div>
      <div class="tbl-wrap" id="nlWrap"><div class="loading-txt">Memuat data XANO…</div></div>
    </div>

  </div>
</div>
</div>

<!-- ══ PAGE: SINKRONISASI ══ -->
<div class="page" id="page-sync">
<div class="main">

  <div class="page-hdr">
    <div class="page-hdr-icon teal">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7">
        <path d="M3 8a5 5 0 0 1 9-3M13 8a5 5 0 0 1-9 3"/>
        <path d="M11 5l1-2 2 1M5 11l-1 2-2-1"/>
      </svg>
    </div>
    <div class="page-hdr-text">
      <h2>Sinkronisasi Formalhault</h2>
      <p>Sinkronisasi data antara SQL (oortmyid_e0) dan XANO API secara langsung</p>
    </div>
    <div class="page-hdr-actions">
      <button class="btn ghost sm" id="refreshDiffBtn">↺ Muat Ulang</button>
    </div>
  </div>

  <!-- Stat row -->
  <div class="stat-row">
    <div class="stat-mini"><span class="sm-label">Entri SQL</span><span class="sm-val" id="syncStatSQL">—</span></div>
    <div class="stat-mini"><span class="sm-label">Entri XANO</span><span class="sm-val" id="syncStatXano">—</span></div>
    <div class="stat-mini"><span class="sm-label">Hanya di SQL</span><span class="sm-val" id="syncStatOnlySQL">—</span></div>
    <div class="stat-mini"><span class="sm-label">Hanya di XANO</span><span class="sm-val" id="syncStatOnlyXano">—</span></div>
    <div class="stat-mini"><span class="sm-label">Berbeda</span><span class="sm-val" id="syncStatDiff">—</span></div>
    <div class="stat-mini"><span class="sm-label">Identik</span><span class="sm-val" id="syncStatSame">—</span></div>
  </div>

  <!-- Sync Actions -->
  <div class="panel" style="margin-bottom:14px;">
    <div class="panel-title">Aksi Sinkronisasi</div>
    <div class="sync-grid">
      <div class="sync-source">
        <h3>SQL · oortmyid_e0</h3>
        <div class="count" id="sqlCount">—</div>
        <div class="label">baris di formalhault</div>
        <div style="margin-top:12px;display:flex;gap:7px;flex-wrap:wrap;">
          <button class="btn primary" id="sqlToXanoBtn">SQL → XANO &nbsp;↗</button>
          <button class="btn ghost sm" id="sqlToXanoContBtn" style="display:none;" data-offset="0">Lanjutkan ›</button>
        </div>
        <div id="prog_sql2xano"></div>
        <div style="font-size:10.5px;color:var(--text3);margin-top:6px;">Upsert SQL ke XANO · 10 entitas per batch</div>
      </div>
      <div class="sync-arrow">
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
          <path d="M4 10h12M11 6l5 4-5 4"/>
        </svg>
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" style="transform:scaleX(-1)">
          <path d="M4 10h12M11 6l5 4-5 4"/>
        </svg>
      </div>
      <div class="sync-source">
        <h3>XANO · formalhault</h3>
        <div class="count" id="xanoCount">—</div>
        <div class="label">baris di XANO</div>
        <div style="margin-top:12px;display:flex;gap:7px;flex-wrap:wrap;">
          <button class="btn teal" id="xanoToSqlBtn">XANO → SQL &nbsp;↙</button>
          <button class="btn ghost sm" id="xanoToSqlContBtn" style="display:none;" data-offset="0">Lanjutkan ›</button>
        </div>
        <div id="prog_xano2sql"></div>
        <div style="font-size:10.5px;color:var(--text3);margin-top:6px;">Upsert XANO ke SQL · 10 entitas per batch</div>
      </div>
    </div>

    <!-- Log -->
    <div style="font-size:10px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:6px;">Log Sinkronisasi</div>
    <div class="log-box" id="syncLog"><span style="color:#6c7086;">— Belum ada aktivitas —</span></div>
  </div>

  <!-- Diff table -->
  <div class="panel">
    <div class="panel-title">Perbandingan Data · Formalhault <span class="pill pill-teal" id="diffCount">memuat…</span></div>
    <div class="diff-legend">
      <span><span class="legend-dot legend-add"></span> Hanya di satu sumber / baru</span>
      <span><span class="legend-dot legend-mod"></span> Nilai berbeda</span>
    </div>
    <div class="tbl-wrap" id="diffWrap"><div class="loading-txt">Memuat perbandingan…</div></div>
  </div>

</div>
</div>

<!-- ══ MODAL: Tambah Name-List ══ -->
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
  document.getElementById('apiDot').className  = 'dot' + (ok ? '' : ' off');
  document.getElementById('apiStatus').textContent = ok ? 'API Terhubung' : 'Demo Mode';
}

const NUM_FIELDS = ['Saldo','Hutang','Modal','RegNo'];
let fhData = [], nlData = [], xanoFhData = [];
let activeFilters = ['Nama','RegNo'];

// ── log helper ───────────────────────────────────────────
function log(msg, cls = 'log-info') {
  const box = document.getElementById('syncLog');
  const ts  = new Date().toLocaleTimeString('id-ID');
  const line = document.createElement('div');
  line.className = cls;
  line.textContent = `[${ts}] ${msg}`;
  if (box.querySelector('span')) box.innerHTML = '';
  box.appendChild(line);
  box.scrollTop = box.scrollHeight;
}

// ── SQL formalhault ───────────────────────────────────────
async function loadFH() {
  try {
    const r = await fetch('?action=get_fh_sql');
    const d = await r.json();
    if (d.error) throw new Error(d.error);
    fhData = d;
  } catch {
    fhData = [
      {Nama:'E0', Jenis:'SQL', RegNo:0, Saldo:0, Hutang:0, Modal:0},
    ];
  }
  const totS = fhData.reduce((a,r)=>a+(+r.Saldo),0);
  const totH = fhData.reduce((a,r)=>a+(+r.Hutang),0);
  const totM = fhData.reduce((a,r)=>a+(+r.Modal),0);
  document.getElementById('fhSaldo').textContent  = fmt(totS);
  document.getElementById('fhHutang').textContent = fmt(totH);
  document.getElementById('fhModal').textContent  = fmt(totM);
  document.getElementById('sqlCount').textContent = fhData.length;
  renderFH();
}

function renderFH() {
  const w = document.getElementById('fhWrap');
  if (!activeFilters.length) { w.innerHTML = '<div class="empty">Pilih filter untuk melihat data</div>'; return; }
  if (!fhData.length) { w.innerHTML = '<div class="empty">Tidak ada data SQL</div>'; return; }
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

// ── XANO name_list ────────────────────────────────────────
async function loadNL() {
  try {
    const r = await fetch('?action=proxy_namelist');
    const d = await r.json();
    if (d.error) throw new Error(d.error);
    nlData = Array.isArray(d) ? d : [];
    setStatus(true);
  } catch {
    nlData = [{id:1, Nama:'E0'}];
    setStatus(false);
  }
  renderNL(nlData);
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

// ── XANO formalhault ──────────────────────────────────────
async function loadXanoFH() {
  try {
    const r = await fetch('?action=get_fh_xano');
    const d = await r.json();
    if (d.error) throw new Error(d.error);
    xanoFhData = Array.isArray(d) ? d : [];
  } catch {
    xanoFhData = [];
  }
  document.getElementById('xanoCount').textContent = xanoFhData.length;
}

// ── Diff / comparison ─────────────────────────────────────
const DIFF_FIELDS = ['Nama','Jenis','RegNo','Saldo','Hutang','Modal'];

function buildDiff() {
  const sqlByRegNo  = {};
  const xanoByRegNo = {};
  fhData.forEach(r  => sqlByRegNo[r.RegNo]  = r);
  xanoFhData.forEach(r => xanoByRegNo[r.RegNo] = r);

  const allKeys = new Set([...Object.keys(sqlByRegNo), ...Object.keys(xanoByRegNo)]);
  const rows = [];

  allKeys.forEach(rn => {
    const sqlR  = sqlByRegNo[rn]  || null;
    const xanoR = xanoByRegNo[rn] || null;
    let status = 'same';
    if (!sqlR)  status = 'only-xano';
    else if (!xanoR) status = 'only-sql';
    else {
      // compare fields
      const changed = DIFF_FIELDS.filter(f => String(sqlR[f]??'') !== String(xanoR[f]??''));
      if (changed.length) status = 'diff';
    }
    rows.push({ regNo: rn, sqlR, xanoR, status });
  });
  return rows;
}

function renderDiff() {
  const rows = buildDiff();
  const onlySQL  = rows.filter(r=>r.status==='only-sql').length;
  const onlyXano = rows.filter(r=>r.status==='only-xano').length;
  const diff     = rows.filter(r=>r.status==='diff').length;
  const same     = rows.filter(r=>r.status==='same').length;

  document.getElementById('syncStatSQL').textContent      = fhData.length;
  document.getElementById('syncStatXano').textContent     = xanoFhData.length;
  document.getElementById('syncStatOnlySQL').textContent  = onlySQL;
  document.getElementById('syncStatOnlyXano').textContent = onlyXano;
  document.getElementById('syncStatDiff').textContent     = diff;
  document.getElementById('syncStatSame').textContent     = same;
  document.getElementById('diffCount').textContent        = rows.length + ' baris';

  const w = document.getElementById('diffWrap');
  if (!rows.length) { w.innerHTML = '<div class="empty">Kedua sumber kosong</div>'; return; }

  let h = `<table><thead><tr>
    <th>RegNo</th><th>Status</th>
    <th>Nama (SQL)</th><th>Saldo (SQL)</th><th>Hutang (SQL)</th><th>Modal (SQL)</th>
    <th>Nama (XANO)</th><th>Saldo (XANO)</th><th>Hutang (XANO)</th><th>Modal (XANO)</th>
  </tr></thead><tbody>`;

  rows.forEach(({regNo, sqlR, xanoR, status}) => {
    let rowCls = '';
    let badge  = '';
    if (status === 'only-sql')  { rowCls = 'diff-add'; badge = `<span class="src-badge src-sql">Hanya SQL</span>`; }
    else if (status === 'only-xano') { rowCls = 'diff-add'; badge = `<span class="src-badge src-api">Hanya XANO</span>`; }
    else if (status === 'diff') { rowCls = 'diff-mod'; badge = `<span class="src-badge" style="background:var(--amber-light);color:var(--amber-dark);">Berbeda</span>`; }
    else { badge = `<span class="src-badge" style="background:var(--surface2);color:var(--text3);">Identik</span>`; }

    const sc = f => sqlR  ? (NUM_FIELDS.includes(f) ? fmt(sqlR[f]) : esc(sqlR[f])) : '<span class="muted">—</span>';
    const xc = f => xanoR ? (NUM_FIELDS.includes(f) ? fmt(xanoR[f]) : esc(xanoR[f])) : '<span class="muted">—</span>';

    h += `<tr class="${rowCls}">
      <td class="num" style="color:var(--blue);font-weight:600;">${esc(regNo)}</td>
      <td>${badge}</td>
      <td>${sc('Nama')}</td><td class="num">${sc('Saldo')}</td><td class="num">${sc('Hutang')}</td><td class="num">${sc('Modal')}</td>
      <td>${xc('Nama')}</td><td class="num">${xc('Saldo')}</td><td class="num">${xc('Hutang')}</td><td class="num">${xc('Modal')}</td>
    </tr>`;
  });
  w.innerHTML = h + '</tbody></table>';
}

// ── Sync batch helpers ────────────────────────────────────
function renderProgress(containerId, done, total) {
  const el = document.getElementById(containerId);
  if (!el) return;
  const pct = total > 0 ? Math.round((done / total) * 100) : 0;
  el.innerHTML = `
    <div style="margin-top:8px;">
      <div style="display:flex;justify-content:space-between;font-size:10.5px;color:var(--text2);margin-bottom:4px;">
        <span>Progres</span><span>${done} / ${total} (${pct}%)</span>
      </div>
      <div style="height:6px;background:var(--surface2);border-radius:20px;overflow:hidden;border:0.5px solid var(--border2);">
        <div style="height:100%;width:${pct}%;background:var(--teal);border-radius:20px;transition:width 0.3s;"></div>
      </div>
    </div>`;
}

async function runSyncBatch(action, offset, btnId, contBtnId, progressId, label, reloadFn) {
  const btn     = document.getElementById(btnId);
  const contBtn = document.getElementById(contBtnId);
  btn.disabled  = true;
  if (contBtn) contBtn.style.display = 'none';

  log(`${offset === 0 ? 'Memulai' : 'Melanjutkan'} ${label} (offset ${offset})…`, 'log-info');
  try {
    const r = await fetch(`?action=${action}&offset=${offset}`);
    const d = await r.json();
    if (d.error) throw new Error(d.error);

    const done = d.offset + d.batch_size;
    log(`Batch ${d.offset}–${done - 1}: ${d.inserted} ditambah, ${d.updated} diperbarui`, 'log-ok');
    if (d.errors?.length) d.errors.forEach(e => log('⚠ ' + e, 'log-err'));

    renderProgress(progressId, done, d.total);

    if (d.has_more) {
      log(`Sisa ${d.total - done} entitas — klik "Lanjutkan" untuk batch berikutnya.`, 'log-warn');
      if (contBtn) { contBtn.style.display = 'inline-flex'; contBtn.dataset.offset = d.next_offset; }
    } else {
      log(`${label} selesai. Total ${d.total} entitas tersinkronkan.`, 'log-ok');
      if (contBtn) contBtn.style.display = 'none';
      await reloadFn();
      renderDiff();
    }
  } catch(e) {
    log('Gagal: ' + e.message, 'log-err');
  }
  btn.disabled = false;
}

// ── Sync: SQL → XANO ─────────────────────────────────────
document.getElementById('sqlToXanoBtn').addEventListener('click', () => {
  runSyncBatch('sync_sql_to_xano', 0, 'sqlToXanoBtn', 'sqlToXanoContBtn', 'prog_sql2xano', 'SQL → XANO', loadXanoFH);
});
document.getElementById('sqlToXanoContBtn').addEventListener('click', function() {
  runSyncBatch('sync_sql_to_xano', parseInt(this.dataset.offset||'0'), 'sqlToXanoBtn', 'sqlToXanoContBtn', 'prog_sql2xano', 'SQL → XANO', loadXanoFH);
});

// ── Sync: XANO → SQL ─────────────────────────────────────
document.getElementById('xanoToSqlBtn').addEventListener('click', () => {
  runSyncBatch('sync_xano_to_sql', 0, 'xanoToSqlBtn', 'xanoToSqlContBtn', 'prog_xano2sql', 'XANO → SQL', loadFH);
});
document.getElementById('xanoToSqlContBtn').addEventListener('click', function() {
  runSyncBatch('sync_xano_to_sql', parseInt(this.dataset.offset||'0'), 'xanoToSqlBtn', 'xanoToSqlContBtn', 'prog_xano2sql', 'XANO → SQL', loadFH);
});

// ── Refresh diff ──────────────────────────────────────────
document.getElementById('refreshDiffBtn').addEventListener('click', async () => {
  document.getElementById('diffWrap').innerHTML = '<div class="loading-txt">Memuat ulang…</div>';
  await Promise.all([loadFH(), loadXanoFH()]);
  renderDiff();
});

// ── Modal: name-list ─────────────────────────────────────
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
  m.textContent = ''; m.className = 'modal-msg';
}

document.getElementById('nlSaveBtn').addEventListener('click', async () => {
  const nama = document.getElementById('newNlNama').value.trim();
  const msg  = document.getElementById('nlModalMsg');
  if (!nama) { msg.textContent = 'Nama tidak boleh kosong.'; msg.className = 'modal-msg err'; return; }
  const btn = document.getElementById('nlSaveBtn');
  btn.textContent = 'Menyimpan…'; btn.disabled = true;
  try {
    const r = await fetch('?action=proxy_namelist', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({Nama: nama})
    });
    const d = await r.json();
    if (d.error) throw new Error(d.error);
    nlData.unshift(d);
    msg.textContent = 'Berhasil ditambahkan!'; msg.className = 'modal-msg ok';
  } catch {
    nlData.unshift({id: Date.now(), Nama: nama});
    msg.textContent = 'Tersimpan (demo mode).'; msg.className = 'modal-msg ok';
  }
  renderNL(nlData);
  btn.textContent = 'Simpan'; btn.disabled = false;
  setTimeout(closeNlModal, 900);
});

// ── Nav routing ───────────────────────────────────────────
document.querySelectorAll('.nav-item').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    document.getElementById(this.dataset.page).classList.add('active');
  });
});

// ── Keyboard ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeNlModal();
  if (e.key === 'Enter' && document.getElementById('nlModalOverlay').classList.contains('open'))
    document.getElementById('nlSaveBtn').click();
});

// ── Init ──────────────────────────────────────────────────
(async () => {
  await Promise.all([loadFH(), loadNL(), loadXanoFH()]);
  renderDiff();
})();
</script>
</body>
</html>