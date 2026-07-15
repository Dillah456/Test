<?php

$api_url = "https://x8ki-letl-twmt.n7.xano.io/api:iQBnFS3Y/cornell_method";

if (isset($_POST['submit'])) {

    $data = [
        "Tittle"   => $_POST['title'],
        "Content"  => $_POST['content'],
        "Summary"  => $_POST['summary'],
        "Question" => $_POST['question']
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    curl_close($ch);

    echo "<script>alert('Data berhasil disimpan');</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Input Cornell Method</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            padding: 40px;
        }
        .card {
            background: white;
            padding: 25px;
            width: 600px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        input, textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
        }
        button {
            padding: 10px 20px;
            background: #2c3e50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #34495e;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Input Cornell Method</h2>
    <form method="POST">
        <label>Title</label>
        <input type="text" name="title" required>

        <label>Content</label>
        <textarea name="content" required></textarea>

        <label>Summary</label>
        <textarea name="summary" required></textarea>

        <label>Question</label>
        <textarea name="question" required></textarea>

        <button type="submit" name="submit">Simpan</button>
    </form>
</div>

</body>
</html>
