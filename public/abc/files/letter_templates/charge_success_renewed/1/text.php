<?php
$s = file_get_contents(__DIR__ . '/body.tpl');
if (isset($q) && is_array($q)) { foreach ($q as $k => $v) { $s = str_replace('{{'.$k.'}}', (string)$v, $s); } }
$s = preg_replace('/\{\{#if\s+user_name\}\}(.*?)\{\{\/if\}\}/s', (isset($q['user_name']) && $q['user_name'] !== '') ? '$1' : '', $s);
echo $s;