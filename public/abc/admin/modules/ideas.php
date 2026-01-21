<?php

//идеи (кладовая идей)
/*
 * 2026-01-16 - создан модуль для управления идеями
 */

//исключение при редактировании модуля
if ($get['u']=='edit') {
	// Обработка пустых полей
	$config['mysql_null'] = true;
	// hypertext обрабатывается автоматически, не нужно обрезать
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
$a18n['rank'] = 'Сортировка';
$a18n['display'] = 'Показывать';

$table = array(
	'id'		=>	'rank:desc created_at:desc id:desc',
	'title'		=>	'',
	'image'		=>	'img',
	'rank'		=>	'',
	'likes'		=>	'',
	'display'	=>	'boolean',
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

$form[] = array('input td8','title');
$form[] = array('input td2','rank');
$form[] = array('checkbox','display');
$form[] = array('input td2','likes',array(
	'name'=>'Количество лайков',
	'value'=>@$post['likes'] ? $post['likes'] : 0
));
$form[] = array('hypertext td12','description');
$form[] = array('file td12','image',array(
	'name'=>'Картинка',
	'sizes'=>array(''=>'', '270x185'=>'resize 270x185')
));
$form[] = array('file td12','pdf_file',array(
	'name'=>'Файл PDF'
));
$form[] = array('file td12','zip_file',array(
	'name'=>'Файл ZIP'
));

$content = html_array('form/hypertext_templates',array('key'=>'description'));
