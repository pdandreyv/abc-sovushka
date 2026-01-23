<?php

// Материалы к темам

// исключение при редактировании модуля
if ($get['u']=='edit') {
	$config['mysql_null'] = true;
	if (@$post['pdf_file']=='') $post['pdf_file'] = null;
	else $post['pdf_file'] = trim($post['pdf_file']);
	if (@$post['zip_file']=='') $post['zip_file'] = null;
	else $post['zip_file'] = trim($post['zip_file']);
	if (@$post['image_file']=='') $post['image_file'] = null;
	else $post['image_file'] = trim($post['image_file']);
}

if ($get['u']=='add') {
	if (isset($get['level']) && intval($get['level'])>0) $post['subscription_level_id'] = intval($get['level']);
	if (isset($get['subject']) && intval($get['subject'])>0) $post['subject_id'] = intval($get['subject']);
	if (isset($get['topic']) && intval($get['topic'])>0) $post['topic_id'] = intval($get['topic']);
}

$levels = mysql_select("SELECT id, title as name FROM subscription_levels ORDER BY sort_order", 'array');
$subjects = mysql_select("SELECT id, title as name FROM subjects ORDER BY rating DESC, title", 'array');
$topicsAll = mysql_select("SELECT id, title as name FROM topics ORDER BY title", 'array');

$a18n['title'] = 'Название';
$a18n['is_blocked'] = 'Заблокирован';
$a18n['display'] = 'Показывать';
$a18n['subscription_level_id'] = 'Уровень подписки';
$a18n['subject_id'] = 'Предмет';
$a18n['topic_id'] = 'Тема';
$a18n['text'] = 'Текст';
$a18n['pdf_file'] = 'Файл PDF';
$a18n['zip_file'] = 'Файл ZIP';
$a18n['image_file'] = 'Файл изображения';

$table = array(
	'id'		=>	'id:desc',
	'title'		=>	'',
	'is_blocked'	=>	'boolean',
	'display'	=>	'boolean',
	'subscription_level_id'	=>	$levels,
	'subject_id'	=>	$subjects,
	'topic_id'	=>	$topicsAll,
);

// Фильтры
$filter[] = array('level', $levels, 'уровень подписки');
$filter[] = array('subject', $subjects, 'предмет');
$filter[] = array('search');

$topics = array();
if (isset($get['level']) && intval($get['level'])>0 && isset($get['subject']) && intval($get['subject'])>0) {
	$topics = mysql_select("
		SELECT id, title as name
		FROM topics
		WHERE subscription_level_id = '".intval($get['level'])."'
			AND subject_id = '".intval($get['subject'])."'
		ORDER BY title
	", 'array');
	$filter[] = array('topic', $topics, 'тема');
}

$where = '';
if (isset($get['level']) && intval($get['level'])>0) {
	$where.= " AND topic_materials.subscription_level_id = '".intval($get['level'])."'";
}
if (isset($get['subject']) && intval($get['subject'])>0) {
	$where.= " AND topic_materials.subject_id = '".intval($get['subject'])."'";
}
if (isset($get['topic']) && intval($get['topic'])>0) {
	$where.= " AND topic_materials.topic_id = '".intval($get['topic'])."'";
}
if (isset($get['search']) && $get['search']!='') {
	$where.= "
		AND (
			LOWER(topic_materials.title) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
		)
	";
}

$query = "
	SELECT topic_materials.*
	FROM topic_materials
	WHERE 1 ".$where."
";

$form[] = array('input td8','title');
$form[] = array('checkbox','is_blocked');
$form[] = array('checkbox','display');
$form[] = array('select td3','subscription_level_id',array(
	'value'=>array(true, $levels)
));
$form[] = array('select td3','subject_id',array(
	'value'=>array(true, $subjects)
));
if (!empty($topics)) {
	$form[] = array('select td6','topic_id',array(
		'value'=>array(true, $topics)
	));
} else {
	$form[] = array('input td3','topic_id',array(
		'help'=>'ID темы (если не выбран уровень/предмет)'
	));
}
$form[] = array('hypertext td12','text');
$form[] = array('file td12','pdf_file',array(
	'name'=>'Файл PDF'
));
$form[] = array('file td12','zip_file',array(
	'name'=>'Файл ZIP'
));
$form[] = array('file td12','image_file',array(
	'name'=>'Файл изображения'
));

$content = html_array('form/hypertext_templates',array('key'=>'text'));
