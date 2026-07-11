<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ─────────────────────────────────────────────────────────────────────────────
//  Inventory — MySQL Edition
//  URL  : https://c.oort678.my.id/HD140283/Sirius/inventory-db.php
//  DB   : oortmyid_cv  |  Tabel: inventory
// ─────────────────────────────────────────────────────────────────────────────
$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_cv";

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die('<div style="font-family:monospace;padding:30px;color:#f87171;background:#0b0d12">
         ✕ Koneksi gagal: ' . htmlspecialchars($conn->connect_error) . '</div>');
}
$conn->set_charset('utf8mb4');

// ── Helpers ───────────────────────────────────────────────────────────────────
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp(float $n): string { return 'Rp&nbsp;' . number_format($n, 0, ',', '.'); }

// ── Bootstrap ─────────────────────────────────────────────────────────────────
$action  = $_GET['action'] ?? 'list';
$id      = (int)($_GET['id'] ?? 0);
$msg     = '';
$msgType = '';

// ── POST: tambah / edit ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pa = $_POST['_action'] ?? '';

    $item_name  = $conn->real_escape_string(trim($_POST['Item_Name']  ?? ''));
    $quantity   = (int)($_POST['Quantity']  ?? 1);
    $hpp        = (float)($_POST['HPP']     ?? 1);
    $no_rak     = $_POST['No_Rak']   !== '' ? (int)$_POST['No_Rak']   : 'NULL';
    $no_gudang  = $_POST['No_Gudang'] !== '' ? (int)$_POST['No_Gudang'] : 'NULL';
    $jenis      = $conn->real_escape_string(trim($_POST['Jenis']      ?? ''));
    $keterangan = $conn->real_escape_string(trim($_POST['Keterangan'] ?? ''));

    $no_rak_sql    = is_int($no_rak)    ? $no_rak    : 'NULL';
    $no_gudang_sql = is_int($no_gudang) ? $no_gudang : 'NULL';

    if ($pa === 'add') {
        $sql = "INSERT INTO Inventory
                  (Item_Name, Quantity, HPP, No_Rak, No_Gudang, Jenis, Keterangan)
                VALUES
                  ('$item_name', $quantity, $hpp, $no_rak_sql, $no_gudang_sql, '$jenis', '$keterangan')";
        if ($conn->query($sql)) {
            $msg     = 'Item <strong>' . h($item_name) . '</strong> berhasil ditambahkan. (ID: ' . $conn->insert_id . ')';
            $msgType = 'success';
        } else {
            $msg     = 'Error: ' . h($conn->error);
            $msgType = 'error';
        }
        $action = 'list';

    } elseif ($pa === 'edit') {
        $eid = (int)($_POST['_id'] ?? 0);
        $sql = "UPDATE Inventory SET
                  Item_Name='$item_name', Quantity=$quantity, HPP=$hpp,
                  No_Rak=$no_rak_sql, No_Gudang=$no_gudang_sql,
                  Jenis='$jenis', Keterangan='$keterangan'
                WHERE Id=$eid";
        if ($conn->query($sql)) {
            $msg     = 'Item berhasil diperbarui.';
            $msgType = 'success';
        } else {
            $msg     = 'Error: ' . h($conn->error);
            $msgType = 'error';
        }
        $action = 'list';
    }
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete' && $id > 0) {
    if ($conn->query("DELETE FROM Inventory WHERE Id=$id")) {
        $msg     = 'Item berhasil dihapus.';
        $msgType = 'success';
    } else {
        $msg     = 'Error: ' . h($conn->error);
        $msgType = 'error';
    }
    $action = 'list';
}

// ── EDIT: ambil baris ─────────────────────────────────────────────────────────
$editRow = null;
if ($action === 'edit' && $id > 0) {
    $res = $conn->query("SELECT * FROM Inventory WHERE Id=$id LIMIT 1");
    if ($res) $editRow = $res->fetch_assoc();
    if (!$editRow) $action = 'list';
}

// ── LIST: data + search ───────────────────────────────────────────────────────
$q     = trim($_GET['q'] ?? '');
$rows  = [];
$total = ['items' => 0, 'qty' => 0, 'hpp' => 0, 'jenis' => 0];

