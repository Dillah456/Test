<?php
// ─────────────────────────────────────────────────────────────────────────────
//  CONFIG
//  File ini ada di  : /HD140283/Sirius/inventory.php
//  JSON ada di      : /HD140283/Sirius/data/session-market.json
// ─────────────────────────────────────────────────────────────────────────────
$JSON_FILE = __DIR__ . '/data/session-market.json';

// ─────────────────────────────────────────────────────────────────────────────
//  DB CONFIG (anka.php — MySQL Import Panel)
//  DB   : oortmyid_cv  |  Tabel: inventory
// ─────────────────────────────────────────────────────────────────────────────
$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_cv";

function getDB_inv(): ?mysqli {
    global $db_server, $db_user, $db_pass, $db_name;
    $conn = @new mysqli($db_server, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) return null;
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function json_read(string $file): array {
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    $arr = json_decode($raw, true);
    if (!is_array($arr)) return [];
    // buang baris dummy awal (Id = 0)
    return array_values(array_filter($arr, fn($r) => !($r['Id'] == 0 && ($r['Item_Name'] ?? '') === '-')));
}

function json_write(string $file, array $data): bool {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return file_put_contents(
        $file,
        json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

function next_id(array $data): int {
    return $data ? (max(array_column($data, 'Id')) + 1) : 1;
}

function rp(float $n): string {
    return 'Rp&nbsp;' . number_format($n, 0, ',', '.');
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────
$data    = json_read($JSON_FILE);
$action  = $_GET['action'] ?? 'list';
$id      = (int)($_GET['id'] ?? 0);
$msg     = '';
$msgType = '';

// ── DB Import: import satu item dari DB ke JSON ───────────────────────────────
if ($action === 'db_import_one' && $id > 0) {
    $conn = getDB_inv();
    if ($conn) {
        $stmt = $conn->prepare("SELECT * FROM Inventory WHERE Id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $dbRow = $stmt->get_result()->fetch_assoc();
        $conn->close();
        if ($dbRow) {
            // Cek apakah item sudah ada di JSON (berdasarkan Item_Name + Jenis)
            $exists = false;
            foreach ($data as &$existing) {
                if (mb_strtolower($existing['Item_Name']) === mb_strtolower($dbRow['Item_Name'])) {
                    // Update quantity & HPP jika sudah ada
                    $existing['Quantity']   = (int)$dbRow['Quantity'];
                    $existing['HPP']        = (float)$dbRow['HPP'];
                    $existing['Jenis']      = $dbRow['Jenis'] ?? '';
                    $existing['Keterangan'] = $dbRow['Keterangan'] ?? '';
                    $exists = true;
                    break;
                }
            }
            unset($existing);
            if (!$exists) {
                $data[] = [
                    'Id'          => next_id($data),
                    'Item_Name'   => $dbRow['Item_Name'],
                    'Quantity'    => (int)$dbRow['Quantity'],
                    'HPP'         => (float)$dbRow['HPP'],
                    'Jenis'       => $dbRow['Jenis'] ?? '',
                    'Keterangan'  => $dbRow['Keterangan'] ?? '',
                ];
            }
            json_write($JSON_FILE, $data);
            $msg     = $exists
                ? 'Item <strong>' . h($dbRow['Item_Name']) . '</strong> diperbarui dari DB.'
                : 'Item <strong>' . h($dbRow['Item_Name']) . '</strong> berhasil diimpor dari DB.';
            $msgType = 'success';
        } else {
            $msg     = 'Item ID #' . $id . ' tidak ditemukan di database.';
            $msgType = 'error';
        }
    } else {
        $msg     = 'Koneksi ke database gagal.';
        $msgType = 'error';
    }
    $action = 'list';
}

// ── DB Import: import semua dari DB ke JSON ───────────────────────────────────
if ($action === 'db_import_all') {
    $conn = getDB_inv();
    if ($conn) {
        $res    = $conn->query("SELECT * FROM Inventory ORDER BY Id ASC");
        $synced = 0;
        $added  = 0;
        if ($res) {
            while ($dbRow = $res->fetch_assoc()) {
                $exists = false;
                foreach ($data as &$existing) {
                    if (mb_strtolower($existing['Item_Name']) === mb_strtolower($dbRow['Item_Name'])) {
                        $existing['Quantity']   = (int)$dbRow['Quantity'];
                        $existing['HPP']        = (float)$dbRow['HPP'];
                        $existing['Jenis']      = $dbRow['Jenis'] ?? '';
                        $existing['Keterangan'] = $dbRow['Keterangan'] ?? '';
                        $exists = true;
                        $synced++;
                        break;
                    }
                }
                unset($existing);
                if (!$exists) {
                    $data[] = [
                        'Id'          => next_id($data),
                        'Item_Name'   => $dbRow['Item_Name'],
                        'Quantity'    => (int)$dbRow['Quantity'],
                        'HPP'         => (float)$dbRow['HPP'],
                        'Jenis'       => $dbRow['Jenis'] ?? '',
                        'Keterangan'  => $dbRow['Keterangan'] ?? '',
                    ];
                    $added++;
                }
            }
        }
        $conn->close();
        json_write($JSON_FILE, $data);
        $msg     = "Import selesai — <strong>$added</strong> item baru, <strong>$synced</strong> item diperbarui.";
        $msgType = 'success';
    } else {
        $msg     = 'Koneksi ke database gagal.';
        $msgType = 'error';
    }
    $action = 'list';
}

// ── DB Browse: ambil semua data dari DB untuk ditampilkan ─────────────────────
$dbRows      = [];
$dbTotal     = 0;
$dbConnected = false;
$dbQSearch   = trim($_GET['dbq'] ?? '');
if ($action === 'db_browse') {
    $conn = getDB_inv();
    if ($conn) {
        $dbConnected = true;
        $where = '';
        if ($dbQSearch) {
            $qs    = $conn->real_escape_string($dbQSearch);
            $where = "WHERE Item_Name LIKE '%$qs%' OR Jenis LIKE '%$qs%'";
        }
        $res = $conn->query("SELECT * FROM Inventory $where ORDER BY Id DESC");
        if ($res) while ($r = $res->fetch_assoc()) $dbRows[] = $r;
        $dbTotal = count($dbRows);
        $conn->close();
    }
}

// ── POST: tambah / edit ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pa = $_POST['_action'] ?? '';

    $item = [
        'Item_Name'  => trim($_POST['Item_Name']  ?? ''),
        'Quantity'   => (int)($_POST['Quantity']   ?? 0),
        'HPP'        => (float)($_POST['HPP']      ?? 0),
        'Jenis'      => trim($_POST['Jenis']       ?? ''),
        'Keterangan' => trim($_POST['Keterangan']  ?? ''),
    ];

    if ($pa === 'add') {
        $item['Id'] = next_id($data);
        $data[]     = $item;
        if (json_write($JSON_FILE, $data)) {
            $msg     = 'Item <strong>' . h($item['Item_Name']) . '</strong> berhasil ditambahkan.';
            $msgType = 'success';
        } else {
            $msg     = 'Gagal menyimpan. Pastikan folder <code>data/</code> writable (chmod 755).';
            $msgType = 'error';
        }
        $action = 'list';

    } elseif ($pa === 'edit') {
        $eid = (int)($_POST['_id'] ?? 0);
        foreach ($data as &$row) {
            if ($row['Id'] === $eid) { $row = array_merge($row, $item); break; }
        }
        unset($row);
        if (json_write($JSON_FILE, $data)) {
            $msg     = 'Item berhasil diperbarui.';
            $msgType = 'success';
        } else {
            $msg     = 'Gagal menyimpan.';
            $msgType = 'error';
        }
        $action = 'list';
    }
}

// ── GET: delete ───────────────────────────────────────────────────────────────
if ($action === 'delete' && $id > 0) {
    $before = count($data);
    $data   = array_values(array_filter($data, fn($r) => $r['Id'] !== $id));
    if (count($data) < $before) {
        json_write($JSON_FILE, $data);
        $msg     = 'Item berhasil dihapus.';
        $msgType = 'success';
    }
    $action = 'list';
}

// ── Edit: ambil baris ─────────────────────────────────────────────────────────
$editRow = null;
if ($action === 'edit' && $id > 0) {
    foreach ($data as $r) { if ($r['Id'] === $id) { $editRow = $r; break; } }
    if (!$editRow) $action = 'list';
}

// ── List: search ──────────────────────────────────────────────────────────────
$q    = trim($_GET['q'] ?? '');
$rows = $data;
if ($q && $action === 'list') {
    $lq   = mb_strtolower($q);
    $rows = array_values(array_filter($rows, fn($r) =>
        str_contains(mb_strtolower($r['Item_Name']  ?? ''), $lq) ||
        str_contains(mb_strtolower($r['Jenis']      ?? ''), $lq) ||
        str_contains(mb_strtolower($r['Keterangan'] ?? ''), $lq)
    ));
}

// ── Stats ─────────────────────────────────────────────────────────────────────
$totalItems = count($data);
$totalQty   = array_sum(array_column($data, 'Quantity'));
$totalHPP   = array_sum(array_column($data, 'HPP'));
$totalJenis = count(array_filter(array_unique(array_column($data, 'Jenis'))));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventaris · Sirius</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ── Reset & base ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg:      #0b0d12;
  --surf:    #131620;
  --surf2:   #191d2c;
  --border:  #222638;
  --accent:  #a30cb1;
  --blue:    #3b82f6;
  --danger:  #ef4444;
  --success: #22c55e;
  --purple:  #a78bfa;
  --text:    #dde0ee;
  --muted:   #525870;
  --r:       8px;
}
html, body {
  background: var(--bg);
  color: var(--text);
  font-family: 'DM Mono', monospace;
  font-size: 13px;
  min-height: 100vh;
}

/* ── Header ── */
.hd {
  position: sticky; top: 0; z-index: 50;
  background: var(--surf);
  border-bottom: 1px solid var(--border);
  padding: 12px 28px;
  display: flex; align-items: center; gap: 12px;
}
.hd-logo {
  width: 32px; height: 32px; background: var(--accent);
  border-radius: 7px; display: grid; place-items: center;
  font-size: 15px; flex-shrink: 0;
}
.hd-title {
  font-family: 'Syne', sans-serif;
  font-weight: 800; font-size: 18px; letter-spacing: -.4px;
}
.hd-title b { color: var(--accent); font-weight: 800; }
.hd-tag {
  margin-left: auto;
  font-size: 10px; letter-spacing: 1px; text-transform: uppercase;
  color: var(--muted);
  background: var(--surf2); border: 1px solid var(--border);
  padding: 3px 10px; border-radius: 20px;
}

/* ── Main ── */
main { max-width: 1200px; margin: 0 auto; padding: 24px 20px; }

/* ── Toast ── */
.toast {
  padding: 11px 16px; border-radius: var(--r);
  margin-bottom: 18px;
  display: flex; align-items: flex-start; gap: 9px;
  font-size: 12px; line-height: 1.6;
  animation: tin .25s ease;
}
.toast.success { background: rgba(34,197,94,.1);  border: 1px solid rgba(34,197,94,.25); color: #86efac; }
.toast.error   { background: rgba(239,68,68,.1);   border: 1px solid rgba(239,68,68,.25); color: #fca5a5; }
@keyframes tin { from { opacity:0; transform: translateY(-5px); } to { opacity:1; transform:none; } }

/* ── Stats ── */
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 22px; }
.sc {
  background: var(--surf); border: 1px solid var(--border);
  border-radius: var(--r); padding: 14px 16px;
}
.sc .sl { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-bottom: 4px; }
.sc .sv { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; }
.sc.c1 .sv { color: var(--accent); }
.sc.c2 .sv { color: var(--blue); }
.sc.c3 .sv { color: var(--purple); }
.sc.c4 .sv { font-size: 14px; color: var(--success); margin-top: 2px; }

/* ── Toolbar ── */
.toolbar {
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 13px; flex-wrap: wrap;
}
.toolbar-title { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; margin-right: auto; }

.sbox {
  display: flex; align-items: center; gap: 6px;
  background: var(--surf2); border: 1px solid var(--border);
  border-radius: var(--r); padding: 0 11px; height: 32px; min-width: 210px;
}
.sbox span { color: var(--muted); font-size: 14px; flex-shrink: 0; }
.sbox input {
  border: none; background: transparent; outline: none;
  color: var(--text); font-family: 'DM Mono', monospace; font-size: 12px; width: 100%;
}
.sbox input::placeholder { color: var(--muted); }

/* ── Buttons ── */
.btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 0 14px; height: 32px; border-radius: var(--r);
  font-family: 'DM Mono', monospace; font-size: 12px;
  border: 1px solid var(--border);
  background: var(--surf2); color: var(--text);
  cursor: pointer; text-decoration: none;
  transition: opacity .15s, transform .1s; white-space: nowrap;
}
.btn:hover  { opacity: .8; }
.btn:active { transform: scale(.97); }
.btn-primary { background: var(--accent); border-color: var(--accent); color: #000; font-weight: 500; }
.btn-blue    { background: rgba(59,130,246,.15); border-color: rgba(59,130,246,.3); color: #93c5fd; }
.btn-danger  { background: rgba(239,68,68,.1);  border-color: rgba(239,68,68,.3);  color: #fca5a5; }
.btn-sm      { padding: 0 9px; height: 26px; font-size: 11px; }

/* ── Table ── */
.twrap {
  background: var(--surf); border: 1px solid var(--border);
  border-radius: var(--r); overflow: hidden;
}
.tscroll { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead tr { background: var(--surf2); }
th {
  padding: 10px 13px; text-align: left;
  font-size: 10px; letter-spacing: 1.1px; text-transform: uppercase;
  color: var(--muted); font-weight: 500;
  border-bottom: 1px solid var(--border); white-space: nowrap;
}
td {
  padding: 10px 13px; border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
tr:last-child td { border-bottom: none; }
tbody tr:hover { background: var(--surf2); }

.idc   { font-size: 10px; color: var(--muted); }
.badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 10px; font-weight: 500; white-space: nowrap; }
.bqok  { background: rgba(34,197,94,.12);  color: #86efac; }
.bqlow { background: rgba(239,68,68,.12);  color: #fca5a5; }
.bjenis{ background: rgba(59,130,246,.12); color: #93c5fd; }
.price { color: var(--accent); font-size: 12px; }
.acts  { display: flex; gap: 5px; }
.tdket { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--muted); }

.empty {
  text-align: center; padding: 44px;
  color: var(--muted); line-height: 1.9;
}

.tfoot {
  padding: 10px 14px; border-top: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
.tfoot span { font-size: 11px; color: var(--muted); }

/* ── Form ── */
.fc {
  background: var(--surf); border: 1px solid var(--border);
  border-radius: var(--r); padding: 24px; max-width: 660px;
}
.fc h2 {
  font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
  margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
}
.fc h2 b { color: var(--accent); font-weight: 700; }
.fg { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.ff { grid-column: 1 / -1; }
.field label {
  display: block; font-size: 10px; text-transform: uppercase;
  letter-spacing: 1px; color: var(--muted); margin-bottom: 5px;
}
.field input, .field textarea {
  width: 100%; background: var(--surf2); border: 1px solid var(--border);
  color: var(--text); border-radius: var(--r);
  padding: 8px 11px; font-family: 'DM Mono', monospace; font-size: 12px;
  outline: none; transition: border .18s; appearance: none;
}
.field input:focus, .field textarea:focus { border-color: var(--blue); }
.field textarea { resize: vertical; min-height: 68px; }
.fa { display: flex; gap: 8px; margin-top: 20px; }

.hint { margin-top: 14px; font-size: 11px; color: var(--muted); }
.hint code { color: var(--accent); }

/* ── DB Panel ── */
.db-banner {
  background: var(--surf); border: 1px solid var(--border);
  border-radius: var(--r); padding: 14px 18px;
  display: flex; align-items: center; gap: 12px;
  margin-bottom: 18px; flex-wrap: wrap;
}
.db-banner .db-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.db-dot.online  { background: #34d399; box-shadow: 0 0 6px #34d399; animation: pulse 2s infinite; }
.db-dot.offline { background: var(--danger); }
.db-banner .db-info { flex: 1; font-size: 11px; color: var(--muted); line-height: 1.7; }
.db-banner .db-info strong { color: var(--text); }
.db-import-all { margin-left: auto; }

.db-table-wrap { background: var(--surf); border: 1px solid var(--border); border-radius: var(--r); overflow: hidden; margin-top: 4px; }
.db-already { color: #34d399; font-size: 10px; }

/* ── Responsive ── */
@media (max-width: 640px) {
  .hd   { padding: 11px 14px; }
  .hd-tag { display: none; }
  main  { padding: 14px 10px; }
  .stats{ grid-template-columns: 1fr 1fr; }
  .sc.c4 .sv { font-size: 12px; }
  .fg   { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<header class="hd">
  <div class="hd-logo">📦</div>
  <div class="hd-title">Inven<b>taris</b></div>
  <div class="hd-tag">session-market.json</div>
</header>

<main>

<?php if ($msg): ?>
  <div class="toast <?= $msgType ?>">
    <span><?= $msgType === 'success' ? '✓' : '✕' ?></span>
    <span><?= $msg ?></span>
  </div>
<?php endif; ?>

<!-- ═══════════════════════  LIST  ═══════════════════════ -->
<?php if ($action === 'list'): ?>

  <div class="stats">
    <div class="sc c1"><div class="sl">Total Item</div><div class="sv"><?= $totalItems ?></div></div>
    <div class="sc c2"><div class="sl">Total Qty</div><div class="sv"><?= number_format($totalQty, 0, ',', '.') ?></div></div>
    <div class="sc c3"><div class="sl">Kategori</div><div class="sv"><?= $totalJenis ?></div></div>
    <div class="sc c4"><div class="sl">Total HPP</div><div class="sv"><?= rp($totalHPP) ?></div></div>
  </div>

  <div class="toolbar">
    <div class="toolbar-title">Daftar Item</div>
    <form method="get" style="display:contents">
      <div class="sbox">
        <span>⌕</span>
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Cari nama, jenis...">
      </div>
      <button type="submit" class="btn">Cari</button>
      <?php if ($q): ?>
        <a href="?" class="btn">✕ Reset</a>
      <?php endif; ?>
    </form>
    <a href="?action=add" class="btn btn-primary">+ Tambah Item</a>
    <a href="?action=db_browse" class="btn btn-blue">🗄 Import dari DB</a>
  </div>

  <div class="twrap">
    <div class="tscroll">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Nama Item</th>
            <th>Jenis</th>
            <th>Qty</th>
            <th>HPP</th>
            <th>Keterangan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7">
              <div class="empty">
                <?php if ($q): ?>
                  Tidak ada hasil untuk "<strong><?= h($q) ?></strong>"
                <?php else: ?>
                  Belum ada data.<br>Klik <strong>+ Tambah Item</strong> untuk mulai.
                <?php endif; ?>
              </div>
            </td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><span class="idc">#<?= (int)$r['Id'] ?></span></td>
              <td><strong><?= h($r['Item_Name'] ?? '') ?></strong></td>
              <td>
                <?php if (!empty($r['Jenis']) && $r['Jenis'] !== '-'): ?>
                  <span class="badge bjenis"><?= h($r['Jenis']) ?></span>
                <?php else: ?>
                  <span style="color:var(--muted)">—</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge <?= (int)$r['Quantity'] <= 5 ? 'bqlow' : 'bqok' ?>">
                  <?= number_format((int)$r['Quantity'], 0, ',', '.') ?>
                </span>
              </td>
              <td class="price"><?= rp($r['HPP'] ?? 0) ?></td>
              <td class="tdket"><?= h(mb_strimwidth($r['Keterangan'] ?? '', 0, 45, '…')) ?></td>
              <td>
                <div class="acts">
                  <a href="?action=edit&id=<?= (int)$r['Id'] ?>" class="btn btn-blue btn-sm">✎ Edit</a>
                  <a href="?action=delete&id=<?= (int)$r['Id'] ?>"
                     class="btn btn-danger btn-sm"
                     onclick="return confirm('Hapus item ini?')">✕</a>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="tfoot">
      <span>
        <?= count($rows) ?> record
        <?= $q ? '&nbsp;·&nbsp; filter: "' . h($q) . '"' : '' ?>
      </span>
      <span>Qty ≤ 5 → <span style="color:#fca5a5">stok rendah</span></span>
    </div>
  </div>

  <p class="hint">
    💾 Data: <code><?= h(realpath($JSON_FILE) ?: $JSON_FILE) ?></code>
  </p>

<!-- ═══════════════════════  FORM ADD / EDIT  ═══════════════════════ -->
<?php elseif ($action === 'add' || $action === 'edit'): ?>

  <div style="margin-bottom:16px">
    <a href="?" class="btn">← Kembali</a>
  </div>

  <div class="fc">
    <h2>
      <?= $action === 'add' ? '+ Tambah' : '✎ Edit' ?>
      <b>Item Inventaris</b>
      <?php if ($action === 'edit'): ?>
        <span style="font-size:11px;color:var(--muted);font-weight:400">#<?= (int)$editRow['Id'] ?></span>
      <?php endif; ?>
    </h2>

    <form method="post" action="">
      <input type="hidden" name="_action" value="<?= $action ?>">
      <?php if ($action === 'edit'): ?>
        <input type="hidden" name="_id" value="<?= (int)$editRow['Id'] ?>">
      <?php endif; ?>

      <div class="fg">

        <div class="field ff">
          <label>Nama Item *</label>
          <input type="text" name="Item_Name" required maxlength="100"
                 value="<?= h($editRow['Item_Name'] ?? '') ?>"
                 placeholder="Contoh: Baut M8 × 30mm">
        </div>

        <div class="field">
          <label>Jenis / Kategori</label>
          <input type="text" name="Jenis" maxlength="50"
                 value="<?= h($editRow['Jenis'] ?? '') ?>"
                 placeholder="Mekanik, Elektrik, ...">
        </div>

        <div class="field">
          <label>Quantity</label>
          <input type="number" name="Quantity" min="0"
                 value="<?= (int)($editRow['Quantity'] ?? 0) ?>">
        </div>

        <div class="field ff">
          <label>HPP — Harga Pokok (Rp)</label>
          <input type="number" name="HPP" min="0" step="1"
                 value="<?= (float)($editRow['HPP'] ?? 0) ?>"
                 placeholder="0">
        </div>

        <div class="field ff">
          <label>Keterangan</label>
          <textarea name="Keterangan"
                    placeholder="Catatan tambahan tentang item ini..."><?= h($editRow['Keterangan'] ?? '') ?></textarea>
        </div>

      </div>

      <div class="fa">
        <button type="submit" class="btn btn-primary">
          <?= $action === 'add' ? '+ Simpan Item' : '✓ Update Item' ?>
        </button>
        <a href="?" class="btn">Batal</a>
      </div>
    </form>
  </div>

<!-- ═══════════════════════  DB BROWSE & IMPORT  ═══════════════════════ -->
<?php elseif ($action === 'db_browse'): ?>

  <div style="margin-bottom:16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <a href="?" class="btn">← Kembali ke Daftar</a>
    <div class="toolbar-title" style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700">
      Import dari Database
    </div>
  </div>

  <!-- Status koneksi DB -->
  <div class="db-banner">
    <div class="db-dot <?= $dbConnected ? 'online' : 'offline' ?>"></div>
    <div class="db-info">
      <strong><?= $dbConnected ? 'Database terhubung' : 'Database tidak dapat dijangkau' ?></strong><br>
      <?= h($db_name) ?> · tabel <code style="color:var(--accent)">Inventory</code> · host <code style="color:var(--accent)"><?= h($db_server) ?></code>
      <?php if ($dbConnected): ?>
        &nbsp;·&nbsp; <span style="color:#34d399"><?= $dbTotal ?> item ditemukan</span>
      <?php endif; ?>
    </div>
    <?php if ($dbConnected && $dbRows): ?>
      <a href="?action=db_import_all"
         class="btn btn-primary db-import-all"
         onclick="return confirm('Import semua <?= $dbTotal ?> item dari DB ke JSON? Item yang sudah ada akan diperbarui.')">
        ⬇ Import Semua (<?= $dbTotal ?>)
      </a>
    <?php endif; ?>
  </div>

  <?php if (!$dbConnected): ?>
    <div class="toast error">
      <span>✕</span>
      <span>Tidak dapat terhubung ke database <strong><?= h($db_name) ?></strong>. Pastikan server MySQL aktif dan kredensial benar.</span>
    </div>

  <?php else: ?>

    <!-- Search di DB -->
    <div class="toolbar" style="margin-bottom:13px">
      <form method="get" style="display:contents">
        <input type="hidden" name="action" value="db_browse">
        <div class="sbox">
          <span>⌕</span>
          <input type="text" name="dbq" value="<?= h($dbQSearch) ?>" placeholder="Cari nama, jenis di DB...">
        </div>
        <button type="submit" class="btn">Cari</button>
        <?php if ($dbQSearch): ?>
          <a href="?action=db_browse" class="btn">✕ Reset</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Tabel item dari DB -->
    <div class="db-table-wrap">
      <div class="tscroll">
        <table>
          <thead>
            <tr>
              <th>ID DB</th>
              <th>Nama Item</th>
              <th>Jenis</th>
              <th>Qty</th>
              <th>HPP</th>
              <th>No. Rak</th>
              <th>No. Gudang</th>
              <th>Keterangan</th>
              <th>Status JSON</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$dbRows): ?>
              <tr><td colspan="10">
                <div class="empty">
                  <?= $dbQSearch ? 'Tidak ada hasil untuk "' . h($dbQSearch) . '"' : 'Tabel Inventory kosong.' ?>
                </div>
              </td></tr>
            <?php else:
              $jsonNames = array_map('mb_strtolower', array_column($data, 'Item_Name'));
              foreach ($dbRows as $r):
                $alreadyInJson = in_array(mb_strtolower($r['Item_Name']), $jsonNames);
            ?>
              <tr>
                <td><span class="idc">#<?= (int)$r['Id'] ?></span></td>
                <td><strong style="color:var(--text)"><?= h($r['Item_Name']) ?></strong></td>
                <td>
                  <?php if (!empty($r['Jenis'])): ?>
                    <span class="badge bjenis"><?= h($r['Jenis']) ?></span>
                  <?php else: ?>
                    <span style="color:var(--muted)">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= (int)$r['Quantity'] <= 5 ? 'bqlow' : 'bqok' ?>">
                    <?= number_format((int)$r['Quantity'], 0, ',', '.') ?>
                  </span>
                </td>
                <td class="price"><?= rp((float)$r['HPP']) ?></td>
                <td><?= ($r['No_Rak'] !== null && $r['No_Rak'] !== '') ? '<span class="badge brak">Rak ' . h($r['No_Rak']) . '</span>' : '<span style="color:var(--muted)">—</span>' ?></td>
                <td><?= ($r['No_Gudang'] !== null && $r['No_Gudang'] !== '') ? '<span class="badge brak">Gdg ' . h($r['No_Gudang']) . '</span>' : '<span style="color:var(--muted)">—</span>' ?></td>
                <td class="tdket"><?= h(mb_strimwidth($r['Keterangan'] ?? '', 0, 35, '…')) ?></td>
                <td>
                  <?php if ($alreadyInJson): ?>
                    <span class="db-already">✓ Ada di JSON</span>
                  <?php else: ?>
                    <span style="color:var(--muted);font-size:10px">Belum diimpor</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="?action=db_import_one&id=<?= (int)$r['Id'] ?>"
                     class="btn btn-blue btn-sm"
                     onclick="return confirm('<?= $alreadyInJson ? 'Update' : 'Import' ?> item \'<?= h(addslashes($r['Item_Name'])) ?>\' ke JSON?')">
                    <?= $alreadyInJson ? '↻ Update' : '⬇ Import' ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <div class="tfoot">
        <span><?= $dbTotal ?> item di database<?= $dbQSearch ? ' · filter: "' . h($dbQSearch) . '"' : '' ?></span>
        <?php
          $jsonNames2 = array_map('mb_strtolower', array_column($data, 'Item_Name'));
          $alreadyCount = count(array_filter($dbRows, fn($r) => in_array(mb_strtolower($r['Item_Name']), $jsonNames2)));
        ?>
        <span style="color:#34d399"><?= $alreadyCount ?> sudah ada di JSON</span>
      </div>
    </div>

    <p class="hint" style="margin-top:14px">
      💡 <strong style="color:var(--text)">Import</strong> = salin item dari DB ke <code><?= h(realpath($JSON_FILE) ?: $JSON_FILE) ?></code>.
      Item yang sudah ada akan diperbarui Qty &amp; HPP-nya. Field <code>No_Rak</code> &amp; <code>No_Gudang</code> tidak disimpan ke JSON.
    </p>

  <?php endif; ?>

<?php endif; ?>

</main>
</body>
</html>