<?php

if (!file_exists('./temp_data'))
    mkdir('./temp_data');

if (!file_exists('./temp_data/store') || filemtime('./temp_data/store') < time() - 10 * 60) {
    $opts = array('http'=>array('header' => "User-Agent:GPJP-API-agent/1.0\r\n"));
    $context = stream_context_create($opts);

    $html = file_get_contents("https://aplikace.skolaonline.cz/SOL/PublicWeb/gpjp/KWE014_VypisTridDenni.aspx", false, $context);

    file_put_contents('./temp_data/store', $html);
} else {
    $html = file_get_contents('./temp_data/store');
}

$html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");

$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML($html);
$xpath = new DOMXPath($doc);

$nlist = $xpath->query("//td[@class='KuvSuplujiciHodina']");

$data = [];

foreach ($nlist as $node) {
    $tooltip = $node->getAttribute('onmouseover');

    $ttp_str = explode("~", substr($tooltip, strpos($tooltip, "','")+3, -2));
    $entry = [];
    for ($i = 0; $i < count($ttp_str); $i += 2) {
        $entry[$ttp_str[$i]] = $ttp_str[$i+1];
    }
    
    foreach (explode(", ", $entry["Třída:"]) as $class) {

        $time = substr($entry["Den (vyuč. hodina):"], strpos($entry["Den (vyuč. hodina):"], "(")+1, -1);

        if (!array_key_exists($class, $data))
            $data[$class] = [];

        $new = [];
        if (array_key_exists("Výměna za:", $entry)) {
            $new = [
                "time"    => $time,
                "subject" => explode(", ", $entry["Výměna za:"])[1],
                "type"    => "add",
                "room"    => $entry["Učebna:"],
                "teacher" => $entry["Učitel:"]
            ];
        } else if (array_key_exists("Nahrazuje hodiny:", $entry)) {
            $info_text = explode(", ", $entry["Nahrazuje hodiny:"]);
            if (strpos($entry["Učitel:"], "<span class=AbsZdroj>") === 0) {
                $new = [
                    "time"    => $time,
                    "subject" => $info_text[1],
                    "type"    => "remove"
                ];
            } else if ($info_text[count($info_text)-1] != $entry["Učebna:"]) {
                $new = [
                    "time"    => $time,
                    "subject" => $info_text[1],
                    "type"    => "room",
                    "from"    => $entry["Učebna:"],
                    "to"      => $info_text[count($info_text)-1]
                ];
            } else if (strpos($entry["Nahrazuje hodiny:"], "<span class=AbsZdroj>") !== false) {
                $new = [
                    "time"    => $time,
                    "subject" => $info_text[1],
                    "type"    => "teacher",
                    "from"    => preg_split("/<.*?>/", $entry["Nahrazuje hodiny:"])[1],
                    "to"      => str_replace(["<span class=AbsZdroj>", "</span>"], "", $entry["Učitel:"])
                ];
            }
        } else if (array_key_exists("Spojení:", $entry)) {
            $info_blocks = explode("<br />", $entry["Spojení:"]);
            $info1 = explode(", ", $info_blocks[0]);
            $info2 = explode(", ", $info_blocks[1]);
            if (strpos($info1[2], "<span class=AbsZdroj>") === 0) {
                $new = [
                    "time"    => $time,
                    "subject" => $info1[1],
                    "type"    => "merge",
                    "from"    => $info1[4] . " - " . str_replace(["<span class=AbsZdroj>", "</span>"], "", $info1[2]) ,
                    "to"      => $info2[4]. " - " . $info2[2]
                ];
            } else if (strpos($info2[2], "<span class=AbsZdroj>") === 0) {
                $new = [
                    "time"    => $time,
                    "subject" => $info1[1],
                    "type"    => "merge",
                    "from"    => $info2[4] . " - " . str_replace(["<span class=AbsZdroj>", "</span>"], "", $info2[2]),
                    "to"      => $info1[4] . " - " . $info1[2]
                ];
            }
        }

        if (count($new) == 0) {
            if (!in_array(["time" => $time, "type" => "unknown"], $data[$class]))
                array_push($data[$class], ["time" => $time, "type" => "unknown"]);
        } else if (in_array($new, $data[$class])) {
            continue;
        } else
            array_push($data[$class], $new);
    }
}

