<?php
/* ================== DEBUG (hapus setelah normal) ================== */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ================== DATABASE ================== */
$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_e0";

/* ================== CONNECT ================== */
$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("❌ Gagal koneksi database: " . mysqli_connect_error());
}

/* ================== POST HANDLER ================== */
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $Nama  = trim($_POST['Nama'] ?? '');
    $Jenis = (int)($_POST['Jenis'] ?? 2); // default 2

    if ($Nama === '') {
        $message = "❌ Nama wajib diisi";
    } else {

        $Nama = mysqli_real_escape_string($conn, $Nama);

        // INSERT PALING AMAN SESUAI STRUKTUR
        $sql = "
            INSERT INTO formalhault (Nama, Jenis)
            VALUES ('$Nama', $Jenis)
        ";

        if (mysqli_query($conn, $sql)) {
            $message = "✅ Data berhasil disimpan ke formalhault";
        } else {
            $message = "❌ Gagal insert: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Formalhault</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 20px;
        }
        .box {
            max-width: 420px;
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }
        label {
            display: block;
            margin-top: 10px;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }
        button {
            margin-top: 15px;
            padding: 10px;
            width: 100%;
            background: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }
        .msg {
            margin-bottom: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="box">

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Nama</label>
        <input type="text" name="Nama" required>

        <label>Jenis</label>
        <select name="Jenis">
            <option value="2" selected>Nyata</option>
            <option value="1">Fiksi</option>
        </select>

        <button type="submit">Simpan</button>
    </form>

</div>

</body>
</html>