if ($action === 'list') {
    $where = '';
    if ($q) {
        $qs    = $conn->real_escape_string($q);
        $where = "WHERE Item_Name LIKE '%$qs%' OR Jenis LIKE '%$qs%' OR Keterangan LIKE '%$qs%'";
    }
    $res = $conn->query("SELECT * FROM Inventory $where ORDER BY Id DESC");
    if ($res) {
        while ($r = $res->fetch_assoc()) $rows[] = $r;
    }

    // stats (selalu dari semua data, bukan hasil filter)
    $st = $conn->query("SELECT COUNT(*) AS c, SUM(Quantity) AS q, SUM(HPP) AS h,
                               COUNT(DISTINCT Jenis) AS j FROM Inventory");
    if ($st) {
        $sv             = $st->fetch_assoc();
        $total['items'] = (int)$sv['c'];
        $total['qty']   = (int)$sv['q'];
        $total['hpp']   = (float)$sv['h'];
        $total['jenis'] = (int)$sv['j'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory DB · Sirius</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg:     #0a0c10;
  --surf:   #10131b;
  --surf2:  #171b27;
  --border: #1f2335;
  --acc:    #34d399;   /* hijau — beda dari versi JSON yang kuning */
  --acc2:   #059669;
  --blue:   #38bdf8;
  --danger: #f87171;
  --warn:   #fbbf24;
  --purple: #a78bfa;
  --text:   #dde1f0;
  --muted:  #4b5270;
  --r:      8px;
}
html, body { background: var(--bg); color: var(--text); font-family: 'DM Mono', monospace; font-size: 13px; min-height: 100vh; }

/* ── header ── */
.hd { position: sticky; top: 0; z-index: 50; background: var(--surf); border-bottom: 1px solid var(--border); padding: 12px 28px; display: flex; align-items: center; gap: 12px; }
.hd-logo { width: 32px; height: 32px; background: var(--acc); border-radius: 7px; display: grid; place-items: center; font-size: 15px; flex-shrink: 0; }
.hd-title { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 18px; letter-spacing: -.4px; }
.hd-title b { color: var(--acc); font-weight: 800; }
.hd-pill { font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); background: var(--surf2); border: 1px solid var(--border); padding: 3px 10px; border-radius: 20px; }
.hd-db { margin-left: auto; font-size: 10px; color: var(--muted); display: flex; align-items: center; gap: 6px; }
.hd-db span { width: 7px; height: 7px; border-radius: 50%; background: var(--acc); display: inline-block; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

main { max-width: 1280px; margin: 0 auto; padding: 24px 20px; }

/* ── toast ── */
.toast { padding: 11px 16px; border-radius: var(--r); margin-bottom: 18px; display: flex; align-items: flex-start; gap: 9px; font-size: 12px; line-height: 1.6; animation: tin .25s ease; }
.toast.success { background: rgba(52,211,153,.1); border: 1px solid rgba(52,211,153,.25); color: #6ee7b7; }
.toast.error   { background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.25); color: #fca5a5; }
@keyframes tin { from { opacity:0; transform: translateY(-5px); } to { opacity:1; transform:none; } }

/* ── stats ── */
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 22px; }
.sc { background: var(--surf); border: 1px solid var(--border); border-radius: var(--r); padding: 14px 16px; }
.sc .sl { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-bottom: 4px; }
.sc .sv { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; }
.sc.c1 .sv { color: var(--acc); }
.sc.c2 .sv { color: var(--blue); }
.sc.c3 .sv { color: var(--purple); }
.sc.c4 .sv { font-size: 14px; color: var(--warn); margin-top: 2px; }

/* ── toolbar ── */
.toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 13px; flex-wrap: wrap; }
.toolbar-title { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; margin-right: auto; }
.sbox { display: flex; align-items: center; gap: 6px; background: var(--surf2); border: 1px solid var(--border); border-radius: var(--r); padding: 0 11px; height: 32px; min-width: 210px; }
.sbox span { color: var(--muted); font-size: 14px; flex-shrink: 0; }
.sbox input { border: none; background: transparent; outline: none; color: var(--text); font-family: 'DM Mono', monospace; font-size: 12px; width: 100%; }
.sbox input::placeholder { color: var(--muted); }

/* ── buttons ── */
.btn { display: inline-flex; align-items: center; gap: 5px; padding: 0 14px; height: 32px; border-radius: var(--r); font-family: 'DM Mono', monospace; font-size: 12px; border: 1px solid var(--border); background: var(--surf2); color: var(--text); cursor: pointer; text-decoration: none; transition: opacity .15s, transform .1s; white-space: nowrap; }
.btn:hover  { opacity: .8; }
.btn:active { transform: scale(.97); }
.btn-primary { background: var(--acc);  border-color: var(--acc);    color: #052e16; font-weight: 500; }
.btn-blue    { background: rgba(56,189,248,.15); border-color: rgba(56,189,248,.3);  color: #7dd3fc; }
.btn-danger  { background: rgba(248,113,113,.1);  border-color: rgba(248,113,113,.3); color: #fca5a5; }
.btn-sm      { padding: 0 9px; height: 26px; font-size: 11px; }

/* ── table ── */
.twrap { background: var(--surf); border: 1px solid var(--border); border-radius: var(--r); overflow: hidden; }
.tscroll { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead tr { background: var(--surf2); }
th { padding: 10px 12px; text-align: left; font-size: 10px; letter-spacing: 1.1px; text-transform: uppercase; color: var(--muted); font-weight: 500; border-bottom: 1px solid var(--border); white-space: nowrap; }
td { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; white-space: nowrap; }
tr:last-child td { border-bottom: none; }
tbody tr:hover { background: var(--surf2); }

.idc   { font-size: 10px; color: var(--muted); }
.badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 10px; font-weight: 500; }
.bqok  { background: rgba(52,211,153,.12);  color: #6ee7b7; }
.bqlow { background: rgba(248,113,113,.12); color: #fca5a5; }
.bjenis{ background: rgba(56,189,248,.12);  color: #7dd3fc; }
.brak  { background: rgba(167,139,250,.12); color: #c4b5fd; font-size: 10px; }
.price { color: var(--warn); font-size: 12px; }
.acts  { display: flex; gap: 5px; }
.tdket { max-width: 160px; overflow: hidden; text-overflow: ellipsis; color: var(--muted); }
.empty { text-align: center; padding: 44px; color: var(--muted); line-height: 1.9; }
.tfoot { padding: 10px 14px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.tfoot span { font-size: 11px; color: var(--muted); }

/* ── form ── */
.fc { background: var(--surf); border: 1px solid var(--border); border-radius: var(--r); padding: 24px; max-width: 700px; }
.fc h2 { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
.fc h2 b { color: var(--acc); font-weight: 700; }

/* 3-column grid untuk form */
.fg3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.fg2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.ff  { grid-column: 1 / -1; }
.fg-sec { margin-top: 18px; }
.fg-sec-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid var(--border); }

.field label { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-bottom: 5px; }
.field input, .field select, .field textarea {
  width: 100%; background: var(--surf2); border: 1px solid var(--border);
  color: var(--text); border-radius: var(--r);
  padding: 8px 11px; font-family: 'DM Mono', monospace; font-size: 12px;
  outline: none; transition: border .18s; appearance: none;
}
.field input:focus, .field select:focus, .field textarea:focus { border-color: var(--acc); }
.field textarea { resize: vertical; min-height: 68px; }
.field .hint-txt { font-size: 10px; color: var(--muted); margin-top: 4px; }
.fa { display: flex; gap: 8px; margin-top: 20px; }

.info-bar { margin-top: 14px; font-size: 11px; color: var(--muted); display: flex; align-items: center; gap: 6px; }
.info-bar code { color: var(--acc); }

@media (max-width: 640px) {
  .hd { padding: 11px 14px; }
  .hd-db { display: none; }
  main { padding: 14px 10px; }
  .stats { grid-template-columns: 1fr 1fr; }
  .sc.c4 .sv { font-size: 12px; }
  .fg3 { grid-template-columns: 1fr 1fr; }
  .fg2 { grid-template-columns: 1fr; }
}
@media (max-width: 400px) {
  .fg3 { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<header class="hd">
  <div class="hd-logo">🗄️</div>
  <div class="hd-title">Inventory <b>DB</b></div>
  <div class="hd-pill">MySQL</div>
  <div class="hd-db">
    <span></span>
    <?= h($db_name) ?> · Inventory
  </div>
</header>

<main>

<?php if ($msg): ?>
  <div class="toast <?= $msgType ?>">
    <span><?= $msgType === 'success' ? '✓' : '✕' ?></span>
    <span><?= $msg ?></span>
  </div>
<?php endif; ?>

<!-- ══════════════════════════  LIST  ══════════════════════════ -->
<?php if ($action === 'list'): ?>

  <div class="stats">
    <div class="sc c1"><div class="sl">Total Item</div><div class="sv"><?= number_format($total['items']) ?></div></div>
    <div class="sc c2"><div class="sl">Total Qty</div><div class="sv"><?= number_format($total['qty'], 0, ',', '.') ?></div></div>
    <div class="sc c3"><div class="sl">Kategori</div><div class="sv"><?= $total['jenis'] ?></div></div>
    <div class="sc c4"><div class="sl">Total HPP</div><div class="sv"><?= rp($total['hpp']) ?></div></div>
  </div>

  <div class="toolbar">
    <div class="toolbar-title">Daftar Item Inventaris</div>
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
  </div>

  <div class="twrap">
    <div class="tscroll">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama Item</th>
            <th>Jenis</th>
            <th>Qty</th>
            <th>HPP</th>
            <th>No. Rak</th>
            <th>No. Gudang</th>
            <th>Keterangan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="9">
              <div class="empty">
                <?php if ($q): ?>
                  Tidak ada hasil untuk "<strong><?= h($q) ?></strong>"
                <?php else: ?>
                  Belum ada data di tabel Inventory.<br>
                  Klik <strong>+ Tambah Item</strong> untuk mulai.
                <?php endif; ?>
              </div>
            </td></tr>
          <?php else: foreach ($rows as $r): ?>
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
              <td>
                <?php if ($r['No_Rak'] !== null && $r['No_Rak'] !== ''): ?>
                  <span class="badge brak">Rak <?= h($r['No_Rak']) ?></span>
                <?php else: ?>
                  <span style="color:var(--muted)">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($r['No_Gudang'] !== null && $r['No_Gudang'] !== ''): ?>
                  <span class="badge brak">Gdg <?= h($r['No_Gudang']) ?></span>
                <?php else: ?>
                  <span style="color:var(--muted)">—</span>
                <?php endif; ?>
              </td>
              <td class="tdket"><?= h(mb_strimwidth($r['Keterangan'] ?? '', 0, 40, '…')) ?></td>
              <td>
                <div class="acts">
                  <a href="?action=edit&id=<?= (int)$r['Id'] ?>" class="btn btn-blue btn-sm">✎ Edit</a>
                  <a href="?action=delete&id=<?= (int)$r['Id'] ?>"
                     class="btn btn-danger btn-sm"
                     onclick="return confirm('Hapus item #<?= (int)$r['Id'] ?> — <?= h(addslashes($r['Item_Name'])) ?>?')">✕</a>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="tfoot">
      <span>
        <?= count($rows) ?> record<?= $q ? ' &nbsp;·&nbsp; filter: "' . h($q) . '"' : '' ?>
      </span>
      <span>Qty ≤ 5 → <span style="color:#fca5a5">stok rendah</span></span>
    </div>
  </div>

  <div class="info-bar">
    🗄️ <code><?= h($db_name) ?></code> · tabel <code>Inventory</code> · host <code><?= h($db_server) ?></code>
  </div>

<!-- ══════════════════════════  FORM  ══════════════════════════ -->
<?php elseif ($action === 'add' || $action === 'edit'): ?>

  <div style="margin-bottom:16px">
    <a href="?" class="btn">← Kembali ke Daftar</a>
  </div>

  <div class="fc">
    <h2>
      <?= $action === 'add' ? '+ Tambah' : '✎ Edit' ?>
      <b>Item Inventaris</b>
      <?php if ($action === 'edit'): ?>
        <span style="font-size:11px;color:var(--muted);font-weight:400">
          · ID #<?= (int)$editRow['Id'] ?>
        </span>
      <?php endif; ?>
    </h2>

    <form method="post" action="">
      <input type="hidden" name="_action" value="<?= $action ?>">
      <?php if ($action === 'edit'): ?>
        <input type="hidden" name="_id" value="<?= (int)$editRow['Id'] ?>">
      <?php endif; ?>

      <!-- Identitas item -->
      <div class="fg-sec">
        <div class="fg-sec-label">Identitas Item</div>
        <div class="fg2">
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
                   placeholder="Mekanik, Elektrik, Consumable...">
          </div>
          <div class="field">
            <label>Keterangan</label>
            <input type="text" name="Keterangan"
                   value="<?= h($editRow['Keterangan'] ?? '') ?>"
                   placeholder="Catatan singkat tentang item">
          </div>
        </div>
      </div>

      <!-- Stok & Harga -->
      <div class="fg-sec">
        <div class="fg-sec-label">Stok &amp; Harga</div>
        <div class="fg3">
          <div class="field">
            <label>Quantity</label>
            <input type="number" name="Quantity" min="0"
                   value="<?= (int)($editRow['Quantity'] ?? 1) ?>">
            <div class="hint-txt">Default: 1</div>
          </div>
          <div class="field">
            <label>HPP — Harga Pokok (Rp)</label>
            <input type="number" name="HPP" min="0" step="1"
                   value="<?= (float)($editRow['HPP'] ?? 1) ?>">
            <div class="hint-txt">Default: 1</div>
          </div>
        </div>
      </div>

      <!-- Lokasi Gudang -->
      <div class="fg-sec">
        <div class="fg-sec-label">Lokasi Penyimpanan</div>
        <div class="fg3">
          <div class="field">
            <label>No. Rak</label>
            <input type="number" name="No_Rak" min="0"
                   value="<?= h($editRow['No_Rak'] ?? '') ?>"
                   placeholder="Opsional">
          </div>
          <div class="field">
            <label>No. Gudang</label>
            <input type="number" name="No_Gudang" min="0"
                   value="<?= h($editRow['No_Gudang'] ?? '') ?>"
                   placeholder="Opsional">
          </div>
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

<?php endif; ?>

</main>
</body>
</html>
<?php $conn->close(); ?>