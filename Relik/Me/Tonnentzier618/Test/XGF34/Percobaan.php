<?php
$db_server = "localhost";
$db_user ="root";
$db_pass ="";
$db_name ="e0";
$conn ="";

try{
$conn = mysqli_connect($db_server, $db_user, 
                        $db_pass, $db_name);
}catch(mysqli_sql_exception){
    echo "SQL Problem perhaps brother ...";
}
if($conn){
    echo "Connected to test";
}else{
    echo "No connection";
}

$Id='Amiya';
/*
$sql = "SELECT Nama, HP, STR, IQ FROM sirius WHERE Nama = '$Id'";
$result = mysqli_query($conn,$sql);
if(mysqli_num_rows($result)>0){
    while($row=mysqli_fetch_assoc($result)){
        $nama = $row['Nama'];
        $HP = $row['HP'];
        $STR = $row['STR'];
        $IQ = $row['IQ'];
        echo "Nama : ".$nama."<br> Health :".$HP."";
    }
}else{
    echo "Tidak ada data";
}
*/
#Menyimpan data ke Array
/*
$data=[];
$sql = "SELECT * FROM sirius";
$result = mysqli_query($conn,$sql);
if(mysqli_num_rows($result)){
    while($row=mysqli_fetch_assoc($result)){
        $data[]=$row;
    }
}else{
    echo"Tidak ada data";
}

print_r($data);
*/
