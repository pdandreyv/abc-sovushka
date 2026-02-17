<?php

// Логи действий пользователя: вход (1), скачивание материала (12), просмотр материала (13)
// Таблица создаётся миграцией Laravel: user_activity_logs

$module['table'] = 'user_activity_logs';

$action_labels = [
    1  => 'Вход',
    12 => 'Скачивание материала темы',
    13 => 'Просмотр материала темы',
];

$a18n['created_at'] = 'Дата создания';
$a18n['updated_at'] = 'Дата обновления';
$a18n['user_id'] = 'Пользователь';
$a18n['ip'] = 'IP';
$a18n['action'] = 'Действие';
$a18n['topic_material_id'] = 'ID материала темы';

$where = '';
if (isset($get['user']) && (int)$get['user'] > 0) {
    $where .= " AND user_activity_logs.user_id = " . (int)$get['user'];
}
if (isset($get['action']) && $get['action'] !== '') {
    $where .= " AND user_activity_logs.action = " . (int)$get['action'];
}
if (isset($get['date_from']) && $get['date_from'] !== '') {
    $where .= " AND user_activity_logs.created_at >= '" . mysql_res($get['date_from']) . " 00:00:00'";
}
if (isset($get['date_to']) && $get['date_to'] !== '') {
    $where .= " AND user_activity_logs.created_at <= '" . mysql_res($get['date_to']) . " 23:59:59'";
}
if (isset($get['search']) && $get['search'] !== '') {
    $s = mysql_res($get['search']);
    $where .= " AND (user_activity_logs.ip LIKE '%" . $s . "%' OR user_activity_logs.user_id = '" . $s . "' OR user_activity_logs.topic_material_id = '" . $s . "')";
}

$query = "
    SELECT user_activity_logs.*, u.email
    FROM user_activity_logs
    LEFT JOIN users u ON u.id = user_activity_logs.user_id
    WHERE 1 " . $where . "
";

$filter[] = array('user', "SELECT u.id, u.email name FROM users u INNER JOIN user_activity_logs l ON l.user_id = u.id GROUP BY u.id ORDER BY u.email", 'пользователь');
$filter[] = array('action', $action_labels, 'действие');
$filter[] = array('search');

$table = array(
    '_edit'   => false,
    'id'      => 'id:desc',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'user_id' => '<a href="/admin.php?m=users&id={user_id}">[{user_id}] {email}</a>',
    'ip'      => '',
    'action'  => $action_labels,
    'topic_material_id' => '',
    '_delete' => true,
);
