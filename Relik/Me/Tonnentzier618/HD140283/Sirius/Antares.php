<?php
// ─────────────────────────────────────────────────────────────────────────────
//  Antares.php — Tambah Stok ke JSON Inventory
//  Saldo dari  : MySQL oortmyid_e0 → tabel formalhault (Nama, Saldo)
//  Stok ke     : /data/session-market.json  (sama seperti Pollux.php)
//  Log setor   : INSERT INTO spica (Dari, Terima, Monetasi, Tujuan, Prioritas=5)
//  Path saran  : /HD140283/Sirius/Antares.php
// ─────────────────────────────────────────────────────────────────────────────

/* ── DB ────────────────────────────────────────────────────────────────────── */
$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_e0";

$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
if (!$conn) { die("Koneksi DB gagal"); }

/* ── JSON inventory ─────────────────────────────────────────────────────────── */
$JSON_FILE = __DIR__ . '/data/session-market.json';

function json_read_market(string $file): array {
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    $arr = json_decode($raw, true);
    if (!is_array($arr)) return [];
    return array_values(array_filter($arr, fn($r) =>
        !($r['Id'] == 0 && ($r['Item_Name'] ?? '') === '-')
    ));
}

function json_write_market(string $file, array $data): bool {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return file_put_contents(
        $file,
        json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$inventory = json_read_market($JSON_FILE);

/* ── Ambil daftar nama & saldo dari formalhault ─────────────────────────────── */
$users = [];
$q = mysqli_query($conn, "SELECT Nama, Saldo FROM formalhault ORDER BY Nama ASC");
while ($r = mysqli_fetch_assoc($q)) {
    $users[$r['Nama']] = (int)$r['Saldo'];
}

/* ── AJAX: assimilate (tambah stok) ────────────────────────────────────────── */
if (isset($_POST['ajax_assimilate'])) {
    header('Content-Type: application/json');

    $penyetor  = trim($_POST['penyetor'] ?? '');
    $item_id   = (int)($_POST['item_id'] ?? 0);
    $qty_add   = max(1, (int)($_POST['qty_add'] ?? 1));
    $biaya     = max(0, (int)($_POST['biaya'] ?? 0)); // biaya per unit (opsional)

    // Reload saldo langsung dari DB
    $safe_penyetor = mysqli_real_escape_string($conn, $penyetor);
    $rr = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Saldo FROM formalhault WHERE Nama='$safe_penyetor'"));
    if (!$rr) {
        echo json_encode(['ok' => false, 'msg' => 'Nama penyetor tidak valid.']);
        exit;
    }
    $saldo_sekarang = (int)$rr['Saldo'];

    // Cari item di JSON
    $inventory = json_read_market($JSON_FILE);
    $itemIdx   = null;
    foreach ($inventory as $i => $it) {
        if ((int)$it['Id'] === $item_id) { $itemIdx = $i; break; }
    }
    if ($itemIdx === null) {
        echo json_encode(['ok' => false, 'msg' => 'Item tidak ditemukan.']);
        exit;
    }

    $item        = $inventory[$itemIdx];
    $total_biaya = $biaya * $qty_add;

    // Validasi saldo jika ada biaya
    if ($total_biaya > 0 && $saldo_sekarang < $total_biaya) {
        echo json_encode([
            'ok'  => false,
            'msg' => "Saldo tidak cukup untuk menanggung biaya pengadaan. Saldo: {$saldo_sekarang} pt, Butuh: {$total_biaya} pt"
        ]);
        exit;
    }

    $qty_lama  = (int)$item['Quantity'];
    $qty_baru  = $qty_lama + $qty_add;
    $keterangan = 'Pengadaan stok: ' . $item['Item_Name'] . ' +' . $qty_add
                . ($biaya > 0 ? ' (biaya ' . $total_biaya . ' pt)' : '');
    $safe_ket  = mysqli_real_escape_string($conn, $keterangan);

    // 1. Kurangi saldo penyetor jika ada biaya
    $saldo_baru = $saldo_sekarang - $total_biaya;
    if ($total_biaya > 0) {
        mysqli_query($conn,
            "UPDATE formalhault SET Saldo=$saldo_baru WHERE Nama='$safe_penyetor'");
    }

    // 2. Log ke spica (Prioritas=5 → pengadaan)
    $monetasi = $total_biaya;
    mysqli_query($conn, "
        INSERT INTO spica (Dari, Terima, Monetasi, Tujuan, Senang, Sedih, Grief, Prioritas)
        VALUES ('$safe_penyetor', 'Assimilate', $monetasi, '$safe_ket', 0, 0, 0, 5)
    ");
    $spica_id = mysqli_insert_id($conn);

    // 3. Tambah stok di JSON
    $inventory[$itemIdx]['Quantity'] = $qty_baru;
    json_write_market($JSON_FILE, $inventory);

    echo json_encode([
        'ok'         => true,
        'msg'        => "Berhasil! Stok {$item['Item_Name']} +{$qty_add} → total {$qty_baru}.",
        'spica_id'   => $spica_id,
        'item_name'  => $item['Item_Name'],
        'qty_lama'   => $qty_lama,
        'qty_baru'   => $qty_baru,
        'qty_add'    => $qty_add,
        'saldo_baru' => $saldo_baru,
        'biaya'      => $total_biaya,
    ]);
    exit;
}

/* ── AJAX: refresh saldo satu user ─────────────────────────────────────────── */
if (isset($_GET['ajax_saldo'])) {
    header('Content-Type: application/json');
    $nama = trim($_GET['nama'] ?? '');
    $safe = mysqli_real_escape_string($conn, $nama);
    $rr   = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Saldo FROM formalhault WHERE Nama='$safe'"));
    echo json_encode(['saldo' => $rr ? (int)$rr['Saldo'] : null]);
    exit;
}

/* ── Stats ──────────────────────────────────────────────────────────────────── */
$totalItems   = count($inventory);
$totalQty     = array_sum(array_column($inventory, 'Quantity'));
$totalNama    = count($users);
$totalSaldo   = array_sum(array_filter(array_values($users), fn($v) => $v > 0));
$allJenis     = array_unique(array_filter(array_column($inventory, 'Jenis')));
sort($allJenis);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Material Assimilate · Stok</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* ── Reset ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg:      #0d0f14;
  --surf:    #141720;
  --surf2:   #1a1e2e;
  --surf3:   #1f2438;
  --border:  #252a38;
  --accent:  #7ef7b8;
  --gold:    #f7c97e;
  --blue:    #7eb8f7;
  --red:     #f77e7e;
  --purple:  #c47ef7;
  --muted:   #6b7494;
  --text:    #e8ecf4;
  --r:       10px;
}
body.day {
  --bg:      #f0f2f8;
  --surf:    #ffffff;
  --surf2:   #f5f6fb;
  --surf3:   #eceef7;
  --border:  #d0d5e8;
  --text:    #111827;
  --muted:   #6b7280;
}
html, body {
  background: var(--bg);
  color: var(--text);
  font-family: 'DM Mono', monospace;
  min-height: 100vh;
  transition: background .3s, color .3s;
}

body::before {
  content: '';
  position: fixed; inset: 0;
  background-image:
    linear-gradient(rgba(126,247,184,.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(126,247,184,.03) 1px, transparent 1px);
  background-size: 40px 40px;
  pointer-events: none; z-index: 0;
}
body.day::before { display: none; }

/* ── Header ── */
.hd {
  position: sticky; top: 0; z-index: 100;
  background: rgba(13,15,20,.92);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border);
  padding: 0 28px; height: 58px;
  display: flex; align-items: center; gap: 14px;
  transition: background .3s;
}
body.day .hd { background: rgba(240,242,248,.92); }

.hd-logo {
  width: 34px; height: 34px;
  background: linear-gradient(135deg, var(--accent), var(--blue));
  border-radius: 8px;
  display: grid; place-items: center;
  font-size: 17px; flex-shrink: 0;
  box-shadow: 0 0 16px rgba(126,247,184,.3);
}
.hd-title {
  font-family: 'DM Serif Display', serif;
  font-size: 1.4rem; color: var(--accent);
  letter-spacing: .02em;
}
.hd-sub {
  font-size: .65rem; text-transform: uppercase;
  letter-spacing: .14em; color: var(--muted);
  background: var(--surf2); border: 1px solid var(--border);
  padding: 3px 10px; border-radius: 20px;
}
.hd-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }

.btn-nav {
  padding: 7px 13px;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--surf2); color: var(--muted);
  font-family: 'DM Mono', monospace; font-size: .72rem;
  cursor: pointer; transition: border-color .2s, color .2s;
  white-space: nowrap;
}
.btn-nav:hover { border-color: var(--accent); color: var(--accent); }

