<?php

	echo '<head><meta charset="UTF-8"><head>';

	set_time_limit(60);
	mb_internal_encoding("UTF-8");

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	require_once 'config.php';
	require_once 'lib/db/safemysql.class.php';
	require_once 'lib/Task.class.php';


	$opts = array(
			'user'    => _DB_LOGIN_,
			'pass'    => _DB_PASSWORD_,
			'db'      => _DB_DATABASE_,
			'charset' => 'utf8'
	);
	$db = new SafeMySQL($opts); // with some of the default settings overwritten

//---------------------------------------------------------------
	$taskObject = new Task($db); 
	
	if (isset($_SERVER['argv'][1])) {
		$p = $_SERVER['argv'][1];
	}else{
		$p='create';
	}

	if ( is_numeric($p) ){  // argv is state
		$task_state = $p;
	}
	else { // argv is action
		$task_action = $p; 
		
		$task_state = mt_rand(1000000,9999999);
		$taskObject->UpdateTask($task_action, $task_state);
	}

	if ( !$data = $taskObject->GetTaskByState($task_state) ){

		die('{"ok":false,"error":"wrong task state"}');

	}else{

		$task_id = $data[0]['id'];
		$task_action = $data[0]['action'];

	}
	
	echo 'task_id: '.$task_id.'<br>';
	echo 'task_action: '.$task_action.'<br>';

	if ( $task_action == 'create') {
		$order_binance_id = $taskObject->SaveOrderBinance($task_id);// preorder_id, stock_id
		$order_binance_log_id = $taskObject->SaveOrderBinanceLog($task_id);
		$binance_request = $taskObject->GetRequest( $task_id );
		$binance_answer = $taskObject->ApiBinanceOrder( $binance_request );
		// 2) sql: update order_binance_log set data=data where id=.. limit 1
		$cnt = $taskObject->UpdateOrderBinanceLog($order_binance_log_id, $binance_request, $binance_answer);
		if ( $cnt == 0 )
			die('{"ok":false,"error":"update OrderBinanceLog problem}');
		// 3) sql: update order_binance
		$cnt = $taskObject->UpdateOrderBinance($order_binance_id, $binance_answer);
		if ( $cnt == 0 )
			die('{"ok":false,"error":"update OrderBinance problem}');
		// 4) sql: update order_binance_log 
		// 			state (int, 0 - по умолчанию, 1 - после update order_binance.data)
		$taskObject->UpdateOrderBinanceLogState($order_binance_id, $order_binance_log_id);
	} else {
		$binance_orders = $taskObject->GetBinanceOrders($task_id);
		$request_data = $taskObject->GetRequestData($task_id);
		$binance_order_logs = [];
		foreach ($binance_orders as $binance_order) {
			$request_data['order_id'] = $binance_order['stock_order_id_1'];
			$binance_request = json_encode($request_data);
			$obl_id = $taskObject->SaveOrderBinanceLog($task_id, $binance_request);
			$binance_order_logs []= $obl_id;
		}
		foreach ($binance_order_logs as $obl_id) {
			$taskObject->OrderBinanceLogHandler($obl_id);
		}
	}

	// if(ok) sql: update task set state=1 where id=.. limit 1
	$cnt = $taskObject->UpdateTaskState($task_id);
	if ($cnt==0) {
		die('{"ok":false,"error":"update Task State problem}');
	}

