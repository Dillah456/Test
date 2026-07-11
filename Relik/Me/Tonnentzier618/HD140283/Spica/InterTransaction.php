<?php
/* ================== DB ================== */
$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_e0";

$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
if (!$conn) { die("Koneksi DB gagal"); }

/* ================== BOBOT MAP ================== */
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

/* ================== NAMA LIST (SQL) ================== */
$list_nama_sql = [];
$q = mysqli_query($conn, "SELECT Nama FROM formalhault ORDER BY Nama ASC");
while ($r = mysqli_fetch_assoc($q)) {
    $list_nama_sql[] = $r['Nama'];
}

/* ================== PROSES SUBMIT SQL ================== */
$message = '';

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

    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Nama,Saldo FROM formalhault WHERE Nama='$Terima'"));
    $saldo_baru = $r['Saldo'] + $Nominal;
    mysqli_query($conn, "UPDATE formalhault SET Saldo=$saldo_baru WHERE Nama='$Terima'");
    $message .= "<div class='result-card'>
        <span class='result-label'>Akun</span><span class='result-val'>{$r['Nama']}</span>
        <span class='result-label'>Saldo</span><span class='result-val'>{$r['Saldo']}</span>
        <span class='result-label'>Terima</span><span class='result-val'>{$Nominal}</span>
        <span class='result-label'>Bobot</span><span class='result-val'>{$Bobot}</span>
        <span class='result-label'>Saldo Akhir</span><span class='result-val highlight'>{$saldo_baru}</span>
    </div>";

    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Nama,Saldo FROM formalhault WHERE Nama='$Dari'"));
    $saldo_baru = $r['Saldo'] - $Nominal;
    mysqli_query($conn, "UPDATE formalhault SET Saldo=$saldo_baru WHERE Nama='$Dari'");
    $message .= "<div class='result-card sender'>
        <span class='result-label'>Dari</span><span class='result-val'>{$r['Nama']}</span>
        <span class='result-label'>Saldo</span><span class='result-val'>{$r['Saldo']}</span>
        <span class='result-label'>Mengirim</span><span class='result-val'>{$Nominal}</span>
        <span class='result-label'>Saldo Akhir</span><span class='result-val highlight'>{$saldo_baru}</span>
    </div>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>InterTransaction</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<style>
/* ── RESET & ROOT ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg:      #0d0f14;
    --surf:    #151820;
    --surf2:   #1c2030;
    --border:  #272c3d;
    --accent:  #5b7cfa;
    --green:   #22d3a0;
    --red:     #f43f5e;
    --yellow:  #eab308;
    --blue:    #60a5fa;
    --purple:  #a78bfa;
    --text:    #e8eaf2;
    --muted:   #6b7591;
    --r:       14px;

    /* Spica colours */
    --accent2: #f7c97e;
    --success: #7ef7b8;
    --danger:  #f77e7e;
}

body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Syne', sans-serif;
    min-height: 100vh;
}

/* ── LAYOUT ── */
.wrap { max-width: 1280px; margin: auto; padding: 30px 20px 60px; }

.topbar {
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
    font-size: .75rem;
    cursor: pointer;
    transition: border-color .2s, color .2s;
}
.btn-nav:hover { border-color: var(--accent); color: var(--accent); }

.header { margin-bottom: 28px; }
.header .tag {
    color: var(--green);
    font-family: 'DM Mono', monospace;
    font-size: 11px;
    letter-spacing: 2px;
    margin-bottom: 8px;
}
.header h1 { font-size: 34px; font-weight: 800; }
.header h1 span { color: var(--accent); }

