<?php

// Акции (promotions) — персональные акции для пользователей
// 2026-02-12

if ($get['u'] == 'edit') {
	$config['mysql_null'] = true;
	if (@$post['used_at'] == '') $post['used_at'] = null;
	if (!isset($post['used'])) $post['used'] = 0;
}

$a18n['user_id'] = 'Пользователь';
$a18n['email'] = 'Пользователь';
$a18n['subscription_level_ids'] = 'Уровни подписок';
$a18n['tariff_id'] = 'Тариф';
$a18n['tariff_display'] = 'Тариф';
$a18n['special_price'] = 'Спец. цена (₽)';
$a18n['free_days'] = 'Бесплатных дней';
$a18n['used'] = 'Использовано';

$table = array(
	'id' => 'id:desc',
	'email' => '',
	'subscription_level_ids' => '',
	'tariff_display' => 'Тариф',
	'special_price' => '',
	'free_days' => '',
	'used' => 'boolean',
);

$where = '';
if (isset($get['search']) && $get['search'] != '') {
	$search = mysql_res($get['search']);
	$where .= "
		AND (
			promotions.id = '" . intval($search) . "'
			OR promotions.user_id = '" . intval($search) . "'
			OR users.email LIKE '%" . $search . "%'
			OR users.user_code = '" . mysql_res($search) . "'
		)
	";
}

$query = "
	SELECT promotions.*,
		users.email,
		users.first_name,
		users.last_name,
		users.user_code,
		(SELECT CONCAT(subscription_tariffs.title, ' (', promotions.special_price, ' ₽)') FROM subscription_tariffs WHERE subscription_tariffs.id = promotions.tariff_id) AS tariff_display
	FROM promotions
	LEFT JOIN users ON users.id = promotions.user_id
	WHERE 1 " . $where . "
";

$filter[] = array('search');

$form[] = array('select td6', 'user_id', array(
	'value' => array(true, 'SELECT id, CONCAT(COALESCE(user_code,""), " ", first_name, " ", last_name, " (", email, ")") as name FROM users WHERE id=\'' . @$post['user_id'] . '\''),
	'attr' => 'data-url="/admin.php?m=promotions&u=get_users" data-min-input="1"',
	'help' => 'Поиск по ID, полному ID (user_code) или email',
));
$form[] = array('checkbox', 'used', array(
	'help' => 'Отметьте, если акция уже использована (или снимайте галочку для сброса).',
));
$form[] = array('multicheckbox td12', 'subscription_level_ids', array(
	'value' => array(true, 'SELECT id, title as name FROM subscription_levels ORDER BY sort_order DESC, id DESC'),
	'name' => 'Уровни подписки',
	'help' => 'Выберите уровни, которые входят в акцию.',
));
$form[] = array('select td6', 'tariff_id', array(
	'value' => array(true, 'SELECT id, CONCAT(title, " (", price, " ₽)") as name FROM subscription_tariffs ORDER BY sort_order'),
	'help' => 'Тариф, по которому будет списание после бесплатных дней.',
));
$form[] = array('input td3', 'special_price', array(
	'value' => @$post['special_price'] !== '' && @$post['special_price'] !== null ? $post['special_price'] : 0,
	'help' => 'Специальная цена за выбранный тариф (отображается в скобках на сайте).',
));
$form[] = array('input td3', 'free_days', array(
	'value' => @$post['free_days'] !== '' && @$post['free_days'] !== null ? $post['free_days'] : 0,
	'help' => 'Количество бесплатных дней после привязки карты.',
));
