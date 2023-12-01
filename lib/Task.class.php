<?php
class Task{
	private $db;
	
	public function __construct($db) {
        $this->db = $db;
    }


	//---------------------------------------
	// Получить Stock Name
	//---------------------------------------
	private function GetStock($stock_id){
    	$stock = $this->db->getRow("SELECT * FROM ?n WHERE `id` = ?i LIMIT 1", _DB_TABLE_STOCK_, $stock_id);
		return $stock;
	}


	// Get request for 'create' action
	public function GetRequest( $task_id )
	{
		$sql  = 'SELECT DBtask.preorder_id, DBtask.action, DBpreorder.* FROM '.
		'`' . _DB_TABLE_TASK_	 . '` AS DBtask, '.
		'`' . _DB_TABLE_PREORDER_ . '` AS DBpreorder '.
		'WHERE DBtask.id=?i AND DBpreorder.id=DBtask.preorder_id';

    	$dbresult = $this->db->query($sql, $task_id);
		$row=$this->db->fetch($dbresult);


		$stock = $this->GetStock($row['stock_id']);

		$result['action']       =$row['action'];
		$result['apikey']       =$stock['apikey'];
		$result['stock']		=$stock['stock'];
		$result['type']			=$row['type'];
		$result['side']			=$row['side'];
		$result['positionSide']	=$row['positionSide'];
		$result['pair']			=$row['pair'];
		$result['data']			=json_decode($row['data'], true);	

		return json_encode($result);
	}

	// Get request data for 'get\cancel' action
	public function GetRequestData( $task_id )
	{
		$sql  = 'SELECT DBtask.preorder_id, DBtask.action, DBpreorder.* FROM '
			  . '`' . _DB_TABLE_TASK_	 . '` AS DBtask, '
			  . '`' . _DB_TABLE_PREORDER_ . '` AS DBpreorder '
			  . 'WHERE DBtask.id=?i AND DBpreorder.id=DBtask.preorder_id';
    	$dbresult = $this->db->query($sql, $task_id);
		$row=$this->db->fetch($dbresult);

		$stock = $this->GetStock($row['stock_id']);

		$result['action'] = $row['action'];
		$result['apikey'] = $stock['apikey'];
		$result['stock']  = $stock['stock'];
		$result['pair']   = $row['pair'];

		return $result;
	}

	//---------------------------------------
	// Получить все order_binance связанные с task_id
	//---------------------------------------
	public function GetBinanceOrders($task_id)
	{
		$sql = 'SELECT preorder_id FROM `'._DB_TABLE_TASK_.'` WHERE id = ?i ORDER BY id ASC LIMIT 1';
		$preorder_id = $this->db->getOne($sql, $task_id);
		$sql = 'SELECT id, stock_order_id_1, parent_id FROM `'._DB_TABLE_ORDER_BINANCE_.'` WHERE preorder_id = ?i';
		$ids = $this->db->getAll($sql, $preorder_id);
		return $ids;
	}


	//---------------------------------------
	// Update задание в БД task (установить state, если на входе был action у первого action)
	//---------------------------------------
	public function UpdateTask( $action, $state ){
		$sql  = 'UPDATE `' . _DB_TABLE_TASK_ . '` SET dt_upd=?s, ts_upd=?i, state=?i WHERE state=0 AND action=?s ORDER BY id ASC LIMIT 1';
		
		$result = $this->db->query($sql, 
										date('Y-m-d H:i:s'), time(),
										$state,
										$action
								  );

		return $result;
	}

	//---------------------------------------
	// Получить task_id (согласно state)
	//---------------------------------------
	public function GetTaskByState( $state ){
		$sql  = 'SELECT id, action FROM `' . _DB_TABLE_TASK_ . '` WHERE state=?i ORDER BY id ASC LIMIT 1';
		
		$result = $this->db->getAll($sql, 
										$state
									);

		return $result;
	}

	//---------------------------------------
	private function GetInfo( $task_id ){
		$result=array();

		$sql  = 'SELECT DBtask.preorder_id, DBtask.action, DBpreorder.stock_id, DBpreorder.user_id FROM '.
		'`' . _DB_TABLE_TASK_     . '` AS DBtask, '.
		'`' . _DB_TABLE_PREORDER_ . '` AS DBpreorder '.
		'WHERE DBtask.id=?i AND DBpreorder.id=DBtask.preorder_id';

		echo $sql;
		
		$row = $this->db->getAll($sql, $task_id);
	
		echo "task:<pre>"; print_r($row); echo "</pre>";

		return $row;	
	}

