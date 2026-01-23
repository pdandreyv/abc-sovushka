<?php

// Предметы

$levels = mysql_select("SELECT id, title as name FROM subscription_levels ORDER BY sort_order", 'array');

$a18n['title'] = 'Название';
$a18n['subscription_level_id'] = 'Уровень подписки';
$a18n['link'] = 'Ссылка';
$a18n['rating'] = 'Рейтинг';
$a18n['display'] = 'Показывать';

$table = array(
	'id'		=>	'rating:desc id:desc',
	'title'		=>	'',
	'subscription_level_id'	=>	$levels,
	'link'		=>	'',
	'rating'	=>	'',
	'display'	=>	'boolean',
);

// Фильтры и поиск
$where = '';
$filter[] = array('level', $levels, 'уровень подписки');
if (isset($get['search']) && $get['search']!='') {
	$where.= "
		AND (
			LOWER(subjects.title) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
		)
	";
}
if (isset($get['level']) && intval($get['level'])>0) {
	$where.= " AND subjects.subscription_level_id = '".intval($get['level'])."'";
}

$query = "
	SELECT subjects.*
	FROM subjects
	WHERE 1 ".$where."
";

$filter[] = array('search');

$form[] = array('input td8','title');
$form[] = array('select td4','subscription_level_id',array(
	'value'=>array(true, $levels)
));
$form[] = array('input td4','link',array(
	'help'=>'Если пусто, ссылка будет через id предмета'
));
$form[] = array('input td2','rating',array(
	'value'=>@$post['rating'] ? $post['rating'] : 0
));
$form[] = array('checkbox','display');
