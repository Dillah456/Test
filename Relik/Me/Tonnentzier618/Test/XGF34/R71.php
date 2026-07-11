<?php
// =======================
// DEBUG (AKTIFKAN DULU)
// =======================
ini_set('display_errors', 1);
error_reporting(E_ALL);

// =======================
// KONEKSI DATABASE
// =======================
$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_e0";

$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// =======================
// PESAN STATUS
// =======================
$message = "";

// =======================
// PROSES FORM
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---- Ambil data ----
    $Nama      = trim($_POST['Nama'] ?? '');
    $Umur      = (int)($_POST['Umur'] ?? 0);
    $Tinggi   = (int)($_POST['Tinggi'] ?? 0);
    $Berat    = (int)($_POST['Berat'] ?? 0);
    $Afiliasi = trim($_POST['Afiliasi'] ?? '');
    $Profesi  = trim($_POST['Profesi'] ?? '');
    $Jenis    = (int)($_POST['Jenis'] ?? 0);

    // ---- Validasi ----
    if ($Nama === '') {
        $message = "❌ Nama wajib diisi";
    } elseif ($Jenis !== 1 && $Jenis !== 2) {
        $message = "❌ Jenis entitas tidak valid";
    } else {

        // ---- Escape ----
        $Nama      = mysqli_real_escape_string($conn, $Nama);
        $Afiliasi = mysqli_real_escape_string($conn, $Afiliasi);
        $Profesi  = mysqli_real_escape_string($conn, $Profesi);

        // =======================
        // INSERT formalhault
        // =======================
        $q1 = "INSERT INTO formalhault (Nama) VALUES ('$Nama')";
        if (!mysqli_query($conn, $q1)) {
            $message = "❌ Gagal insert formalhault: " . mysqli_error($conn);
        } else {

            // =======================
            // DATA DEFAULT sirius
            // =======================
            $Lv = 1;
            $HP = 10;

            $CONS = 1;
            $STR  = 1;
            $HEX  = 1;
            $CHA  = 1;
            $IQ   = 1;
            $WIS  = 1;

            // =======================
            // INSERT sirius
            // =======================
            $q2 = "
                INSERT INTO sirius
                (Jenis, Nama, Afiliasi, Umur, Lv, Tinggi, CONS, CHA, STR, HEX, IQ, WIS, HP, Profesi, Berat)
                VALUES
                ($Jenis, '$Nama', '$Afiliasi', $Umur, $Lv, $Tinggi, $CONS, $CHA, $STR, $HEX, $IQ, $WIS, $HP, '$Profesi', $Berat)
            ";

            if (!mysqli_query($conn, $q2)) {
                $message = "❌ Gagal insert sirius: " . mysqli_error($conn);
            } else {
                $message = "✅ Data berhasil disimpan";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Entitas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 20px;
        }

        .container {
            display: flex;
            gap: 20px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 900px;
            margin: auto;
        }

        form {
            width: 100%;
        }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            box-sizing: border-box;
        }

        .radio-group {
            margin-top: 8px;
        }

        .radio-group label {
            margin-right: 15px;
            font-weight: normal;
        }

        .btn-group {
            margin-top: 20px;
        }

        input[type="submit"],
        input[type="button"] {
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            color: #fff;
            background: #007BFF;
            border-radius: 4px;
            margin-right: 10px;
        }

        input[type="submit"]:hover,
        input[type="button"]:hover {
            background: #0056b3;
        }

        .message {
            margin-bottom: 15px;
            font-weight: bold;
        }

        img {
            width: 300px;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            img {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <form method="post">

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <label>Nama</label>
        <input type="text" name="Nama" required>

        <label>Umur</label>
        <input type="number" name="Umur" min="0">

        <label>Tinggi</label>
        <input type="number" name="Tinggi" min="0">

        <label>Berat Badan</label>
        <input type="number" name="Berat" min="0">

        <label>Afiliasi</label>
        <input type="text" name="Afiliasi">

        <label>Profesi</label>
        <input type="text" name="Profesi">

        <label>Jenis Entitas</label>
        <div class="radio-group">
            <label>
                <input type="radio" name="Jenis" value="2" checked> Nyata
            </label>
            <label>
                <input type="radio" name="Jenis" value="1"> Fiksi
            </label>
        </div>

        <div class="btn-group">
            <input type="submit" value="Simpan">
            <input type="button" value="Kembali" onclick="location.href='Mimosa.php'">
        </div>

    </form>

    <img src="illust_86200817_20230725_203740.jpg" alt="Ilustrasi">

</div>

</body>
</html>
<?php mysqli_close($conn); ?>