/* Wallet chip */
.wallet-chip {
  display: flex; align-items: center; gap: 10px;
  background: var(--surf2); border: 1px solid var(--border);
  border-radius: var(--r); padding: 6px 14px;
  cursor: pointer; transition: border-color .2s;
}
.wallet-chip:hover { border-color: var(--accent); }
.wc-meta { line-height: 1.6; }
.wc-label { font-size: .6rem; text-transform: uppercase; letter-spacing: .12em; color: var(--muted); }
.wc-name  { font-size: .78rem; color: var(--text); }
.wc-val   { font-size: .92rem; font-weight: 500; color: var(--gold); white-space: nowrap; }

/* ── Wrap ── */
.wrap { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 26px 22px 60px; }

/* ── Page head ── */
.page-head { margin-bottom: 24px; }
.page-head .tag { font-size: .62rem; text-transform: uppercase; letter-spacing: .16em; color: var(--accent); margin-bottom: 6px; }
.page-head h1 { font-family: 'DM Serif Display', serif; font-size: 2rem; color: var(--text); letter-spacing: .01em; }
.page-head h1 span { color: var(--accent); }
.page-head p { font-size: .68rem; color: var(--muted); margin-top: 6px; letter-spacing: .08em; }

/* ── Alert ── */
.alert {
  border-radius: var(--r); padding: 13px 17px;
  margin-bottom: 20px; font-size: .8rem;
  border: 1px solid; display: none; line-height: 1.8;
}
.alert.show { display: block; }
.alert.ok  { background: rgba(126,247,184,.06); border-color: rgba(126,247,184,.25); color: var(--accent); }
.alert.err { background: rgba(247,126,126,.06); border-color: rgba(247,126,126,.25); color: var(--red); }

