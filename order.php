<?php

	echo '<head><meta charset="UTF-8"><head>';

	set_time_limit(60);
	mb_internal_encoding("UTF-8");

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	require_once 'config.php';
	require_once 'lib/db/safemysql.class.php';

	$opts = array(
			'user'    => _DB_LOGIN_,
			'pass'    => _DB_PASSWORD_,
			'db'      => _DB_DATABASE_,
			'charset' => 'utf8'
	);
	$db = new SafeMySQL($opts); // with some of the default settings overwritten


	// debug data
	if (!isset($_POST['json'])){
		$_POST['json']=file_get_contents('../cryptogate_example_request.json');
		// echo "<pre>"; print_r(json_decode($_POST['json'],true)); echo "</pre>";		
	}

//---------------------------------------------------------------
	if ( (!isset($_GET['sync'])) && (!isset($_GET['async'])) ){

		$sync_mode = false;

	}else{

		$sync_mode = (isset($_GET['sync'])) ? 'sync' : 'async';

	}
//---------------------------------------------------------------
	if ( isset($_GET['token']) ){

		require_once 'lib/Users.class.php';
		$user = new Users($db); 

		$token=$_GET['token'];		
		$user_id=$user->GetUserId($token);

		if ($user_id==false){
			die('{"ok":false,"error":"wrong token"}');
		}

	}else{
		die('{"ok":false,"error":"need token"}');
	}
//---------------------------------------------------------------
	if ( isset($_GET['action']) ) {

		require_once 'lib/Order.class.php';
		$orderObject = new Order($db); 


		switch ($_GET['action']) {
			case 'create':
				
				if ( !isset($_GET['stock']) ) {
					die('{"ok":false,"error":"need stock"}');
				}

				if ($sync_mode===false) $sync_mode = _default_mode_order_create_;
				$state = ($sync_mode=='sync') ? mt_rand(1000000,9999999) : 0;

				$deal_id=-1;

				$orders=json_decode($_POST['json'],true);
				if (count($orders)>1) {

					$deal_id=$orderObject->SaveDeal( $user_id, count($orders) );
				}


				foreach ($orders as $key => $order) {
					$orderstate = ($key>0) ? 'pending' : 'new';

					$stock_id = $orderObject->GetStockId( $user_id, $order['stock'] );

					if ( $stock_id === false){
						die('{"ok":false,"error":"wrong stock"}');
					}

					// echo $stock_id.'<br>';
					// echo "<pre>"; print_r($apikeys); echo "</pre>";

					//----------------------------------------------------
					// сохранить входящий ордер					
					$order['user_id']=$user_id;
					$order['deal_id']=$deal_id;
					$order['stock_id']=$stock_id;
					$order['state']=$orderstate;
					$preorder_id=$orderObject->SavePreOrder( $order );
					
					//----------------------------------------------------
					// Если первый из набора ордеров, сохраняем в задачи
					if ($orderstate == 'new'){
						
						$task_order_id=$preorder_id; // сохраняем главный preorder_id 
						
						$task_id=$orderObject->SaveTask( 
													$task_order_id, 
													$_GET['action'], 
													$sync_mode,
													$state
												 );
					} 
					//----------------------------------------------------
			
				}

				break;
			
			case 'get':

				if ($sync_mode===false) $sync_mode = _default_mode_order_get_;
				$state = ($sync_mode=='sync') ? mt_rand(1000000,9999999) : 0;


				//----------------------------------------------------
				// сохраняем в задачи
				$task_order_id=$_GET['order_id'];
				$task_id=$orderObject->SaveTask( 
												$task_order_id, 
												$_GET['action'], 
												$sync_mode,
												$state
											 );
				//----------------------------------------------------

				break;
			
			case 'cancel':
			
				if ($sync_mode===false) $sync_mode = _default_mode_order_cancel_;
				$state = ($sync_mode=='sync') ? mt_rand(1000000,9999999) : 0;


				//----------------------------------------------------
				// сохраняем в задачи
				$task_order_id=$_GET['order_id'];
				$task_id=$orderObject->SaveTask( 
												$task_order_id, 
												$_GET['action'], 
												$sync_mode,
												$state
											 );
			//----------------------------------------------------
				break;
			
			default:
				die('{"ok":false,"error":"wrong action"}');
				break;

		}

		// if ($sync_mode == 'sync') shell_exec("tasker.php $state");

		$result= $orderObject->GetResponse($task_id);
		echo "<pre>"; print_r($result); echo "</pre>";

	}else{
		die('{"ok":false,"error":"need action"}');
	}
//---------------------------------------------------------------




?>