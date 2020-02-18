<?php
$client = new SoapClient(null, [
    "location" => "https://itsm-fp.northwestern.edu/MRcgi/MRWebServices.pl",
    "uri" => "MRWebServices",
    "style" => SOAP_RPC,
    "use" => SOAP_ENCODED
]);

$sep = " → ";

$file = fopen("datan3.txt", 'r');
$txt = fread($file, filesize("datan3.txt"));

$all = explode("\r\n", $txt);

$sql = new mysqli('127.0.0.1', 'root', '', 'fpml');


$stmt = $sql->prepare("INSERT INTO train_data (mrid, mrdescription, service__bfamily, service, category, sub__ucategory) VALUES (?, ?, ?, ?, ?, ?)");

for ($ct = 0; $ct < sizeof($all); $ct++) {
    $b = $all[$ct];
    $arr = explode($sep, $b);
    echo "$ct $b\n";

    $wherearr = [];
    for ($i = 0; $i < sizeof($arr); $i++) {
        switch ($i) {
            case 0: {
                $wherearr[] = 'MASTER1.service__bfamily=\''.$arr[$i].'\'';
                break;
            }
            case 1: {
                $wherearr[] = 'MASTER1.service=\''.$arr[$i].'\'';
                break;
            }
            case 2: {
                $wherearr[] = 'MASTER1.category=\''.$arr[$i].'\'';
                break;
            }
            case 3: {
                $wherearr[] = 'MASTER1.sub__ucategory=\''.$arr[$i].'\'';
                break;
            }
        }
    }

    $farr = array("", "", "", "");
    for ($j = 0; $j < sizeof($wherearr); $j++) {
        $farr[$j] = $arr[$j];
    }

    $wherestr = implode(" AND ", $wherearr);
    $req = "SELECT MASTER1.mrID,MASTER1_DESCRIPTIONS.mrDESCRIPTION FROM MASTER1 INNER JOIN MASTER1_DESCRIPTIONS on MASTER1.mrID = MASTER1_DESCRIPTIONS.mrID AND MASTER1_DESCRIPTIONS.mrGENERATION = 1 AND MASTER1.submission__btracking = 'Email' WHERE $wherestr FETCH NEXT 50 ROWS ONLY";

    $a = $client->MRWebServices__search("tco393", "g00dn!ght", "", $req);

    // echo($a[0]->ct);
    // echo("\n");

    foreach ($a as $c) {
        try {
            $stmt->bind_param("isssss", $c->mrid, $c->mrdescription, $farr[0], $farr[1], $farr[2], $farr[3]);

            $stmt->execute();
        } catch (Exception $ignored) {
            echo "    ERROR";
        }
    }
}
