<?php

//идеи (кладовая идей)
/*
 * 2026-01-16 - создан модуль для управления идеями
 */

//исключение при редактировании модуля
if ($get['u']=='edit') {
	// Обработка пустых полей
	$config['mysql_null'] = true;
	if (@$post['description']=='') $post['description'] = null;
	else $post['description'] = trim($post['description']);
	if (@$post['image']=='') $post['image'] = null;
	else $post['image'] = trim($post['image']);
	if (@$post['pdf_file']=='') $post['pdf_file'] = null;
	else $post['pdf_file'] = trim($post['pdf_file']);
	if (@$post['zip_file']=='') $post['zip_file'] = null;
	else $post['zip_file'] = trim($post['zip_file']);
	// Устанавливаем значение по умолчанию для likes
	if (!isset($post['likes']) || $post['likes']=='') $post['likes'] = 0;
}

$a18n['title'] = 'Название';
$a18n['description'] = 'Описание';
$a18n['image'] = 'Картинка';
$a18n['pdf_file'] = 'Файл PDF';
$a18n['zip_file'] = 'Файл ZIP';
$a18n['likes'] = 'Лайки';

$table = array(
	'id'		=>	'created_at:desc id',
	'title'		=>	'',
	'description'	=>	'',
	'likes'		=>	'',
	'created_at'	=>	'date_smart',
);

// Поиск
$where = '';
if (isset($get['search']) && $get['search']!='') {
	$where.= "
		AND (
			LOWER(ideas.title) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
			OR LOWER(ideas.description) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
		)
	";
}

$query = "
	SELECT ideas.*
	FROM ideas
	WHERE 1 ".$where."
";

$filter[] = array('search');

$form[] = array('input td12','title');
$form[] = array('textarea td12','description');
$form[] = array('file td12','image',array(
	'name'=>'Картинка'
));
$form[] = array('file td12','pdf_file',array(
	'name'=>'Файл PDF'
));
$form[] = array('file td12','zip_file',array(
	'name'=>'Файл ZIP'
));
$form[] = array('input td3','likes',array(
	'name'=>'Количество лайков',
	'value'=>@$post['likes'] ? $post['likes'] : 0
));