/* ── TABS ── */
.tab-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--border);
    padding-bottom: 0;
}
.tab-btn {
    padding: 10px 22px;
    border: none;
    background: none;
    color: var(--muted);
    font-family: 'DM Mono', monospace;
    font-size: .78rem;
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

/* ── CARDS ── */
.card {
    background: var(--surf);
    border: 1px solid var(--border);
    border-radius: var(--r);
    padding: 24px;
    margin-bottom: 20px;
}
.card h2 { margin-bottom: 18px; font-size: 15px; }

.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media(max-width:900px){ .grid2 { grid-template-columns: 1fr; } }

/* ── FORM FIELDS ── */
.form-group { margin-bottom: 16px; }
.field-label {
    display: block;
    margin-bottom: 8px;
    font-size: 11px;
    letter-spacing: 1px;
    color: var(--muted);
    text-transform: uppercase;
    font-family: 'DM Mono', monospace;
}

select, input[type=range] { width: 100%; }
select {
    background: var(--surf2);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    padding: 12px;
    font-family: 'DM Mono', monospace;
    font-size: .85rem;
    outline: none;
    appearance: none;
    transition: border-color .2s;
}
select:focus { border-color: var(--accent); }

/* Source picker (popup trigger) */
.source-picker {
    display: flex;
    gap: 8px;
    align-items: center;
}
.source-picker select { flex: 1; }
.btn-popup {
    padding: 10px 14px;
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
.btn-popup:hover { border-color: var(--accent); color: var(--accent); }

/* Source badge */
.source-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .68rem;
    font-family: 'DM Mono', monospace;
    letter-spacing: .08em;
    margin-bottom: 8px;
}
.source-badge.sql { background: rgba(91,124,250,.12); color: var(--accent); border: 1px solid rgba(91,124,250,.25); }
.source-badge.api { background: rgba(34,211,160,.12); color: var(--green); border: 1px solid rgba(34,211,160,.25); }

/* Hidden input display */
.chosen-display {
    padding: 10px 14px;
    background: var(--surf2);
    border: 1px solid var(--border);
    border-radius: 8px;
    font-family: 'DM Mono', monospace;
    font-size: .85rem;
    color: var(--text);
    min-height: 42px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}
.chosen-display .placeholder { color: var(--muted); }

/* Range */
.range-val { margin-top: 6px; font-family: 'DM Mono', monospace; color: var(--green); }

/* Nominal radio (Spica style) */
.monetasi-group { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
.monetasi-group input[type="radio"] { display: none; }
.monetasi-group label {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 9px 4px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--surf2);
    color: var(--muted);
    font-size: .75rem;
    font-family: 'DM Mono', monospace;
    cursor: pointer;
    transition: background .15s, color .15s, border-color .15s;
}
.monetasi-group input[type="radio"]:checked + label {
    background: var(--accent2);
    color: #0d0f14;
    border-color: var(--accent2);
    font-weight: 500;
}
.monetasi-group label:hover { border-color: var(--accent2); color: var(--accent2); }

/* Bobot badge */
.bobot-badge {
    display: inline-block;
    margin-top: 8px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .68rem;
    letter-spacing: .1em;
    background: rgba(91,124,250,.1);
    color: var(--accent);
    border: 1px solid rgba(91,124,250,.25);
    transition: opacity .2s;
    font-family: 'DM Mono', monospace;
}
.bobot-badge.hidden { opacity: 0; pointer-events: none; }

/* Scale buttons (Spica style) */
.scale { display: flex; flex-wrap: wrap; gap: 7px; }
.scale button {
    width: 38px;
    height: 38px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surf2);
    color: var(--muted);
    font-family: 'DM Mono', monospace;
    font-size: .82rem;
    cursor: pointer;
    transition: background .15s, color .15s, border-color .15s;
}
.scale button.active {
    background: var(--accent);
    color: #0d0f14;
    border-color: var(--accent);
    font-weight: 500;
}
.scale button:hover:not(.active) { border-color: var(--accent); color: var(--accent); }

/* Preview */
.preview {
    margin-top: 20px;
    background: var(--surf2);
    border: 1px dashed var(--border);
    border-radius: 10px;
    padding: 14px;
    line-height: 2;
    font-size: 13px;
    font-family: 'DM Mono', monospace;
}

/* Submit buttons */
.btn {
    width: 100%;
    margin-top: 18px;
    border: none;
    border-radius: 10px;
    padding: 14px;
    background: var(--accent);
    color: white;
    font-size: 15px;
    font-weight: 700;
    font-family: 'Syne', sans-serif;
    cursor: pointer;
    transition: opacity .2s, transform .1s;
}
.btn:hover:not(:disabled) { opacity: .85; transform: translateY(-1px); }
.btn:disabled { opacity: .38; cursor: not-allowed; }
.btn.sql-btn { background: #5b7cfa; }
.btn.api-btn { background: var(--green); color: #0d0f14; }

/* Alert */
.alert {
    margin-bottom: 20px;
    padding: 14px 18px;
    border-radius: 12px;
    display: none;
    font-family: 'DM Mono', monospace;
    font-size: .82rem;
    line-height: 1.7;
}
.alert.ok { background: rgba(34,211,160,.1); border: 1px solid rgba(34,211,160,.3); color: var(--green); }
.alert.err { background: rgba(244,63,94,.1); border: 1px solid rgba(244,63,94,.3); color: var(--red); }

/* Divider */
hr.divider { border: none; border-top: 1px solid var(--border); margin: 22px 0; }

/* Saldo table */
.table-wrap { overflow: auto; }
table { width: 100%; border-collapse: collapse; }
th {
    text-align: left;
    padding: 10px;
    color: var(--muted);
    font-size: 11px;
    font-family: 'DM Mono', monospace;
    letter-spacing: 1px;
}
td { padding: 12px 10px; border-top: 1px solid var(--border); font-size: 13px; }
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(91,124,250,.15);
    color: var(--accent);
    font-family: 'DM Mono', monospace;
    font-size: 11px;
}
.badge.green { background: rgba(34,211,160,.15); color: var(--green); }

/* Result cards (SQL) */
.results { margin-top: 24px; }
.result-card {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 16px;
    background: var(--surf2);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 12px;
    font-size: .8rem;
    font-family: 'DM Mono', monospace;
}
.result-card.sender { border-color: rgba(247,201,126,.3); }
.result-label { color: var(--muted); }
.result-val { color: var(--text); text-align: right; }
.result-val.highlight { color: var(--success); font-weight: 500; }

/* ── POPUP OVERLAY ── */
.popup-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.65);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.popup-overlay.open { display: flex; }