/* ── Stats ── */
.stat-bar {
  display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 22px;
}
.sc {
  background: var(--surf); border: 1px solid var(--border);
  border-radius: var(--r); padding: 14px 16px; position: relative; overflow: hidden;
}
.sc::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; }
.sc.c1::before { background: var(--accent); }
.sc.c2::before { background: var(--gold); }
.sc.c3::before { background: var(--blue); }
.sc.c4::before { background: var(--purple); }
.sc .sl { font-size: .62rem; text-transform: uppercase; letter-spacing: .12em; color: var(--muted); margin-bottom: 6px; }
.sc .sv { font-family: 'DM Serif Display', serif; font-size: 1.6rem; }
.sc.c1 .sv { color: var(--accent); }
.sc.c2 .sv { color: var(--gold); }
.sc.c3 .sv { color: var(--blue); }
.sc.c4 .sv { color: var(--purple); }

/* ── Controls ── */
.controls {
  display: flex; align-items: center; gap: 9px; flex-wrap: wrap; margin-bottom: 20px;
}
.sbox {
  display: flex; align-items: center; gap: 8px;
  background: var(--surf); border: 1px solid var(--border);
  border-radius: var(--r); padding: 0 13px; height: 36px; min-width: 220px;
  transition: border-color .2s;
}
.sbox:focus-within { border-color: var(--accent); }
.sbox span { color: var(--muted); font-size: .85rem; }
.sbox input {
  border: none; background: transparent; outline: none;
  color: var(--text); font-family: 'DM Mono', monospace;
  font-size: .8rem; width: 100%;
}
.sbox input::placeholder { color: var(--muted); }

.filter-btns { display: flex; gap: 6px; flex-wrap: wrap; }
.fbtn {
  padding: 0 13px; height: 32px; border-radius: 20px;
  font-family: 'DM Mono', monospace; font-size: .65rem;
  border: 1px solid var(--border);
  background: var(--surf); color: var(--muted);
  cursor: pointer; transition: all .15s;
  text-transform: uppercase; letter-spacing: .1em;
}
.fbtn:hover, .fbtn.active { border-color: var(--accent); background: rgba(126,247,184,.1); color: var(--accent); }

.sort-sel {
  margin-left: auto;
  background: var(--surf); border: 1px solid var(--border);
  color: var(--text); border-radius: var(--r);
  padding: 0 11px; height: 36px;
  font-family: 'DM Mono', monospace; font-size: .72rem;
  outline: none; cursor: pointer; appearance: none;
}
.sort-sel:focus { border-color: var(--accent); }

/* ── Grid ── */
.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
}

.item-card {
  background: var(--surf); border: 1px solid var(--border);
  border-radius: 12px; overflow: hidden;
  display: flex; flex-direction: column;
  transition: transform .2s, border-color .2s, box-shadow .2s;
  cursor: pointer;
  position: relative;
}
.item-card:hover {
  transform: translateY(-3px);
  border-color: rgba(126,247,184,.4);
  box-shadow: 0 8px 28px rgba(126,247,184,.08);
}

.ic-thumb {
  height: 96px;
  background: linear-gradient(135deg, var(--surf2), var(--surf3));
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  font-size: 40px; position: relative;
}
.ic-thumb::after {
  content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg, var(--accent), var(--blue));
  opacity: 0; transition: opacity .2s;
}
.item-card:hover .ic-thumb::after { opacity: 1; }

.ic-jenis {
  position: absolute; top: 9px; right: 9px;
  font-size: .6rem; text-transform: uppercase; letter-spacing: .1em;
  background: rgba(126,247,184,.12); color: var(--accent);
  border: 1px solid rgba(126,247,184,.2);
  padding: 2px 8px; border-radius: 20px;
}
.ic-qty-badge {
  position: absolute; top: 9px; left: 9px;
  font-size: .6rem; padding: 2px 8px; border-radius: 20px;
}
.ic-qty-badge.ok  { background: rgba(126,247,184,.12); color: var(--accent); border: 1px solid rgba(126,247,184,.2); }
.ic-qty-badge.low { background: rgba(247,126,126,.12); color: var(--red);    border: 1px solid rgba(247,126,126,.2); }
.ic-qty-badge.zero{ background: rgba(247,201,126,.12); color: var(--gold);   border: 1px solid rgba(247,201,126,.2); }

.ic-body { padding: 14px; flex: 1; display: flex; flex-direction: column; gap: 5px; }
.ic-name { font-size: .82rem; font-weight: 500; line-height: 1.35; color: var(--text); }
.ic-ket {
  font-size: .68rem; color: var(--muted); line-height: 1.5;
  overflow: hidden;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  min-height: 28px;
}
.ic-price {
  margin-top: auto; padding-top: 9px;
  display: flex; align-items: center; justify-content: space-between;
}
.ic-hpp  { font-size: .82rem; font-weight: 500; color: var(--gold); }
.ic-qty-text { font-size: .65rem; color: var(--muted); }

