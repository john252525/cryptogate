<?php
class Task{
	private $db;
	
	public function __construct($db) {
        $this->db = $db;
    }


	//---------------------------------------
	// Получить Stock Name
	//---------------------------------------
	private function GetStockName($stock_id){

		$sql  = 'SELECT stock FROM `' . _DB_TABLE_STOCK_ . '` WHERE id=?i';

    	$stock = $this->db->getOne( $sql, $stock_id );
		return $stock;
	}


	public function GetRequest( $task_id ){

		$sql  = 'SELECT DBtask.preorder_id, DBpreorder.* FROM '.
		'`' . _DB_TABLE_TASK_	 . '` AS DBtask, '.
		'`' . _DB_TABLE_PREORDER_ . '` AS DBpreorder '.
		'WHERE DBtask.id=?i AND DBpreorder.id=DBtask.preorder_id';

    	$dbresult = $this->db->query($sql, $task_id);
		$row=$this->db->fetch($dbresult);

		$result['stock']		=$this->GetStockName($row['stock_id']);
		$result['type']			=$row['type'];
		$result['side']			=$row['side'];
		$result['positionSide']	=$row['positionSide'];
		$result['pair']			=$row['pair'];
		$result['data']			=json_decode($row['data'], true);	

		return json_encode($result);
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
		'`' . _DB_TABLE_TASK_	 . '` AS DBtask, '.
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
	public function SaveOrderBinanceLog( $task_id ){

		$response=$this->GetInfo($task_id);

		$sql  = 'INSERT INTO `' . _DB_TABLE_ORDER_BINANCE_LOG_ . '` SET dt_ins=?s, ts_ins=?i, user_id=?i, stock_id=?i, action=?s';
		
		$this->db->query($sql, 
								date('Y-m-d H:i:s'), time(),
								$response[0]['user_id'],
								$response[0]['stock_id'],	
								$response[0]['action'] 		// task action
						);
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
	public function UpdateOrderBinanceLog( $order_binance_log_id, $request, $data ){

		$sql  = 'UPDATE `' . _DB_TABLE_ORDER_BINANCE_LOG_ . '` SET request=?s, data=?s'.
		// ', order_id_1=?i, order_id_2=?s, state=?i'.
		' WHERE id=?i';
		
		$this->db->query($sql, 
								$request,
								$data,
								// $stock_order_id_1,
								// $stock_order_id_2,
								// $state,
								
								$order_binance_log_id
						);

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
	public function UpdateOrderBinance( $order_binance_id, $data ){

		$sql = 'SELECT data FROM `' . _DB_TABLE_ORDER_BINANCE_ . '` WHERE id=?i';
		$olddata = $this->db->getOne($sql, $order_binance_id);


		// чтобы dt_upd и dt_check совпадали полностью, для будущей проверки изменения data
		$currDate=date('Y-m-d H:i:s'); $currTime=time();

		$sql  = 'UPDATE `' . _DB_TABLE_ORDER_BINANCE_ . '` SET'.
		// ' order_id_1=?i, order_id_2=?s, state=?i'.
		' dt_check=?s, ts_check=?i';

		// Добавить data (ответ от binance API), если он отличается от уже записанного
		$datanew=false;
		if ( $data != $olddata ){
			$datanew=true;
			
			$sql.= ', data=?s, dt_upd=?s, ts_upd=?i';
			$this->db->query($sql, $currDate, $currTime, $data, $currDate, $currTime);
		}else{
			
			$this->db->query($sql, $currDate, $currTime);
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

		$sql = 'SELECT ts_upd, ts_check FROM `' . _DB_TABLE_ORDER_BINANCE_ . '` WHERE id=?i';
		$row = $this->db->getAll($sql, $order_binance_id);
		
		if ( $row[0]['ts_upd'] == $row[0]['ts_check'] ){
			$sql  = 'UPDATE `' . _DB_TABLE_ORDER_BINANCE_LOG_ . '` SET state=1 WHERE id=?i';
			$this->db->query($sql, $order_binance_log_id);	
		}
		
		// $cnt=$db->affectedRows();
		// return $cnt;
	}

	
	//---------------------------------------
	// if(ok) sql: update task set state=1 where id=.. limit 1
	//---------------------------------------
	public function UpdateTaskState( $task_id ){

		$sql  = 'UPDATE `' . _DB_TABLE_TASK_ . '` SET state=1 WHERE id=?i';
		$this->db->query($sql, $task_id);	
		
		$cnt = $this->db->affectedRows();
		return $cnt;
	}


	//---------------------------------------
	// пока что заглушка, ответ - рыба
	//---------------------------------------
	public function ApiBinanceOrder($task_id){

		$json=file_get_contents('../cryptogate_example_binance_response.json');

		return $json;
	}
}
