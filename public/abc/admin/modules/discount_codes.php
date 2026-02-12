<?php

// Промокоды (коды скидок) для подписок
// 2026-02-03

$a18n['code'] = 'Код';
$a18n['valid_until'] = 'Действует до (включительно)';
$a18n['usage_limit'] = 'Количество использований';
$a18n['subscription_level_ids'] = 'Уровни подписок';
$a18n['discount_percent'] = 'Процент скидки';

$levels = mysql_select("SELECT id, title as name FROM subscription_levels ORDER BY sort_order", 'array');

$table = array(
	'id'       => 'id:desc',
	'code'     => '',
	'valid_until' => 'date',
	'usage_limit' => '',
	'subscription_level_ids' => '',
	'discount_percent' => '',
);

$where = '';
if (isset($get['search']) && $get['search'] != '') {
	$where .= "
		AND (
			LOWER(discount_codes.code) LIKE '%" . mysql_res(mb_strtolower($get['search'], 'UTF-8')) . "%'
		)
	";
}

$query = "
	SELECT discount_codes.*
	FROM discount_codes
	WHERE 1 " . $where . "
";

$filter[] = array('search');

$form[] = array('input td3', 'code', array(
	'help' => 'Уникальный код например: WELCOME10. Регистр не учитывается при проверке.',
));
$form[] = array('input td3', 'discount_percent', array(
	'value' => @$post['discount_percent'] !== '' && @$post['discount_percent'] !== null ? $post['discount_percent'] : 0,
	'help' => 'Процент скидки 0–100. При 100% подписка бесплатная, на оплату не перенаправляем.',
));
$form[] = array('input td3', 'valid_until', array(
	'attr' => 'type="date"',
	'value' => @$post['valid_until'] ? substr($post['valid_until'], 0, 10) : '',
	'help' => 'Дата, до которой код действует (включительно)',
));
$form[] = array('input td3', 'usage_limit', array(
	'value' => @$post['usage_limit'] !== '' && @$post['usage_limit'] !== null ? $post['usage_limit'] : 1,
	'help' => 'Максимум использований кода (всего).',
));
$form[] = array('multicheckbox td12', 'subscription_level_ids', array(
	'value' => array(true, 'SELECT id, title as name FROM subscription_levels ORDER BY sort_order'),
	'name' => 'Уровни подписок (к каким применяется код)',
	'help' => 'Если ни один не выбран — код не применяется ни к каким уровням.',
));
