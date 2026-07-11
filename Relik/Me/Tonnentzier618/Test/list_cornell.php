<?php

$api_url = "https://x8ki-letl-twmt.n7.xano.io/api:iQBnFS3Y/cornell_method";

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Koleksi Cornell Method</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            padding: 40px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: white;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background: #2c3e50;
            color: white;
        }

        tr:hover {
            background: #ecf0f1;
            cursor: pointer;
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 8% auto;
            padding: 20px;
            width: 60%;
            border-radius: 8px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .close {
            float: right;
            font-size: 22px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<h2>Koleksi Cornell Method</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Identitas MOC</th>
        <th>Title</th>
        <th>Summary</th>
    </tr>

    <?php foreach ($data as $row): 

        $id         = $row['id'];
        $title      = htmlspecialchars($row['Tittle']);
        $content    = htmlspecialchars($row['Content']);
        $summary    = htmlspecialchars($row['Summary']);
        $question   = htmlspecialchars($row['Question']);

        $identitas_moc = "MOC-" . str_pad($id, 4, "0", STR_PAD_LEFT);

    ?>

    <tr onclick="openModal(
        '<?= $id ?>',
        `<?= $title ?>`,
        `<?= $content ?>`,
        `<?= $question ?>`
    )">
        <td><?= $id ?></td>
        <td><?= $identitas_moc ?></td>
        <td><?= $title ?></td>
        <td><?= $summary ?></td>
    </tr>

    <?php endforeach; ?>

</table>

<!-- MODAL -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 id="modalTitle"></h3>
        <hr>
        <p><strong>Content:</strong></p>
        <p id="modalContent"></p>
        <hr>
        <p><strong>Question:</strong></p>
        <p id="modalQuestion"></p>
    </div>
</div>

<script>

function openModal(id, title, content, question) {
    document.getElementById("modalTitle").innerText = title;
    document.getElementById("modalContent").innerText = content;
    document.getElementById("modalQuestion").innerText = question;
    document.getElementById("myModal").style.display = "block";
}

function closeModal() {
    document.getElementById("myModal").style.display = "none";
}

// Tutup kalau klik di luar modal
window.onclick = function(event) {
    let modal = document.getElementById("myModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

</script>

</body>
</html>
