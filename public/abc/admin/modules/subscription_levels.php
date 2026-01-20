<?php

//Уровни подписок (subscription_levels)
/*
 * 2026-01-20 - создан модуль для управления уровнями подписок
 */

$a18n['title'] = 'Название';
$a18n['slug'] = 'Идентификатор';
$a18n['link'] = 'Ссылка';
$a18n['sort_order'] = 'Сортировка';
$a18n['is_active'] = 'Активен';

$table = array(
	'id'		=>	'sort_order:asc id',
	'title'		=>	'',
	'slug'		=>	'',
	'is_active'	=>	'boolean',
	'sort_order'	=>	'',
);

// Поиск
$where = '';
if (isset($get['search']) && $get['search']!='') {
	$where.= "
		AND (
			LOWER(subscription_levels.title) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
			OR LOWER(subscription_levels.slug) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
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
$form[] = array('input td4','slug',array(
	'help'=>'Уникальный идентификатор (например: grade1, grade2)'
));
$form[] = array('input td2','sort_order',array(
	'value'=>@$post['sort_order'] ? $post['sort_order'] : 0
));
$form[] = array('checkbox','is_active');
