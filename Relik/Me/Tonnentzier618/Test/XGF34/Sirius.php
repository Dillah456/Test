<?php
$min = 1;
function Attribute_Dice($min){
    $max = 8;
    $Attr = rand($min,$max);
    return $Attr;
}

function Action_Dice($min){
    $max = 20;
    $Action = rand($min,$max);
    return $Action;
}

function Sampling_Dice($min){
    $max = 10;
    $Sampling = rand($min,$max);
    return $Sampling;
}

function Integrity_Dice($min){
    $max = 100;
    $Integrity = rand($min,$max);
    return $Integrity;
}

function Symbolic_Dice($min){
    $max = 12;
    #Bukan Zodiac yang dipakai untuk Ramalan yak :v
    $Zodiac = rand($min,$max);
    return $Zodiac;
}

function Populate_Dice($min){
    $max = 4;
    $Quartile = rand($min,$max);
    return $Quartile;
}
$CONS;$CHA;$STR;$HEX;$IQ;$WIS;$HP;

$CONS = Attribute_Dice($min)+Action_Dice($min);
$STR = Attribute_Dice($min)+Action_Dice($min);
$HEX = Attribute_Dice($min)+Action_Dice($min);
$CHA = Attribute_Dice($min)+Action_Dice($min);
$IQ = Attribute_Dice($min)+Action_Dice($min);
$WIS = Attribute_Dice($min)+Action_Dice($min);


function Role_Fictional($min){
$CONS = Attribute_Dice($min)*Symbolic_Dice($min);
$STR = Attribute_Dice($min)*Symbolic_Dice($min);
$HEX = Attribute_Dice($min)*Symbolic_Dice($min);
$CHA = Attribute_Dice($min)*Symbolic_Dice($min);
$IQ = Attribute_Dice($min)*Symbolic_Dice($min);
$WIS = Attribute_Dice($min)*Symbolic_Dice($min);
}

?>