.ic-add-btn {
  width: 100%; border: none;
  background: linear-gradient(90deg, var(--accent), var(--blue));
  color: #0d0f14;
  font-family: 'DM Mono', monospace; font-size: .78rem; font-weight: 500;
  padding: 9px; cursor: pointer;
  transition: opacity .2s; letter-spacing: .06em;
}
.ic-add-btn:hover { opacity: .82; }

/* ── Empty ── */
.empty-state {
  text-align: center; padding: 56px 20px;
  color: var(--muted); font-size: .75rem; line-height: 2;
  grid-column: 1/-1;
}
.empty-state .es-icon { font-size: 36px; margin-bottom: 12px; opacity: .5; }

/* ── Modal ── */
.modal-overlay {
  display: none; position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,.7); backdrop-filter: blur(6px);
  align-items: center; justify-content: center;
}
.modal-overlay.open { display: flex; }

.modal {
  background: var(--surf); border: 1px solid var(--border);
  border-radius: 16px; padding: 28px;
  width: 100%; max-width: 420px; margin: 16px;
  animation: mopen .18s ease; position: relative;
}
@keyframes mopen { from { opacity:0; transform:scale(.96); } to { opacity:1; transform:none; } }

.modal-close {
  position: absolute; top: 13px; right: 15px;
  background: none; border: none; color: var(--muted);
  font-size: 18px; cursor: pointer; transition: color .15s;
}
.modal-close:hover { color: var(--text); }

.modal h3 { font-family: 'DM Serif Display', serif; font-size: 1.25rem; margin-bottom: 3px; }
.modal h3 span { color: var(--accent); }
.modal .msub { font-size: .68rem; color: var(--muted); margin-bottom: 20px; letter-spacing: .06em; }

.mfg { margin-bottom: 15px; }
.mfg label {
  display: block; font-size: .62rem; text-transform: uppercase;
  letter-spacing: .13em; color: var(--muted); margin-bottom: 7px;
}
.mfg select, .mfg input[type="number"] {
  width: 100%; background: var(--bg); border: 1px solid var(--border);
  border-radius: var(--r); color: var(--text);
  font-family: 'DM Mono', monospace; font-size: .82rem;
  padding: 9px 12px; outline: none; appearance: none;
  transition: border-color .2s; cursor: pointer;
}
.mfg select:focus, .mfg input[type="number"]:focus { border-color: var(--accent); }
.mfg select option { background: var(--surf2); }

.qty-row {
  display: flex; align-items: center; gap: 8px; margin-bottom: 15px;
}
.qty-row label {
  font-size: .62rem; text-transform: uppercase;
  letter-spacing: .13em; color: var(--muted); white-space: nowrap;
}
.qty-row input[type=number] {
  flex: 1; background: var(--bg); border: 1px solid var(--border);
  border-radius: var(--r); color: var(--text);
  font-family: 'DM Mono', monospace; font-size: .85rem;
  padding: 9px 12px; outline: none; transition: border-color .2s; min-width: 0;
}
.qty-row input[type=number]:focus { border-color: var(--accent); }
.step-btn {
  width: 34px; height: 34px; border-radius: 8px;
  background: var(--surf2); border: 1px solid var(--border);
  color: var(--text); font-size: 1.1rem; cursor: pointer;
  display: grid; place-items: center; flex-shrink: 0;
  transition: background .15s;
}
.step-btn:hover { background: var(--surf3); }

/* Preview box */
.preview-box {
  background: var(--bg); border: 1px solid var(--border);
  border-radius: var(--r); padding: 13px 15px; margin-bottom: 16px;
  font-size: .75rem;
}
.pv-row {
  display: flex; justify-content: space-between;
  padding: 3px 0; color: var(--muted);
}
.pv-row span:last-child { color: var(--text); }
.pv-row.sep { border-top: 1px solid var(--border); margin-top: 8px; padding-top: 9px; font-weight: 500; }
.pv-row.sep span:last-child { color: var(--accent); }
.pv-row.cost span:last-child { color: var(--gold); }
.pv-row.saldo-after span:last-child { color: var(--blue); }

.btn-submit {
  width: 100%; border: none;
  background: linear-gradient(90deg, var(--accent), var(--blue));
  color: #0d0f14;
  font-family: 'DM Mono', monospace; font-size: .85rem; font-weight: 500;
  padding: 12px; border-radius: var(--r); cursor: pointer;
  transition: opacity .2s, transform .1s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  letter-spacing: .06em;
}
.btn-submit:hover:not(:disabled) { opacity: .85; transform: translateY(-1px); }
.btn-submit:disabled { opacity: .38; cursor: not-allowed; }

/* Spinner */
.spinner {
  display: inline-block; width: 13px; height: 13px;
  border: 2px solid rgba(13,15,20,.3);
  border-top-color: #0d0f14;
  border-radius: 50%; animation: spin .6s linear infinite;
}
@keyframes spin { to { transform:rotate(360deg); } }

