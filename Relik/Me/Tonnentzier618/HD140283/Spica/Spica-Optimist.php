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

/* ================== NAMA LIST ================== */
$list_nama = [];
$q = mysqli_query($conn, "SELECT Nama FROM formalhault ORDER BY Nama ASC");
while ($r = mysqli_fetch_assoc($q)) {
    $list_nama[] = $r['Nama'];
}

/* ================== PROSES SUBMIT ================== */
$message = '';

if (isset($_POST['submit'])) {
    $Dari    = $_POST['Spica-F'];
    $Terima  = $_POST['Spica-R'];
    $Nominal = intval($_POST['Spica-M']);
    $Tujuan  = $_POST['Spica-O'];
    $Senang  = intval($_POST['Spica-A4']);
    $Sedih   = intval($_POST['Spica-F2']);
    $Grief   = intval($_POST['Spica-N0N4']);
    $Bobot   = isset($bobotMap[$Tujuan]) ? $bobotMap[$Tujuan] : 0;

    /* Insert spica log */
    mysqli_query($conn, "
        INSERT INTO spica (Dari,Terima,Monetasi,Tujuan,Senang,Sedih,Grief,Prioritas)
        VALUES ('$Dari','$Terima',$Nominal,'$Tujuan',$Senang,$Sedih,$Grief,4)
    ");

    /* Credit Terima */
    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Nama,Saldo FROM formalhault WHERE Nama='$Terima'"));
    $saldo_baru = $r['Saldo'] + $Nominal;
    mysqli_query($conn, "UPDATE formalhault SET Saldo=$saldo_baru WHERE Nama='$Terima'");
    $message .= "<div class='result-card'>
        <span class='result-label'>Akun</span><span class='result-val'>{$r['Nama']}</span>
        <span class='result-label'>Saldo</span><span class='result-val'>{$r['Saldo']}</span>
        <span class='result-label'>Terima</span><span class='result-val'>{$Nominal}</span>
        <span class='result-label'>Bobot Tujuan</span><span class='result-val'>{$Bobot}</span>
        <span class='result-label'>Saldo Akhir</span><span class='result-val highlight'>{$saldo_baru}</span>
    </div>";

    /* Debit Dari */
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
<title>Spica</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@300;400;500&display=swap');

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:      #0d0f14;
    --surface: #141720;
    --border:  #252a38;
    --accent:  #7eb8f7;
    --accent2: #f7c97e;
    --text:    #e8ecf4;
    --muted:   #6b7494;
    --danger:  #f77e7e;
    --success: #7ef7b8;
    --radius:  10px;
  }

  body.day {
    --bg:      #f0f2f8;
    --surface: #ffffff;
    --border:  #d0d5e8;
    --text:    #111827;
    --muted:   #6b7280;
  }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Mono', monospace;
    min-height: 100vh;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 32px 16px 60px;
    transition: background .3s, color .3s;
  }

  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 36px 40px;
    width: 100%;
    max-width: 560px;
    box-shadow: 0 8px 48px rgba(0,0,0,.4);
  }

  .topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
  }
  .btn-nav {
    padding: 7px 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--bg);
    color: var(--muted);
    font-family: 'DM Mono', monospace;
    font-size: .75rem;
    cursor: pointer;
    transition: border-color .2s, color .2s;
  }
  .btn-nav:hover { border-color: var(--accent); color: var(--accent); }

  h1 {
    font-family: 'DM Serif Display', serif;
    font-size: 1.9rem;
    color: var(--accent);
    letter-spacing: .02em;
  }
  .subtitle {
    font-size: .7rem;
    color: var(--muted);
    letter-spacing: .14em;
    text-transform: uppercase;
    margin-bottom: 32px;
  }

  .field { margin-bottom: 22px; }
  .field > label {
    display: block;
    font-size: .68rem;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 8px;
  }

  select {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    font-family: 'DM Mono', monospace;
    font-size: .85rem;
    padding: 10px 14px;
    outline: none;
    appearance: none;
    transition: border-color .2s;
  }
  select:focus { border-color: var(--accent); }

  .monetasi-group {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
  }
  .monetasi-group input[type="radio"] { display: none; }
  .monetasi-group label {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 9px 4px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg);
    color: var(--muted);
    font-size: .75rem;
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

  .bobot-badge {
    display: inline-block;
    margin-top: 8px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .68rem;
    letter-spacing: .1em;
    background: rgba(126,184,247,.1);
    color: var(--accent);
    border: 1px solid rgba(126,184,247,.25);
    transition: opacity .2s;
  }
  .bobot-badge.hidden { opacity: 0; pointer-events: none; }

  .scale { display: flex; flex-wrap: wrap; gap: 7px; }
  .scale button {
    width: 38px;
    height: 38px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--bg);
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

  .divider { border: none; border-top: 1px solid var(--border); margin: 26px 0; }

  #validation-error {
    color: var(--danger);
    font-size: .73rem;
    margin-top: 6px;
    display: none;
  }

  .btn-submit {
    width: 100%;
    margin-top: 8px;
    padding: 13px;
    background: var(--accent);
    color: #0d0f14;
    border: none;
    border-radius: var(--radius);
    font-family: 'DM Mono', monospace;
    font-size: .88rem;
    font-weight: 500;
    letter-spacing: .08em;
    cursor: pointer;
    transition: opacity .2s, transform .1s;
  }
  .btn-submit:hover:not(:disabled) { opacity: .85; transform: translateY(-1px); }
  .btn-submit:disabled { opacity: .38; cursor: not-allowed; }

  .results { margin-top: 28px; }
  .result-card {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 16px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 16px 20px;
    margin-bottom: 12px;
    font-size: .8rem;
  }
  .result-card.sender { border-color: rgba(247,201,126,.3); }
  .result-label { color: var(--muted); }
  .result-val { color: var(--text); text-align: right; }
  .result-val.highlight { color: var(--success); font-weight: 500; }

  @media(max-width:480px){
    .card { padding: 24px 18px; }
  }
