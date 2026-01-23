<?php

// Темы

if ($get['u']=='add') {
	if (isset($get['level']) && intval($get['level'])>0) $post['subscription_level_id'] = intval($get['level']);
	if (isset($get['subject']) && intval($get['subject'])>0) $post['subject_id'] = intval($get['subject']);
}
if ($get['u']=='edit') {
	if (!isset($post['rank']) || $post['rank']==='') $post['rank'] = 0;
}

$levels = mysql_select("SELECT id, title as name FROM subscription_levels ORDER BY sort_order", 'array');
$subjects = mysql_select("SELECT id, title as name FROM subjects ORDER BY rating DESC, title", 'array');

$a18n['title'] = 'Название';
$a18n['text'] = 'Текст';
$a18n['rank'] = 'Рейтинг';
$a18n['is_blocked'] = 'Заблокирован';
$a18n['display'] = 'Показывать';
$a18n['subscription_level_id'] = 'Уровень подписки';
$a18n['subject_id'] = 'Предмет';

$table = array(
	'id'		=>	'rank:desc id:desc',
	'title'		=>	'',
	'subscription_level_id'	=>	$levels,
	'subject_id'	=>	$subjects,
	'rank'		=>	'',
	'is_blocked'	=>	'boolean',
	'display'	=>	'boolean',
);

// Фильтры
$filter[] = array('level', $levels, 'уровень подписки');
$filter[] = array('subject', $subjects, 'предмет');
$filter[] = array('search');

$where = '';
if (isset($get['level']) && intval($get['level'])>0) {
	$where.= " AND topics.subscription_level_id = '".intval($get['level'])."'";
}
if (isset($get['subject']) && intval($get['subject'])>0) {
	$where.= " AND topics.subject_id = '".intval($get['subject'])."'";
}
if (isset($get['search']) && $get['search']!='') {
	$where.= "
		AND (
			LOWER(topics.title) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
			OR LOWER(topics.text) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
		)
	";
}

$query = "
	SELECT topics.*
	FROM topics
	WHERE 1 ".$where."
";

$form[] = array('input td6','title');
$form[] = array('input td2','rank',array(
	'value'=>@$post['rank'] ? $post['rank'] : 0
));
$form[] = array('checkbox td2','display');
$form[] = array('checkbox td2','is_blocked');
$form[] = array('select td2','subscription_level_id',array(
	'value'=>array(true, $levels)
));
$form[] = array('select td2','subject_id',array(
	'value'=>array(true, $subjects)
));
$form[] = array('tinymce td12','text');