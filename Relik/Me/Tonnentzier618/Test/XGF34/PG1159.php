<?php
date_default_timezone_set('Asia/Jakarta');

$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_e0";

$xano_base = "https://x8ki-letl-twmt.n7.xano.io/api:ZBg3zfDx";
$kwitansi_api = $xano_base . "/kwitansi";

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

function http_json($method, $url, $data = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return [null, $err];
    return [json_decode($res, true), null];
}

function safe($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function fmt_point($v) {
    return number_format((float)$v, 0, ',', '.') . ' point';
}

function dt_to_ts($v) {
    if (!$v) return null;
    if (is_numeric($v)) return (int)$v;
    $ts = strtotime($v);
    return $ts ?: null;
}

$spica_rows = [];
$res = $conn->query("SELECT * FROM spica ORDER BY Registration DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) $spica_rows[] = $row;
}

$formal_rows = [];
$res2 = $conn->query("SELECT * FROM formalhault ORDER BY Saldo DESC");
if ($res2) {
    while ($row = $res2->fetch_assoc()) $formal_rows[] = $row;
}

$latest_spica = array_slice($spica_rows, 0, 5);

$daily = [];
for ($i = 3; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i day"));
    $daily[$day] = 0;
}

foreach ($spica_rows as $row) {
    $ts = dt_to_ts($row['Registration'] ?? null);
    if (!$ts) continue;
    $day = date('Y-m-d', $ts);
    if (isset($daily[$day])) {
        $daily[$day] += (float)($row['Monetasi'] ?? 0);
    }
}

$top_formal = $formal_rows[0] ?? null;

$sync_message = '';
$sync_errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_spica'])) {
    $synced = 0;

    foreach ($latest_spica as $row) {
        $payload = [
            'Nama_Pengirim' => $row['Dari']   ?? '',
            'Nama_Penerima' => $row['Terima'] ?? '',
            'Keterangan'    => trim(($row['Dari'] ?? '') . ' -> ' . ($row['Tujuan'] ?? '')),
            'Nominal'       => (float)($row['Monetasi'] ?? 0),
        ];

        [$resSync, $errSync] = http_json('POST', $kwitansi_api, $payload);
        if ($errSync) {
            $sync_errors[] = $errSync;
        } else {
            $synced++;
        }
    }

    $sync_message = "Sinkronisasi selesai: {$synced} transaksi dikirim ke Kwitansi.";
    if ($sync_errors) {
        $sync_message .= " " . count($sync_errors) . " gagal.";
    }
}

$chart_labels = array_keys($daily);
$chart_values = array_values($daily);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Spica</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background:#f5f7fb; margin:0; color:#1f2937; }
        .wrap { max-width:1280px; margin:0 auto; padding:24px; }
        .topbox { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:20px; }
        .mini, .card { background:#fff; border-radius:16px; box-shadow:0 8px 24px rgba(0,0,0,.06); }
        .mini { padding:18px; }
        .card { padding:20px; margin-top:20px; }
        .val { font-size:24px; font-weight:700; margin-top:8px; }
        .muted { color:#6b7280; font-size:13px; }
        .grid { display:grid; grid-template-columns:2fr 1fr; gap:20px; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:14px; }
        th { background:#f9fafb; }
        .badge { display:inline-block; padding:6px 10px; border-radius:999px; background:#eef2ff; color:#4338ca; font-size:12px; }
        .btn { border:0; padding:12px 16px; border-radius:10px; background:#2563eb; color:#fff; cursor:pointer; }
        .btn:hover { background:#1d4ed8; }
        .alert-ok  { background:#ecfdf5; border:1px solid #a7f3d0; }
        .alert-err { background:#fef2f2; border:1px solid #fca5a5; }
        @media(max-width:900px){ .topbox,.grid{ grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="topbox">
        <div class="mini">
            <div class="muted">Total Transaksi Spica</div>
            <div class="val"><?= count($spica_rows) ?></div>
        </div>
        <div class="mini">
            <div class="muted">Total Formalhault</div>
            <div class="val"><?= count($formal_rows) ?></div>
        </div>
        <div class="mini">
            <div class="muted">Saldo Tertinggi</div>
            <div class="val"><?= $top_formal ? safe($top_formal['Nama'] ?? '-') : '-' ?></div>
        </div>
    </div>

    <?php if ($sync_message): ?>
        <div class="card <?= $sync_errors ? 'alert-err' : 'alert-ok' ?>">
            <?= safe($sync_message) ?>
            <?php foreach ($sync_errors as $e): ?>
                <div class="muted" style="margin-top:4px;">⚠ <?= safe($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2>Transaksi 4 Hari Terakhir</h2>
            <canvas id="trendChart" height="120"></canvas>
        </div>
        <div class="card">
            <h2>Akun dengan Saldo Tertinggi</h2>
            <?php if ($top_formal): ?>
                <p><strong><?= safe($top_formal['Nama'] ?? '-') ?></strong></p>
                <p class="muted">RegNo: <?= safe($top_formal['RegNo'] ?? '-') ?></p>
                <p class="muted">Saldo: <strong><?= fmt_point($top_formal['Saldo'] ?? 0) ?></strong></p>
                <p class="muted">Hutang: <?= fmt_point($top_formal['Hutang'] ?? 0) ?></p>
                <p class="muted">Modal: <?= fmt_point($top_formal['Modal'] ?? 0) ?></p>
            <?php else: ?>
                <p>Tidak ada data formalhault.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2>5 Transaksi Terbaru dari Spica</h2>
        <table>
            <thead>
                <tr>
                    <th>Registration</th>
                    <th>Terima</th>
                    <th>Dari</th>
                    <th>Tujuan</th>
                    <th>Monetasi</th>
                    <th>Unit</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($latest_spica as $row): ?>
                <tr>
                    <td><?= safe($row['Registration'] ?? '-') ?></td>
                    <td><?= safe($row['Terima'] ?? '-') ?></td>
                    <td><?= safe($row['Dari'] ?? '-') ?></td>
                    <td><?= safe($row['Tujuan'] ?? '-') ?></td>
                    <td><?= fmt_point($row['Monetasi'] ?? 0) ?></td>
                    <td><span class="badge">point</span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Sinkronisasi Spica ke Kwitansi</h2>
        <form method="post">
            <button class="btn" type="submit" name="sync_spica" value="1">Sinkronkan 5 Transaksi Terbaru</button>
        </form>
        <p class="muted" style="margin-top:12px;">
            Data diambil dari SQL tabel <code>spica</code>, lalu dikirim ke endpoint Xano <code>kwitansi</code>.<br>
            Field yang dikirim: <code>Nama_Pengirim</code> (Dari), <code>Nama_Penerima</code> (Terima), <code>Keterangan</code>, <code>Nominal</code> (Monetasi).
        </p>
    </div>
</div>

<script>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Transaksi point',
            data: <?= json_encode($chart_values) ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,0.15)',
            fill: true,
            tension: 0.35
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>