//echo json_encode($data);
//exit;

/**
 * data response:
 * 
 * class: {class name}
 *  time: {name}
 *  subject: {name}
 *  type: {teacher},{room},{remove},{add},{merge}
 *  [teacher,room]
 *   from: {name}
 *   to:   {name}
 *  [removed]
 *  [added]
 *   room:    {room}
 *   teacher: {name}
 *  [merge]
 *   from: {name}
 *   to:   {name}
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css?family=Oswald:200,300,400" rel="stylesheet">
    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>
</head>
<body>
    <div class="footer">
        Provozovatel: Výpočetní a informační centrum GPJP
        <span class="right">Informace jsou aktualizovány každých 10 minut</span>
    </div>
<?php
if (date("N") > 5):
    echo '<div class="inform">Informujte IT tým, že se televize přes víkend nevypnula.</div>';
elseif (date("G") < 5 || date("G") > 20):
    echo '<div class="inform">Informujte IT tým, že se televize přes noc nevypnula.</div>';
elseif (count($data) == 0):
    echo '<div class="nochange">Rozvrh je beze změny.</div>';
else:?>
    <h1 class="title">Změny v rozvrhu</h1>
    <img src="./logo.svg" class="logo" />
    <table class="header" cellspacing="0">
        <tr class="header">
            <th style="width: 12%; text-indent: 3%;">Třída</th>
            <th style="width: 10%;">Hodina</th>
            <th style="width: 15%;">Změna</th>
            <th style="width: 35%;"></th>
            <th style="width: 28%;">Původní stav</th>
        </tr>
    </table>
    <table class="body" id="tab1" cellspacing="0">
        <tr class="spacing-header">
            <th style="width: 12%;"></th>
            <th style="width: 10%;"></th>
            <th style="width: 15%;"></th>
            <th style="width: 35%;"></th>
            <th style="width: 28%;"></th>
        </tr>
<?php
foreach ($data as $class => $rows) {
    $row_c = count($rows);
    $first = 1;
    foreach ($rows as $row) {
        if ($first) {
            echo '<tr class="border"><td class="course" rowspan="'.$row_c.'">'.strtoupper($class).'</td>' . PHP_EOL;
            $first = 0;
        } else echo '<tr>' . PHP_EOL;
        echo '<td class="time"><span class="hour">'.
        $row['time'].
        '.</span><span class="subject">'.
        explode(" ", (array_key_exists('subject', $row) ? $row['subject'] : "?"))[0].
        '</span></td>' . PHP_EOL;
        
        if ($row['type'] == 'room') {
            echo '<td class="change label">Učebna</td>
            <td class="change to">'.roomName($row['to']).'</td>
            <td class="change from">'.roomName($row['from']).'</td>' . PHP_EOL;
        } else if ($row['type'] == 'teacher') {
            echo '<td class="change label">Supluje</td>
            <td class="change to">'.$row['to'].'</td>
            <td class="change from">'.$row['from'].'</td>' . PHP_EOL;
        } else if ($row['type'] == 'add') {
            echo '<td class="change label">Nově</td>
            <td class="change to">'.roomName($row['room']).' - '.$row['teacher'].'</td><td></td>' . PHP_EOL;
        } else if ($row['type'] == 'remove') {
            echo '<td class="change label">Odpadá</td><td></td><td></td>' . PHP_EOL;
        } else if ($row['type'] == 'unknown') {
            echo '<td class="change label">Neznámá událost</td><td class="change to">Sledujte web Škola Online</td><td></td>' . PHP_EOL;
        } else if ($row['type'] == 'merge') {
            echo '<td class="change label">Spojení</td>
            <td class="change to">'.$row['to'].'</td>
            <td class="change from">'.$row['from'].'</td>' . PHP_EOL;
        }
    }
    echo '</tr>' . PHP_EOL;
}
endif;

function roomName($room) {
    if (substr($room, 0, 1) == "J")
        return "Jer" . substr($room, 3);
    return "U" . $room;
}
?>
    </table>
</body>
</html>