/* ── History ── */
.history-section { margin-top: 32px; }
.panel-title {
  font-size: .62rem; text-transform: uppercase; letter-spacing: .16em;
  color: var(--muted); margin-bottom: 12px;
}
.hcard { background: var(--surf); border: 1px solid var(--border); border-radius: var(--r); overflow: hidden; }
.hcard table { width: 100%; border-collapse: collapse; }
.hcard th {
  font-size: .62rem; text-transform: uppercase; letter-spacing: .1em;
  color: var(--muted); padding: 10px 12px; border-bottom: 1px solid var(--border); text-align: left;
}
.hcard td { padding: 10px 12px; border-bottom: 1px solid var(--border); font-size: .75rem; }
.hcard tr:last-child td { border-bottom: none; }
.hcard tbody tr:hover td { background: var(--surf2); }

.badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:.65rem; }
.badge.green  { background:rgba(126,247,184,.12); color:var(--accent); }
.badge.gold   { background:rgba(247,201,126,.12); color:var(--gold); }
.badge.muted  { background:rgba(255,255,255,.05); color:var(--muted); }
.badge.blue   { background:rgba(126,184,247,.12); color:var(--blue); }

/* ── Biaya toggle ── */
.biaya-toggle {
  display: flex; align-items: center; gap: 8px;
  font-size: .7rem; color: var(--muted); margin-bottom: 12px; cursor: pointer;
}
.biaya-toggle input[type=checkbox] { accent-color: var(--accent); width: 14px; height: 14px; cursor: pointer; }

/* ── Responsive ── */
@media(max-width:900px) { .stat-bar { grid-template-columns:1fr 1fr; } }
@media(max-width:580px) {
  .hd { padding: 0 14px; }
  .wrap { padding: 14px 10px 40px; }
  .items-grid { grid-template-columns: repeat(auto-fill, minmax(155px,1fr)); }
  .stat-bar { grid-template-columns: 1fr 1fr; }
  .hd-sub { display: none; }
}
</style>
</head>
<body>

<!-- ── Header ── -->
<header class="hd">
  <div class="hd-logo">📥</div>
  <div class="hd-title">Assimilate</div>
  <div class="hd-sub">Pengadaan Stok</div>
  <div class="hd-right">
    <div class="wallet-chip" id="walletChip" onclick="openWalletModal()">
      <div class="wc-meta">
        <div class="wc-label">Saldo</div>
        <div class="wc-name" id="chipName">Pilih akun</div>
      </div>
      <div class="wc-val" id="chipSaldo">—</div>
    </div>
    <button class="btn-nav" onclick="location.href='Pollux.php'">🛒 Market</button>
    <button class="btn-nav" onclick="location.href='menu.php'">⬅ Menu</button>
    <button class="btn-nav" onclick="toggleMode()">🌗</button>
  </div>
</header>

<!-- ── Main ── -->
<div class="wrap">

  <div class="page-head">
    <div class="tag">▸ Pengadaan Material</div>
    <h1>Material <span>Assimilate</span></h1>
    <p>Pilih item · Tambah stok ke JSON inventory · Saldo dari formalhault</p>
  </div>

  <div class="alert" id="alertBox"></div>

  <!-- Stats -->
  <div class="stat-bar">
    <div class="sc c1"><div class="sl">Total Item</div><div class="sv"><?= $totalItems ?></div></div>
    <div class="sc c2"><div class="sl">Total Stok</div><div class="sv"><?= number_format($totalQty,0,',','.') ?></div></div>
    <div class="sc c3"><div class="sl">Stok Ditambah</div><div class="sv" id="statAdded">0</div></div>
    <div class="sc c4"><div class="sl">Nama Terdaftar</div><div class="sv"><?= $totalNama ?></div></div>
  </div>

  <!-- Controls -->
  <div class="controls">
    <div class="sbox">
      <span>⌕</span>
      <input type="text" id="searchInput" placeholder="Cari nama item, jenis..." oninput="renderGrid()">
    </div>
    <div class="filter-btns" id="filterBtns">
      <button class="fbtn active" data-jenis="" onclick="setFilter(this)">Semua</button>
      <?php foreach ($allJenis as $j): ?>
        <button class="fbtn" data-jenis="<?= h($j) ?>" onclick="setFilter(this)"><?= h($j) ?></button>
      <?php endforeach; ?>
    </div>
    <select class="sort-sel" id="sortSel" onchange="renderGrid()">
      <option value="name">A–Z</option>
      <option value="qty_asc">Stok ↑</option>
      <option value="qty_desc">Stok ↓</option>
      <option value="price_asc">HPP ↑</option>
      <option value="price_desc">HPP ↓</option>
    </select>
  </div>

  <!-- Item Grid -->
  <div class="items-grid" id="itemsGrid">
    <div class="empty-state">
      <div class="es-icon">📦</div><div>Memuat item...</div>
    </div>
  </div>

  <!-- History -->
  <div class="history-section" id="historySection" style="display:none">
    <div class="panel-title">▸ Riwayat Pengadaan Sesi Ini</div>
    <div class="hcard">
      <div style="overflow-x:auto">
        <table>
          <thead>
            <tr>
              <th>Waktu</th>
              <th>Penyetor</th>
              <th>Item</th>
              <th>+Qty</th>
              <th>Stok Baru</th>
              <th>Biaya</th>
              <th>Spica ID</th>
            </tr>
          </thead>
          <tbody id="historyBody"></tbody>
        </table>
      </div>
    </div>
  </div>

