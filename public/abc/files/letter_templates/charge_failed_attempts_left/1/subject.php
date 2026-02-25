<?php
$s = file_get_contents(__DIR__ . '/subject.tpl');
if (isset($q) && is_array($q)) { foreach ($q as $k => $v) { $s = str_replace('{{'.$k.'}}', (string)$v, $s); } }
echo $s;