.popup-box {
    background: var(--surf);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 28px 30px;
    width: 100%;
    max-width: 480px;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 16px 64px rgba(0,0,0,.6);
}

.popup-box h3 {
    font-family: 'DM Serif Display', serif;
    font-size: 1.3rem;
    color: var(--accent);
    margin-bottom: 6px;
}
.popup-subtitle {
    font-size: .7rem;
    font-family: 'DM Mono', monospace;
    color: var(--muted);
    letter-spacing: .12em;
    text-transform: uppercase;
    margin-bottom: 20px;
}

.popup-close {
    position: absolute;
    top: 16px; right: 18px;
    background: none;
    border: none;
    color: var(--muted);
    font-size: 1.2rem;
    cursor: pointer;
    transition: color .2s;
}
.popup-close:hover { color: var(--text); }

/* Source toggle inside popup */
.source-toggle {
    display: flex;
    gap: 8px;
    margin-bottom: 18px;
}
.src-btn {
    flex: 1;
    padding: 9px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--surf2);
    color: var(--muted);
    font-family: 'DM Mono', monospace;
    font-size: .75rem;
    cursor: pointer;
    transition: all .2s;
}
.src-btn.active-sql { background: rgba(91,124,250,.15); color: var(--accent); border-color: var(--accent); }
.src-btn.active-api { background: rgba(34,211,160,.15); color: var(--green); border-color: var(--green); }

/* Search in popup */
.popup-search {
    width: 100%;
    background: var(--surf2);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: 'DM Mono', monospace;
    font-size: .85rem;
    padding: 10px 14px;
    margin-bottom: 12px;
    outline: none;
    transition: border-color .2s;
}
.popup-search:focus { border-color: var(--accent); }

/* Name list in popup */
.popup-list { display: flex; flex-direction: column; gap: 6px; }
.popup-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--surf2);
    cursor: pointer;
    transition: border-color .2s, background .2s;
}
.popup-item:hover { border-color: var(--accent); background: rgba(91,124,250,.06); }
.popup-item.selected { border-color: var(--green); background: rgba(34,211,160,.06); }
.popup-item-name { font-size: .88rem; font-weight: 600; }
.popup-item-meta {
    font-family: 'DM Mono', monospace;
    font-size: .72rem;
    color: var(--muted);
}
.popup-item-saldo {
    font-family: 'DM Mono', monospace;
    font-size: .78rem;
    color: var(--green);
}
.popup-loading {
    text-align: center;
    color: var(--muted);
    font-family: 'DM Mono', monospace;
    font-size: .8rem;
    padding: 20px;
}

/* Mode toggle */
body.day {
    --bg: #f0f2f8; --surf: #ffffff; --surf2: #f5f6fb;
    --border: #d0d5e8; --text: #111827; --muted: #6b7280;
}

