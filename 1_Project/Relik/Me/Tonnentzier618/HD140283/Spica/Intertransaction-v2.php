<?php
/* ═══════════════════════════════════════════
   DB CONNECTION
═══════════════════════════════════════════ */
$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_e0";

$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
if (!$conn) { die("Koneksi DB gagal"); }

/* ═══════════════════════════════════════════
   BOBOT MAP
═══════════════════════════════════════════ */
$bobotMap = [
    'Social Acceptance'        => 5,
    'Interpersonal Acceptance' => 5,
    'Affection'                => 5,
    'Self-Actualization'       => 7,
    'Skill-Actualization'      => 3,
    'Ideal-Actualization'      => 7,
    'Accepting Authority'      => 10,
    'Fullfilled'               => 3,
];

/* ═══════════════════════════════════════════
   SQL NAME LIST
═══════════════════════════════════════════ */
$list_nama_sql = [];
$q = mysqli_query($conn, "SELECT Nama, Saldo FROM formalhault ORDER BY Nama ASC");
$sql_rows = [];
while ($r = mysqli_fetch_assoc($q)) {
    $list_nama_sql[] = $r['Nama'];
    $sql_rows[] = $r;
}

/* ═══════════════════════════════════════════
   HANDLE SQL SUBMIT (Spica)
═══════════════════════════════════════════ */
$spica_result = null;