</div><!-- .wrap -->

<!-- ── Assimilate Modal ── -->
<div class="modal-overlay" id="addModal" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <h3><span id="mItemName">—</span></h3>
    <div class="msub" id="mItemSub">—</div>

    <div class="mfg">
      <label>Penyetor (dari formalhault)</label>
      <select id="mPenyetor" onchange="onPenyetorChange()">
        <option value="">— Pilih Nama —</option>
        <?php foreach ($users as $nama => $saldo): ?>
          <option value="<?= h($nama) ?>" data-saldo="<?= $saldo ?>"><?= h($nama) ?> — <?= number_format($saldo,0,',','.') ?> pt</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="qty-row">
      <label>Tambah Qty</label>
      <button class="step-btn" type="button" onclick="stepQty(-1)">−</button>
      <input type="number" id="mQtyAdd" value="1" min="1" oninput="updatePreview()">
      <button class="step-btn" type="button" onclick="stepQty(1)">+</button>
    </div>

    <label class="biaya-toggle">
      <input type="checkbox" id="useBiaya" onchange="toggleBiayaField()">
      Kenakan biaya pengadaan ke saldo penyetor
    </label>

    <div class="mfg" id="biayaField" style="display:none">
      <label>Biaya per Unit (pt)</label>
      <input type="number" id="mBiaya" value="0" min="0" oninput="updatePreview()">
    </div>

    <div class="preview-box">
      <div class="pv-row"><span>Stok sekarang</span><span id="pvQtyNow">—</span></div>
      <div class="pv-row sep"><span>+Qty ditambah</span><span id="pvQtyAdd">—</span></div>
      <div class="pv-row"><span>Stok setelah</span><span id="pvQtyAfter">—</span></div>
      <div class="pv-row cost" id="pvCostRow" style="display:none"><span>Total biaya</span><span id="pvCost">—</span></div>
      <div class="pv-row saldo-after" id="pvSaldoAfterRow" style="display:none"><span>Saldo setelah</span><span id="pvSaldoAfter">—</span></div>
    </div>

    <button class="btn-submit" id="btnAdd" onclick="submitAssimilate()" disabled>
      <span id="addSpinner" class="spinner" style="display:none"></span>
      <span id="addLabel">Konfirmasi Pengadaan</span>
    </button>
  </div>
</div>

<!-- ── Wallet Modal ── -->
<div class="modal-overlay" id="walletModal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:340px">
    <button class="modal-close" onclick="document.getElementById('walletModal').classList.remove('open')">✕</button>
    <h3>Pilih <span>Akun</span></h3>
    <div class="msub">Akun aktif untuk pengadaan</div>
    <div class="mfg">
      <label>Nama</label>
      <select id="walletSel" onchange="onWalletChange()">
        <option value="">— Pilih —</option>
        <?php foreach ($users as $nama => $saldo): ?>
          <option value="<?= h($nama) ?>" data-saldo="<?= $saldo ?>"><?= h($nama) ?> — <?= number_format($saldo,0,',','.') ?> pt</option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<!-- ── PHP → JS ── -->
<script>
const INVENTORY = <?= json_encode(
    array_map(fn($r) => [
        'id'         => (int)$r['Id'],
        'name'       => $r['Item_Name'] ?? '',
        'qty'        => (int)($r['Quantity'] ?? 0),
        'hpp'        => (int)($r['HPP'] ?? 0),
        'jenis'      => $r['Jenis'] ?? '',
        'keterangan' => $r['Keterangan'] ?? '',
    ], $inventory),
    JSON_UNESCAPED_UNICODE
) ?>;

let balances    = <?= json_encode($users, JSON_UNESCAPED_UNICODE) ?>;
let activeUser  = '';
let activeItem  = null;
let filterJenis = '';
let sessionAdded = 0;
let history     = [];

/* ── Helpers ── */
function fmtPt(n) { return Number(n).toLocaleString('id-ID') + ' pt'; }

function itemEmoji(j) {
    const m = {mekanik:'⚙️',elektrik:'⚡',kimia:'🧪',digital:'💾',alat:'🔧',bahan:'📦',obat:'💊',listrik:'🔌',kayu:'🪵'};
    const k = (j||'').toLowerCase();
    for (const [key,val] of Object.entries(m)) { if (k.includes(key)) return val; }
    return '📦';
}

function showAlert(msg, ok) {
    const el = document.getElementById('alertBox');
    el.innerHTML = msg;
    el.className = 'alert show ' + (ok ? 'ok' : 'err');
    window.scrollTo({top:0,behavior:'smooth'});
    setTimeout(() => el.classList.remove('show'), 8000);
}