@media(max-width:480px){
    .card { padding: 18px 14px; }
    .popup-box { padding: 22px 16px; }
    .monetasi-group { grid-template-columns: repeat(5,1fr); }
}
</style>
</head>
<body>
<div class="wrap">

  <div class="topbar">
    <button class="btn-nav" onclick="location.href='menu.php'">⬅ Menu</button>
    <button class="btn-nav" onclick="toggleMode()">🌗 Mode</button>
  </div>

  <div class="header">
    <div class="tag">■ INTERTRANSACTION SYSTEM</div>
    <h1>Inter <span>Transfer</span></h1>
  </div>

  <div class="alert" id="alertBox"></div>

  <!-- ══════════ TABS ══════════ -->
  <div class="tab-bar">
    <button class="tab-btn active" onclick="switchTab('api')">🌐 API Transfer</button>
    <button class="tab-btn" onclick="switchTab('sql')">🗄️ SQL Transfer (Spica)</button>
  </div>

  <!-- ══════════ TAB: API ══════════ -->
  <div class="tab-panel active" id="tab-api">
    <div class="grid2">

      <!-- FORM API -->
      <div class="card">
        <h2>🔁 Transfer Point — API</h2>

        <!-- Pengirim -->
        <div class="form-group">
          <label class="field-label">Pengirim</label>
          <div class="source-badge sql" id="badge-pengirim-api">⬤ SQL</div>
          <div class="source-picker">
            <div class="chosen-display" id="display-pengirim-api">
              <span class="placeholder">Belum dipilih</span>
            </div>
            <button class="btn-popup" onclick="openPopup('pengirim-api')">🔍 Pilih</button>
          </div>
          <input type="hidden" id="val-pengirim-api" data-source="sql">
        </div>

        <!-- Penerima -->
        <div class="form-group">
          <label class="field-label">Penerima</label>
          <div class="source-badge sql" id="badge-penerima-api">⬤ SQL</div>
          <div class="source-picker">
            <div class="chosen-display" id="display-penerima-api">
              <span class="placeholder">Belum dipilih</span>
            </div>
            <button class="btn-popup" onclick="openPopup('penerima-api')">🔍 Pilih</button>
          </div>
          <input type="hidden" id="val-penerima-api" data-source="sql">
          <div id="err-api" style="color:var(--danger);font-size:.72rem;font-family:'DM Mono',monospace;margin-top:5px;display:none;">Pengirim dan penerima tidak boleh sama!</div>
        </div>

        <!-- Keterangan -->
        <div class="form-group">
          <label class="field-label">Keterangan</label>
          <select id="sel_keterangan">
            <option value="">Pilih</option>
            <option value="Kelelahan">Kelelahan</option>
            <option value="Afeksi">Afeksi</option>
            <option value="Self-Actualization">Self-Actualization</option>
            <option value="Represion">Represion</option>
            <option value="Distract / Brainfog">Distract / Brainfog</option>
          </select>
        </div>

        <!-- Nominal range -->
        <div class="form-group">
          <label class="field-label">Nominal — <span id="nominalDisplay">10</span> pt</label>
          <input type="range" id="inp_nominal" min="10" max="100" step="10" value="10">
          <div class="range-val"><span id="nominalVal">10</span> pt</div>
        </div>

        <hr class="divider">

        <!-- Joy / Sad / Grief -->
        <div class="form-group">
          <label class="field-label">😊 Joy — <span style="color:var(--yellow)" id="joyDisplay">0</span></label>
          <input type="range" id="or_joy" min="0" max="10" value="0">
          <div class="range-val" style="color:var(--yellow)"><span id="joyVal">0</span></div>
        </div>
        <div class="form-group">
          <label class="field-label">😔 Sad — <span style="color:var(--blue)" id="sadDisplay">0</span></label>
          <input type="range" id="or_sad" min="0" max="10" value="0">
          <div class="range-val" style="color:var(--blue)"><span id="sadVal">0</span></div>
        </div>
        <div class="form-group">
          <label class="field-label">🖤 Grief — <span style="color:var(--purple)" id="griefDisplay">0</span></label>
          <input type="range" id="or_grief" min="0" max="10" value="0">
          <div class="range-val" style="color:var(--purple)"><span id="griefVal">0</span></div>
        </div>

        <div class="preview" id="previewBox">Menunggu input...</div>
        <button class="btn api-btn" id="btnSubmitApi">Kirim Transfer</button>
      </div>

      <!-- SALDO API -->
      <div class="card">
        <h2>💰 Saldo Formalhault</h2>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Nama</th><th>Sumber</th><th>Saldo</th></tr></thead>
            <tbody id="saldoBody"><tr><td colspan="3">Memuat...</td></tr></tbody>
          </table>
        </div>
      </div>

    </div><!-- /grid2 -->
  </div><!-- /tab-api -->

  <!-- ══════════ TAB: SQL (Spica) ══════════ -->
  <div class="tab-panel" id="tab-sql">
    <div class="grid2">

      <!-- FORM SQL -->
      <div class="card">
        <h2>⚡ Spica — SQL Entry</h2>

        <form method="POST" id="spica-form">

          <!-- Dari -->
          <div class="form-group">
            <label class="field-label">Dari</label>
            <div class="source-badge sql">⬤ SQL</div>
            <div class="source-picker">
              <div class="chosen-display" id="display-dari-sql">
                <span class="placeholder">Belum dipilih</span>
              </div>
              <button type="button" class="btn-popup" onclick="openPopup('dari-sql')">🔍 Pilih</button>
            </div>
            <input type="hidden" name="Spica-F" id="val-dari-sql">
          </div>

          <!-- Penerima SQL -->
          <div class="form-group">
            <label class="field-label">Penerima</label>
            <div class="source-badge sql">⬤ SQL</div>
            <div class="source-picker">
              <div class="chosen-display" id="display-terima-sql">
                <span class="placeholder">Belum dipilih</span>
              </div>
              <button type="button" class="btn-popup" onclick="openPopup('terima-sql')">🔍 Pilih</button>
            </div>
            <input type="hidden" name="Spica-R" id="val-terima-sql">
            <div id="err-sql" style="color:var(--danger);font-size:.72rem;font-family:'DM Mono',monospace;margin-top:5px;display:none;">Dari dan Terima tidak boleh sama!</div>
          </div>

          <!-- Monetasi -->
          <div class="form-group">
            <label class="field-label">Monetasi</label>
            <div class="monetasi-group">
              <?php for ($v = 10; $v <= 100; $v += 10): ?>
                <input type="radio" name="Spica-M" id="mon-<?= $v ?>" value="<?= $v ?>" required>
                <label for="mon-<?= $v ?>"><?= $v ?></label>
              <?php endfor; ?>
            </div>
          </div>

          <!-- Tujuan -->
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

          <!-- Emotional scales -->
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

          <button type="submit" name="submit_sql" class="btn sql-btn" id="submit-sql-btn">Kirim (SQL)</button>
        </form>

        <?php if ($message): ?>
          <div class="results"><?= $message ?></div>
        <?php endif; ?>
      </div>

      <!-- Saldo SQL -->
      <div class="card">
        <h2>🗄️ Saldo SQL — formalhault</h2>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Nama</th><th>Saldo (SQL)</th></tr></thead>
            <tbody id="saldoSQLBody">
              <?php
                $q2 = mysqli_query($conn, "SELECT Nama, Saldo FROM formalhault ORDER BY Nama ASC");
                while ($row = mysqli_fetch_assoc($q2)):
              ?>
              <tr>
                <td><?= htmlspecialchars($row['Nama']) ?></td>
                <td><span class="badge"><?= $row['Saldo'] ?> pt</span></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /grid2 -->
  </div><!-- /tab-sql -->