</style>
</head>
<body>
<div class="card">

  <div class="topbar">
    <button class="btn-nav" onclick="location.href='menu.php'">⬅ Menu</button>
    <button class="btn-nav" onclick="toggleMode()">🌗 Mode</button>
  </div>

  <h1>Spica</h1>
  <p class="subtitle">Transaction Entry</p>

  <form method="POST" id="spica-form">

    <div class="field">
      <label for="dari-select">Dari</label>
      <select name="Spica-F" id="dari-select" required>
        <option value="">— Pilih Dari —</option>
        <?php foreach ($list_nama as $n): ?>
          <option value="<?= htmlspecialchars($n) ?>"><?= htmlspecialchars($n) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="terima-select">Penerima</label>
      <select name="Spica-R" id="terima-select" required>
        <option value="">— Pilih Penerima —</option>
        <?php foreach ($list_nama as $n): ?>
          <option value="<?= htmlspecialchars($n) ?>"><?= htmlspecialchars($n) ?></option>
        <?php endforeach; ?>
      </select>
      <div id="validation-error">Terima dan Dari tidak boleh sama!</div>
    </div>

    <div class="field">
      <label>Monetasi</label>
      <div class="monetasi-group">
        <?php for ($v = 10; $v <= 100; $v += 10): ?>
          <input type="radio" name="Spica-M" id="mon-<?= $v ?>" value="<?= $v ?>" required>
          <label for="mon-<?= $v ?>"><?= $v ?></label>
        <?php endfor; ?>
      </div>
    </div>

    <div class="field">
      <label for="tujuan-select">Tujuan</label>
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

    <div class="field">
      <label>😊 Senang (0–10)</label>
      <div class="scale" data-max="10" data-target="A4"></div>
      <input type="hidden" id="A4" name="Spica-A4" value="0">
    </div>

    <div class="field">
      <label>😔 Sedih (0–10)</label>
      <div class="scale" data-max="10" data-target="F2"></div>
      <input type="hidden" id="F2" name="Spica-F2" value="0">
    </div>

    <div class="field">
      <label>🖤 Grief (0–7)</label>
      <div class="scale" data-max="7" data-target="N0N4"></div>
      <input type="hidden" id="N0N4" name="Spica-N0N4" value="0">
    </div>

    <button type="submit" name="submit" class="btn-submit" id="submit-btn">Kirim</button>
  </form>

  <?php if ($message): ?>
    <div class="results"><?= $message ?></div>
  <?php endif; ?>

</div>
<script>
  /* Scale buttons */
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

  /* Same-value guard */
  function validateDropdowns() {
    const terima  = document.getElementById('terima-select').value;
    const dari    = document.getElementById('dari-select').value;
    const err     = document.getElementById('validation-error');
    const btn     = document.getElementById('submit-btn');
    const invalid = terima && dari && terima === dari;
    err.style.display = invalid ? 'block' : 'none';
    btn.disabled = invalid;
    return !invalid;
  }
  document.getElementById('terima-select').addEventListener('change', validateDropdowns);
  document.getElementById('dari-select').addEventListener('change', validateDropdowns);
  document.getElementById('spica-form').addEventListener('submit', e => {
    if (!validateDropdowns()) e.preventDefault();
  });

  /* Bobot badge */
  document.getElementById('tujuan-select').addEventListener('change', function () {
    const badge = document.getElementById('bobot-badge');
    const opt   = this.options[this.selectedIndex];
    if (this.value) {
      badge.textContent = 'Bobot: ' + opt.dataset.bobot;
      badge.classList.remove('hidden');
    } else {
      badge.classList.add('hidden');
    }
  });

  /* Day / Night */
  function toggleMode() {
    document.body.classList.toggle('day');
    localStorage.setItem('mode', document.body.classList.contains('day') ? 'day' : 'night');
  }
  if (localStorage.getItem('mode') === 'day') document.body.classList.add('day');
</script>
</body>
</html>