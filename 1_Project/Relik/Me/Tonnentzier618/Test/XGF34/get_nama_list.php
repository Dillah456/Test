<?php
header('Content-Type: application/json');

$db_server = "localhost";
$db_user = "oortmyid_root";
$db_pass = "KMS_z23@24";
$db_name = "oortmyid_e0";
$conn = "";

try {
    $conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
} catch (mysqli_sql_exception) {
    echo json_encode(['error' => 'SQL Problem perhaps brother ...']);
    exit;
}

$nama_list = [];
$sql = "SELECT DISTINCT Nama FROM formalhault ORDER BY Nama";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $nama_list[] = $row['Nama'];
    }
}

echo json_encode($nama_list);
mysqli_close($conn);
?>
