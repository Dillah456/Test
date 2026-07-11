<?php
// =======================
// DEBUG (MATIKAN NANTI)
// =======================
ini_set('display_errors', 1);
error_reporting(E_ALL);

// =======================
// KONEKSI DATABASE
// =======================
$db_server = "localhost";
$db_user   = "oortmyid_root";
$db_pass   = "KMS_z23@24";
$db_name   = "oortmyid_cv";

$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$message = "";

// =======================
// PROSES FORM
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $Title   = trim($_POST['Title'] ?? '');
    $Kondisi = (int)($_POST['Kondisi'] ?? 0);
    $Tingkat = (int)($_POST['Tingkat'] ?? 1);
    $Details = trim($_POST['Details'] ?? '');

    // ---- Validasi ----
    if ($Title === '') {
        $message = "❌ Title wajib diisi";
    } else {

        // ---- Escape ----
        $Title   = mysqli_real_escape_string($conn, $Title);
        $Details = mysqli_real_escape_string($conn, $Details);

        // =======================
        // INSERT DATA
        // =======================
        $sql = "
            INSERT INTO Notes
            (Title, Kondisi, Tingkat, Details)
            VALUES
            ('$Title', $Kondisi, $Tingkat, '$Details')
        ";

        if (mysqli_query($conn, $sql)) {
            $message = "✅ Data berhasil disimpan";
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
    <title>Tambah Data</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 20px;
        }

        .box {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
        }

        h2 {
            margin-top: 0;
        }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        button {
            margin-top: 15px;
            padding: 10px 20px;
            background: #007BFF;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }

        button:hover {
            background: #0056b3;
        }

        .message {
            margin-bottom: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="box">

    <h2>Tambah Data</h2>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form method="post">

        <label>Title</label>
        <input type="text" name="Title" required>

        <label>Kondisi</label>
        <input type="number" name="Kondisi" value="1">

        <label>Tingkat</label>
        <input type="number" name="Tingkat" value="1">

        <label>Details</label>
        <textarea name="Details"></textarea>

        <button type="submit">Simpan</button>

    </form>

</div>

</body>
</html>
<?php mysqli_close($conn); ?>