function updateChip() {
    document.getElementById('chipName').textContent = activeUser || 'Pilih akun';
    document.getElementById('chipSaldo').textContent = activeUser ? fmtPt(balances[activeUser] ?? 0) : '—';
}

/* ── Render grid ── */
function getItems() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const s = document.getElementById('sortSel').value;
    let items = INVENTORY.filter(it => {
        if (filterJenis && it.jenis !== filterJenis) return false;
        if (q && !it.name.toLowerCase().includes(q) && !it.jenis.toLowerCase().includes(q)) return false;
        return true;
    });
    items.sort((a,b) => {
        if (s==='qty_asc')    return a.qty - b.qty;
        if (s==='qty_desc')   return b.qty - a.qty;
        if (s==='price_asc')  return a.hpp - b.hpp;
        if (s==='price_desc') return b.hpp - a.hpp;
        return a.name.localeCompare(b.name,'id');
    });
    return items;
}

function renderGrid() {
    const grid  = document.getElementById('itemsGrid');
    const items = getItems();

    if (!items.length) {
        grid.innerHTML = `<div class="empty-state"><div class="es-icon">🔍</div><div>Tidak ada item yang cocok.</div></div>`;
        return;
    }
    grid.innerHTML = items.map(it => {
        const zero   = it.qty === 0;
        const low    = !zero && it.qty <= 5;
        const emoji  = itemEmoji(it.jenis);
        const badgeCls = zero ? 'zero' : low ? 'low' : 'ok';
        const badgeTxt = zero ? 'Habis' : 'Stok: ' + it.qty;
        return `<div class="item-card" onclick="openAddModal(${it.id})">
          <div class="ic-thumb">
            ${emoji}
            ${it.jenis?`<span class="ic-jenis">${it.jenis}</span>`:''}
            <span class="ic-qty-badge ${badgeCls}">${badgeTxt}</span>
          </div>
          <div class="ic-body">
            <div class="ic-name">${it.name}</div>
            <div class="ic-ket">${it.keterangan||'—'}</div>
            <div class="ic-price">
              <span class="ic-hpp">${it.hpp > 0 ? fmtPt(it.hpp) : 'Gratis'}</span>
              <span class="ic-qty-text">×${it.qty}</span>
            </div>
          </div>
          <button class="ic-add-btn" onclick="event.stopPropagation();openAddModal(${it.id})">+ Tambah Stok</button>
        </div>`;
    }).join('');
}