	//---------------------------------------
	// Сохранить задание в БД order_binance (ордера, отправленные на биржу binance)
	// (if(action=create) sql: insert into order_binance)
	//---------------------------------------
	public function SaveOrderBinance( $task_id ){

		$response=$this->GetInfo($task_id);

		$sql  = 'INSERT INTO `' . _DB_TABLE_ORDER_BINANCE_ . '` SET dt_ins=?s, ts_ins=?i, preorder_id=?i, stock_id=?i';
		
		$this->db->query($sql, 
								date('Y-m-d H:i:s'), time(),
								$response[0]['preorder_id'],
								$response[0]['stock_id']
						  );
		$result = $this->db->insertId();

	// preorder_id
	// stock_id
	
	// data (ранее response)
	// order_id_1 (в принципе наверное можно и без stock_, надо обсудить)
	// order_id_2 (в принципе наверное можно и без stock_, надо обсудить)
	// state (created, canceled, filled)

		return $result;
	}

	//---------------------------------------
	// Сохранить задание в БД order_binance_log (логирование изменений статусов ордеров)
	// (sql: insert into order_binance_log)
	//---------------------------------------
	public function SaveOrderBinanceLog( $task_id, $request=false ){

		$response=$this->GetInfo($task_id);

		$params = [
			'dt_ins' => date('Y-m-d H:i:s'),
			'ts_ins' => time(),
			'user_id' => $response[0]['user_id'],
			'stock_id' => $response[0]['stock_id'],
			'action' => $response[0]['action'],
			'preorder_id' => $response[0]['preorder_id'],
		];
		if ($request)
			$params['request'] = $request;
		$sql = 'INSERT INTO ?n SET ?u';
		$this->db->query($sql, _DB_TABLE_ORDER_BINANCE_LOG_, $params);

		$result = $this->db->insertId();

		// dt_ins
		// ts_ins
		// user_id
		// stock_id
		// action (create, cancel, get, websocket)   - из task

		return $result;
	}

	//---------------------------------------
	// 2) sql: update order_binance_log set data=.. where id=.. limit 1
	//---------------------------------------
	public function UpdateOrderBinanceLog( $order_binance_log_id, $request, $data )
	{
		$sql = 'UPDATE ?n SET ?u WHERE id = ?i LIMIT 1';

		$params = [
			'request' => $request,
			'data' => $data
		];
		$this->db->query($sql, _DB_TABLE_ORDER_BINANCE_LOG_, $params, $order_binance_log_id);

		$stock_order = $this->extractStockOrder($data);
		$params = [
			'stock_order_id_1' => $stock_order['id_1'],
			'stock_order_id_2' => $stock_order['id_2'],
			'stock_order_state' => $stock_order['state'],
		];
		$this->db->query($sql, _DB_TABLE_ORDER_BINANCE_LOG_, $params, $order_binance_log_id);

		// request (json) - генерация ордера
		// data (json) ответ binance api
		// stock_order_id_1 (в принципе наверное можно и без stock_, надо обсудить)
		// stock_order_id_2 (в принципе наверное можно и без stock_, надо обсудить)
		// state (int, 0 - по умолчанию, 1 - после update  order_binance.data)
	
		$cnt = $this->db->affectedRows();
		return $cnt;
	}