if (isset($_POST['submit_sql'])) {
    $Dari    = $_POST['Spica-F'];
    $Terima  = $_POST['Spica-R'];
    $Nominal = intval($_POST['Spica-M']);
    $Tujuan  = $_POST['Spica-O'];
    $Senang  = intval($_POST['Spica-A4']);
    $Sedih   = intval($_POST['Spica-F2']);
    $Grief   = intval($_POST['Spica-N0N4']);
    $Bobot   = isset($bobotMap[$Tujuan]) ? $bobotMap[$Tujuan] : 0;

    mysqli_query($conn, "
        INSERT INTO spica (Dari,Terima,Monetasi,Tujuan,Senang,Sedih,Grief,Prioritas)
        VALUES ('$Dari','$Terima',$Nominal,'$Tujuan',$Senang,$Sedih,$Grief,4)
    ");

    // Credit penerima
    $rc = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Nama,Saldo FROM formalhault WHERE Nama='$Terima'"));
    $saldo_terima = $rc['Saldo'] + $Nominal;
    mysqli_query($conn, "UPDATE formalhault SET Saldo=$saldo_terima WHERE Nama='$Terima'");

    // Debit pengirim
    $rd = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Nama,Saldo FROM formalhault WHERE Nama='$Dari'"));
    $saldo_dari = $rd['Saldo'] - $Nominal;
    mysqli_query($conn, "UPDATE formalhault SET Saldo=$saldo_dari WHERE Nama='$Dari'");

    $spica_result = [
        'ok'           => true,
        'dari'         => $Dari,
        'terima'       => $Terima,
        'nominal'      => $Nominal,
        'tujuan'       => $Tujuan,
        'bobot'        => $Bobot,
        'saldo_dari'   => $saldo_dari,
        'saldo_terima' => $saldo_terima,
    ];

    // Refresh SQL rows after transaction
    $sql_rows = [];
    $list_nama_sql = [];
    $q2 = mysqli_query($conn, "SELECT Nama, Saldo FROM formalhault ORDER BY Nama ASC");
    while ($r2 = mysqli_fetch_assoc($q2)) {
        $list_nama_sql[] = $r2['Nama'];
        $sql_rows[] = $r2;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>InterTransaction Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* ════════════════════════════════════
   ROOT & RESET
════════════════════════════════════ */
:root {
    --bg:     #0d0f14;
    --surf:   #151820;
    --surf2:  #1c2030;
    --border: #272c3d;
    --accent: #5b7cfa;
    --green:  #22d3a0;
    --red:    #f43f5e;
    --orange: #f97316;
    --yellow: #eab308;
    --blue:   #60a5fa;
    --purple: #a78bfa;
    --text:   #e8eaf2;
    --muted:  #6b7591;
    --r:      14px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Syne', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
}
body::before {
    content: '';
    position: fixed; inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
    background-size: 48px 48px;
    pointer-events: none;
    z-index: 0;
}
.wrap { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 32px 24px 64px; }

/* ── Topbar nav ── */
.topnav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
}
.btn-nav {
    padding: 8px 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surf);
    color: var(--muted);
    font-family: 'DM Mono', monospace;
    font-size: .72rem;
    cursor: pointer;
    transition: border-color .2s, color .2s;
}
.btn-nav:hover { border-color: var(--accent); color: var(--accent); }
.nav-right { display: flex; gap: 8px; }

/* ── Header ── */
.header { margin-bottom: 28px; }
.header .tag {
    font-family: 'DM Mono', monospace; font-size: 11px;
    color: var(--green); text-transform: uppercase;
    letter-spacing: 2px; margin-bottom: 8px;
}
.header h1 { font-size: 34px; font-weight: 800; letter-spacing: -1px; }
.header h1 span { color: var(--accent); }
.header .sub {
    font-family: 'DM Mono', monospace; font-size: 11px;
    color: var(--muted); margin-top: 8px; line-height: 1.8;
}

/* ── Spinner ── */
.spinner {
    display: inline-block;
    width: 14px; height: 14px;
    border: 2px solid var(--border);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin .6s linear infinite;
    vertical-align: middle;
    margin-right: 6px;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Stat boxes ── */
.topbox { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.mini {
    background: var(--surf); border: 1px solid var(--border);
    border-radius: var(--r); padding: 20px;
    position: relative; overflow: hidden;
}
.mini::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--accent); }
.mini.green::after  { background: var(--green); }
.mini.orange::after { background: var(--orange); }
.mini.purple::after { background: var(--purple); }
.mini.sql::after    { background: #5b7cfa; }
.mini .label { font-family: 'DM Mono', monospace; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }
.mini .val { font-size: 28px; font-weight: 800; margin-top: 10px; }

/* ── Layout ── */
.grid2       { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
.two-tables  { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.card { background: var(--surf); border: 1px solid var(--border); border-radius: var(--r); padding: 24px; }
.card h2 { font-size: 14px; font-weight: 700; margin-bottom: 18px; letter-spacing: .3px; }

/* ── Tabs ── */
.tab-bar {
    display: flex;
    gap: 6px;
    margin-bottom: 22px;
    border-bottom: 1px solid var(--border);
    padding-bottom: 0;
}
.tab-btn {
    padding: 9px 20px;
    border: none;
    background: none;
    color: var(--muted);
    font-family: 'DM Mono', monospace;
    font-size: .75rem;
    letter-spacing: .1em;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: color .2s, border-color .2s;
}
.tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
.tab-btn:hover:not(.active) { color: var(--text); }

.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* ── Form fields ── */
.form-group { margin-bottom: 16px; }
.field-label {
    display: block;
    font-family: 'DM Mono', monospace; font-size: 11px;
    color: var(--muted); text-transform: uppercase; letter-spacing: 1px;
    margin-bottom: 7px;
}
select, input[type=number] {
    width: 100%;
    background: var(--surf2); border: 1px solid var(--border);
    border-radius: 8px; padding: 10px 12px;
    color: var(--text); font-family: 'Syne', sans-serif;
    font-size: 14px; outline: none; transition: border-color .2s;
    appearance: none;
}
select:focus, input[type=number]:focus { border-color: var(--accent); }
select option { background: var(--surf2); }
select:disabled { opacity: .5; cursor: not-allowed; }

/* Picker row (display + button) */
.picker-row {
    display: flex;
    gap: 8px;
    align-items: stretch;
}
.chosen-display {
    flex: 1;
    background: var(--surf2);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px 12px;
    font-family: 'DM Mono', monospace;
    font-size: .84rem;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 42px;
    cursor: default;
}
.chosen-display .placeholder { color: var(--muted); }
.chosen-display .cd-saldo { color: var(--green); font-size: .72rem; }
.chosen-display .cd-source {
    font-size: .65rem;
    padding: 2px 6px;
    border-radius: 4px;
    margin-left: auto;
    flex-shrink: 0;
}
.cd-source.sql { background: rgba(91,124,250,.15); color: var(--accent); }
.cd-source.api { background: rgba(34,211,160,.15); color: var(--green); }

.btn-pick {
    padding: 0 14px;
    background: var(--surf2);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--muted);
    font-family: 'DM Mono', monospace;
    font-size: .78rem;
    cursor: pointer;
    white-space: nowrap;
    transition: border-color .2s, color .2s;
    flex-shrink: 0;
}
.btn-pick:hover { border-color: var(--accent); color: var(--accent); }

/* ── Range Slider ── */
.range-wrap {
    display: flex; align-items: center; gap: 12px;
}
.range-wrap input[type=range] {
    -webkit-appearance: none; appearance: none;
    flex: 1; height: 6px;
    background: var(--surf2);
    border-radius: 999px; border: 1px solid var(--border);
    outline: none; cursor: pointer; transition: background .2s; padding: 0;
}
.range-wrap input[type=range]::-webkit-slider-thumb {
    -webkit-appearance: none; appearance: none;
    width: 18px; height: 18px; border-radius: 50%;
    background: var(--accent); border: 2px solid var(--bg);
    cursor: pointer; box-shadow: 0 0 6px rgba(91,124,250,.5);
    transition: transform .1s, box-shadow .1s;
}
.range-wrap input[type=range]::-webkit-slider-thumb:hover { transform: scale(1.2); box-shadow: 0 0 12px rgba(91,124,250,.8); }
.range-wrap input[type=range]::-moz-range-thumb {
    width: 18px; height: 18px; border-radius: 50%;
    background: var(--accent); border: 2px solid var(--bg); cursor: pointer;
}
.range-joy   input[type=range]::-webkit-slider-thumb { background: var(--yellow); box-shadow: 0 0 6px rgba(234,179,8,.5); }
.range-joy   input[type=range]::-moz-range-thumb     { background: var(--yellow); }
.range-sad   input[type=range]::-webkit-slider-thumb { background: var(--blue);   box-shadow: 0 0 6px rgba(96,165,250,.5); }
.range-sad   input[type=range]::-moz-range-thumb     { background: var(--blue); }
.range-grief input[type=range]::-webkit-slider-thumb { background: var(--purple); box-shadow: 0 0 6px rgba(167,139,250,.5); }
.range-grief input[type=range]::-moz-range-thumb     { background: var(--purple); }
.range-nominal input[type=range]::-webkit-slider-thumb { background: var(--green); box-shadow: 0 0 6px rgba(34,211,160,.5); }
.range-nominal input[type=range]::-moz-range-thumb     { background: var(--green); }

.range-val {
    font-family: 'DM Mono', monospace; font-size: 15px; font-weight: 700;
    min-width: 30px; text-align: right;
}
.range-joy    .range-val { color: var(--yellow); }
.range-sad    .range-val { color: var(--blue); }
.range-grief  .range-val { color: var(--purple); }
.range-nominal .range-val { color: var(--green); }

.range-ticks { display: flex; justify-content: space-between; margin-top: 4px; padding: 0 2px; }
.range-ticks span { font-family: 'DM Mono', monospace; font-size: 9px; color: var(--muted); }

/* Monetasi radio (Spica) */
.monetasi-group { display: grid; grid-template-columns: repeat(5, 1fr); gap: 7px; }
.monetasi-group input[type="radio"] { display: none; }
.monetasi-group label {
    display: flex; align-items: center; justify-content: center;
    padding: 9px 4px;
    border-radius: 8px; border: 1px solid var(--border);
    background: var(--surf2); color: var(--muted);
    font-size: .74rem; font-family: 'DM Mono', monospace;
    cursor: pointer; transition: all .15s;
}
.monetasi-group input[type="radio"]:checked + label {
    background: #f7c97e; color: #0d0f14;
    border-color: #f7c97e; font-weight: 600;
}
.monetasi-group label:hover { border-color: #f7c97e; color: #f7c97e; }

/* Bobot badge */
.bobot-badge {
    display: inline-block; margin-top: 7px;
    padding: 3px 10px; border-radius: 20px;
    font-size: .67rem; font-family: 'DM Mono', monospace;
    letter-spacing: .1em;
    background: rgba(91,124,250,.1); color: var(--accent);
    border: 1px solid rgba(91,124,250,.25);
    transition: opacity .2s;
}
.bobot-badge.hidden { opacity: 0; pointer-events: none; }

/* Scale buttons */
.scale { display: flex; flex-wrap: wrap; gap: 6px; }
.scale button {
    width: 36px; height: 36px;
    border: 1px solid var(--border); border-radius: 7px;
    background: var(--surf2); color: var(--muted);
    font-family: 'DM Mono', monospace; font-size: .8rem;
    cursor: pointer; transition: all .15s;
}
.scale button.active { background: var(--accent); color: #0d0f14; border-color: var(--accent); font-weight: 600; }
.scale button:hover:not(.active) { border-color: var(--accent); color: var(--accent); }

/* ── Section title ── */
.section-title {
    font-family: 'DM Mono', monospace; font-size: 10px;
    color: var(--muted); text-transform: uppercase;
    letter-spacing: 2px; margin-bottom: 14px;
}

/* ── Preview ── */
.preview {
    font-family: 'DM Mono', monospace; font-size: 12px; color: var(--muted);
    background: var(--surf2); border: 1px dashed var(--border);
    border-radius: 8px; padding: 12px; margin-bottom: 14px; line-height: 2;
}
.preview .pv-val   { color: var(--accent); }
.pv-joy   { color: var(--yellow) !important; }
.pv-sad   { color: var(--blue)   !important; }
.pv-grief { color: var(--purple) !important; }

/* ── Divider ── */
.divider { border: none; border-top: 1px solid var(--border); margin: 18px 0; }

/* ── Buttons ── */
.btn {
    width: 100%; border: none; padding: 13px; border-radius: 10px;
    background: var(--accent); color: #fff;
    font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
    cursor: pointer; transition: opacity .2s, transform .1s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn:hover:not(:disabled) { opacity: .85; transform: translateY(-1px); }
.btn:disabled { opacity: .5; cursor: not-allowed; }
.btn.sql-btn { background: linear-gradient(135deg, #5b7cfa, #7c3aed); margin-top: 8px; }

/* ── Alert ── */
.alert {
    border-radius: var(--r); padding: 14px 18px;
    margin-bottom: 22px; font-size: 14px;
    border: 1px solid; display: none; line-height: 1.8;
    font-family: 'DM Mono', monospace;
}
.alert.show { display: block; }
.alert.ok   { background: rgba(34,211,160,.08); border-color: rgba(34,211,160,.3); color: var(--green); }
.alert.err  { background: rgba(244,63,94,.08);  border-color: rgba(244,63,94,.3);  color: var(--red); }

/* ── Table ── */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 400px; }
th {
    font-family: 'DM Mono', monospace; font-size: 11px;
    color: var(--muted); text-transform: uppercase; letter-spacing: 1px;
    padding: 10px 12px; border-bottom: 1px solid var(--border);
    text-align: left; white-space: nowrap;
}
td { padding: 11px 12px; border-bottom: 1px solid var(--border); font-size: 13px; white-space: nowrap; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(255,255,255,.02); }
.empty-row { color: var(--muted); text-align: center; padding: 28px !important; white-space: normal !important; }

.badge {
    display: inline-block; padding: 3px 10px; border-radius: 999px;
    font-family: 'DM Mono', monospace; font-size: 11px;
}
.badge.pt     { background: rgba(91,124,250,.15);  color: var(--accent); }
.badge.id     { background: rgba(255,255,255,.05); color: var(--muted); font-size: 10px; }
.badge.joy    { background: rgba(234,179,8,.12);   color: var(--yellow); }
.badge.sad    { background: rgba(96,165,250,.12);  color: var(--blue); }
.badge.grief  { background: rgba(167,139,250,.12); color: var(--purple); }
.badge.sql-b  { background: rgba(91,124,250,.12);  color: var(--accent); }
.badge.api-b  { background: rgba(34,211,160,.12);  color: var(--green); }

/* Validation error */
.val-err {
    display: none; margin-top: 5px;
    font-family: 'DM Mono', monospace; font-size: .71rem;
    color: var(--red);
}

/* ════════════════════════════════════
   POPUP OVERLAY
════════════════════════════════════ */
.popup-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.7);
    backdrop-filter: blur(6px);
    z-index: 1000;
    align-items: center; justify-content: center;
}
.popup-overlay.open { display: flex; }

.popup-box {
    background: var(--surf);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 28px 28px 24px;
    width: 100%; max-width: 460px;
    max-height: 82vh; overflow-y: auto;
    position: relative;
    box-shadow: 0 24px 80px rgba(0,0,0,.7);
}
.popup-box h3 {
    font-size: 16px; font-weight: 700; margin-bottom: 4px;
}
.popup-sub {
    font-family: 'DM Mono', monospace; font-size: 10px;
    color: var(--muted); letter-spacing: .14em; text-transform: uppercase;
    margin-bottom: 18px;
}
.popup-close {
    position: absolute; top: 16px; right: 18px;
    background: none; border: none;
    color: var(--muted); font-size: 1.1rem; cursor: pointer;
    transition: color .2s;
}
.popup-close:hover { color: var(--text); }

/* Source toggle */
.src-toggle { display: flex; gap: 8px; margin-bottom: 14px; }
.src-btn {
    flex: 1; padding: 8px 10px;
    border: 1px solid var(--border); border-radius: 8px;
    background: var(--surf2); color: var(--muted);
    font-family: 'DM Mono', monospace; font-size: .73rem;
    cursor: pointer; transition: all .2s;
}
.src-btn.act-sql { background: rgba(91,124,250,.15); color: var(--accent); border-color: var(--accent); }
.src-btn.act-api { background: rgba(34,211,160,.15); color: var(--green);  border-color: var(--green); }

/* Popup search */
.popup-search {
    width: 100%; padding: 9px 12px;
    background: var(--surf2); border: 1px solid var(--border);
    border-radius: 8px; color: var(--text);
    font-family: 'DM Mono', monospace; font-size: .84rem;
    outline: none; margin-bottom: 10px;
    transition: border-color .2s;
}
.popup-search:focus { border-color: var(--accent); }

/* Popup list */
.popup-list { display: flex; flex-direction: column; gap: 5px; }
.popup-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 12px;
    border: 1px solid var(--border); border-radius: 10px;
    background: var(--surf2); cursor: pointer;
    transition: border-color .18s, background .18s;
}
.popup-item:hover  { border-color: var(--accent); background: rgba(91,124,250,.05); }
.popup-item.sel    { border-color: var(--green);  background: rgba(34,211,160,.05); }
.pi-name  { font-size: .88rem; font-weight: 600; }
.pi-meta  { font-family: 'DM Mono', monospace; font-size: .68rem; color: var(--muted); }
.pi-saldo { font-family: 'DM Mono', monospace; font-size: .75rem; color: var(--green); }
.popup-loading {
    text-align: center; padding: 22px;
    font-family: 'DM Mono', monospace; font-size: .8rem; color: var(--muted);
}

/* Responsive */
@media(max-width:900px) { .topbox, .grid2, .two-tables { grid-template-columns: 1fr !important; } }
@media(max-width:500px) { .topbox { grid-template-columns: repeat(2,1fr) !important; } }
@media(max-width:480px) { .card { padding: 18px 14px; } .popup-box { padding: 20px 14px; } }
</style>
</head>
<body>
<div class="wrap">

  <!-- ── Nav ── -->
  <div class="topnav">
    <button class="btn-nav" onclick="location.href='menu.php'">⬅ Menu</button>
    <div class="nav-right">
      <button class="btn-nav" onclick="reloadAll()">↺ Refresh</button>
    </div>
  </div>

  <!-- ── Header ── -->
  <div class="header">
    <div class="tag">■ INTERTRANSACTION SYSTEM</div>
    <h1>Transfer <span>Dashboard</span></h1>
    <div class="sub">
      API (Xano) ↔ SQL (MySQL) &nbsp;|&nbsp; <span id="headerTime">—</span>
    </div>
  </div>

  <div class="alert" id="alertBox"></div>

  <!-- ── Stat boxes ── -->
  <div class="topbox">
    <div class="mini">
      <div class="label">Nama API</div>
      <div class="val" id="statNames"><span class="spinner"></span></div>
    </div>
    <div class="mini green">
      <div class="label">Total Kwitansi</div>
      <div class="val" id="statKwitansi"><span class="spinner"></span></div>
    </div>
    <div class="mini orange">
      <div class="label">Point Hari Ini</div>
      <div class="val" id="statToday"><span class="spinner"></span></div>
    </div>
    <div class="mini purple">
      <div class="label">Total Originium</div>
      <div class="val" id="statOriginium"><span class="spinner"></span></div>
    </div>
  </div>

  <!-- ── Chart + Form tabs ── -->
  <div class="grid2">

    <!-- Chart -->
    <div class="card">
      <h2>📈 Nominal Transaksi — 4 Hari Terakhir</h2>
      <canvas id="trendChart" height="120"></canvas>
    </div>

    <!-- Form card with tabs -->
    <div class="card" style="overflow-y:auto;max-height:900px;">

      <div class="tab-bar">
        <button class="tab-btn active" id="tabBtnApi" onclick="switchTab('api')">🌐 API Transfer</button>
        <button class="tab-btn" id="tabBtnSql" onclick="switchTab('sql')">🗄️ Spica (SQL)</button>
      </div>

      <!-- ════ TAB: API ════ -->
      <div id="tabApi" class="tab-panel active">

        <div class="section-title">▸ Data Transfer (Kwitansi)</div>

        <div class="form-group">
          <label class="field-label">Pengirim</label>
          <div class="picker-row">
            <div class="chosen-display" id="disp-pg-api"><span class="placeholder">Belum dipilih</span></div>
            <button class="btn-pick" type="button" onclick="openPopup('pg-api')">🔍 Pilih</button>
          </div>
          <input type="hidden" id="val-pg-api" data-source="">
        </div>

        <div class="form-group">
          <label class="field-label">Penerima</label>
          <div class="picker-row">
            <div class="chosen-display" id="disp-pr-api"><span class="placeholder">Belum dipilih</span></div>
            <button class="btn-pick" type="button" onclick="openPopup('pr-api')">🔍 Pilih</button>
          </div>
          <input type="hidden" id="val-pr-api" data-source="">
          <div class="val-err" id="err-api">Pengirim dan penerima tidak boleh sama!</div>
        </div>

        <div class="form-group">
          <label class="field-label">Keterangan</label>
          <select id="sel_keterangan">
            <option value="">— Pilih Keterangan —</option>
            <option value="Kelelahan">Kelelahan</option>
            <option value="Afeksi">Afeksi</option>
            <option value="Self-Actualization">Self-Actualization</option>
            <option value="Represion">Represion</option>
            <option value="Distract / Brainfog">Distract / Brainfog</option>
          </select>
        </div>

        <div class="form-group">
          <label class="field-label">Nominal — <span style="color:var(--green);font-family:'DM Mono',monospace;" id="nominalDisplay">10</span> pt</label>
          <div class="range-wrap range-nominal">
            <input type="range" id="inp_nominal" min="10" max="100" step="10" value="10">
            <span class="range-val" id="nominalVal">10</span>
          </div>
          <div class="range-ticks">
            <span>10</span><span>20</span><span>30</span><span>40</span><span>50</span>
            <span>60</span><span>70</span><span>80</span><span>90</span><span>100</span>
          </div>
        </div>

        <hr class="divider">
        <div class="section-title">▸ Emosi Saat Ini (Originium)</div>

        <div class="form-group">
          <label class="field-label">Scale Joy — <span style="color:var(--yellow);font-family:'DM Mono',monospace;" id="orJoyDisplay">0</span></label>
          <div class="range-wrap range-joy">
            <input type="range" id="or_joy" min="0" max="10" step="1" value="0">
            <span class="range-val" id="orJoyVal">0</span>
          </div>
          <div class="range-ticks"><span>0</span><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span><span>7</span><span>8</span><span>9</span><span>10</span></div>
        </div>

        <div class="form-group">
          <label class="field-label">Scale Sad — <span style="color:var(--blue);font-family:'DM Mono',monospace;" id="orSadDisplay">0</span></label>
          <div class="range-wrap range-sad">
            <input type="range" id="or_sad" min="0" max="10" step="1" value="0">
            <span class="range-val" id="orSadVal">0</span>
          </div>
          <div class="range-ticks"><span>0</span><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span><span>7</span><span>8</span><span>9</span><span>10</span></div>
        </div>

        <div class="form-group">
          <label class="field-label">Scale Grief — <span style="color:var(--purple);font-family:'DM Mono',monospace;" id="orGriefDisplay">0</span></label>
          <div class="range-wrap range-grief">
            <input type="range" id="or_grief" min="0" max="10" step="1" value="0">
            <span class="range-val" id="orGriefVal">0</span>
          </div>
          <div class="range-ticks"><span>0</span><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span><span>7</span><span>8</span><span>9</span><span>10</span></div>
        </div>

        <hr class="divider">

        <div class="preview">
          <div><span>Keterangan : </span><span class="pv-val" id="pvKet">—</span></div>
          <div><span>Nominal    : </span><span class="pv-val" id="pvNominal">10 pt</span></div>
          <div>
            <span>Originium  : </span>
            Joy=<span class="pv-joy" id="pvOrJoy">0</span>&nbsp;
            Sad=<span class="pv-sad" id="pvOrSad">0</span>&nbsp;
            Grief=<span class="pv-grief" id="pvOrGrief">0</span>
          </div>
        </div>

        <button class="btn" id="btnSubmitApi">
          <span class="spinner" id="btnSpinner" style="display:none"></span>
          <span id="btnLabel">Kirim Transfer</span>
        </button>

      </div><!-- /tabApi -->

      <!-- ════ TAB: SQL (Spica) ════ -->
      <div id="tabSql" class="tab-panel">

        <form method="POST" id="spica-form">

          <div class="section-title">▸ Data Spica (MySQL)</div>

          <div class="form-group">
            <label class="field-label">Dari</label>
            <div class="picker-row">
              <div class="chosen-display" id="disp-dari-sql"><span class="placeholder">Belum dipilih</span></div>
              <button type="button" class="btn-pick" onclick="openPopup('dari-sql')">🔍 Pilih</button>
            </div>
            <input type="hidden" name="Spica-F" id="val-dari-sql">
          </div>

          <div class="form-group">
            <label class="field-label">Penerima</label>
            <div class="picker-row">
              <div class="chosen-display" id="disp-terima-sql"><span class="placeholder">Belum dipilih</span></div>
              <button type="button" class="btn-pick" onclick="openPopup('terima-sql')">🔍 Pilih</button>
            </div>
            <input type="hidden" name="Spica-R" id="val-terima-sql">
            <div class="val-err" id="err-sql">Dari dan Penerima tidak boleh sama!</div>
          </div>

          <div class="form-group">
            <label class="field-label">Monetasi</label>
            <div class="monetasi-group">
              <?php for ($v = 10; $v <= 100; $v += 10): ?>
                <input type="radio" name="Spica-M" id="mon-<?= $v ?>" value="<?= $v ?>" required>
                <label for="mon-<?= $v ?>"><?= $v ?></label>
              <?php endfor; ?>
            </div>
          </div>

          <div class="form-group">
            <label class="field-label">Tujuan</label>
            <select name="Spica-O" id="tujuan-select" required>
              <option value="">— Pilih Tujuan —</option>
              <option value="Social Acceptance"        data-bobot="5">Social Acceptance</option>
              <option value="Interpersonal Acceptance" data-bobot="5">Interpersonal Acceptance</option>
              <option value="Affection"                data-bobot="5">Affection</option>
              <option value="Self-Actualization"       data-bobot="7">Self-Actualization</option>
              <option value="Skill-Actualization"      data-bobot="3">Skill-Actualization</option>
              <option value="Ideal-Actualization"      data-bobot="7">Ideal-Actualization</option>
              <option value="Accepting Authority"      data-bobot="10">Accepting Authority</option>
              <option value="Fullfilled"               data-bobot="3">Fullfilled</option>
            </select>
            <span class="bobot-badge hidden" id="bobot-badge">Bobot: —</span>
          </div>

          <input type="hidden" name="Spica-P" value="4">
          <hr class="divider">

          <div class="section-title">▸ Emosi Spica</div>

          <div class="form-group">
            <label class="field-label">😊 Senang (0–10)</label>
            <div class="scale" data-max="10" data-target="A4"></div>
            <input type="hidden" id="A4" name="Spica-A4" value="0">
          </div>
          <div class="form-group">
            <label class="field-label">😔 Sedih (0–10)</label>
            <div class="scale" data-max="10" data-target="F2"></div>
            <input type="hidden" id="F2" name="Spica-F2" value="0">
          </div>
          <div class="form-group">
            <label class="field-label">🖤 Grief (0–7)</label>
            <div class="scale" data-max="7" data-target="N0N4"></div>
            <input type="hidden" id="N0N4" name="Spica-N0N4" value="0">
          </div>

          <button type="submit" name="submit_sql" class="btn sql-btn" id="submit-sql-btn">
            Kirim Spica (SQL)
          </button>

        </form>

        <?php if ($spica_result): ?>
        <div style="margin-top:18px;padding:14px;background:var(--surf2);border:1px solid rgba(34,211,160,.3);border-radius:10px;font-family:'DM Mono',monospace;font-size:.8rem;line-height:1.9;color:var(--green);">
          ✅ Transaksi Spica berhasil!<br>
          <span style="color:var(--muted)">Dari</span> <?= htmlspecialchars($spica_result['dari']) ?>
          → <span style="color:var(--muted)">Terima</span> <?= htmlspecialchars($spica_result['terima']) ?><br>
          <span style="color:var(--muted)">Nominal</span> <?= $spica_result['nominal'] ?> pt &nbsp;|&nbsp;
          <span style="color:var(--muted)">Tujuan</span> <?= htmlspecialchars($spica_result['tujuan']) ?> &nbsp;(Bobot: <?= $spica_result['bobot'] ?>)<br>
          <span style="color:var(--muted)">Saldo Baru:</span>
          <?= htmlspecialchars($spica_result['dari']) ?> = <?= $spica_result['saldo_dari'] ?> pt &nbsp;|&nbsp;
          <?= htmlspecialchars($spica_result['terima']) ?> = <?= $spica_result['saldo_terima'] ?> pt
        </div>
        <?php endif; ?>

      </div><!-- /tabSql -->

    </div><!-- /form card -->
  </div><!-- /grid2 -->

  <!-- ── Tables ── -->
  <div class="two-tables">
    <div class="card">
      <h2>🧾 5 Kwitansi Terbaru</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Tanggal</th><th>Pengirim → Penerima</th><th>Nominal</th><th>Joy</th><th>Sad</th><th>Grief</th></tr></thead>
          <tbody id="kwitansiBody"><tr><td colspan="7" class="empty-row"><span class="spinner"></span> Memuat...</td></tr></tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <h2>🔮 5 Originium Terbaru</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Tanggal</th><th>Joy</th><th>Sad</th><th>Grief</th></tr></thead>
          <tbody id="originiumBody"><tr><td colspan="5" class="empty-row"><span class="spinner"></span> Memuat...</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- SQL Saldo table -->
  <div class="card">
    <h2>🗄️ Saldo SQL — formalhault</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Nama</th><th>Saldo (SQL)</th></tr></thead>
        <tbody>
          <?php foreach ($sql_rows as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['Nama']) ?></td>
            <td><span class="badge pt"><?= $row['Saldo'] ?> pt</span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /wrap -->


<!-- ══════════════════════════════════════
     POPUP PILIH NAMA
══════════════════════════════════════ -->
<div class="popup-overlay" id="popupOverlay">
  <div class="popup-box">
    <button class="popup-close" onclick="closePopup()">✕</button>
    <h3 id="popupTitle">Pilih Nama</h3>
    <p class="popup-sub" id="popupSub">—</p>

    <div class="src-toggle">
      <button class="src-btn act-sql" id="srcSql" onclick="switchSrc('sql')">🗄️ SQL</button>
      <button class="src-btn"         id="srcApi" onclick="switchSrc('api')">🌐 API (Xano)</button>
    </div>

    <input type="text" class="popup-search" id="popupSearch" placeholder="Cari nama..." oninput="filterList()">
    <div class="popup-list" id="popupList"><div class="popup-loading">Memuat...</div></div>
  </div>
</div>


<script>
/* ═══════════════ CONSTANTS ═══════════════ */
const FORMALHAULT_API = 'https://x8ki-letl-twmt.n7.xano.io/api:X6h8irt0/formalhault';
const KWITANSI_API    = 'https://x8ki-letl-twmt.n7.xano.io/api:ZBg3zfDx/kwitansi';
const NAMELIST_API    = 'https://x8ki-letl-twmt.n7.xano.io/api:ZBg3zfDx/name_list';
const ORIGINIUM_API   = 'https://x8ki-letl-twmt.n7.xano.io/api:X6h8irt0/originium';

/* ═══════════════ DATA STORES ═══════════════ */
let apiData  = [];   // Xano formalhault
let chartInst = null;

// SQL names injected from PHP
const sqlNames = <?= json_encode(array_column($sql_rows, 'Nama')) ?>;
const sqlSaldo = <?= json_encode(array_column($sql_rows, 'Saldo', 'Nama')) ?>;

/* ═══════════════ POPUP STATE ═══════════════ */
let popTarget  = null;   // e.g. 'pg-api', 'dari-sql'
let popSrc     = 'sql';  // 'sql' | 'api'
let popItems   = [];

/* ═══════════════ HELPERS ═══════════════ */
function toTs(v) {
    if (!v) return null;
    const n = Number(v);
    if (!isNaN(n) && n > 0) return n > 1e10 ? Math.floor(n/1000) : n;
    return Math.floor(new Date(v).getTime()/1000) || null;
}
function fmtDate(v) {
    const ts = toTs(v); if (!ts) return '–';
    return new Date(ts*1000).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
}
function dayStr(off=0) { const d=new Date(); d.setDate(d.getDate()-off); return d.toISOString().slice(0,10); }
function getRangeVal(id) { return parseInt(document.getElementById(id).value); }
function scaleBadge(v,t) { if(v==null) return '–'; return `<span class="badge ${t}">${v}</span>`; }

function showAlert(msg, ok=true) {
    const el = document.getElementById('alertBox');
    el.innerHTML = msg;
    el.className = 'alert show ' + (ok ? 'ok' : 'err');
    window.scrollTo({top:0, behavior:'smooth'});
    setTimeout(() => el.classList.remove('show'), 7000);
}

/* ═══════════════ TABS ═══════════════ */
function switchTab(tab) {
    document.getElementById('tabBtnApi').classList.toggle('active', tab==='api');
    document.getElementById('tabBtnSql').classList.toggle('active', tab==='sql');
    document.getElementById('tabApi').classList.toggle('active', tab==='api');
    document.getElementById('tabSql').classList.toggle('active', tab==='sql');
}

/* ═══════════════ RANGE BIND ═══════════════ */
function bindRange(inputId, valId, displayId) {
    const inp  = document.getElementById(inputId);
    const val  = document.getElementById(valId);
    const disp = displayId ? document.getElementById(displayId) : null;
    const sync = () => { val.textContent=inp.value; if(disp) disp.textContent=inp.value; updatePreview(); };
    inp.addEventListener('input', sync);
    sync();
}

/* ═══════════════ PREVIEW ═══════════════ */
function updatePreview() {
    const ket   = document.getElementById('sel_keterangan')?.value || '—';
    const nom   = getRangeVal('inp_nominal');
    const joy   = getRangeVal('or_joy');
    const sad   = getRangeVal('or_sad');
    const grief = getRangeVal('or_grief');
    document.getElementById('pvKet').textContent     = ket;
    document.getElementById('pvNominal').textContent = nom + ' pt';
    document.getElementById('pvOrJoy').textContent   = joy;
    document.getElementById('pvOrSad').textContent   = sad;
    document.getElementById('pvOrGrief').textContent = grief;
}

/* ═══════════════ POPUP ═══════════════ */
function openPopup(target) {
    popTarget = target;
    const labels = {
        'pg-api':    'Pengirim — API Transfer',
        'pr-api':    'Penerima — API Transfer',
        'dari-sql':  'Dari — Spica (SQL)',
        'terima-sql':'Penerima — Spica (SQL)',
    };
    const roles = {
        'pg-api':'PENGIRIM', 'pr-api':'PENERIMA',
        'dari-sql':'DARI', 'terima-sql':'PENERIMA',
    };
    document.getElementById('popupTitle').textContent = labels[target] || 'Pilih Nama';
    document.getElementById('popupSub').textContent   = roles[target] || '';
    document.getElementById('popupSearch').value = '';

    // default source: sql-targets start with sql
    const defSrc = target.endsWith('-sql') ? 'sql' : 'sql';
    switchSrc(defSrc, false);
    renderList();

    document.getElementById('popupOverlay').classList.add('open');
    setTimeout(() => document.getElementById('popupSearch').focus(), 80);
}
function closePopup() {
    document.getElementById('popupOverlay').classList.remove('open');
    popTarget = null;
}
document.getElementById('popupOverlay').addEventListener('click', function(e) {
    if (e.target === this) closePopup();
});

function switchSrc(src, render=true) {
    popSrc = src;
    document.getElementById('srcSql').className = 'src-btn' + (src==='sql' ? ' act-sql' : '');
    document.getElementById('srcApi').className = 'src-btn' + (src==='api' ? ' act-api' : '');
    if (render) renderList();
}

function renderList(filter='') {
    const f = filter.toLowerCase();
    let items;
    if (popSrc === 'sql') {
        items = sqlNames
            .filter(n => n.toLowerCase().includes(f))
            .map(n => ({ name: n, saldo: sqlSaldo[n] ?? '?', source: 'sql', id: null }));
    } else {
        items = apiData
            .filter(x => x.Nama.toLowerCase().includes(f))
            .map(x => ({ name: x.Nama, saldo: x.Saldo, source: 'api', id: x.id }));
    }
    popItems = items;

    const list = document.getElementById('popupList');
    if (!items.length) { list.innerHTML = '<div class="popup-loading">Tidak ada hasil.</div>'; return; }

    list.innerHTML = items.map((item, idx) => `
        <div class="popup-item" id="pi-${idx}" onclick="selectItem(${idx})">
            <div>
                <div class="pi-name">${item.name}</div>
                <div class="pi-meta">${item.source.toUpperCase()}</div>
            </div>
            <div class="pi-saldo">${item.saldo !== '?' ? item.saldo + ' pt' : '–'}</div>
        </div>
    `).join('');
}

function filterList() {
    renderList(document.getElementById('popupSearch').value);
}

function selectItem(idx) {
    const item = popItems[idx];
    if (!item || !popTarget) return;

    const disp   = document.getElementById('disp-' + popTarget);
    const hidden = document.getElementById('val-' + popTarget);

    if (disp) {
        disp.innerHTML =
            `<span style="font-weight:700">${item.name}</span>` +
            (item.saldo !== '?' ? `<span class="cd-saldo">${item.saldo} pt</span>` : '') +
            `<span class="cd-source ${item.source}">${item.source.toUpperCase()}</span>`;
    }
    if (hidden) {
        hidden.value = item.name;
        hidden.dataset.source = item.source;
        if (item.id) hidden.dataset.id = item.id;
    }

    validatePair();
    updatePreview();
    closePopup();
}

/* ═══════════════ VALIDATION ═══════════════ */
function validatePair() {
    // API pair
    const pg  = document.getElementById('val-pg-api')?.value;
    const pr  = document.getElementById('val-pr-api')?.value;
    const eA  = document.getElementById('err-api');
    const sameA = pg && pr && pg === pr;
    if (eA) eA.style.display = sameA ? 'block' : 'none';

    // SQL pair
    const d   = document.getElementById('val-dari-sql')?.value;
    const t   = document.getElementById('val-terima-sql')?.value;
    const eS  = document.getElementById('err-sql');
    const sameS = d && t && d === t;
    if (eS) eS.style.display = sameS ? 'block' : 'none';
}

/* ═══════════════ LOAD API DATA ═══════════════ */
async function loadNames() {
    try {
        const res   = await fetch(NAMELIST_API);
        const data  = await res.json();
        const names = (Array.isArray(data) ? data : []).map(r=>r.Nama).filter(Boolean).sort();
        document.getElementById('statNames').textContent = names.length;
    } catch(e) { document.getElementById('statNames').textContent = '–'; }
}

async function loadFormalhault() {
    try {
        const res = await fetch(FORMALHAULT_API);
        apiData   = await res.json();
        apiData.sort((a,b)=>a.Nama.localeCompare(b.Nama));
    } catch(e) { /* silent */ }
}

async function loadKwitansi() {
    try {
        const res  = await fetch(KWITANSI_API);
        const data = await res.json();
        const rows = Array.isArray(data) ? data : [];
        rows.sort((a,b)=>(toTs(b.created_at)??0)-(toTs(a.created_at)??0));
        document.getElementById('statKwitansi').textContent = rows.length;
        renderKwitansi(rows);
        buildChart(rows);
    } catch(e) { document.getElementById('statKwitansi').textContent = '–'; }
}

async function loadOriginium() {
    try {
        const res  = await fetch(ORIGINIUM_API);
        const data = await res.json();
        const rows = Array.isArray(data) ? data : [];
        rows.sort((a,b)=>(toTs(b.created_at)??0)-(toTs(a.created_at)??0));
        document.getElementById('statOriginium').textContent = rows.length;
        renderOriginium(rows);
    } catch(e) { document.getElementById('statOriginium').textContent = '–'; }
}

/* ═══════════════ RENDER TABLES ═══════════════ */
function renderKwitansi(rows) {
    const tbody = document.getElementById('kwitansiBody');
    if (!rows.length) { tbody.innerHTML=`<tr><td colspan="7" class="empty-row">Belum ada data.</td></tr>`; return; }
    tbody.innerHTML = rows.slice(0,5).map(r=>`
        <tr>
            <td>${scaleBadge(r.id,'id')}</td>
            <td style="font-family:'DM Mono',monospace;font-size:11px;color:var(--muted)">${fmtDate(r.created_at)}</td>
            <td style="color:var(--muted);font-family:'DM Mono',monospace;font-size:11px">${r.Nama_Pengirim??'–'} → ${r.Nama_Penerima??'–'}</td>
            <td>${scaleBadge(Number(r.Nominal??0).toLocaleString('id-ID')+' pt','pt')}</td>
            <td>${scaleBadge(r.Scale_Joy,'joy')}</td>
            <td>${scaleBadge(r.Scale_Sad,'sad')}</td>
            <td>${scaleBadge(r.Scale_Grief,'grief')}</td>
        </tr>`).join('');
}

function renderOriginium(rows) {
    const tbody = document.getElementById('originiumBody');
    if (!rows.length) { tbody.innerHTML=`<tr><td colspan="5" class="empty-row">Belum ada data.</td></tr>`; return; }
    tbody.innerHTML = rows.slice(0,5).map(r=>`
        <tr>
            <td>${scaleBadge(r.id,'id')}</td>
            <td style="font-family:'DM Mono',monospace;font-size:11px;color:var(--muted)">${fmtDate(r.created_at)}</td>
            <td>${scaleBadge(r.Scale_Joy,'joy')}</td>
            <td>${scaleBadge(r.Scale_Sad,'sad')}</td>
            <td>${scaleBadge(r.Scale_Grief,'grief')}</td>
        </tr>`).join('');
}

/* ═══════════════ CHART ═══════════════ */
function buildChart(rows) {
    const daily = {};
    for (let i=3; i>=0; i--) daily[dayStr(i)] = 0;
    rows.forEach(r => {
        const ts = toTs(r.created_at); if (!ts) return;
        const day = new Date(ts*1000).toISOString().slice(0,10);
        if (day in daily) daily[day] += Number(r.Nominal??0);
    });
    document.getElementById('statToday').textContent = Number(daily[dayStr(0)]??0).toLocaleString('id-ID');
    if (chartInst) chartInst.destroy();
    chartInst = new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: Object.keys(daily),
            datasets: [{
                label: 'Nominal (pt)',
                data: Object.values(daily),
                borderColor: '#5b7cfa',
                backgroundColor: 'rgba(91,124,250,.1)',
                pointBackgroundColor: '#5b7cfa',
                pointRadius: 5, fill: true, tension: 0.35,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { labels: { color:'#6b7591', font:{family:'DM Mono'} } } },
            scales: {
                x: { ticks:{color:'#6b7591',font:{family:'DM Mono',size:11}}, grid:{color:'#272c3d'} },
                y: { beginAtZero:true, ticks:{color:'#6b7591',font:{family:'DM Mono',size:11}}, grid:{color:'#272c3d'} },
            },
        },
    });
}

/* ═══════════════ API SUBMIT ═══════════════ */
document.getElementById('btnSubmitApi').addEventListener('click', async () => {
    const pgEl  = document.getElementById('val-pg-api');
    const prEl  = document.getElementById('val-pr-api');
    const pg    = pgEl?.value.trim();
    const pr    = prEl?.value.trim();
    const ket   = document.getElementById('sel_keterangan').value.trim();
    const nom   = getRangeVal('inp_nominal');
    const joy   = getRangeVal('or_joy');
    const sad   = getRangeVal('or_sad');
    const grief = getRangeVal('or_grief');

    if (!pg||!pr)   return showAlert('Pilih Pengirim dan Penerima.', false);
    if (pg===pr)    return showAlert('Pengirim dan penerima tidak boleh sama.', false);
    if (!ket)       return showAlert('Pilih Keterangan terlebih dahulu.', false);
    if (!(nom > 0)) return showAlert('Nominal harus lebih dari 0.', false);

    const btn     = document.getElementById('btnSubmitApi');
    const spinner = document.getElementById('btnSpinner');
    const label   = document.getElementById('btnLabel');
    btn.disabled = true; spinner.style.display='inline-block'; label.textContent='Mengirim...';

    try {
        // 1. POST Kwitansi
        const kwRes = await fetch(KWITANSI_API, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({ Nama_Pengirim:pg, Nama_Penerima:pr, Keterangan:ket, Nominal:nom }),
        });
        if (!kwRes.ok) throw new Error(`Kwitansi HTTP ${kwRes.status}`);
        const kwData = await kwRes.json();

        // 2. POST Originium
        const orRes = await fetch(ORIGINIUM_API, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({ Scale_Joy:joy, Scale_Sad:sad, Scale_Grief:grief }),
        });
        if (!orRes.ok) throw new Error(`Originium HTTP ${orRes.status}`);

        // 3. Update saldo if both in API
        const senderApi   = apiData.find(x=>x.Nama===pg);
        const receiverApi = apiData.find(x=>x.Nama===pr);
        if (senderApi) {
            await fetch(`${FORMALHAULT_API}/${senderApi.id}`, {
                method:'PATCH', headers:{'Content-Type':'application/json'},
                body:JSON.stringify({ Saldo: Number(senderApi.Saldo)-nom }),
            });
        }
        if (receiverApi) {
            await fetch(`${FORMALHAULT_API}/${receiverApi.id}`, {
                method:'PATCH', headers:{'Content-Type':'application/json'},
                body:JSON.stringify({ Saldo: Number(receiverApi.Saldo)+nom }),
            });
        }

        showAlert(
            `✅ Berhasil!<br>Kwitansi <b>#${kwData?.id??'?'}</b> — <b>${pg}</b> → <b>${pr}</b> · ${nom} pt<br>` +
            `Keterangan: <b>${ket}</b><br>Originium: Joy=${joy} · Sad=${sad} · Grief=${grief}<br>` +
            `<span style="font-size:.7rem;color:var(--muted)">Sumber: ${pgEl.dataset.source?.toUpperCase()??'?'} → ${prEl.dataset.source?.toUpperCase()??'?'}</span>`,
            true
        );

        // Reset
        ['inp_nominal','or_joy','or_sad','or_grief'].forEach(id => {
            const el = document.getElementById(id);
            el.value = id==='inp_nominal' ? '10' : '0';
            el.dispatchEvent(new Event('input'));
        });
        document.getElementById('sel_keterangan').value = '';
        ['disp-pg-api','disp-pr-api'].forEach(id => {
            document.getElementById(id).innerHTML = '<span class="placeholder">Belum dipilih</span>';
        });
        document.getElementById('val-pg-api').value = '';
        document.getElementById('val-pr-api').value = '';
        updatePreview();

        await Promise.all([loadFormalhault(), loadKwitansi(), loadOriginium()]);

    } catch(e) {
        showAlert('❌ Gagal: ' + e.message, false);
    } finally {
        btn.disabled=false; spinner.style.display='none'; label.textContent='Kirim Transfer';
    }
});