</div><!-- /wrap -->


<!-- ══════════════════════════════
     POPUP PILIH PENGIRIM / PENERIMA
══════════════════════════════ -->
<div class="popup-overlay" id="popup-overlay">
  <div class="popup-box">
    <button class="popup-close" onclick="closePopup()">✕</button>
    <h3 id="popup-title">Pilih Nama</h3>
    <p class="popup-subtitle" id="popup-role-label">Pengirim / Penerima</p>

    <!-- Source toggle -->
    <div class="source-toggle">
      <button class="src-btn active-sql" id="srcBtn-sql" onclick="switchSource('sql')">🗄️ SQL (Database)</button>
      <button class="src-btn" id="srcBtn-api" onclick="switchSource('api')">🌐 API (Xano)</button>
    </div>

    <!-- Search -->
    <input
      type="text"
      class="popup-search"
      id="popupSearch"
      placeholder="Cari nama..."
      oninput="filterPopupList()"
    >

    <!-- List -->
    <div class="popup-list" id="popupList">
      <div class="popup-loading">Memuat data...</div>
    </div>
  </div>
</div>


<script>
/* ═══════════════════════════════════════
   CONSTANTS
═══════════════════════════════════════ */
const FORMALHAULT_API = 'https://x8ki-letl-twmt.n7.xano.io/api:X6h8irt0/formalhault';
const KWITANSI_API    = 'https://x8ki-letl-twmt.n7.xano.io/api:ZBg3zfDx/kwitansi';
const ORIGINIUM_API   = 'https://x8ki-letl-twmt.n7.xano.io/api:X6h8irt0/originium';

/* Data stores */
let apiData  = [];   // from Xano API
let sqlNames = <?= json_encode($list_nama_sql) ?>;  // from PHP/SQL
let sqlData  = sqlNames.map(n => ({ Nama: n, Saldo: '?', source: 'sql' }));

/* Popup state */
let currentTarget = null;   // e.g. 'pengirim-api', 'penerima-api', 'dari-sql', 'terima-sql'
let currentSource = 'sql';  // 'sql' | 'api'
let popupItems   = [];      // current list being shown

/* ═══════════════════════════════════════
   TABS
═══════════════════════════════════════ */
function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach((b,i) => {
    b.classList.toggle('active', (i === 0 && tab==='api') || (i === 1 && tab==='sql'));
  });
  document.getElementById('tab-api').classList.toggle('active', tab==='api');
  document.getElementById('tab-sql').classList.toggle('active', tab==='sql');
}

