<?php
    $serveris = "localhost";
    $lietotajs = "grobina1_pavlovskis";
    $parole = "3LZeL@hxv";
    $db_nosaukums = "grobina1_pavlovskis";

    $savienojums = mysqli_connect($serveris, $lietotajs, $parole, $db_nosaukums);

    if(!$savienojums){
       #die("Kļūda ar datu bāzi".mysqli_connect_error());
    }else{
        #echo "Savienojums veiksmīgi izveidots!";
    }
    ?>