<?php
$sql = new mysqli('127.0.0.1', 'root', '', 'fpml');

$res = $sql->query("SELECT mrid,LOWER(`mrdescription`) AS mrdesc FROM `train_data`;");

$freqs = [];

if ($res->num_rows != 0) {
    while ($row = $res->fetch_assoc()) {
        $row["mrdesc"] = html_entity_decode($row["mrdesc"]);
        $row["mrdesc"] = preg_replace("/(?:^<!\s.*?\s>)|(?:{|}|<|>)/", "", $row["mrdesc"]);
        $row["mrdesc"] = preg_replace("/(?:\s\.)|(?:\.\s)/", "", $row["mrdesc"]);
        $words = preg_split("/\s| /", $row["mrdesc"]);

        foreach ($words as $word) {
            $word = preg_replace("/\r|\n|,|#|\*|:/", "", $word);
            $word = trim($word);

            if (!array_key_exists($word, $freqs))
                $freqs[$word] = 0;

            $freqs[$word]++;
        }
    }
}

function gt1($a) {
    return $a > 1;
}

$freqs = array_filter($freqs, "gt1");
arsort($freqs);

foreach ($freqs as $word => $freq) {
    if ($word && $freq)
        printf("%-7s $word\n", $freq);
}