/* ═══════════════════════════════════════
   LOAD API DATA
═══════════════════════════════════════ */
async function loadApiData() {
  try {
    const res  = await fetch(FORMALHAULT_API);
    apiData    = await res.json();
    apiData.sort((a,b)=>a.Nama.localeCompare(b.Nama));
    renderSaldoTable();
  } catch(e) {
    showAlert('⚠️ Gagal memuat data API: ' + e.message, false);
  }
}

/* ═══════════════════════════════════════
   SALDO TABLE (API tab)
═══════════════════════════════════════ */
function renderSaldoTable() {
  const tbody = document.getElementById('saldoBody');

  // Combine: SQL names first, then API-only entries
  const sqlSet = new Set(sqlNames);
  const apiSet = new Set(apiData.map(x=>x.Nama));

  let rows = '';

  // SQL entries
  sqlNames.forEach(nama => {
    const apiEntry = apiData.find(x=>x.Nama===nama);
    const saldo    = apiEntry ? apiEntry.Saldo : '–';
    rows += `<tr>
      <td>${nama}</td>
      <td><span class="badge">SQL</span></td>
      <td><span class="badge green">${saldo} pt</span></td>
    </tr>`;
  });

  // API-only
  apiData.filter(x=>!sqlSet.has(x.Nama)).forEach(x => {
    rows += `<tr>
      <td>${x.Nama}</td>
      <td><span class="badge" style="background:rgba(34,211,160,.15);color:var(--green)">API</span></td>
      <td><span class="badge green">${x.Saldo} pt</span></td>
    </tr>`;
  });

  tbody.innerHTML = rows || '<tr><td colspan="3">Tidak ada data</td></tr>';
}

/* ═══════════════════════════════════════
   POPUP
═══════════════════════════════════════ */
function openPopup(target) {
  currentTarget = target;

  // Set title
  const labels = {
    'pengirim-api': 'Pilih Pengirim',
    'penerima-api': 'Pilih Penerima',
    'dari-sql':     'Pilih Dari (Spica)',
    'terima-sql':   'Pilih Penerima (Spica)',
  };
  document.getElementById('popup-title').textContent = labels[target] || 'Pilih Nama';
  document.getElementById('popup-role-label').textContent =
    target.includes('dari') || target.includes('pengirim') ? 'Pengirim' : 'Penerima';

  // Default source
  const defaultSrc = target.endsWith('-sql') ? 'sql' : 'sql';
  switchSource(defaultSrc, false);

  document.getElementById('popupSearch').value = '';
  document.getElementById('popup-overlay').classList.add('open');
  document.getElementById('popupSearch').focus();
}

function closePopup() {
  document.getElementById('popup-overlay').classList.remove('open');
  currentTarget = null;
}

// Close on overlay click
document.getElementById('popup-overlay').addEventListener('click', function(e){
  if (e.target === this) closePopup();
});

function switchSource(src, render=true) {
  currentSource = src;
  document.getElementById('srcBtn-sql').className = 'src-btn' + (src==='sql' ? ' active-sql' : '');
  document.getElementById('srcBtn-api').className = 'src-btn' + (src==='api' ? ' active-api' : '');
  if (render) renderPopupList();
}

function renderPopupList(filter='') {
  const list = document.getElementById('popupList');
  const f    = filter.toLowerCase();

  let items;
  if (currentSource === 'sql') {
    items = sqlNames
      .filter(n => n.toLowerCase().includes(f))
      .map(n => {
        const apiEntry = apiData.find(x=>x.Nama===n);
        return { name: n, saldo: apiEntry ? apiEntry.Saldo : '?', source: 'sql' };
      });
  } else {
    items = apiData
      .filter(x => x.Nama.toLowerCase().includes(f))
      .map(x => ({ name: x.Nama, saldo: x.Saldo, source: 'api', id: x.id }));
  }

  popupItems = items;

  if (!items.length) {
    list.innerHTML = '<div class="popup-loading">Tidak ada hasil</div>';
    return;
  }

  list.innerHTML = items.map((item, idx) => `
    <div class="popup-item" id="pitem-${idx}" onclick="selectPopupItem(${idx})">
      <div>
        <div class="popup-item-name">${item.name}</div>
        <div class="popup-item-meta">${item.source.toUpperCase()}</div>
      </div>
      <div class="popup-item-saldo">${item.saldo !== '?' ? item.saldo + ' pt' : '–'}</div>
    </div>
  `).join('');
}

function filterPopupList() {
  renderPopupList(document.getElementById('popupSearch').value);
}

