<?php

// Блоки дашборда ЛК: баннеры (картинка + ссылка) и текстовые объявления
// 2026-02-19

if ($get['u'] == 'edit') {
	$config['mysql_null'] = true;
	if (@$post['image'] == '') $post['image'] = null;
	else $post['image'] = trim($post['image']);
	if (@$post['url'] == '') $post['url'] = null;
	else $post['url'] = trim($post['url']);
	if (@$post['text'] == '') $post['text'] = null;
	if (!isset($post['rank']) || $post['rank'] === '') $post['rank'] = 0;
}

$a18n['type'] = 'Тип';
$a18n['title'] = 'Заголовок';
$a18n['image'] = 'Изображение';
$a18n['url'] = 'Ссылка (URL)';
$a18n['text'] = 'Текст объявления';
$a18n['rank'] = 'Сортировка';
$a18n['display'] = 'Показывать';

$types = array(
	'banner'       => 'Карточка',
	'announcement' => 'Блок на всю ширину',
);

$table = array(
	'id'     => 'rank:desc id:desc',
	'type'   => $types,
	'title'  => '',
	'image'  => 'img',
	'url'    => '',
	'text'   => '',
	'rank'   => '',
	'display'=> 'boolean',
);

$where = '';
if (isset($get['search']) && $get['search'] !== '') {
	$where .= "
		AND (
			LOWER(dashboard_blocks.title) LIKE '%" . mysql_res(mb_strtolower($get['search'], 'UTF-8')) . "%'
			OR LOWER(dashboard_blocks.text) LIKE '%" . mysql_res(mb_strtolower($get['search'], 'UTF-8')) . "%'
		)
	";
}

$query = "
	SELECT dashboard_blocks.*
	FROM dashboard_blocks
	WHERE 1 " . $where . "
";

$filter[] = array('search');

$form[] = array('select td2', 'type', array(
	'value' => array(true, $types, ''),
	'name'  => 'Тип',
	'help'  => 'Карточка — в сетке несколько колонок. Блок на всю ширину, картинка до 200px по высоте.',
));
$form[] = array('input td6', 'title', array(
	'name' => 'Заголовок (необязательно)',
));
$form[] = array('input td2', 'rank', array(
	'value' => @$post['rank'] ?: 0,
));
$form[] = array('checkbox', 'display');

$form[] = array('input td12', 'url', array(
	'name' => 'Ссылка (URL)',
	'help' => 'При клике переход на этот адрес.',
));
$form[] = array('textarea td12', 'text', array(
	'name' => 'Текст',
	'help' => 'Необязательно. Для отображения нужно хотя бы одно: картинка, название или текст.',
));
$form[] = array('file td12', 'image', array(
	'name'  => 'Изображение',
	'sizes' => array('' => '', '600x200' => 'resize 600x200'),
	'help'  => 'Необязательно. Для карточки без картинки показывается только название и/или текст.',
));
