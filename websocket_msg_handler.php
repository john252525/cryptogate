<?php
// ini_set('display_errors', 1);
require_once 'config.php';
require_once 'lib/db/safemysql.class.php';
require_once 'lib/Task.class.php';

$api_key = $_GET['apikey'] ?? '';
$json = $_GET['json'] ?? '';

$message = [ 'ok' => true ];
try {
    $db = new SafeMySQL([
        'user'    => _DB_LOGIN_,
        'pass'    => _DB_PASSWORD_,
        'db'      => _DB_DATABASE_,
        'charset' => 'utf8'
    ]);
    $taskObject = new Task($db); 
    $params = [
        'dt_ins' => date('Y-m-d H:i:s'),
        'ts_ins' => time(),
        'action' => 'websocket',
        'data' => $json,
        'stock_order_id_1' => '',
        'stock_order_id_2' => '',
        'stock_order_state' => ''
    ];
    $db->query('INSERT INTO ?n SET ?u', _DB_TABLE_ORDER_BINANCE_LOG_, $params);
    $order_log_id = $db->insertId();
    $data = json_decode($json, true);
    $first = true;
    if (empty($data))
        $message = [
            'ok' => false,
            'error' => 'Incorrect input'
        ];
    foreach ($data as $item) {
        if (empty($item['info'])){
            $message = [
                'ok' => false,
                'error' => 'Incorrect input'
            ];
            break;
        }
        if ( ! $first) {
            $db->query('INSERT INTO ?n SET ?u', _DB_TABLE_ORDER_BINANCE_LOG_, $params);
            $order_log_id = $db->insertId();
        }
        updateOrder($order_log_id, $item);
        $first = false;
    }
    echo json_encode($message);
} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}

function updateOrder($order_log_id, $data)
{
    global $db, $api_key, $taskObject;

    $json = json_encode($data);

    $sql = "SELECT id, user_id FROM `" . _DB_TABLE_STOCK_ . "`
            WHERE apikey = ?s LIMIT 1";
    $stock = $db->getRow($sql, $api_key);
    $stock_id = empty($stock['id'])
              ? 0
              : $stock['id'];
    $user_id = empty($stock['user_id'])
             ? 0
             : $stock['user_id'];
    $params = [
        'dt_ins' => date('Y-m-d H:i:s'),
        'ts_ins' => time(),
        'user_id' => $user_id,
        'stock_id' => $stock_id,
        'data' => $json
    ];
    $stock_order = $taskObject->extractStockOrder($json);
    if ( ! empty($stock_order['id_1'])) {
        $sql = 'SELECT * FROM ?n WHERE stock_order_id_1 = ?s';
        $orders = $db->getAll($sql, _DB_TABLE_ORDER_BINANCE_, $stock_order['id_1']);
    }
    if ( ! empty($orders)) {
        $sql = 'UPDATE ?n SET preorder_id = ?i WHERE id = ?i LIMIT 1';
        $db->query($sql, _DB_TABLE_ORDER_BINANCE_LOG_, $orders[0]['preorder_id'], $order_log_id);
    }
    $sql = 'UPDATE ?n SET ?u WHERE id = ?i LIMIT 1';
    $db->query($sql, _DB_TABLE_ORDER_BINANCE_LOG_, $params, $order_log_id);
    $taskObject->OrderBinanceLogHandler($order_log_id);
}
