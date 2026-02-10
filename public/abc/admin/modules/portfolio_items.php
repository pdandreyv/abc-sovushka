<?php

// Портфолио (сертификаты, дипломы, награды)
// 2026-02-06 — создан модуль для ЛК

if ($get['u'] == 'edit') {
	$config['mysql_null'] = true;
	if (@$post['image_thumb'] == '') $post['image_thumb'] = null;
	else $post['image_thumb'] = trim($post['image_thumb']);
	if (@$post['image_file'] == '') $post['image_file'] = null;
	else $post['image_file'] = trim($post['image_file']);
	if (!isset($post['sort_order']) || $post['sort_order'] === '') $post['sort_order'] = 0;
}

$a18n['title'] = 'Название';
$a18n['badge'] = 'Тип';
$a18n['image_thumb'] = 'Превью';
$a18n['image_file'] = 'Документ (файл)';
$a18n['sort_order'] = 'Сортировка';
$a18n['display'] = 'Показывать';

$table = array(
	'id'         => 'sort_order:desc id:desc',
	'title'      => '',
	'badge'      => '',
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
