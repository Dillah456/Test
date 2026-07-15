<?php
// ================= KONEKSI =================
$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_cv";

$koneksi = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// ================= SIMPAN ORGANISASI =================
if (isset($_POST['simpan_organisasi'])) {
    $nama   = mysqli_real_escape_string($koneksi, $_POST['org_nama']);
    $bidang = mysqli_real_escape_string($koneksi, $_POST['org_bidang']);

    mysqli_query($koneksi,
        "INSERT INTO Organisasi (Nama, Bidang) VALUES ('$nama','$bidang')"
    );
}

// ================= SIMPAN PERSONALIA =================
if (isset($_POST['simpan_personalia'])) {
    $nama        = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $divisi      = mysqli_real_escape_string($koneksi, $_POST['divisi']);
    $cabang      = mysqli_real_escape_string($koneksi, $_POST['cabang']);
    $dokumentasi = mysqli_real_escape_string($koneksi, $_POST['dokumentasi']);

    mysqli_query($koneksi,
        "INSERT INTO Personalia (Nama, Divisi, Cabang, Dokumentasi)
         VALUES ('$nama','$divisi','$cabang','$dokumentasi')"
    );
}

// ================= DATA ORGANISASI =================
$dataOrganisasi = mysqli_query($koneksi, "SELECT * FROM Organisasi ORDER BY Nama ASC");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Master Data</title>
<style>
body { font-family: Arial; background:#f4f4f4; }
.container { width: 500px; margin: 40px auto; background:#fff; padding:20px; border-radius:6px; }
.menu button {
    padding:10px;
    margin-right:5px;
    border:none;
    cursor:pointer;
    background:#ddd;
}
.menu button.active { background:#0d6efd; color:#fff; }
form { display:none; margin-top:15px; }
input, textarea, select, button {
    width:100%;
    padding:8px;
    margin-top:6px;
}
.submit {
    background:#198754;
    color:#fff;
    border:none;
    cursor:pointer;
}
</style>
</head>
<body>

<div class="container">

    <div class="menu">
        <button onclick="showForm('organisasi')" class="active" id="btnOrg">Tambah Organisasi</button>
        <button onclick="showForm('personalia')" id="btnPer">Tambah Personalia</button>
    </div>

    <!-- FORM ORGANISASI -->
    <form id="organisasi" method="POST" style="display:block;">
        <h3>Tambah Organisasi</h3>
        <input type="text" name="org_nama" placeholder="Nama Organisasi" required>
        <input type="text" name="org_bidang" placeholder="Bidang" required>
        <button class="submit" name="simpan_organisasi">Simpan</button>
    </form>

    <!-- FORM PERSONALIA -->
    <form id="personalia" method="POST">
        <h3>Tambah Personalia</h3>
        <input type="text" name="nama" placeholder="Nama" required>
        <input type="text" name="divisi" placeholder="Divisi" required>

        <select name="cabang" required>
            <option value="">-- Pilih Organisasi --</option>
            <?php while ($o = mysqli_fetch_assoc($dataOrganisasi)) { ?>
                <option value="<?= $o['Id']; ?>">
                    <?= $o['Nama']; ?> (<?= $o['Bidang']; ?>)
                </option>
            <?php } ?>
        </select>

        <textarea name="dokumentasi" placeholder="Dokumentasi"></textarea>
        <button class="submit" name="simpan_personalia">Simpan</button>
    </form>

</div>

<script>
function showForm(form) {
    document.getElementById('organisasi').style.display = 'none';
    document.getElementById('personalia').style.display = 'none';
    document.getElementById('btnOrg').classList.remove('active');
    document.getElementById('btnPer').classList.remove('active');

    document.getElementById(form).style.display = 'block';
    if (form === 'organisasi') {
        document.getElementById('btnOrg').classList.add('active');
    } else {
        document.getElementById('btnPer').classList.add('active');
    }
}
</script>

</body>
</html>
