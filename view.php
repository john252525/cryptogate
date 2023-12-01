<?php
// ini_set('display_errors', 1);
require_once __DIR__ . '/lib/db/safemysql.class.php';

$sync_param = isset($_GET['async'])
            ? 'async&'
            : (isset($_GET['sync']) ? 'sync&' : '');
$token = isset($_GET['token']) ? $_GET['token'] : '';
$action_url = "/cryptogate/order.php?{$sync_param}token={$token}&action=%s&order_id=%d";

require_once __DIR__ . '/../.env.cryptogate.php';
$db_config = [
    'user' => _DB_LOGIN_,
    'pass' => _DB_PASSWORD_,
    'charset' => 'utf8mb4',
    'db' => _DB_DATABASE_,
];
$db = new SafeMySQL($db_config);

$filters = ['preorder_id', 'deal_id', 'presignal_id', 'signal_id', 'cryptogate_preorder_id', 'cryptogate_deal_id'];
$filtered = false;
foreach ($filters as $item)
    if ( ! empty($_GET[$item]))
        $filtered = true;
$limit = (isset($_GET['limit']) and intval($_GET['limit']) > 0)
       ? 'LIMIT ' . intval($_GET['limit'])
       : ($filtered ? '' : 'LIMIT 5');
$sort = (isset($_GET['sort']) and in_array(strtoupper($_GET['sort']), ['ASC', 'DESC']))
      ? strtoupper($_GET['sort'])
      : 'DESC';
$viewer_url = explode('?', $_SERVER['REQUEST_URI'])[0] . '?'
            . (isset($_GET['admin']) ? 'admin&' : '')
            . (isset($_GET['pretty']) ? 'pretty&' : '');

$databases = isset($_GET['admin'])
           ? [
                'signal-trade' => [ 'user', 'provider', 'rule', 'presignal', 'signal', 'order' ],
                'cryptogate' => [ 'user', 'stock', 'deal', 'preorder', 'task', 'order_binance', 'order_binance_log' ],
             ]
           : [
                'signal-trade' => [ 'rule', 'presignal', 'signal', 'order' ],
                'cryptogate' => [ 'deal', 'preorder', 'task', 'order_binance', 'order_binance_log' ],
             ];
$data = [];

if ( ! empty($_GET['preorder_id'])) {
    $preorder_id = (int) $_GET['preorder_id'];
    $title_suffix = "(preorder #{$preorder_id})";
    $data = getCryptogateByPreorder($preorder_id, $data);
    $sql = "SELECT id FROM `signal-trade`.`signal`
            WHERE cryptogate_deal_id = ?i LIMIT 1";
    $signal_id = $db->getOne($sql, $data['cryptogate.deal'][0]['id']);
    if ( ! empty($signal_id))
        $data = getSignaltradeBySignal($signal_id, $data);
}
else if ( ! empty($_GET['deal_id'])) {
    $deal_id = (int) $_GET['deal_id'];
    $title_suffix = "(deal #{$deal_id})";
    $data = getCryptogateByDeal($deal_id, $data);
    $sql = "SELECT id FROM `signal-trade`.`signal`
            WHERE cryptogate_deal_id = ?i LIMIT 1";
    $signal_id = $db->getOne($sql, $_GET['deal_id']);
    if ( ! empty($signal_id))
        $data = getSignaltradeBySignal($signal_id, $data);
}
else if ( ! empty($_GET['presignal_id'])) {
    $presignal_id = (int) $_GET['presignal_id'];
    $title_suffix = "(presignal #{$presignal_id})";
    $data = getSignaltradeByPresignal($presignal_id, $data);
    if ( ! empty($data['signal-trade.signal'][0]['cryptogate_deal_id']))
        $data = getCryptogateByDeal($data['signal-trade.signal'][0]['cryptogate_deal_id'], $data);
}
else if ( ! empty($_GET['signal_id'])) {
    $signal_id = (int) $_GET['signal_id'];
    $title_suffix = "(signal #{$signal_id})";
    $data = getSignaltradeBySignal($signal_id, $data);
    if ( ! empty($data['signal-trade.signal'][0]['cryptogate_deal_id']))
        $data = getCryptogateByDeal($data['signal-trade.signal'][0]['cryptogate_deal_id'], $data);
}
else {
    foreach ($databases as $db_name => $tables) {
        foreach ($tables as $table) {
            $sql = "SELECT * FROM `{$db_name}`.`{$table}`
                    ORDER BY `id` {$sort} {$limit}";
            $data["{$db_name}.{$table}"] = $db->getAll($sql);
        }
    }
}
$data = dataSort($data);

