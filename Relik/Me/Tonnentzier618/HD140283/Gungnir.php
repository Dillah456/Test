<?php
/* ================== DEBUG MODE ================== */


/* ================== KONEKSI ================== */
$conn = mysqli_connect("localhost","oortmyid_root","KMS_z23@24","oortmyid_cv");
if(!$conn){
    die("Koneksi gagal: ".mysqli_connect_error());
}

/* ================== AMBIL DATA PERSONALIA ================== */
$sql_personalia = "
SELECT Id, Nama
FROM Personalia
ORDER BY Nama ASC
";

$q = mysqli_query($conn,$sql_personalia);
$list_personalia = mysqli_fetch_all($q, MYSQLI_ASSOC);


/* ================== PROSES SUBMIT ================== */
if(isset($_POST['submit'])){

    $Id      = intval($_POST['personalia_id']);
    $Nominal = floatval($_POST['nominal']);
    $Jenis   = $_POST['jenis'];

    if($Jenis == "Debit"){
        $Debit  = $Nominal;
        $Kredit = 0;
    } else {
        $Debit  = 0;
        $Kredit = $Nominal;
    }

    mysqli_begin_transaction($conn);

    try{

        /* 1️⃣ Insert ke Akun_Nominal */
        $sql_insert = "
        INSERT INTO Akun_Nominal (Id, Debit, Kredit)
        VALUES ($Id, $Debit, $Kredit)
        ";
        mysqli_query($conn,$sql_insert);
        debug("Insert Nominal", $sql_insert);

        /* 2️⃣ Cek apakah sudah ada di Akun_Rill */
        $cek = mysqli_query($conn,"SELECT * FROM Akun_Rill WHERE Id=$Id");

        if(mysqli_num_rows($cek)==0){
            mysqli_query($conn,"
            INSERT INTO Akun_Rill (Id, Modal, Hutang)
            VALUES ($Id,0,0)
            ");
        }

        /* 3️⃣ Update Akun_Rill */
        if($Jenis=="Debit"){
            $sql_update = "
            UPDATE Akun_Rill
            SET Modal = Modal + $Nominal
            WHERE Id=$Id
            ";
        }else{
            $sql_update = "
            UPDATE Akun_Rill
            SET Hutang = Hutang + $Nominal
            WHERE Id=$Id
            ";
        }

        mysqli_query($conn,$sql_update);
        debug("Update Rill", $sql_update);

        mysqli_commit($conn);

        echo "<p style='color:lightgreen'>Transaksi berhasil.</p>";

    }catch(Exception $e){
        mysqli_rollback($conn);
        echo "<p style='color:red'>Gagal transaksi</p>";
        debug("Error", $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Tambah Saldo</title>
<style>
body{font-family:system-ui;background:#111;color:#eee}
.box{max-width:400px;margin:20px auto;padding:15px;background:#1b1f27;border-radius:10px}
select,input,button{width:100%;padding:8px;margin:8px 0;border-radius:6px;border:none}
button{background:#4cafef;font-weight:bold}
</style>
</head>
<body>

<div class="box">
<h3>Tambah Saldo Akun</h3>

<form method="POST">

<select name="personalia_id" required>
<option value="">-- pilih personil --</option>

<?php foreach($list_personalia as $p): ?>
    <option value="<?= $p['Id'] ?>">
        <?= htmlspecialchars($p['Nama']) ?>
    </option>
<?php endforeach; ?>

</select>


<select name="jenis" required>
<option value="Debit">Debit (Tambah Modal)</option>
<option value="Kredit">Kredit (Tambah Hutang)</option>
</select>

<input type="number" name="nominal" step="0.01" min="1" required>

<button name="submit">Simpan</button>

</form>
</div>

</body>
</html>