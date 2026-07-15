<?php
/* ================== KONEKSI ================== */
$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_e0";

$conn = mysqli_connect($db_server,$db_user,$db_pass,$db_name);
if(!$conn){ die("Koneksi DB gagal"); }

/* ================== AMBIL DATA NAMA ================== */
$list_nama = [];
$q = mysqli_query($conn,"SELECT Nama FROM formalhault ORDER BY Nama ASC");
while($r = mysqli_fetch_assoc($q)){
    $list_nama[] = $r['Nama'];
}

/* ================== PROSES SUBMIT ================== */
if(isset($_POST['submit'])){

    $Dari     = $_POST['Spica-F'];
    $Terima   = $_POST['Spica-R'];
    $Nominal  = intval($_POST['Spica-M']);
    $Tujuan   = $_POST['Spica-O'];

    $Senang = intval($_POST['Spica-A4']);
    $Sedih  = intval($_POST['Spica-F2']);
    $Grief  = intval($_POST['Spica-N0N4']);

    /* Insert SPICA */
    mysqli_query($conn,"
        INSERT INTO spica
        (Dari,Terima,Monetasi,Tujuan,Senang,Sedih,Grief)
        VALUES
        ('$Dari','$Terima',$Nominal,'$Tujuan',$Senang,$Sedih,$Grief)
    ");

    /* Update saldo Terima */
    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Saldo FROM formalhault WHERE Nama='$Terima'"));
    $saldo_baru = $r['Saldo'] + $Nominal;
    mysqli_query($conn,
        "UPDATE formalhault SET Saldo=$saldo_baru WHERE Nama='$Terima'");

    /* Update saldo Dari */
    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Saldo FROM formalhault WHERE Nama='$Dari'"));
    $saldo_baru = $r['Saldo'] - $Nominal;
    mysqli_query($conn,
        "UPDATE formalhault SET Saldo=$saldo_baru WHERE Nama='$Dari'");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Spica</title>

<style>
:root{
    --bg:#0f1115;
    --box:#161a22;
    --text:#eee;
    --input:#2a2f3a;
    --accent:#4cafef;
}
body.day{
    --bg:#f4f6fb;
    --box:#ffffff;
    --text:#111;
    --input:#e4e6eb;
}

body{
    margin:0;
    font-family:system-ui;
    background:var(--bg);
    color:var(--text);
}

.box{
    max-width:420px;
    margin:16px auto;
    padding:16px;
    background:var(--box);
    border-radius:12px;
}

h3{
    margin:16px 0 6px;
    font-size:14px;
    font-weight:500;
}

select{
    width:100%;
    padding:10px;
    border-radius:8px;
    border:none;
    background:var(--input);
    color:var(--text);
}

.scale{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
}
.scale button{
    flex:1 0 32px;
    height:34px;
    border:none;
    border-radius:8px;
    background:var(--input);
    color:var(--text);
}
.scale button.active{
    background:var(--accent);
    color:#000;
}

.actions{
    display:flex;
    gap:8px;
    margin-bottom:12px;
}
button{
    padding:10px;
    border:none;
    border-radius:8px;
    font-weight:600;
}
.submit{background:var(--accent);}
.back{background:#888;}
.mode{background:#555;color:#fff;}

@media(max-width:480px){
    .box{margin:8px;}
}
</style>
</head>

<body>

<div class="box">

<div class="actions">
    <button class="back" type="button"
        onclick="location.href='menu.php'">⬅ Menu</button>
    <button class="mode" type="button"
        onclick="toggleMode()">🌗 Mode</button>
</div>

<form method="POST">

<h3>Dari</h3>
<select name="Spica-F" required>
    <option value="">-- pilih --</option>
    <?php foreach($list_nama as $n): ?>
        <option value="<?= $n ?>"><?= $n ?></option>
    <?php endforeach; ?>
</select>

<h3>Terima</h3>
<select name="Spica-R" required>
    <option value="">-- pilih --</option>
    <?php foreach($list_nama as $n): ?>
        <option value="<?= $n ?>"><?= $n ?></option>
    <?php endforeach; ?>
</select>

<h3>Nominal</h3>
<select name="Spica-M" required>
    <?php for($i=10;$i<=100;$i+=10): ?>
        <option value="<?= $i ?>"><?= $i ?></option>
    <?php endfor; ?>
</select>

<h3>Tujuan</h3>
<select name="Spica-O" required>
    <option value="">-- pilih tujuan --</option>
    <option value="Kondisi Kesehatan - Maag">Kondisi Kesehatan – Maag</option>
    <option value="Mental - Panic attack">Mental – Panic attack</option>
    <option value="Patologi - Kelelahan">Patologi – Kelelahan</option>
    <option value="Kesepian">Kesepian</option>
    <option value="Distraksi">Distraksi</option>
</select>

<h3>😊 Senang (0–10)</h3>
<div class="scale" data-max="10" data-target="A4"></div>
<input type="hidden" id="A4" name="Spica-A4" value="0">

<h3>😔 Sedih (0–10)</h3>
<div class="scale" data-max="10" data-target="F2"></div>
<input type="hidden" id="F2" name="Spica-F2" value="0">

<h3>🖤 Grief (0–7)</h3>
<div class="scale" data-max="7" data-target="N0N4"></div>
<input type="hidden" id="N0N4" name="Spica-N0N4" value="0">

<button class="submit" name="submit">Kirim</button>

</form>
</div>

<script>
/* Skala */
document.querySelectorAll('.scale').forEach(s=>{
    let max=s.dataset.max, target=s.dataset.target;
    for(let i=0;i<=max;i++){
        let b=document.createElement('button');
        b.type="button"; b.textContent=i;
        b.onclick=()=>{
            s.querySelectorAll('button').forEach(x=>x.classList.remove('active'));
            b.classList.add('active');
            document.getElementById(target).value=i;
        };
        s.appendChild(b);
    }
});

/* Day / Night */
function toggleMode(){
    document.body.classList.toggle('day');
    localStorage.setItem('mode',
        document.body.classList.contains('day')?'day':'night');
}
if(localStorage.getItem('mode')==='day'){
    document.body.classList.add('day');
}
</script>

</body>
</html>
