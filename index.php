<?php
ini_set('display_errors',1);

$cards = 1;
$testing = 0;

echo '<html><head><meta name="viewport" content="width=device-width, initial-scale=1"></head><body><pre>';

$key = '711770f02eb30d3a1f2f67990b8b305b';
$token = 'f98c2e353578ed66e00ae546adb10be9af429d5155988d57ac1e7e18aafc7675';
$key_token = "&key=$key&token=$token";
$url = "https://api.trello.com/1/members/andrescrucitti/boards?$key_token";
$batch_url_base = "https://api.trello.com/1/batch?urls=";

$response = file_get_contents($url);
$json = json_decode($response);

$index = 0;
$batch_url = $batch_url_base;
foreach ($json as $board) {
    $index++;

    if ($testing && $board->name != 'Gevorg') continue;

    //if ($board->name != 'Studio9') continue;

    $boards[$board->id]['name'] = $board->name;
    $boards[$board->id]['last_date'] = $board->dateLastActivity;
    $boards[$board->id]['url'] = $board->url;

    $batch_url .= "/boards/$board->id/lists?cards=open,";

    if ($index > 3) {
        $batch_urls[] = trim($batch_url,',') . $key_token;
        $batch_url = $batch_url_base;
        $index = 0;
    }
    //break;
}
foreach ($batch_urls as $url) {
    $response = file_get_contents($url);
    $responses = json_decode($response);

    foreach ($responses as $lists) {
        //echo $url;
        foreach ($lists as $val=>$sublists) {
        foreach ($sublists as $list) {

            $board_id = $list->idBoard;
            $list_id = $list->id;
/*
            if (stripos($list->name,'gevorg')  !== FALSE) continue;
            if (stripos($list->name,'testing')  !== FALSE) continue;
            if (stripos($list->name,'done')  !== FALSE) continue;
            if (stripos($list->name,'input') !== FALSE) continue;
            if (stripos($list->name,'ideas') !== FALSE) continue;
            if (stripos($list->name,'questions') !== FALSE) continue;
            if (stripos($list->name,'next') !== FALSE) continue;
            if (stripos($list->name,'long-term') !== FALSE) continue;
            if (stripos($list->name,'longterm') !== FALSE) continue;
            if (stripos($list->name,'long term') !== FALSE) continue;
            if (stripos($list->name,'try') !== FALSE) continue;
            if (stripos($list->name,'finished') !== FALSE) continue;
            if (stripos($list->name,'completed') !== FALSE) continue;
 */
            $has_cards = false;

            foreach ($list->cards as $card) {
                $tmp['name'] = $card->name;
                $tmp['url'] = $card->url;
                $tmp['last_date'] = $card->dateLastActivity;
                $boards[$board_id]['lists'][$list_id]['cards'][$card->id] = $tmp;
                $has_cards = true;
            }
            if ($has_cards) {
                $boards[$board_id]['lists'][$list_id]['name'] = $list->name;
            }
        }
        }
    }
}

function hasIgnoreString($str) {
    $words = [
        'Product Lines to Add to Site',
        'Things That Wendy Notices',
        'gevorg',
        'testing',
        'done',
        'input',
        'ideas',
        'questions',
        'next',
        'long-term',
        'longterm',
        'long term',
        'try',
        'finished',
        'completed',
        'missing',
    ];

    foreach ($words as $word) {
        if (stripos($str,$word) !== FALSE) {
            return true;
        }
    }

    return false;
}

$all_cards = array();
foreach ($boards as $board) {
    if (isset($board['lists']))
    foreach ($board['lists'] as $list) {
        if (isset($list['cards']))
            foreach ($list['cards'] as $card) {
                if (
                    hasIgnoreString($board['name']) ||
                    hasIgnoreString($list['name']) ||
                    hasIgnoreString($card['name']) ||
                    0
                ) {
                    continue;
                }
                $tmp = array();
                $tmp['board'] = $board['name'];
                $tmp['list'] = $list['name'];
                $tmp['card'] = $card['name'];
                $tmp['date'] = $card['last_date'];
                $all_cards[] = $tmp;
            }
    }
}


function sortByDate($a, $b) {
    return $a['date'] < $b['date'] ? 1 : -1;
}

usort($all_cards,'sortByDate');

function printCard($card) {
    echo '<li>';
    echo $card['name'];
    echo '</li>';

}

function printList($list) {

    echo '<li>';
    echo $list['name'] .' list';
    echo '<ul>';

    if (isset($list['cards']))
    foreach ($list['cards'] as $card)
        printCard($card);
    else
        '<li>NO CARDS?</li>';

    echo '</ul></li>';
}

function printBoard($board) {

    echo '<ul>';
    echo '<b>"'.$board['name'].'"</b> board';

    if (isset($board['lists']))
    foreach ($board['lists'] as $list)
        printList($list);
    else
        '<li>NO LISTS?</li>';
    echo '</ul>';
    echo '<hr/>';
}


if ($cards) {
    echo '<table width="1200">';
    echo '<tr>';
    echo '<th width="500">Card</th>';
    echo '<th >List</th>';
    echo '<th width="300">Board</th>';
    echo '<th width="200">Date</th>';
    echo '</tr>';
    foreach ($all_cards as $card) {
        echo '<tr>';
        echo '<td>'.$card['card'].'</td>';
        echo '<td>'.$card['list'].'</td>';
        echo '<td align="center">'.$card['board'].'</td>';
        echo '<td align="center">'.date("H:i:s m/d/Y",strtotime($card['date'])).'</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '<style>
body {
    border-spacing: box-border;
}
table {
    margin: 0;
    border-collapse: collapse;
    padding: 0;
    border-right: 1px solid #e5e5e5;
}
table tr {
    border-top: 1px solid #e5e5e5;
    border-bottom: 1px solid #e5e5e5;
}
table th {
    background: #ddd;
    padding: p5x;
}
table td {
    padding: 5px;
    border-left: 1px solid #e5e5e5;
    vertical-align: top;
}

        </style>';
} else {
    foreach ($boards as $board)
        printBoard($board);
}
