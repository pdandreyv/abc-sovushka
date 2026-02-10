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
$form[] = array('input td6','link',array('help'=>'Ссылка на страницу направления (например: demo/sub_1.html или путь для роута)'));
$form[] = array('file td12','demo_file',array(
	'name'=>'Демо-файл (любой файл для отображения и скачивания, как на demo/sub_1.html)',
	'help'=>'Загрузите файл — в форме будет отображаться ссылка на него и возможность скачать.'
));
$form[] = array('input td2','sort_order',array(
	'value'=>@$post['sort_order'] ? $post['sort_order'] : 0
));
$form[] = array('checkbox','open',array('help'=>'Если включено: в боковом меню ЛК появляется ссылка на этот уровень, материалы доступны всем без подписки'));
$form[] = array('checkbox','display',array('help'=>'Если включено: уровень показывается на странице выбора подписок. При display=0 уровень скрыт из выбора, но в меню ЛК по-прежнему отображаются пункты с open=1'));
$form[] = array('checkbox','is_active');
