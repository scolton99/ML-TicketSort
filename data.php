<?php
$client = new SoapClient(null, [
    "location" => "https://itsm-fp.northwestern.edu/MRcgi/MRWebServices.pl",
    "uri" => "MRWebServices",
    "style" => SOAP_RPC,
    "use" => SOAP_ENCODED
]);

$sep = " → ";

$file = fopen("categories.txt", 'r');
$txt = fread($file, filesize("categories.txt"));

$all = explode("\n", $txt);

for ($j = 332; $j < sizeof($all); $j++) {
    $b = $all[$j];
    $arr = explode($sep, $b);

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
    $req = "SELECT COUNT(*) as ct FROM MASTER1 WHERE $wherestr AND submission__btracking='Email'";

    $a = $client->MRWebServices__search("tco393", "g00dn!ght", "", $req);

    $ct = $a[0]->ct;

    printf("%-6s $b\n", $ct);
}
