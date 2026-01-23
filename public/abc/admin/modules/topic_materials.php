<?php

// Материалы к темам

if (@$get['id']=='new' || @$get['u']=='add') {
	if (!isset($post['subscription_level_id']) && isset($get['level']) && intval($get['level'])>0) $post['subscription_level_id'] = intval($get['level']);
	if (!isset($post['subject_id']) && isset($get['subject']) && intval($get['subject'])>0) $post['subject_id'] = intval($get['subject']);
	if (!isset($post['topic_id']) && isset($get['topic']) && intval($get['topic'])>0) $post['topic_id'] = intval($get['topic']);
}

$levels = mysql_select("SELECT id, title as name FROM subscription_levels ORDER BY sort_order", 'array');
$subjects = mysql_select("SELECT id, title as name FROM subjects ORDER BY rating DESC, title", 'array');
$topicsAll = mysql_select("SELECT id, title as name FROM topics ORDER BY title", 'array');

$a18n['title'] = 'Название';
$a18n['display'] = 'Показывать';
$a18n['rank'] = 'Рейтинг';
$a18n['subscription_level_id'] = 'Уровень подписки';
$a18n['subject_id'] = 'Предмет';
$a18n['topic_id'] = 'Тема';
$a18n['pdf_file'] = 'Файл 1';
$a18n['zip_file'] = 'Файл 2';

$table = array(
	'id'		=>	'rank:desc id:desc',
	'title'		=>	'',
	'subscription_level_id'	=>	$levels,
	'subject_id'	=>	$subjects,
	'topic_id'	=>	$topicsAll,
	'rank'		=>	'',
	'display'	=>	'boolean',
);

// Фильтры
$filter[] = array('level', $levels, 'уровень подписки');
$filter[] = array('subject', $subjects, 'предмет');

$selectedLevel = isset($get['level']) ? intval($get['level']) : (isset($post['subscription_level_id']) ? intval($post['subscription_level_id']) : 0);
$selectedSubject = isset($get['subject']) ? intval($get['subject']) : (isset($post['subject_id']) ? intval($post['subject_id']) : 0);

$topics = array();
if ($selectedLevel > 0 && $selectedSubject > 0) {
	$topics = mysql_select("
		SELECT id, title as name
		FROM topics
		WHERE subscription_level_id = '".$selectedLevel."'
			AND subject_id = '".$selectedSubject."'
		ORDER BY title
	", 'array');
	$filter[] = array('topic', $topics, 'тема');
}
$filter[] = array('search');

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

$form[] = array('input td6','title');
$form[] = array('input td2','rank',array(
	'value'=>@$post['rank'] ? $post['rank'] : 0
));
$form[] = array('checkbox','display');
$form[] = array('select td3','subscription_level_id',array(
	'value'=>array(true, $levels),
	'attr'=>'data-topic-filter="level"'
));
$form[] = array('select td3','subject_id',array(
	'value'=>array(true, $subjects),
	'attr'=>'data-topic-filter="subject"'
));
$form[] = array('select td6','topic_id',array(
	'value'=>array(true, $topics)
));
$form[] = array('file td12','pdf_file',array(
	'name'=>'Файл 1'
));
$form[] = array('file td12','zip_file',array(
	'name'=>'Файл 2'
));
$form[] = '
<script>
(function() {
  var level = document.querySelector(\'select[name="subscription_level_id"][data-topic-filter="level"]\');
  var subject = document.querySelector(\'select[name="subject_id"][data-topic-filter="subject"]\');
  if (!level || !subject) return;
  function reload() {
    var params = new URLSearchParams(window.location.search);
    if (level.value) params.set("level", level.value); else params.delete("level");
    if (subject.value) params.set("subject", subject.value); else params.delete("subject");
    params.delete("topic");
    params.set("id", "new");
    window.location.search = params.toString();
  }
  level.addEventListener("change", reload);
  subject.addEventListener("change", reload);
})();
</script>';
