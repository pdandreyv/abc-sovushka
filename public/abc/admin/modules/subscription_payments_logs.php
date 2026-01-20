<?php

//Логи платежей подписок (subscription_payments_logs)
/*
 * 2026-01-20 - создан модуль для просмотра логов платежей подписок
 */

//исключение при редактировании модуля
if ($get['u']=='edit') {
	$config['mysql_null'] = true;
	if (@$post['message']=='') $post['message'] = null;
	if (@$post['response_data']=='') $post['response_data'] = null;
	if (@$post['payment_provider']=='') $post['payment_provider'] = null;
	if (@$post['transaction_id']=='') $post['transaction_id'] = null;
}

$a18n['subscription_order_id'] = 'Заказ подписки';
$a18n['status'] = 'Статус';
$a18n['amount'] = 'Сумма';
$a18n['message'] = 'Сообщение';
$a18n['response_data'] = 'Данные ответа';
$a18n['payment_provider'] = 'Провайдер';
$a18n['transaction_id'] = 'ID транзакции';
$a18n['attempted_at'] = 'Время попытки';

$table = array(
	'id'		=>	'attempted_at:desc id',
	'subscription_order_id'	=>	'',
	'status'	=>	'',
	'amount'	=>	'',
	'payment_provider'	=>	'',
	'transaction_id'	=>	'',
	'attempted_at'	=>	'datetime',
	'created_at'	=>	'date_smart',
);

// Поиск
$where = '';
if (isset($get['search']) && $get['search']!='') {
	$where.= "
		AND (
			subscription_payments_logs.id = '".mysql_res($get['search'])."'
			OR subscription_payments_logs.subscription_order_id = '".mysql_res($get['search'])."'
			OR LOWER(subscription_payments_logs.transaction_id) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
		)
	";
}

$query = "
	SELECT subscription_payments_logs.*,
		subscription_orders.user_id,
		users.first_name,
		users.last_name
	FROM subscription_payments_logs
	LEFT JOIN subscription_orders ON subscription_orders.id = subscription_payments_logs.subscription_order_id
	LEFT JOIN users ON users.id = subscription_orders.user_id
	WHERE 1 ".$where."
";

$filter[] = array('search');

$form[] = array('select td6','subscription_order_id',array(
	'value'=>array(true, 'SELECT id, CONCAT("Заказ #", id, " (Пользователь: ", user_id, ")") as name FROM subscription_orders ORDER BY id DESC'),
	'help'=>'Выберите заказ подписки'
));
$form[] = array('select td3','status',array(
	'value'=>array(true, array('success'=>'Успешно', 'error'=>'Ошибка', 'pending'=>'Ожидание'))
));
$form[] = array('input td3','amount',array(
	'help'=>'Сумма в рублях',
	'value'=>@$post['amount'] ? $post['amount'] : 0
));
$form[] = array('input td6','payment_provider',array(
	'help'=>'Название платежного провайдера'
));
$form[] = array('input td6','transaction_id',array(
	'help'=>'ID транзакции от платежной системы'
));
$form[] = array('textarea td12','message',array(
	'help'=>'Сообщение об ошибке или успехе'
));
$form[] = array('textarea td12','response_data',array(
	'help'=>'JSON данные ответа от платежной системы'
));
$form[] = array('datetime td6','attempted_at');
