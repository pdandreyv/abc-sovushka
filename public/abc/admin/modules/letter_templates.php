<?php

/**
 * v1.4.14 - event_func
 * v1.4.17 - сокращение параметров form
 * v1.4.71 - косяк при быстром редактировании
 * Шаблоны: название = тема письма (рус.), контент в .tpl, без кода в форме.
 */

$a18n['name'] = '';

$template = array(
	1 => 'основной шаблон'
);

$tabs = mysql_select("SELECT id,name FROM languages ORDER BY `rank` DESC",'array');

// Дефолты отправителя
$default_sender_name = 'Совушкина школа';
$default_sender = 'info@kssovushka.ru';

if ($get['u']=='edit') {
	foreach ($tabs as $k=>$v) {
		unset($post['subject'.$k],$post['text'.$k]);
	}
}

//v1.4.14 - event_func: сохраняем тему и текст в .tpl (без PHP-кода)
function event_change_letter_templates($q) {
	global $tabs;

	$id = isset($q['id']) ? (int)$q['id'] : 0;
	if (!$id) return;
	$row = mysql_select("SELECT slug, name FROM letter_templates WHERE id=".$id, 'row');
	$pathBase = !empty($row['slug']) ? $row['slug'] : $row['name'];
	$path = ROOT_DIR.'files/letter_templates/'.$pathBase.'/';
	if (is_dir($path) || mkdir($path,0755,true)) {
		foreach ($tabs as $k=>$v) {
			if (isset($_POST['subject'.$k]) || isset($_POST['text'.$k])) {
				$langDir = $path . $k . '/';
				if (is_dir($langDir) || mkdir($langDir, 0755, true)) {
					// тема письма — чистый текст в subject.tpl
					$subj = isset($_POST['subject'.$k]) ? $_POST['subject'.$k] : '';
					file_put_contents($langDir . 'subject.tpl', $subj);
					// тело письма — HTML в body.tpl
					$body = isset($_POST['text'.$k]) ? $_POST['text'.$k] : '';
					file_put_contents($langDir . 'body.tpl', $body);
				}
			}
		}
	}
}

$table = array(
	'id'		=>	'name id',
	'name'		=>	'{name}',
	'sender_name'	=>	'',
	'sender'	=>	'',
	'receiver'	=>	'',
	'description'	=>	''
);

$query = "
	SELECT *,
	IF (sender_name='','".addslashes($default_sender_name)."',sender_name) sender_name,
	IF (sender='','".addslashes($default_sender)."',sender) sender,
	IF (receiver='','',receiver) receiver
	FROM letter_templates
	WHERE 1
";

// Загрузка темы и текста из .tpl (чистый текст/HTML, без кода)
if (($get['u']=='form' OR $get['u']=='') AND $get['id']>0) {
	$row = mysql_select("SELECT slug, name FROM letter_templates WHERE id=".intval($get['id']), 'row');
	$pathBase = $row ? (!empty($row['slug']) ? $row['slug'] : $row['name']) : '';
	if ($pathBase) {
		$path = ROOT_DIR . 'files/letter_templates/' . $pathBase . '/';
		foreach ($tabs as $k => $v) {
			$post['subject' . $k] = '';
			$subjFile = $path . $k . '/subject.tpl';
			if (is_file($subjFile)) {
				$post['subject' . $k] = file_get_contents($subjFile);
			}
			$post['text' . $k] = '';
			$bodyFile = $path . $k . '/body.tpl';
			if (is_file($bodyFile)) {
				$post['text' . $k] = file_get_contents($bodyFile);
			}
		}
	}
}

// Название = тема письма (отображается как тема при получении)
$form[1][] = array('input td6','name', array('name'=>'Название (тема письма)'));
$form[1][] = array('input td6','description');
$form[1][] = array('input td3','sender_name', array('help'=>'имя отправителя','attr'=>'placeholder="'.$default_sender_name.'"'));
$form[1][] = array('input td3','sender', array('help'=>'email отправителя','attr'=>'placeholder="'.$default_sender.'"'));
$form[1][] = array('input td3','receiver', array('help'=>'оставьте пустым, если получатель задаётся при отправке','attr'=>'placeholder="пусто"'));
$form[1][] = 'clear';

foreach ($tabs as $k=>$v) {
	$form[$k][] = array('input td12','subject'.$k, array('name'=>'Тема письма (как при получении)'));
	$form[$k][] = array('tinymce td12','text'.$k, array('name'=>'Текст письма (HTML)','attr'=>'style="height:400px"'));
}

$content = '<br />Название шаблона должно совпадать с темой письма. Отправитель по умолчанию: '.$default_sender_name.' &lt;'.$default_sender.'&gt;. Глобальная настройка <a href="/admin.php?m=config">тут</a>.';