	//---------------------------------------
	// 3) sql: update order_binance (if changed)
	//---------------------------------------
	public function UpdateOrderBinance( $order_binance_id, $data )
	{
		$stock_order = $this->extractStockOrder($data);

		$sql = 'SELECT * FROM `' . _DB_TABLE_ORDER_BINANCE_ . '` WHERE id=?i LIMIT 1';
		$row = $this->db->getRow($sql, $order_binance_id);
		$olddata = $row['data'];


		// чтобы dt_upd и dt_check совпадали полностью, для будущей проверки изменения data
		$currDate=date('Y-m-d H:i:s');
		$currTime=time();

		$sql_update = 'UPDATE `' . _DB_TABLE_ORDER_BINANCE_ . '` SET dt_check=?s, ts_check=?i';

		// Добавить data (ответ от binance API), если он отличается от уже записанного
		$datanew=false;
		if ( $data != $olddata ){
			$datanew=true;

			$sql_update .= ', data=?s, dt_upd=?s, ts_upd=?i, stock_order_id_1=?s, stock_order_id_2=?s, stock_order_state=?s';
			$sql_update .= ' WHERE `id` = ?i LIMIT 1';
			$result = $this->db->query(
				$sql_update,
				$currDate,
				$currTime,
				$data,
				$currDate,
				$currTime,
				! empty($row['stock_order_id_1']) ? $row['stock_order_id_1'] : $stock_order['id_1'],
				! empty($row['stock_order_id_2']) ? $row['stock_order_id_2'] : $stock_order['id_2'],
				$stock_order['state'],
				$order_binance_id
			);  // $row['id']

			if ($result !== false and $stock_order['state'] == 'FILLED')
				$this->setPreorderFilled($row['preorder_id']);

			// Разбор вложенных orderReports
			$info = @json_decode($data, true);
			if ( ! empty($info['orderReports'])) {
				foreach ($info['orderReports'] as $order) {
					if (empty($order['orderId']) or empty($order['status']) or empty($order['clientOrderId']))
						continue;
					$sql  = 'INSERT INTO ?n SET ?u';
					$result = $this->db->query($sql, _DB_TABLE_ORDER_BINANCE_, [
						'parent_id' => $row['id'],
						'dt_ins' => $currDate,
						'ts_ins' => $currTime,
						'dt_upd' => $currDate,
						'ts_upd' => $currTime,
						'preorder_id' => $row['preorder_id'],
						'stock_id' => $row['stock_id'],
						'data' => json_encode(['info' => $order]),
						'stock_order_id_1' => $order['orderId'],
						'stock_order_id_2' => $order['clientOrderId'],
						'stock_order_state' => $order['status'],
					]);
					if ($result !== false and $order['status'] == 'FILLED')
						$this->setPreorderFilled($row['preorder_id']);
				}
			}
		}else{
			$sql_update .= ' WHERE `id` = ?i LIMIT 1';
			$this->db->query($sql_update, $currDate, $currTime, $order_binance_id);  // $row['id']
		}

		$cnt = $this->db->affectedRows();

		// data (ранее response)
		// stock_order_id_1 (в принципе наверное можно и без stock_, надо обсудить)
		// stock_order_id_2 (в принципе наверное можно и без stock_, надо обсудить)
		// state (created, canceled, filled)
		
		// dt_upd (дата обновления) - при изменении data
		// ts_upd
		// dt_check (дата актуализации) - при каждом запросе по таску
		// ts_check

		return $cnt;
	}

