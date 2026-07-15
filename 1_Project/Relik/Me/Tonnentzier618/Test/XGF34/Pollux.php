<?php
date_default_timezone_set('Asia/Jakarta');

$xano_base = "https://x8ki-letl-twmt.n7.xano.io/api:ZBg3zfDx";
$kwitansi_api = $xano_base . "/kwitansi";

function http_json_get($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
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

function to_ts($v) {
    if (!$v) return null;
    if (is_numeric($v)) {
        $v = (int)$v;
        // Xano returns created_at in milliseconds — convert to seconds
        if ($v > 1e10) $v = intdiv($v, 1000);
        return $v;
    }
    $ts = strtotime($v);
    return $ts ?: null;
}

function pick_label($row) {
    return $row['Nama'] ?? $row['Nama_Pengirim'] ?? $row['Nama_Penerima'] ?? $row['Terima'] ?? '-';
}

function pick_amount($row) {
    return $row['Nominal'] ?? $row['Monetasi'] ?? $row['Saldo'] ?? 0;
}

function pick_note($row) {
    return $row['Keterangan'] ?? $row['Tujuan'] ?? $row['Dari'] ?? '-';
}

function pick_date($row) {
    return $row['created_at'] ?? $row['Registration'] ?? null;
}

[$kwitansi_rows, $err] = http_json_get($kwitansi_api);
if ($err || !is_array($kwitansi_rows)) {
    $kwitansi_rows = [];
}

usort($kwitansi_rows, function($a, $b) {
    return to_ts(pick_date($b)) <=> to_ts(pick_date($a));
});

$latest_rows = array_slice($kwitansi_rows, 0, 5);

$daily = [];
for ($i = 3; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i day"));
    $daily[$day] = 0;
}

foreach ($kwitansi_rows as $row) {
    $ts = to_ts(pick_date($row));
    if (!$ts) continue;
    $day = date('Y-m-d', $ts);
    if (isset($daily[$day])) {
        $daily[$day] += (float)pick_amount($row);
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
    <title>Dashboard Kwitansi</title>
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
        @media(max-width:900px){ .topbox,.grid{ grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="topbox">
        <div class="mini">
            <div class="muted">Total Data Kwitansi</div>
            <div class="val"><?= count($kwitansi_rows) ?></div>
        </div>
        <div class="mini">
            <div class="muted">5 Transaksi Terbaru</div>
            <div class="val"><?= count($latest_rows) ?></div>
        </div>
        <div class="mini">
            <div class="muted">Unit Transaksi</div>
            <div class="val">point</div>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <h2>Transaksi 4 Hari Terakhir</h2>
            <canvas id="trendChart" height="120"></canvas>
        </div>
        <div class="card">
            <h2>Ringkasan</h2>
            <p class="muted">Data diambil langsung dari API Kwitansi.</p>
            <p class="muted">Tampilan mengikuti layout versi SQL.</p>
        </div>
    </div>

    <div class="card">
        <h2>5 Data Transaksi Terbaru dari Kwitansi</h2>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Keterangan</th>
                    <th>Nominal</th>
                    <th>Unit</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($latest_rows as $row): ?>
                <tr>
                    <td>
                        <?php
                            $ts = to_ts(pick_date($row));
                            echo $ts ? safe(date('Y-m-d H:i:s', $ts)) : '-';
                        ?>
                    </td>
                    <td><?= safe(pick_label($row)) ?></td>
                    <td><?= safe(pick_note($row)) ?></td>
                    <td><?= fmt_point(pick_amount($row)) ?></td>
                    <td><span class="badge">point</span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Kwitansi point',
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