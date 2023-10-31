<?php
	
  //define('_DB_LOGIN_',  	'');
  //define('_DB_PASSWORD_', '');
  //define('_DB_DATABASE_', '');
    require_once '../.env.cryptogate';

	define('_DB_TABLE_USER_', 	 		 	'user'); // пользователи
	define('_DB_TABLE_STOCK_', 				'stock'); // биржи
	define('_DB_TABLE_DEAL_',				'deal'); // сделки, объединяющие несколько ордеров
	define('_DB_TABLE_PREORDER_',  			'preorder'); // входящие запросы, ордера
	define('_DB_TABLE_TASK_',  				'task'); // задания, что надо сделать с ордерами из preorder
	define('_DB_TABLE_ORDER_BINANCE_',		'order_binance'); // ордера, отправленные на биржу binance
	define('_DB_TABLE_ORDER_BINANCE_LOG_', 	'order_binance_log'); // логирование изменений статусов ордеров

	define('_default_mode_order_create_',  	'async'); // sync/async
	define('_default_mode_order_cancel_',  	'sync'); // sync/async
	define('_default_mode_order_get_',  	'sync'); // sync/async
