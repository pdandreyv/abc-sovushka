<?php

// Портфолио (сертификаты, дипломы, награды)
// 2026-02-06 — создан модуль для ЛК
// 2026-02-10 — уровень подписки, период дат, пользователь (именной сертификат)

if ($get['u'] == 'edit') {
	$config['mysql_null'] = true;
	if (@$post['image_thumb'] == '') $post['image_thumb'] = null;
	else $post['image_thumb'] = trim($post['image_thumb']);
	if (@$post['image_file'] == '') $post['image_file'] = null;
	else $post['image_file'] = trim($post['image_file']);
	if (!isset($post['sort_order']) || $post['sort_order'] === '') $post['sort_order'] = 0;
	if (@$post['subscription_level_id'] == '') $post['subscription_level_id'] = null;
	if (@$post['date_from'] == '') $post['date_from'] = null;
	if (@$post['date_to'] == '') $post['date_to'] = null;
	if (@$post['user_id'] == '') $post['user_id'] = null;
}

$a18n['title'] = 'Название';
$a18n['badge'] = 'Тип';
$a18n['image_thumb'] = 'Превью';
$a18n['image_file'] = 'Документ (файл)';
$a18n['sort_order'] = 'Сортировка';
$a18n['display'] = 'Показывать';
$a18n['subscription_level_id'] = 'Уровень подписки';
$a18n['date_from'] = 'Период от';
$a18n['date_to'] = 'Период до';
$a18n['user_id'] = 'Пользователь (именной сертификат)';

$subscription_levels = mysql_select("SELECT id, title as name FROM subscription_levels ORDER BY sort_order, id", 'array');

$table = array(
	'id'         => 'sort_order:desc id:desc',
	'title'      => '',
	'badge'      => '',
	'subscription_level_id' => $subscription_levels,
	'date_from'  => 'date',
	'date_to'    => 'date',
	'user_id'    => '',
	'image_thumb' => 'img',
	'sort_order' => '',
	'display'    => 'boolean',
);

$where = '';
if (isset($get['search']) && $get['search'] !== '') {
	$where .= "
		AND (
			LOWER(portfolio_items.title) LIKE '%" . mysql_res(mb_strtolower($get['search'], 'UTF-8')) . "%'
			OR LOWER(portfolio_items.badge) LIKE '%" . mysql_res(mb_strtolower($get['search'], 'UTF-8')) . "%'
		)
	";
}

$query = "
	SELECT portfolio_items.*
	FROM portfolio_items
	WHERE 1 " . $where . "
";

$filter[] = array('search');

$form[] = array('input td8', 'title');
$form[] = array('input td4', 'badge', array(
	'help' => 'Подпись на карточке в ЛК (например: Сертификат, Диплом, Награда).',
));
$form[] = array('select td6', 'subscription_level_id', array(
	'value' => array(true, 'SELECT id, title as name FROM subscription_levels WHERE is_active=1 ORDER BY sort_order, id', ''),
	'name'  => 'Уровень подписки',
	'help'  => 'Для не именного сертификата: показывается, если у пользователя была подписка на этот уровень в указанный период. Пусто — без привязки к уровню.',
));
$form[] = array('input td3', 'date_from', array(
	'attr'  => 'type="date"',
	'value' => @$post['date_from'] ? substr($post['date_from'], 0, 10) : '',
	'name'  => 'Период от',
	'help'  => 'Дата начала периода (ГГГГ-ММ-ДД). Для не именного: сертификат виден, если подписка была в этом периоде.',
));
$form[] = array('input td3', 'date_to', array(
	'attr'  => 'type="date"',
	'value' => @$post['date_to'] ? substr($post['date_to'], 0, 10) : '',
	'name'  => 'Период до',
	'help'  => 'Дата окончания периода (ГГГГ-ММ-ДД).',
));
$form[] = array('select td6', 'user_id', array(
	'value' => array(true, 'SELECT id, CONCAT(COALESCE(last_name,""), " ", COALESCE(first_name,""), " (", email, ")") as name FROM users WHERE id=\'' . @$post['user_id'] . '\''),
	'name'  => 'Пользователь (именной сертификат)',
	'attr'  => 'data-url="/admin.php?m=portfolio_items&u=get_users" data-min-input="1"',
	'help'  => 'Если указан — именной сертификат: отображается только у этого пользователя в ЛК. Если пусто — сертификат по подписке и периоду.',
));
$form[] = array('input td2', 'sort_order', array(
	'value' => @$post['sort_order'] ?: 0,
));
$form[] = array('checkbox', 'display');
$form[] = array('file td12', 'image_thumb', array(
	'name'  => 'Превью (картинка для карточки)',
	'sizes' => array('' => '', '270x185' => 'resize 270x185'),
));
$form[] = array('file td12', 'image_file', array(
	'name' => 'Документ (файл для кнопок «Посмотреть» и «Скачать» — PDF или изображение)',
));
