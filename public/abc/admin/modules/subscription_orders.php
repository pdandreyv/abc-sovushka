<?php

//Заказы подписок (subscription_orders)
/*
 * 2026-01-20 - создан модуль для управления заказами подписок
 */

//исключение при редактировании модуля
if ($get['u']=='edit') {
	$config['mysql_null'] = true;
	if (@$post['date_next_pay']=='') $post['date_next_pay'] = null;
	if (@$post['sum_next_pay']=='') $post['sum_next_pay'] = null;
	if (@$post['hash']=='') $post['hash'] = null;
	if (!isset($post['errors']) || $post['errors']=='') $post['errors'] = 0;
	if (!isset($post['paid'])) $post['paid'] = 0;
	if (!isset($post['auto'])) $post['auto'] = 0;
}

$a18n['user_id'] = 'Пользователь';
$a18n['email'] = 'Пользователь';
$a18n['subscription_level_ids'] = 'Уровни подписок';
$a18n['date_subscription'] = 'Дата подписки';
$a18n['sum_subscription'] = 'Сумма подписки';
$a18n['sum_without_discount'] = 'Сумма без скидки';
$a18n['discount_code'] = 'Промокод';
$a18n['days'] = 'Количество дней';
$a18n['date_next_pay'] = 'Дата следующего платежа';
$a18n['sum_next_pay'] = 'Сумма следующего платежа';
$a18n['hash'] = 'Хеш карты';
$a18n['card_last4'] = 'Карта (4 цифры)';
$a18n['errors'] = 'Ошибки';
$a18n['logs'] = 'Логи';
$a18n['paid'] = 'Оплачен';
$a18n['auto'] = 'Автопродление';

$table = array(
	'id'		=>	'created_at:desc id',
	'email'		=>	'',
	'subscription_level_ids'	=>	'{level_titles}',
	'date_subscription'	=>	'date',
	'sum_subscription'	=>	'',
	'sum_without_discount'	=>	'',
	'discount_code'	=>	'',
	'days'		=>	'',
	'date_next_pay'	=>	'date',
	'sum_next_pay'	=>	'',
	'card_last4'	=>	'',
	'errors'	=>	'',
	'logs'		=>	'<a href="/admin.php?m=subscription_payments_logs&search={id}">Логи</a>',
	'paid'		=>	'boolean',
	'auto'		=>	'boolean',
);

// Поиск (по id заказа, user_id, email пользователя)
$where = '';
if (isset($get['search']) && $get['search']!='') {
	$search = mysql_res($get['search']);
	$where.= "
		AND (
			subscription_orders.id = '".$search."'
			OR subscription_orders.user_id = '".$search."'
			OR users.email LIKE '%".$search."%'
		)
	";
}

$query = "
	SELECT subscription_orders.*,
		users.first_name,
		users.last_name,
		users.email,
		(SELECT GROUP_CONCAT(sl.title ORDER BY sl.id) FROM subscription_levels sl
		 WHERE FIND_IN_SET(sl.id, COALESCE(NULLIF(TRIM(subscription_orders.subscription_level_ids), ''), subscription_orders.levels, '0'))) AS level_titles
	FROM subscription_orders
	LEFT JOIN users ON users.id = subscription_orders.user_id
	WHERE 1 ".$where."
";

$filter[] = array('search');

$form[] = array('select td6','user_id',array(
	'value'=>array(true, 'SELECT id, CONCAT(first_name, " ", last_name, " (", email, ")") as name FROM users WHERE id=\''.@$post['user_id'].'\''),
	//как в модуле заказов: автозаполнение
	'attr'=>'data-url="/admin.php?m=subscription_orders&u=get_users" data-min-input="1"',
	'help'=>'Выберите пользователя'
));
$form[] = array('multicheckbox td12', 'subscription_level_ids', array(
	'value' => array(true, 'SELECT id, title as name FROM subscription_levels ORDER BY sort_order, id'),
	'name'  => 'Уровни подписок',
	'help'  => 'Выберите уровни подписок по названию. Сохраняются как ID через запятую.',
));
$form[] = array('input td3','date_subscription',array(
	'attr'=>'type="date"',
	'value'=>@$post['date_subscription'] ? substr($post['date_subscription'],0,10) : '',
	'help'=>'Дата оформления подписки (ГГГГ-ММ-ДД)'
));
$form[] = array('input td3','sum_subscription',array(
	'help'=>'Сумма в рублях',
	'value'=>@$post['sum_subscription'] ? $post['sum_subscription'] : 0
));
$form[] = array('input td3','sum_without_discount',array(
	'help'=>'Сумма в рублях без скидки',
	'value'=>@$post['sum_without_discount'] ? $post['sum_without_discount'] : 0
));
$form[] = array('input td3','discount_code',array(
	'help'=>'Промокод (строка), если был применён при оформлении'
));
$form[] = array('input td3','days',array(
	'value'=>@$post['days'] ? $post['days'] : 0
));
$form[] = array('input td3','date_next_pay',array(
	'attr'=>'type="date"',
	'value'=>@$post['date_next_pay'] ? substr($post['date_next_pay'],0,10) : '',
	'help'=>'Дата следующего списания (можно менять)'
));
$form[] = array('input td3','sum_next_pay',array(
	'value'=>@$post['sum_next_pay'] ? $post['sum_next_pay'] : 0
));
$form[] = array('input td6','hash',array(
	'help'=>'Хеш карты для рекуррентного платежа'
));
$form[] = array('input td2','card_last4',array(
	'help'=>'Последние 4 цифры карты (из ЮKassa), только для отображения'
));
$form[] = array('input td3','errors',array(
	'value'=>@$post['errors'] ? $post['errors'] : 0
));
$form[] = array('checkbox','paid',array('help'=>'Заказ оплачен (для будущих рекуррентных записей обычно 0)'));
$form[] = array('checkbox','auto',array('help'=>'Включено автопродление по карте'));
