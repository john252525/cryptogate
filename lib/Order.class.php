<?php
class Order{
	private $db;
	
	public function __construct($db) {
        $this->db = $db;
    }

	//---------------------------------------
	// UUID Generator
	//---------------------------------------
	private function guidv4($data = null) {
	    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
	    $data = $data ?? random_bytes(16);
	    assert(strlen($data) == 16);

	    // Set version to 0100
	    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
	    // Set bits 6-7 to 10
	    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

	    // Output the 36 character UUID.
	    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	//---------------------------------------
	// Сохранить кол-во ордеров в БД deal (сделки, объединяющие несколько ордеров)
	//---------------------------------------
	public function SaveDeal($user_id, $count_order){
		$sql  = 'INSERT INTO `' . _DB_TABLE_DEAL_ . '` SET uuid=?s, dt_ins=?s, ts_ins=?i, user_id=?i, count_order=?i';
		
		$this->db->query($sql, 
								$this->guidv4(), date('Y-m-d H:i:s'), time(),
								$user_id,
								$count_order
						);

		$result=$this->db->insertId();

		return $result;
	}



	//---------------------------------------
	// Получить Stock ID 
	//---------------------------------------
	public function GetStockId($user_id, $stock){

		if ( is_numeric($stock) ) {
	
			$sql  = 'SELECT id FROM `' . _DB_TABLE_STOCK_ . '` WHERE user_id=?i AND id=?i';

		}else{

			$sql  = 'SELECT id FROM `' . _DB_TABLE_STOCK_ . '` WHERE user_id=?i AND stock=?s ORDER BY id ASC LIMIT 0,1';
		}
    	$stock_id = $this->db->getOne($sql, 
											$user_id,
											$stock
										  );
		return $stock_id;
	}

	//---------------------------------------
	// Получить Stock Name
	//---------------------------------------
	private function GetStockName($stock_id){

		$sql  = 'SELECT stock FROM `' . _DB_TABLE_STOCK_ . '` WHERE id=?i';

    	$stock = $this->db->getOne( $sql, $stock_id );
		return $stock;
	}

	//---------------------------------------
	// Сохранить входящий ордер в БД preorder (входящие запросы, ордера)
	//---------------------------------------
	public function SavePreOrder($order){
		$sql  = 'INSERT INTO `' . _DB_TABLE_PREORDER_ . '` SET uuid=?s, dt_ins=?s, ts_ins=?i, user_id=?i, deal_id=?i, stock_id=?i, type=?s, side=?s, positionSide=?s, pair=?s, data=?s, state=?s';
		
	// user_id
	// deal_id (по умолчанию =-1)
	// stock_id
	// type (market, limit, oco)
	// side (buy, sell)
	// positionSide (long, short)
	// pair (btc_usdt)
	// data (json: qty, price, stoploss)
	// state (new, pending, created, canceled, filled)

		$data = (isset($order['data'])) ? json_encode($order['data']) : '';

		$this->db->query($sql, 
								$this->guidv4(), date('Y-m-d H:i:s'), time(),
								$order['user_id'],
								$order['deal_id'],
								$order['stock_id'],
								$order['type'],
								$order['side'],
					      @trim($order['positionSide']),  // иначе Warning: Undefined array key "positionSide", "pair"; Fatal error: Uncaught mysqli_sql_exception: Column 'positionSide' cannot be null
						  @trim($order['pair']),
								$data,
								$order['state']
						);

		$result=$this->db->insertId();

		return $result;
	}
	
	//---------------------------------------
	// Сохранить задание в БД task (задания, что надо сделать с ордерами из preorder)
	//---------------------------------------
	public function SaveTask( $preorder_id, $action, $mode,	$state){
		$sql  = 'INSERT INTO `' . _DB_TABLE_TASK_ . '` SET dt_ins=?s, ts_ins=?i, preorder_id=?i, action=?s, mode=?s, state=?i';
		
	// id
	// dt_ins
	// ts_ins
	// preorder_id
	// action (create, cancel, get)
	// mode (sync, async)
	// state (int, 0 - по умолчанию, 1 - таск исполнен)
	// dt_upd
	// ts_upd

		$this->db->query($sql, 
								date('Y-m-d H:i:s'), time(),
								$preorder_id,
								$action,
								$mode,
								$state
						);

		$result=$this->db->insertId();

		return $result;
	}


	//---------------------------------------
	// Сохранить задание в БД task (задания, что надо сделать с ордерами из preorder)
	// (sql: select from deal, preorder, task, order_binance)
	//---------------------------------------
	// public function GetResponse( $preorder_id ){
	public function GetResponse( $task_id ){

		$result=array('deal'=>array(), 'orders'=>array(), 'task'=>array() );

		//----------------------------------------------------------
		// Данные из task
		//----------------------------------------------------------

		// $sql  = 'SELECT state, id, action, mode FROM `' . _DB_TABLE_TASK_ . '` WHERE preorder_id=?i';
		// echo $preorder_id;
		// $row = $this->db->getAll($sql, $preorder_id);

		$sql  = 'SELECT id, state, action, preorder_id, mode FROM `' . _DB_TABLE_TASK_ . '` WHERE id=?i';
		$row = $this->db->getAll($sql, $task_id);

		// echo $task_id;
		// echo "task:<pre>"; print_r($row); echo "</pre>";
		
		$result['task']['state']	=$row[0]['state'];
		$result['task']['id']		=$row[0]['id'];
		$result['task']['action']	=$row[0]['action'];
		$result['task']['mode']		=$row[0]['mode'];
		$result['task']['order_id']	=$row[0]['preorder_id'];
		
		//----------------------------------------------------------
		// Данные из preorder
		//----------------------------------------------------------
		$preorder_id=$result['task']['order_id'];
		
		$sql  = 'SELECT * FROM `' . _DB_TABLE_PREORDER_ . '` WHERE id=?i';
    	$dbresult = $this->db->query($sql, $preorder_id);
		$row=$this->db->fetch($dbresult);

		$deal_id=$row['deal_id'];

		// Проверка, если Deal_ID >0 , то есть и другие ордера
		if ( $deal_id == -1 ){

				$result['task']['order_uuid']=$row['uuid'];

	    		$result['orders'][0]['state']			=$row['state'];
	    		$result['orders'][0]['id']				=$row['id'];
	    		$result['orders'][0]['uuid']			=$row['uuid'];
	    		$result['orders'][0]['stock']			=$row['stock_id'];
	    		$result['orders'][0]['type']			=$row['type'];
	    		$result['orders'][0]['side']			=$row['side'];
	    		$result['orders'][0]['positionSide']	=$row['positionSide'];
	    		$result['orders'][0]['pair']			=$row['pair'];
	    		$result['orders'][0]['data']			=json_decode($row['data'], true);
		

		}else{
			$sql  = 'SELECT * FROM `' . _DB_TABLE_PREORDER_ . '` WHERE deal_id=?i ORDER BY id ASC';		
	    	$dbresult = $this->db->query($sql, $deal_id);

	    	$key=0;
	    	while ($row=$this->db->fetch($dbresult)){

				if ( $key==0) $result['task']['order_uuid']=$row['uuid'];

	    		$result['orders'][$key]['state']		=$row['state'];
	    		$result['orders'][$key]['id']			=$row['id'];
	    		$result['orders'][$key]['uuid']			=$row['uuid'];
	    		$result['orders'][$key]['stock']		=$this->GetStockName($row['stock_id']);
	    		$result['orders'][$key]['type']			=$row['type'];
	    		$result['orders'][$key]['side']			=$row['side'];
	    		$result['orders'][$key]['positionSide']	=$row['positionSide'];
	    		$result['orders'][$key]['pair']			=$row['pair'];
	    		$result['orders'][$key]['data']			=json_decode($row['data'], true);

	    		$key++;
				// echo "<pre>"; print_r($row); echo "</pre>";
	    	}
		}

    	
		//----------------------------------------------------------
		// Данные из deal
		//----------------------------------------------------------
		if ( $deal_id > -1 ){

			$sql  = 'SELECT id, uuid, count_order FROM `' . _DB_TABLE_DEAL_ . '` WHERE id=?i';		
	    	$row = $this->db->getAll($sql, $deal_id);
			
			$result['deal']['id']			=$row[0]['id'];
			$result['deal']['uuid']			=$row[0]['uuid'];
			$result['deal']['count_orders']	=$row[0]['count_order'];

		}


		return $result;
	}







}