	public function OrderBinanceLogHandler($obl_id)
	{
		$obl_table = _DB_TABLE_ORDER_BINANCE_LOG_;
		$ob_table = _DB_TABLE_ORDER_BINANCE_;
	    $sql = 'SELECT * FROM ?n WHERE id = ?i LIMIT 1';
	    if ( ! $obl = $this->db->getRow($sql, $obl_table, $obl_id))
	    	return;

    	if ( ! empty($obl['request']) and empty($obl['data'])) {
    		$request = @json_decode($obl['request'], true);
    		$obl['data'] = $answer = $this->ApiBinanceOrderAction($request);
    		$sql = 'UPDATE ?n SET data = ?s WHERE id = ?i LIMIT 1';
    		$this->db->query($sql, $obl_table, $answer, $obl_id);
    		$response = @json_decode($answer, true);
    		$orderReports = @$response['orderReports'] ?? @$response['info']['orderReports'];
    		if ( ! empty($orderReports)) {
    			foreach ($orderReports as $order) {
    				$sql = 'INSERT INTO ?n SET ?u';
    				$this->db->query($sql, $obl_table, [
    					'dt_ins' => date('Y-m-d H:i:s'),
    					'ts_ins' => time(),
    					'action' => $obl['action'],
    					'user_id' => $obl['user_id'],
    					'stock_id' => $obl['stock_id'],
    					'preorder_id' => $obl['preorder_id'],
    					'data' => json_encode(['info' => $order]),
    				]);
    				$this->OrderBinanceLogHandler($this->db->insertId());
    			}
    		}
    	}

    	$stock_order = $this->extractStockOrder($obl['data']);

    	if ( ! empty($stock_order['id_1'])) {

    		$sql = 'UPDATE ?n SET ?u WHERE id = ?i LIMIT 1';
    		$params = [
    			'stock_order_id_1' => $stock_order['id_1'],
    			'stock_order_id_2' => $stock_order['id_2'],
    			'stock_order_state' => $stock_order['state'],
    		];
    		$this->db->query($sql, $obl_table, $params, $obl['id']);

    		$sql = 'SELECT * FROM ?n WHERE stock_order_id_1 = ?s';
    		$ob = $this->db->getAll($sql, $ob_table, $stock_order['id_1']);

    		if (empty($ob)) {
    			return;
    		}
    		if (count($ob) != 1) {
    			$sql = 'UPDATE ?n SET state = -1 WHERE id = ?i LIMIT 1';
    			$this->db->query($sql, $obl_table, $obl['id']);
    			return;
    		}

    		$obl_state = 1;

    		$params = [
    			'dt_check' => date('Y-m-d H:i:s'),
    			'ts_check' => time()
    		];
    		if ($ob[0]['data'] != $obl['data']) {
    			$obl_state = 2;
    			$params['dt_upd'] = date('Y-m-d H:i:s');
    			$params['ts_upd'] = time();
    			$params['data'] = $obl['data'];
    			$params['stock_order_state'] = $stock_order['state'];
    		}
    		$sql = 'UPDATE ?n SET ?u WHERE id = ?i LIMIT 1';
    		$result = $this->db->query($sql, $ob_table, $params, $ob[0]['id']);

    		if ($result !== false) {
    			// set obl.state
				$sql = "UPDATE ?n SET state = {$obl_state} WHERE id = ?i LIMIT 1";
				$this->db->query($sql, $obl_table, $obl['id']);
				// set preorder state
				if ($stock_order['state'] == 'FILLED') {
					$this->setPreorderFilled($ob[0]['preorder_id']);
				}
    		}
    	}
	}

	public function setPreorderFilled($preorder_id)
	{
		$sql = 'SELECT * FROM ?n WHERE id = ?i LIMIT 1';
		$preorder = $this->db->getRow($sql, _DB_TABLE_PREORDER_, $preorder_id);
		if ($preorder['state'] != 'filled') {
			// set state
			$sql = 'UPDATE ?n SET state = "filled" WHERE id = ?i LIMIT 1';
			$result = $this->db->query($sql, _DB_TABLE_PREORDER_, $preorder_id);
			if ($result === false)
				return;
			// handle next preorder
			$sql = 'SELECT * FROM ?n
					WHERE deal_id = ?i AND state = "pending"
					ORDER BY id ASC LIMIT 1';
			$next_preorder = $this->db->getRow($sql, _DB_TABLE_PREORDER_, $preorder['deal_id']);
			if (empty($next_preorder))
				return;
			// new task
			$state = mt_rand(1000000,9999999);
			$result = $this->db->query('INSERT INTO ?n SET ?u', _DB_TABLE_TASK_, [
				'dt_ins' => date('Y-m-d H:i:s'),
				'ts_ins' => time(),
				'preorder_id' => $next_preorder['id'],
				'action' => 'create',
				'mode' => 'after_filled',
				'state' => $state,
			]);
			shell_exec('php -f ' . __DIR__ . '/../tasker.php ' . $state);
			if (empty($result))
				return;
			$sql = 'UPDATE ?n SET state = "new" WHERE id = ?i LIMIT 1';
			$this->db->query($sql, _DB_TABLE_PREORDER_, $next_preorder['id']);
		}
	}

