<?php

//поиск по пользователям
/*
* v1.4.31 - создание новой записи в автозаплнении
*/

$api = array(
	'items'=>array()
);

$where = '';
if (@$_GET['q']) {
	$q = trim($_GET['q']);
	$qEsc = mysql_res(mb_strtolower($q, 'UTF-8'));
	$where.= " AND ( ";
	// поиск по id (число) или полному id (user_code)
	if (is_numeric($q)) {
		$where.= " users.id = '".intval($q)."' OR ";
	}
	if ($q !== '') {
		$where.= " ( users.user_code = '".mysql_res($q)."' OR LOWER(users.email) LIKE '%".$qEsc."%'";
		$where.= " OR LOWER(CONCAT(users.last_name,' ',users.first_name,' ',users.middle_name)) LIKE '%".$qEsc."%'";
		$where.= " OR LOWER(CONCAT(users.first_name,' ',users.last_name)) LIKE '%".$qEsc."%'";
		$where.= " OR LOWER(users.phone) LIKE '%".$qEsc."%' )";
	}
	$where.= " ) ";
}
//v1.2.28 другие пользователи не видят суперадмина
if ($user['id']!=1) $where.= ' AND users.id!=1';

$query = "
	SELECT *
	FROM users
	WHERE 1 ".$where."
	ORDER BY id
	LIMIT 20
";

$users = mysql_select($query,'rows');
if ($users) {
	foreach ($users as $q) {
		$full_name = trim($q['last_name'].' '.$q['first_name'].' '.$q['middle_name']);
		$name = $full_name ? $full_name.' ('.$q['email'].')' : $q['email'];
		if ($q['phone']) $name.= ' ['.$q['phone'].']';
		$api['items'][] = array(
			'id'=>$q['id'],
			'text'=>$name,
		);
	}
}
//добавление нового
if (access('admin module','users')) {
	if ($api['items'] == false) {
		$api['items'][] = array(
			'id' => 'add',
			'text' => 'добавить пользователя',
			'value' => $_GET['q'],
		);
	}
}

header('Content-type: application/json; charset=UTF-8');
echo json_encode($api);
die();