<?php
declare(ticks = 1);
$sql = new mysqli('127.0.0.1', 'root', '', 'fpml');

$sep = " → ";

$cat_file = fopen("datan2.txt", 'r');
$cat_txt = fread($cat_file, filesize("datan2.txt"));

$cats = explode("\r\n", $cat_txt);

$word_file = fopen("words.txt", 'r');
$word_txt = fread($word_file, filesize("words.txt"));

$words = explode("\r\n", $word_txt);

function fv($ticket) {
    global $words;

    $fv = [];

    foreach ($words as $word) {
        $fv[] = preg_match_all("/(?:^|\s|\.|,|:|;|\"|'|{|}|\[|\]|\(|\))($word)(?:^|\s|\.|,|:|;|\"|'|{|}|\[|\]|\(|\))/", $ticket);
    }

    $fv[] = 1;

    return $fv;
}

function dotp($a, $b) {
    if (count($a) != count($b))
        return null;

    $ret = [];
    for ($i = 0; $i < count($a); $i++)
        $ret[] = $a[$i] * $b[$i];

    return array_sum($ret);
}

function get_real_category($ticket_obj) {
    global $sep;

    if ($ticket_obj["service"] == "") {
        return $ticket_obj["service__bfamily"];
    } else if ($ticket_obj["category"] == "") {
        return implode($sep, [$ticket_obj["service__bfamily"], $ticket_obj["service"]]);
    } else if ($ticket_obj["sub__ucategory"] == "") {
        return implode($sep, [$ticket_obj["service__bfamily"], $ticket_obj["service"], $ticket_obj["category"]]);
    } else {
        return implode($sep, [$ticket_obj["service__bfamily"], $ticket_obj["service"], $ticket_obj["category"], $ticket_obj["sub__ucategory"]]);
    }
}

function add_fv($fv, $wv_index) {
    global $weights;

    if (count($fv) != count($weights[$wv_index]))
        throw new Exception('addfv size wrong');

    for ($i = 0; $i < count($fv); $i++)
        $weights[$wv_index][$i] += $fv[$i];
}

function sub_fv($fv, $wv_index) {
    global $weights;

    if (count($fv) != count($weights[$wv_index]))
        throw new Exception('subvf size wrong');

    for ($i = 0; $i < count($fv); $i++)
        $weights[$wv_index][$i] -= $fv[$i];
}

function save_all() {
    global $weights;

    $wt_fmt = array_map("serialize", $weights);
    $str = implode("\n", $wt_fmt);

    echo "Saving weight vectors...\n";
    file_put_contents("weights.txt", $str);

    exit();
}

$weights = [];
if (file_exists("weights.txt")) {
    $wt_txt = file_get_contents("weights.txt");

    $wts = explode("\n", $wt_txt);
    foreach ($wts as $wt) {
        $weights[] = unserialize($wt);
    }
} else {
    for ($i = 0; $i < count($cats); $i++) {
        $weights[$i] = array_fill(0, count($words), 0);
        
        // bias
        $weights[$i][count($words)] = 1;
    }
}

pcntl_signal(SIGINT, "save_all");

$tickets_res = $sql->query("SELECT mrdescription,service__bfamily,service,category,sub__ucategory FROM train_data");
while (true) {
    $n = $tickets_res->num_rows;
    $ch = 0;
    $t3 = 0;

    while ($ticket = $tickets_res->fetch_assoc()) {
        $category = get_real_category($ticket);

        $feature_vector = fv($ticket["mrdescription"]);

        $vals = [];
        foreach ($weights as $weight)
            $vals[] = dotp($feature_vector, $weight);

        $svals = $vals;
        arsort($svals);

        $n1 = null;
        $n2 = null;
        $n3 = null;

        $t3s = 0;
        foreach ($svals as $cat => $val) {
            if ($t3s >= 3)
                break;

            switch ($t3s) {
                case 0: {
                    $n1 = $cats[$cat];
                    break;
                }
                case 1: {
                    $n2 = $cats[$cat];
                    break;
                }
                case 2: {
                    $n3 = $cats[$cat];
                    break;
                }
            }

            $t3s++;
        }

        if ($category == $n1 || $category == $n2 || $category == $n3)
            $t3++;

        $found_cat = $cats[array_search(max($vals), $vals)];

        if ($found_cat != $category) {
            $good_weight_index = array_search($category, $cats);
            $bad_weight_index = array_search($found_cat, $cats);

            add_fv($feature_vector, $good_weight_index);
            sub_fv($feature_vector, $bad_weight_index);
            
            $ch++;
        }
    }

    $tickets_res->data_seek(0);

    echo (($n - $ch) / $n) . "     ". (($n - $t3) / $n) . "\n";
}