/* ═══════════════ SQL FORM GUARD ═══════════════ */
document.getElementById('spica-form').addEventListener('submit', function(e) {
    const d = document.getElementById('val-dari-sql').value;
    const t = document.getElementById('val-terima-sql').value;
    if (!d||!t) { e.preventDefault(); showAlert('❌ Pilih Dari dan Penerima terlebih dahulu.', false); switchTab('sql'); return; }
    if (d===t)  { e.preventDefault(); document.getElementById('err-sql').style.display='block'; }
});

/* ═══════════════ SPICA: Bobot + Scales ═══════════════ */
document.getElementById('tujuan-select').addEventListener('change', function() {
    const badge = document.getElementById('bobot-badge');
    const opt   = this.options[this.selectedIndex];
    if (this.value) { badge.textContent='Bobot: '+opt.dataset.bobot; badge.classList.remove('hidden'); }
    else badge.classList.add('hidden');
});

document.querySelectorAll('.scale').forEach(s => {
    const max=parseInt(s.dataset.max), target=s.dataset.target;
    for (let i=0; i<=max; i++) {
        const b=document.createElement('button'); b.type='button'; b.textContent=i;
        b.onclick=()=>{ s.querySelectorAll('button').forEach(x=>x.classList.remove('active')); b.classList.add('active'); document.getElementById(target).value=i; };
        s.appendChild(b);
    }
});

/* ═══════════════ INIT ═══════════════ */
document.getElementById('headerTime').textContent = new Date().toLocaleString('id-ID', {
    day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'
}) + ' WIB';

bindRange('inp_nominal','nominalVal','nominalDisplay');
bindRange('or_joy','orJoyVal','orJoyDisplay');
bindRange('or_sad','orSadVal','orSadDisplay');
bindRange('or_grief','orGriefVal','orGriefDisplay');

['sel_keterangan'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', updatePreview);
});

async function reloadAll() {
    await Promise.all([loadNames(), loadFormalhault(), loadKwitansi(), loadOriginium()]);
}
reloadAll();

<?php if ($spica_result): ?>
// Auto-show SQL tab after SQL submission
switchTab('sql');
<?php endif; ?>
</script>
</body>
</html>