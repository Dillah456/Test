<?php
include("Spica.html");

$db_server = "localhost";
$db_user ="oortmyid_root";
$db_pass ="KMS_z23@24";
$db_name ="oortmyid_e0";
$conn ="";

try{
$conn = mysqli_connect($db_server, $db_user, 
                        $db_pass, $db_name);
}catch(mysqli_sql_exception){
    echo "SQL Problem perhaps brother ...";
}
if($conn){
    echo "<hr><br><br>Connection : Connected to test";
}else{
    echo "<hr><br><br>Connection : No connection";
}
$Client = ''; $Level =''; $Objective = ''; $Priority=''; 
$Monetasi = ''; $Sender='';$ValueA4='';$ValueF2='';$ValueN0='';


$Client = isset($_GET["Spica-R"]) ? $_GET["Spica-R"] : '';
$Level = isset($_GET["Spica-W"]) ? $_GET["Spica-W"] : '';
$Objective = isset($_GET["Spica-O"]) ? $_GET["Spica-O"] : '';
$Priority = isset($_GET["Spica-P"]) ? $_GET["Spica-P"] : '';
$Monetasi = isset($_GET["Spica-M"]) ? $_GET["Spica-M"] : '';
$Sender = isset($_GET["Spica-F"]) ? $_GET["Spica-F"] : '';
$ValueA4 = isset($_GET["Spica-A4"]) ? $_GET["Spica-A4"] : '';
$ValueF2 = isset($_GET["Spica-F2"]) ? $_GET["Spica-F2"] : '';
$ValueN0 = isset($_GET["Spica-N0N4"]) ? $_GET["Spica-N0N4"] : '';

$sql = "INSERT INTO spica (Terima,Beban,Prioritas,Monetasi,Tujuan,Dari,Senang,Sedih,Grief)
    VALUES ('$Client',$Level,$Priority,$Monetasi,'$Objective','$Sender',$ValueA4,$ValueF2,$ValueN0)";
    mysqli_query($conn,$sql);

$Gungnir =['',0,0,0,0];
$sql = "SELECT Nama,RegNo,Saldo,Hutang,Modal FROM formalhault WHERE Nama = '$Client'";
$result = mysqli_query($conn,$sql);
    if(mysqli_num_rows($result)>0){
        while($row=mysqli_fetch_assoc($result)){
        $Nama = $row['Nama'];
        $RegNo = $row['RegNo'];
        $Saldo = $row['Saldo'];
        $Hutang = $row['Hutang'];
        $Modal = $row['Modal'];
        $Gungnir =[$RegNo,$Nama,$Modal,$Hutang,$Saldo];
    }
    }else{
    echo "Tidak ada data";
    };

$Saldo_Akhir=$Gungnir[4]+intval($Monetasi);

echo "<br> Akun : {$Gungnir[1]}<br> Saldo : {$Gungnir[4]}<br>Terima :{$Monetasi}<br>Saldo Akhir = {$Saldo_Akhir}";
$sql = "UPDATE formalhault SET Saldo = $Saldo_Akhir WHERE Nama = '$Client'";
mysqli_query($conn,$sql);
##Mengurangi Saldo
$sql = "SELECT Nama,RegNo,Saldo,Hutang,Modal FROM formalhault WHERE Nama = '$Sender'";
$result = mysqli_query($conn,$sql);
    if(mysqli_num_rows($result)>0){
        while($row=mysqli_fetch_assoc($result)){
        $Nama = $row['Nama'];
        $RegNo = $row['RegNo'];
        $Saldo = $row['Saldo'];
        $Hutang = $row['Hutang'];
        $Modal = $row['Modal'];
        $Gungnir =[$RegNo,$Nama,$Modal,$Hutang,$Saldo];
    }
    }else{
    echo "Tidak ada data";
    }
$Saldo_Akhir=$Gungnir[4]-intval($Monetasi);
echo "<br><hr> Dari : {$Gungnir[1]}<br> Saldo : {$Gungnir[4]}<br>Mengirim :{$Monetasi}<br>Saldo Akhir = {$Saldo_Akhir}";
$sql = "UPDATE formalhault SET Saldo = $Saldo_Akhir WHERE Nama = '$Sender'";
mysqli_query($conn,$sql);
?>