function getCryptogateByPreorder($preorder_id, $data=[])
{
    global $db, $databases, $limit, $sort;

    $sql = "SELECT * FROM `cryptogate`.`preorder`
            WHERE id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['cryptogate.preorder'] = $db->getAll($sql, $preorder_id);

    $deal_id = $data['cryptogate.preorder'][0]['deal_id'];
    $sql = "SELECT * FROM `cryptogate`.`deal`
            WHERE id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['cryptogate.deal'] = $db->getAll($sql, $deal_id);

    $sql = "SELECT * FROM `cryptogate`.`task`
            WHERE preorder_id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['cryptogate.task'] = $db->getAll($sql, $preorder_id);

    $sql = "SELECT * FROM `cryptogate`.`order_binance`
            WHERE preorder_id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['cryptogate.order_binance'] = $db->getAll($sql, $preorder_id);

    $sql = "SELECT * FROM `cryptogate`.`order_binance_log`
            WHERE preorder_id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['cryptogate.order_binance_log'] = $db->getAll($sql, $preorder_id);

    if (in_array('stock', $databases['cryptogate'])) {
        $stock_id = $data['cryptogate.preorder'][0]['stock_id'];
        $sql = "SELECT * FROM `cryptogate`.`stock`
                WHERE id = ?i
                ORDER BY `id` {$sort} {$limit}";
        $data['cryptogate.stock'] = $db->getAll($sql, $stock_id);
    }

    if (in_array('user', $databases['cryptogate'])) {
        $user_id = $data['cryptogate.preorder'][0]['user_id'];
        $sql = "SELECT * FROM `cryptogate`.`user`
                WHERE id = ?i
                ORDER BY `id` {$sort} {$limit}";
        $data['cryptogate.user'] = $db->getAll($sql, $user_id);
    }

    return $data;
}

function getCryptogateByDeal($deal_id, $data=[])
{
    global $db, $databases, $limit, $sort;

    $sql = "SELECT * FROM `cryptogate`.`deal`
            WHERE id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['cryptogate.deal'] = $db->getAll($sql, $deal_id);

    $sql = "SELECT * FROM `cryptogate`.`preorder`
            WHERE deal_id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['cryptogate.preorder'] = $db->getAll($sql, $deal_id);

    if ( ! empty($data['cryptogate.preorder'])) {

        $preorder_ids = implode(',', array_column($data['cryptogate.preorder'], 'id'));
        $sql = "SELECT * FROM `cryptogate`.`task`
                WHERE preorder_id IN ({$preorder_ids})
                ORDER BY `id` {$sort} {$limit}";
        $data['cryptogate.task'] = $db->getAll($sql);

        $sql = "SELECT * FROM `cryptogate`.`order_binance`
                WHERE preorder_id IN ({$preorder_ids})
                ORDER BY `id` {$sort} {$limit}";
        $data['cryptogate.order_binance'] = $db->getAll($sql);

        $sql = "SELECT * FROM `cryptogate`.`order_binance_log`
                WHERE preorder_id IN ({$preorder_ids})
                ORDER BY `id` {$sort} {$limit}";
        $data['cryptogate.order_binance_log'] = $db->getAll($sql);

        if (in_array('stock', $databases['cryptogate'])) {
            $stock_ids = implode(',', array_column($data['cryptogate.preorder'], 'stock_id'));
            $sql = "SELECT * FROM `cryptogate`.`stock`
                    WHERE id IN ({$stock_ids})
                    ORDER BY `id` {$sort} {$limit}";
            $data['cryptogate.stock'] = $db->getAll($sql);
        }

        if (in_array('user', $databases['cryptogate'])) {
            $user_ids = implode(',', array_column($data['cryptogate.preorder'], 'user_id'));
            $sql = "SELECT * FROM `cryptogate`.`user`
                    WHERE id IN ({$user_ids})
                    ORDER BY `id` {$sort} {$limit}";
            $data['cryptogate.user'] = $db->getAll($sql);
        }

    }

    return $data;
}