function setFilter(btn) {
    document.querySelectorAll('.fbtn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterJenis = btn.dataset.jenis;
    renderGrid();
}

/* ── Modal ── */
function openAddModal(id) {
    activeItem = INVENTORY.find(i => i.id === id);
    if (!activeItem) return;
    document.getElementById('mItemName').textContent = activeItem.name;
    document.getElementById('mItemSub').textContent  =
        (activeItem.jenis ? activeItem.jenis + ' · ' : '') +
        'Stok: ' + activeItem.qty + ' · HPP: ' + fmtPt(activeItem.hpp);
    document.getElementById('mQtyAdd').value = 1;
    document.getElementById('mBiaya').value  = 0;
    document.getElementById('useBiaya').checked = false;
    document.getElementById('biayaField').style.display = 'none';
    if (activeUser) document.getElementById('mPenyetor').value = activeUser;
    updatePreview();
    document.getElementById('addModal').classList.add('open');
}
function closeModal() { document.getElementById('addModal').classList.remove('open'); }

function onPenyetorChange() {
    activeUser = document.getElementById('mPenyetor').value;
    updateChip();
    updatePreview();
}

function stepQty(d) {
    const el = document.getElementById('mQtyAdd');
    el.value = Math.max(1, (parseInt(el.value)||1) + d);
    updatePreview();
}

function toggleBiayaField() {
    const show = document.getElementById('useBiaya').checked;
    document.getElementById('biayaField').style.display = show ? 'block' : 'none';
    updatePreview();
}

function updatePreview() {
    if (!activeItem) return;
    const penyetor  = document.getElementById('mPenyetor').value;
    const qtyAdd    = Math.max(1, parseInt(document.getElementById('mQtyAdd').value)||1);
    const useBiaya  = document.getElementById('useBiaya').checked;
    const biayaUnit = useBiaya ? Math.max(0, parseInt(document.getElementById('mBiaya').value)||0) : 0;
    const totalBiaya = biayaUnit * qtyAdd;
    const saldo     = penyetor ? (balances[penyetor] ?? 0) : null;
    const qtyAfter  = activeItem.qty + qtyAdd;
    const saldoAfter = saldo !== null ? saldo - totalBiaya : null;

    document.getElementById('pvQtyNow').textContent   = activeItem.qty;
    document.getElementById('pvQtyAdd').textContent   = '+' + qtyAdd;
    document.getElementById('pvQtyAfter').textContent = qtyAfter;

    const costRow     = document.getElementById('pvCostRow');
    const saldoRow    = document.getElementById('pvSaldoAfterRow');
    if (useBiaya && biayaUnit > 0) {
        costRow.style.display    = '';
        saldoRow.style.display   = '';
        document.getElementById('pvCost').textContent        = fmtPt(totalBiaya);
        document.getElementById('pvSaldoAfter').textContent  = saldoAfter !== null ? fmtPt(saldoAfter) : '—';
    } else {
        costRow.style.display  = 'none';
        saldoRow.style.display = 'none';
    }

    // Enable button: butuh penyetor; jika ada biaya, saldo harus cukup
    const saldoCukup = !useBiaya || biayaUnit === 0 || (saldo !== null && saldoAfter >= 0);
    document.getElementById('btnAdd').disabled = !penyetor || !saldoCukup;
}

/* ── Submit pengadaan ── */
async function submitAssimilate() {
    const penyetor  = document.getElementById('mPenyetor').value.trim();
    const qtyAdd    = parseInt(document.getElementById('mQtyAdd').value) || 0;
    const useBiaya  = document.getElementById('useBiaya').checked;
    const biaya     = useBiaya ? Math.max(0, parseInt(document.getElementById('mBiaya').value)||0) : 0;
    if (!penyetor || !activeItem || qtyAdd <= 0) return;

    const btn     = document.getElementById('btnAdd');
    const spinner = document.getElementById('addSpinner');
    const label   = document.getElementById('addLabel');
    btn.disabled          = true;
    spinner.style.display = 'inline-block';
    label.textContent     = 'Memproses...';

    try {
        const fd = new FormData();
        fd.append('ajax_assimilate', '1');
        fd.append('penyetor', penyetor);
        fd.append('item_id',  activeItem.id);
        fd.append('qty_add',  qtyAdd);
        fd.append('biaya',    biaya);

        const res  = await fetch('Antares.php', { method:'POST', body: fd });
        const data = await res.json();

        if (!data.ok) throw new Error(data.msg);

        // Update state lokal
        if (biaya > 0) balances[penyetor] = data.saldo_baru;
        const inv = INVENTORY.find(i => i.id === activeItem.id);
        if (inv) inv.qty = data.qty_baru;

        sessionAdded += qtyAdd;
        document.getElementById('statAdded').textContent = sessionAdded;

        // Update dropdown (saldo baru jika ada biaya)
        if (biaya > 0) {
            ['mPenyetor','walletSel'].forEach(selId => {
                const opt = document.querySelector(`#${selId} option[value="${penyetor}"]`);
                if (opt) opt.textContent = penyetor + ' — ' + fmtPt(data.saldo_baru);
            });
        }

        activeUser = penyetor;
        updateChip();
        renderGrid();

        history.unshift({
            time:     new Date().toLocaleTimeString('id-ID'),
            penyetor: penyetor,
            item:     data.item_name,
            qtyAdd:   data.qty_add,
            qtyBaru:  data.qty_baru,
            biaya:    data.biaya,
            sid:      data.spica_id,
        });
        renderHistory();
        closeModal();

        const biayaInfo = data.biaya > 0
            ? ` · Biaya: <b>${fmtPt(data.biaya)}</b> · Sisa saldo: <b>${fmtPt(data.saldo_baru)}</b>`
            : '';
        showAlert(
            `✅ ${data.msg}<br>Spica ID: <b>#${data.spica_id}</b>${biayaInfo}`,
            true
        );

    } catch(e) {
        showAlert('❌ Gagal: ' + e.message, false);
        btn.disabled = false;
    } finally {
        spinner.style.display = 'none';
        label.textContent     = 'Konfirmasi Pengadaan';
    }
}

/* ── History ── */
function renderHistory() {
    if (!history.length) return;
    document.getElementById('historySection').style.display = '';
    document.getElementById('historyBody').innerHTML = history.map(r => `
        <tr>
          <td style="color:var(--muted);font-size:.7rem">${r.time}</td>
          <td>${r.penyetor}</td>
          <td><strong>${r.item}</strong></td>
          <td><span class="badge green">+${r.qtyAdd}</span></td>
          <td><span class="badge blue">${r.qtyBaru}</span></td>
          <td>${r.biaya > 0 ? '<span class="badge gold">' + fmtPt(r.biaya) + '</span>' : '<span class="badge muted">—</span>'}</td>
          <td><span class="badge muted">#${r.sid}</span></td>
        </tr>
    `).join('');
}

/* ── Wallet selector ── */
function openWalletModal() { document.getElementById('walletModal').classList.add('open'); }
function onWalletChange() {
    activeUser = document.getElementById('walletSel').value;
    updateChip();
    document.getElementById('walletModal').classList.remove('open');
    if (document.getElementById('mPenyetor'))
        document.getElementById('mPenyetor').value = activeUser;
}

/* ── Day/Night ── */
function toggleMode() {
    document.body.classList.toggle('day');
    localStorage.setItem('mode', document.body.classList.contains('day') ? 'day' : 'night');
}
if (localStorage.getItem('mode') === 'day') document.body.classList.add('day');

/* ── Init ── */
renderGrid();
</script>
</body>
</html>