	public function UpdateOrderBinanceByStockData( $data )
	{
		$stock_order = $this->extractStockOrder($data);

		$sql = 'SELECT * FROM ?n WHERE stock_order_id_1 = ?i LIMIT 1';
		$order_binance = $this->db->getRow($sql, _DB_TABLE_ORDER_BINANCE_, $stock_order['id_1']);
		$olddata = $row['data'];

		$sql = 'SELECT * FROM `' . _DB_TABLE_ORDER_BINANCE_ . '` WHERE id=?i LIMIT 1';
		$row = $this->db->getRow($sql, $order_binance_id);
		$olddata = $row['data'];


		// чтобы dt_upd и dt_check совпадали полностью, для будущей проверки изменения data
		$currDate=date('Y-m-d H:i:s');
		$currTime=time();

		$sql_update = 'UPDATE `' . _DB_TABLE_ORDER_BINANCE_ . '` SET dt_check=?s, ts_check=?i';

		// Добавить data (ответ от binance API), если он отличается от уже записанного
		$datanew=false;
		if ( $data != $olddata ){
			$datanew=true;

			$state = $stock_order['state'];
			// Проверка соответствия stock_order_id_1
			// в случае несовпадения stock_order_state меняется не у текущей записи, а у соответствующей
			if ( ! empty($stock_order['id_1']) and ! empty($row['stock_order_id_1']) and $stock_order['id_1'] != $row['stock_order_id_1']) {
				$state = $row['stock_order_state'];
			    $sql = 'SELECT * FROM `' . _DB_TABLE_ORDER_BINANCE_ . '`
			            WHERE stock_order_id_1 = ?s';
			    $db_orders = $this->db->getAll($sql, $stock_order['id_1']);
			    if (count($db_orders) == 1) {
			        $sql = 'UPDATE `' . _DB_TABLE_ORDER_BINANCE_ . '`
			                SET stock_order_state = ?s
			                WHERE id = ?i
			                LIMIT 1';
			        $this->db->query($sql, $stock_order['state'], $db_orders[0]['id']);
			    }
			}
			
			$sql_update .= ', data=?s, dt_upd=?s, ts_upd=?i, stock_order_id_1=?s, stock_order_id_2=?s, stock_order_state=?s';
			$sql_update .= ' WHERE `id` = ?i LIMIT 1';
			$this->db->query(
				$sql_update,
				$currDate,
				$currTime,
				$data,
				$currDate,
				$currTime,
				! empty($row['stock_order_id_1']) ? $row['stock_order_id_1'] : $stock_order['id_1'],
				! empty($row['stock_order_id_2']) ? $row['stock_order_id_2'] : $stock_order['id_2'],
				$state,
				$order_binance_id
			);  // $row['id']

			// Разбор вложенных orderReports
			$info = @json_decode($data, true);
			if ( ! empty($info['orderReports'])) {
				foreach ($info['orderReports'] as $order) {
					if (empty($order['orderId']) or empty($order['status']))
						continue;
				    $sql = 'SELECT * FROM `' . _DB_TABLE_ORDER_BINANCE_ . '`
				            WHERE stock_order_id_1 = ?s';
				    $db_orders = $this->db->getAll($sql, $order['orderId']);
				    // Если order_binance с таким stock_order_id_1 существует, его stock_order_state обновляется
				    if (count($db_orders) > 0)  {
				    	$sql = 'UPDATE `' . _DB_TABLE_ORDER_BINANCE_ . '`
				                SET stock_order_state = ?s
				                WHERE id = ?i
				                LIMIT 1';
			       		$this->db->query($sql, $order['status'], $db_orders[0]['id']);
				    }
				    // Если order_binance с таким stock_order_id_1 не существует, он создается
				    else {
						$sql  = 'INSERT INTO ?n SET ?u';
						$this->db->query($sql, _DB_TABLE_ORDER_BINANCE_, [
							'parent_id' => $row['id'],
							'dt_ins' => $currDate,
							'ts_ins' => $currTime,
							'dt_upd' => $currDate,
							'ts_upd' => $currTime,
							'preorder_id' => $row['preorder_id'],
							'stock_id' => $row['stock_id'],
							'data' => '',
							'stock_order_id_1' => $order['orderId'],
							'stock_order_id_2' => $order['clientOrderId'] ?? '',
							'stock_order_state' => $order['status'],
						]);
				    }
				}
			}
		}else{
			$sql_update .= ' WHERE `id` = ?i LIMIT 1';
			$this->db->query($sql_update, $currDate, $currTime, $order_binance_id);  // $row['id']
		}

		$cnt = $this->db->affectedRows();

		// data (ранее response)
		// stock_order_id_1 (в принципе наверное можно и без stock_, надо обсудить)
		// stock_order_id_2 (в принципе наверное можно и без stock_, надо обсудить)
		// state (created, canceled, filled)
		
		// dt_upd (дата обновления) - при изменении data
		// ts_upd
		// dt_check (дата актуализации) - при каждом запросе по таску
		// ts_check

		return $cnt;
	}