function selectPopupItem(idx) {
  const item = popupItems[idx];
  if (!item || !currentTarget) return;

  // Update display
  const display = document.getElementById('display-' + currentTarget);
  const hidden  = document.getElementById('val-' + currentTarget);
  const badge   = document.getElementById('badge-' + currentTarget);

  if (display) {
    display.innerHTML = `
      <span style="font-weight:600">${item.name}</span>
      ${item.saldo !== '?' ? `<span style="color:var(--green);font-family:'DM Mono',monospace;font-size:.75rem">${item.saldo} pt</span>` : ''}
    `;
  }
  if (hidden) {
    hidden.value = item.name;
    hidden.dataset.source = item.source;
    if (item.id) hidden.dataset.id = item.id;
  }
  if (badge) {
    badge.className   = 'source-badge ' + item.source;
    badge.textContent = item.source === 'sql' ? '⬤ SQL' : '⬤ API';
  }

  validatePair();
  updatePreview();
  closePopup();
}

/* ═══════════════════════════════════════
   VALIDATION
═══════════════════════════════════════ */
function validatePair() {
  // API tab
  const pVal = document.getElementById('val-pengirim-api')?.value;
  const rVal = document.getElementById('val-penerima-api')?.value;
  const errApi = document.getElementById('err-api');
  const btnApi = document.getElementById('btnSubmitApi');
  const sameApi = pVal && rVal && pVal === rVal;
  if (errApi) errApi.style.display = sameApi ? 'block' : 'none';
  if (btnApi) btnApi.disabled = sameApi;

  // SQL tab
  const dVal = document.getElementById('val-dari-sql')?.value;
  const tVal = document.getElementById('val-terima-sql')?.value;
  const errSql = document.getElementById('err-sql');
  const btnSql = document.getElementById('submit-sql-btn');
  const sameSql = dVal && tVal && dVal === tVal;
  if (errSql) errSql.style.display = sameSql ? 'block' : 'none';
  if (btnSql) btnSql.disabled = sameSql;
}

/* ═══════════════════════════════════════
   PREVIEW
═══════════════════════════════════════ */
function updatePreview() {
  const p       = document.getElementById('val-pengirim-api')?.value || '–';
  const r       = document.getElementById('val-penerima-api')?.value || '–';
  const ket     = document.getElementById('sel_keterangan')?.value   || '–';
  const nominal = document.getElementById('inp_nominal')?.value      || '0';
  const joy     = document.getElementById('or_joy')?.value           || '0';
  const sad     = document.getElementById('or_sad')?.value           || '0';
  const grief   = document.getElementById('or_grief')?.value         || '0';

  const box = document.getElementById('previewBox');
  if (box) box.innerHTML =
    `Pengirim   : ${p}<br>` +
    `Penerima   : ${r}<br>` +
    `Keterangan : ${ket}<br>` +
    `Nominal    : ${nominal} pt<br><br>` +
    `Joy=${joy}  Sad=${sad}  Grief=${grief}`;
}

/* ═══════════════════════════════════════
   RANGE BINDINGS
═══════════════════════════════════════ */
function bindRange(id, valId, displayId) {
  const inp  = document.getElementById(id);
  const val  = document.getElementById(valId);
  const disp = document.getElementById(displayId);
  const sync = () => { val.textContent = inp.value; disp.textContent = inp.value; updatePreview(); };
  inp.addEventListener('input', sync);
  sync();
}
[
  ['inp_nominal','nominalVal','nominalDisplay'],
  ['or_joy','joyVal','joyDisplay'],
  ['or_sad','sadVal','sadDisplay'],
  ['or_grief','griefVal','griefDisplay'],
].forEach(r => bindRange(...r));

['sel_pengirim','sel_penerima','sel_keterangan'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('change', updatePreview);
});

/* ═══════════════════════════════════════
   ALERT
═══════════════════════════════════════ */
function showAlert(msg, ok=true) {
  const box = document.getElementById('alertBox');
  box.innerHTML = msg;
  box.className = 'alert ' + (ok ? 'ok' : 'err');
  box.style.display = 'block';
  window.scrollTo({ top: 0, behavior: 'smooth' });
  setTimeout(() => { box.style.display = 'none'; }, 7000);
}

