<?php

//Тарифы подписок (subscription_tariffs)
/*
 * 2026-01-20 - создан модуль для управления тарифами подписок
 */

$a18n['title'] = 'Название';
$a18n['price'] = 'Цена';
$a18n['days'] = 'Количество дней';
$a18n['rating'] = 'Рейтинг';
$a18n['is_visible'] = 'Показывать';
$a18n['sort_order'] = 'Сортировка';

$table = array(
	'id'		=>	'sort_order:asc id',
	'title'		=>	'',
	'price'		=>	'',
	'days'		=>	'',
	'is_visible'	=>	'boolean',
	'sort_order'	=>	'',
);

// Поиск
$where = '';
if (isset($get['search']) && $get['search']!='') {
	$where.= "
		AND (
			LOWER(subscription_tariffs.title) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
		)
	";
}

$query = "
	SELECT subscription_tariffs.*
	FROM subscription_tariffs
	WHERE 1 ".$where."
";

$filter[] = array('search');

$form[] = array('input td8','title');
$form[] = array('input td2','price',array(
	'help'=>'Цена в рублях',
	'value'=>@$post['price'] ? $post['price'] : 0
));
$form[] = array('input td2','days',array(
	'help'=>'Количество дней (1 месяц = 30, 3 месяца = 91, 12 месяцев = 365)',
	'value'=>@$post['days'] ? $post['days'] : 0
));
$form[] = array('input td2','sort_order',array(
	'value'=>@$post['sort_order'] ? $post['sort_order'] : 0
));
$form[] = array('checkbox','is_visible');