function getSignaltradeByPresignal($presignal_id, $data=[])
{
    global $db, $databases, $limit, $sort;

    $sql = "SELECT * FROM `signal-trade`.`presignal`
            WHERE id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['signal-trade.presignal'] = $db->getAll($sql, $presignal_id);

    $provider_id = $data['signal-trade.presignal'][0]['provider_id'];
    $sql = "SELECT * FROM `signal-trade`.`rule`
            WHERE provider_id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['signal-trade.rule'] = $db->getAll($sql, $provider_id);

    $sql = "SELECT * FROM `signal-trade`.`signal`
            WHERE presignal_id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['signal-trade.signal'] = $db->getAll($sql, $presignal_id);

    $signal_id = $data['signal-trade.signal'][0]['id'];
    $sql = "SELECT * FROM `signal-trade`.`order`
            WHERE signal_id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['signal-trade.order'] = $db->getAll($sql, $signal_id);

    if (in_array('provider', $databases['signal-trade'])) {
        $sql = "SELECT * FROM `signal-trade`.`provider`
                WHERE id = ?i
                ORDER BY `id` {$sort} {$limit}";
        $data['signal-trade.provider'] = $db->getAll($sql, $provider_id);
    }

    if (in_array('user', $databases['signal-trade'])) {
        $user_id = $data['signal-trade.presignal'][0]['user_id'];
        $sql = "SELECT * FROM `signal-trade`.`user`
                WHERE id = ?i
                ORDER BY `id` {$sort} {$limit}";
        $data['signal-trade.user'] = $db->getAll($sql, $user_id);
    }

    return $data;
}

function getSignaltradeBySignal($signal_id, $data=[])
{
    global $db, $databases, $limit, $sort;

    $sql = "SELECT * FROM `signal-trade`.`signal`
            WHERE id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['signal-trade.signal'] = $db->getAll($sql, $signal_id);

    $sql = "SELECT * FROM `signal-trade`.`order`
            WHERE signal_id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['signal-trade.order'] = $db->getAll($sql, $signal_id);

    $rule_id = $data['signal-trade.signal'][0]['rule_id'];
    $sql = "SELECT * FROM `signal-trade`.`rule`
            WHERE id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['signal-trade.rule'] = $db->getAll($sql, $rule_id);

    $presignal_id = $data['signal-trade.signal'][0]['presignal_id'];
    $sql = "SELECT * FROM `signal-trade`.`presignal`
            WHERE id = ?i
            ORDER BY `id` {$sort} {$limit}";
    $data['signal-trade.presignal'] = $db->getAll($sql, $presignal_id);

    if (in_array('provider', $databases['signal-trade'])) {
        $provider_id = $data['signal-trade.presignal'][0]['provider_id'];
        $sql = "SELECT * FROM `signal-trade`.`provider`
                WHERE id = ?i
                ORDER BY `id` {$sort} {$limit}";
        $data['signal-trade.provider'] = $db->getAll($sql, $provider_id);
    }

    if (in_array('user', $databases['signal-trade'])) {
        $user_id = $data['signal-trade.presignal'][0]['user_id'];
        $sql = "SELECT * FROM `signal-trade`.`user`
                WHERE id = ?i
                ORDER BY `id` {$sort} {$limit}";
        $data['signal-trade.user'] = $db->getAll($sql, $user_id);
    }

    return $data;
}

function dataSort($data)
{
    global $databases;

    $result = [];
    foreach ($databases as $db_name => $tables) {
        foreach ($tables as $table) {
            if ( ! empty($data["{$db_name}.{$table}"]))
                $result["{$db_name}.{$table}"] = $data["{$db_name}.{$table}"];
        }
    }

    return $result;
}

