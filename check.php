<?php
$sql = new mysqli('127.0.0.1', 'root', '', 'fpml');

$file = fopen("datan2.txt", 'r');
$txt = fread($file, filesize("datan2.txt"));

$all = explode("\r\n", $txt);

$sep = " → ";
$ct = 0;

foreach ($all as $a) {
    $arr = explode($sep, $a);

    $wherearr = [];
    for ($i = 0; $i < sizeof($arr); $i++) {
        switch ($i) {
            case 0: {
                $wherearr[] = 'service__bfamily=\''.$arr[$i].'\'';
                break;
            }
            case 1: {
                $wherearr[] = 'service=\''.$arr[$i].'\'';
                break;
            }
            case 2: {
                $wherearr[] = 'category=\''.$arr[$i].'\'';
                break;
            }
            case 3: {
                $wherearr[] = 'sub__ucategory=\''.$arr[$i].'\'';
                break;
            }
        }
    }
    $wherestr = implode(" AND ", $wherearr);

    $res = $sql->query("SELECT COUNT(*) as ct FROM train_data WHERE $wherestr");
    echo $sql->error;
    $x = $res->fetch_assoc()["ct"];

    printf("%-3s %-3s $a\n", $x, $ct);
    $ct++;
}
