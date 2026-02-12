<?php

//Уровни подписок (subscription_levels)
/*
 * 2026-01-20 - создан модуль для управления уровнями подписок
 * 2026-02-03 - добавлено поле demo_file (демо-файл для отображения и скачивания)
 */

if ($get['u'] == 'edit') {
	$config['mysql_null'] = true;
	if (@$post['demo_file'] == '') $post['demo_file'] = null;
	else $post['demo_file'] = trim($post['demo_file']);
}

$a18n['title'] = 'Название';
//$a18n['slug'] = 'Идентификатор';
$a18n['link'] = 'Ссылка';
$a18n['demo_file'] = 'Демо-файл';
$a18n['demo_block_title'] = 'Заголовок блока демо-уроков';
$a18n['demo_block_description'] = 'Описание блока демо-уроков';
$a18n['sort_order'] = 'Сортировка';
$a18n['open'] = 'Открытый раздел';
$a18n['display'] = 'В выборе подписок';
$a18n['is_active'] = 'Активен';

$table = array(
	'id'		=>	'sort_order:desc id:desc',
	'title'		=>	'',
	//'slug'		=>	'',
	'demo_file'	=>	'',
	'open'		=>	'boolean',
	'display'	=>	'boolean',
	'is_active'	=>	'boolean',
	'sort_order'	=>	'',
);

// Поиск
$where = '';
if (isset($get['search']) && $get['search']!='') {
	$where.= "
		AND (
			LOWER(subscription_levels.title) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
		)
	";
}

$query = "
	SELECT subscription_levels.*
	FROM subscription_levels
	WHERE 1 ".$where."
";

$filter[] = array('search');

$form[] = array('input td8','title');
$form[] = array('input td6','link',array('help'=>'Ссылка на страницу направления'));
$form[] = array('file td12','demo_file',array(
	'name'=>'Демо-файл (любой файл для отображения и скачивания)',
	'help'=>'Загрузите файл — в форме будет отображаться ссылка на него и возможность скачать.'
));
$form[] = array('input td12','demo_block_title',array(
	'name'=>'Заголовок блока демо-уроков',
	'help'=>'Текст над блоком демо-файла на странице уровня (например: Демо-уроки (можно скачать бесплатно)).',
	'placeholder'=>'Демо-уроки (можно скачать бесплатно)'
));
$form[] = array('textarea td12','demo_block_description',array(
	'name'=>'Описание блока демо-уроков',
	'help'=>'Подзаголовок под заголовком демо-блока на странице уровня. В тексте можно использовать {title} — подставится название уровня.',
	'placeholder'=>'Этот блок помогает пользователю увидеть пример материалов по подписке. Позже сюда можно подгружать демо-уроки из базы данных.'
));
$form[] = array('input td2','sort_order',array(
	'value'=>@$post['sort_order'] ? $post['sort_order'] : 0
));
$form[] = array('checkbox','open',array('help'=>'Если включено: в боковом меню ЛК появляется ссылка на этот уровень, материалы доступны всем без подписки'));
$form[] = array('checkbox','display',array('help'=>'Если включено: уровень показывается на странице выбора подписок. При display=0 уровень скрыт из выбора, но в меню ЛК по-прежнему отображаются пункты с open=1'));
$form[] = array('checkbox','is_active');