function safe($string)
{
    return htmlspecialchars($string, ENT_QUOTES);
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Viewer <?= $title_suffix ?? '' ?></title>
    <style>
        body {
            font: 11px Verdana;
        }
        table {
            border-collapse: collapse;
            border-spacing: 0.1em;
            margin-bottom: 2em;
            margin-top: 1em
        }
        tr:hover {
            background: #c0ffc0;
        }
        th {
            background: #eee;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 0.3em 1em;
        }
        a {
            color: #06c;
        }
        pre {
            margin: 0;
        }
        .button-pretty {
            display: inline;
            color: #06c;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <?php foreach ($data as $table => $arr): ?>

        <p><b><?= $table ?></b></p>
        <table>

            <?php if ( ! empty($arr)): ?>
                
                <tr>

                    <?php foreach ($arr[0] as $key => $value): ?>
                        
                        <th><?= $key ?></th>

                    <?php endforeach ?>

                    <?php if ($table == 'cryptogate.preorder'): ?>

                        <th colspan="3">actions</th>

                    <?php endif ?>

                </tr>

                <?php foreach ($arr as $row): ?>
                    
                    <tr>
                    
                    <?php foreach ($row as $key => $value): ?>
                        <?php $value = trim($value); ?>
                        
                        <td>
                            <?php
                                $tmp_key = $key == 'id'
                                         ? explode('.', $table)[1] . '_id'
                                         : $key;
                                if (strpos($tmp_key, 'cryptogate_') !== false)
                                    $tmp_key = substr($tmp_key, 11);
                            ?>
                            <?php if (in_array($tmp_key, $filters) and ! empty($value)): ?>
                                
                                <a href="<?= $viewer_url ?><?= $tmp_key ?>=<?= $value ?>">
                                    <?= safe($value) ?>
                                </a>

                            <?php else: ?>
                                
                                <?php if (in_array($value[0], ['{', '[']) and $tmp_data = @json_decode($value)): ?>
                                    
                                    <div class="short" <?= isset($_GET['pretty']) ? 'style="display: none;"' : '' ?>>
                                        <div class="button-pretty show">[+]</div>
                                        <?= safe($value) ?>
                                    </div>
                                    <div class="pretty" <?= isset($_GET['pretty']) ? '' : 'style="display: none;"' ?>>
                                        <pre><div class="button-pretty hide">[-]</div> <?= safe(json_encode($tmp_data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
                                    </div>

                                <?php else: ?>

                                    <?= safe($value) ?>

                                <?php endif ?>

                            <?php endif ?>
                            
                        </td>

                    <?php endforeach ?>

                    <?php if ($table == 'cryptogate.preorder'): ?>

                        <td><a href="<?= sprintf($action_url, 'get', $row['id']) ?>" target="_blank">Get</a></td>
                        <td><a href="<?= sprintf($action_url, 'cancel', $row['id']) ?>" target="_blank">Cancel</a></td>
                        <td><a href="<?= sprintf($action_url, 'recreate', $row['id']) ?>" target="_blank">Create</a></td>
                        
                    <?php endif ?>

                    </tr>

                <?php endforeach ?>

            <?php endif ?>

        </table>

    <?php endforeach ?>

    <script>
        // JSON prettifier
        document.addEventListener('DOMContentLoaded', function() {
            var buttonPrettyElements = document.querySelectorAll('.button-pretty');
            for (var i = 0; i < buttonPrettyElements.length; i++) {
                buttonPrettyElements[i].addEventListener('click', function () {
                    var closestTd = this.closest('td');
                    var shortElements = closestTd.querySelectorAll('.short');
                    var prettyElements = closestTd.querySelectorAll('.pretty');
                    for (var j = 0; j < shortElements.length; j++) {
                        shortElements[j].style.display = 'none';
                    }
                    for (var k = 0; k < prettyElements.length; k++) {
                        prettyElements[k].style.display = 'none';
                    }
                    if (this.classList.contains('show')) {
                        for (var l = 0; l < prettyElements.length; l++) {
                            prettyElements[l].style.display = 'block';
                        }
                    } else {
                        for (var m = 0; m < shortElements.length; m++) {
                            shortElements[m].style.display = 'block';
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>