	//---------------------------------------
	// 4) sql: update order_binance_log 
	//    state (int, 0 - по умолчанию, 1 - после update  order_binance.data)
	//---------------------------------------
	public function UpdateOrderBinanceLogState( $order_binance_id, $order_binance_log_id ){

		$sql = 'SELECT ts_upd, ts_check FROM `' . _DB_TABLE_ORDER_BINANCE_ . '` WHERE id=?i LIMIT 1';
		$row = $this->db->getAll($sql, $order_binance_id);
		
		if ( $row[0]['ts_upd'] == $row[0]['ts_check'] ){
			$sql  = 'UPDATE `' . _DB_TABLE_ORDER_BINANCE_LOG_ . '` SET state=1 WHERE id=?i LIMIT 1';
			$this->db->query($sql, $order_binance_log_id);	
		}
		
		// $cnt=$db->affectedRows();
		// return $cnt;
	}

	
	//---------------------------------------
	// if(ok) sql: update task set state=1 where id=.. limit 1
	//---------------------------------------
	public function UpdateTaskState( $task_id ){

		$sql  = 'UPDATE `' . _DB_TABLE_TASK_ . '` SET dt_upd=?s, ts_upd=?i, state=1 WHERE id=?i';

		$this->db->query($sql, 
			date('Y-m-d H:i:s'), time(),
			$task_id
		);	
		
		$cnt = $this->db->affectedRows();
		return $cnt;
	}


	public function ApiBinanceOrder($binance_request){  // {"action":"create","apikey":"****","stock":"binance_spot","type":"limit","side":"buy","positionSide":"long","pair":"btc_usdt","data":{"qty":"20","price":"26444"}}
      //echo $binance_request;
		$d = json_decode($binance_request, 1);

      //if($d['action']       != 'create')       return '{"debug":"only_create"}';
	  //if($d['stock']        != 'binance_spot') return '{"debug":"only_binance_spot"}';
		if($d['positionSide'] != 'long')         return '{"debug":"only_long"}';

		$p = [];
		
		$p['action'] = $d['action'];
		$p['stock']  = $d['stock'];
		
		$p['apikey'] = $d['apikey'];
		$p['type']   = $d['type'];
		$p['pair']   = $d['pair'];
		$p['side']   = $d['side'];
		$p['amount'] = $d['data']['qty'];
		$p['price']  = $d['data']['price'];
		if(!empty($d['data']['stoploss'])) $p['stop_price'] = $d['data']['stoploss'];

		$response = file_get_contents(_URL_ . '?' . http_build_query($p));
	    //$json = file_get_contents('../cryptogate_example_binance_response.json');  // debug
		if (empty($response))
			$response = $http_response_header[0];
		return $response;
	}

	public function ApiBinanceOrderAction($binance_request)
	{
		$response = @file_get_contents(_URL_ . '?' . http_build_query($binance_request));
		if (empty($response))
			$response = $http_response_header[0];
		return $response;
	}


	public function extractStockOrder($json)
	{
	    $data = @json_decode($json, true);
	    $stock_order_id_1 = @$data['info']['i'];
	    $stock_order_id_2 = @$data['info']['c'];
	    $stock_order_state = @$data['info']['X'];
	    if (empty($stock_order_id_1)) {
	        $stock_order_id_1 = @$data['info']['orderId'];
	        $stock_order_id_2 = @$data['info']['clientOrderId'];
	        $stock_order_state = @$data['info']['status'];
	    }
	    if (empty($stock_order_id_1)) {
	        $stock_order_id_1 = @$data['orderListId'];
	        $stock_order_id_2 = @$data['listClientOrderId'];
	        $stock_order_state = @$data['listStatusType'] . '__' . @$data['listOrderStatus'];
	    }
	    if (empty($stock_order_id_1)) {
	        $stock_order_id_1 = @$data['info']['orderListId'];
	        $stock_order_id_2 = @$data['info']['listClientOrderId'];
	        $stock_order_state = @$data['info']['listStatusType'] . '__' . @$data['info']['listOrderStatus'];
	    }
	    return [
	        'id_1' => $stock_order_id_1 ?? '',
	        'id_2' => $stock_order_id_2 ?? '',
	        'state' => $stock_order_state ?? ''
	    ];
	}
}