/* ═══════════════════════════════════════
   API SUBMIT
═══════════════════════════════════════ */
document.getElementById('btnSubmitApi').addEventListener('click', async () => {
  const pengirimEl = document.getElementById('val-pengirim-api');
  const penerimaEl = document.getElementById('val-penerima-api');
  const pengirim   = pengirimEl?.value;
  const penerima   = penerimaEl?.value;
  const keterangan = document.getElementById('sel_keterangan').value;
  const nominal    = Number(document.getElementById('inp_nominal').value);
  const joy        = Number(document.getElementById('or_joy').value);
  const sad        = Number(document.getElementById('or_sad').value);
  const grief      = Number(document.getElementById('or_grief').value);

  try {
    if (!pengirim || !penerima) throw new Error('Pilih pengirim dan penerima.');
    if (pengirim === penerima)  throw new Error('Pengirim dan penerima tidak boleh sama.');

    const srcPengirim = pengirimEl.dataset.source;
    const srcPenerima = penerimaEl.dataset.source;

    // Find sender & receiver data (from API store for saldo check)
    const senderApi   = apiData.find(x=>x.Nama===pengirim);
    const receiverApi = apiData.find(x=>x.Nama===penerima);

    // Saldo check only if both are in API
    if (senderApi && Number(senderApi.Saldo) < nominal) {
      throw new Error(`Saldo ${pengirim} tidak cukup (${senderApi.Saldo} pt).`);
    }

    // 1. POST Kwitansi
    const kwRes = await fetch(KWITANSI_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ Nama_Pengirim: pengirim, Nama_Penerima: penerima, Keterangan: keterangan, Nominal: nominal })
    });
    if (!kwRes.ok) throw new Error('POST kwitansi gagal.');

    // 2. POST Originium
    const orRes = await fetch(ORIGINIUM_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ Scale_Joy: joy, Scale_Sad: sad, Scale_Grief: grief })
    });
    if (!orRes.ok) throw new Error('POST originium gagal.');

    // 3. Update saldo via API (only if entry exists in API)
    if (senderApi) {
      const newSaldo = Number(senderApi.Saldo) - nominal;
      await fetch(`${FORMALHAULT_API}/${senderApi.id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ Saldo: newSaldo })
      });
    }
    if (receiverApi) {
      const newSaldo = Number(receiverApi.Saldo) + nominal;
      await fetch(`${FORMALHAULT_API}/${receiverApi.id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ Saldo: newSaldo })
      });
    }

    showAlert(
      `✅ Transfer berhasil!<br><br>` +
      `${pengirim} → ${penerima}<br>` +
      `${nominal} pt | ${keterangan}<br>` +
      `Joy=${joy} Sad=${sad} Grief=${grief}<br><br>` +
      `<span style="font-size:.72rem;color:var(--muted)">Sumber: ${srcPengirim.toUpperCase()} → ${srcPenerima.toUpperCase()}</span>`,
      true
    );

    await loadApiData();

  } catch(err) {
    showAlert('❌ ' + err.message, false);
  }
});

/* ═══════════════════════════════════════
   SQL FORM: validate before submit
═══════════════════════════════════════ */
document.getElementById('spica-form').addEventListener('submit', function(e) {
  const d = document.getElementById('val-dari-sql').value;
  const t = document.getElementById('val-terima-sql').value;
  if (!d || !t) {
    e.preventDefault();
    showAlert('❌ Pilih Dari dan Penerima terlebih dahulu.', false);
    switchTab('sql');
    return;
  }
  if (d === t) {
    e.preventDefault();
    document.getElementById('err-sql').style.display = 'block';
  }
});

/* ═══════════════════════════════════════
   SPICA: Bobot badge
═══════════════════════════════════════ */
document.getElementById('tujuan-select').addEventListener('change', function() {
  const badge = document.getElementById('bobot-badge');
  const opt   = this.options[this.selectedIndex];
  if (this.value) {
    badge.textContent = 'Bobot: ' + opt.dataset.bobot;
    badge.classList.remove('hidden');
  } else {
    badge.classList.add('hidden');
  }
});

/* ═══════════════════════════════════════
   SPICA: Scale buttons
═══════════════════════════════════════ */
document.querySelectorAll('.scale').forEach(s => {
  const max    = parseInt(s.dataset.max);
  const target = s.dataset.target;
  for (let i = 0; i <= max; i++) {
    const b = document.createElement('button');
    b.type = 'button';
    b.textContent = i;
    b.onclick = () => {
      s.querySelectorAll('button').forEach(x => x.classList.remove('active'));
      b.classList.add('active');
      document.getElementById(target).value = i;
    };
    s.appendChild(b);
  }
});

/* ═══════════════════════════════════════
   DAY / NIGHT MODE
═══════════════════════════════════════ */
function toggleMode() {
  document.body.classList.toggle('day');
  localStorage.setItem('mode', document.body.classList.contains('day') ? 'day' : 'night');
}
if (localStorage.getItem('mode') === 'day') document.body.classList.add('day');

/* ═══════════════════════════════════════
   INIT
═══════════════════════════════════════ */
loadApiData();

// Pre-render SQL popup with SQL names (no saldo)
// Popup will fetch saldo from apiData when available

// Open popup triggers renderPopupList after source switch
// so we hook loadApiData done to auto-re-render if popup is open
const _origLoad = loadApiData;
</script>
</body>
</html>