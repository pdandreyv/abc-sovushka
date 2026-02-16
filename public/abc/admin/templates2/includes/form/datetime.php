<div class="form-group <?= $q['class'] ?? '' ?>">
	<label<?= !empty($q['title']) ? ' title="' . htmlspecialchars($q['title']) . '"' : '' ?>>
		<span><?= $q['name'] ?? '' ?></span>
		<?= html_array('form/help', $q) ?>
	</label>
	<?php
	$val = $q['value'] ?? '';
	if ($val && (is_numeric($val) || strtotime($val))) {
		$val = date('Y-m-d\TH:i', is_numeric($val) ? (int) $val : strtotime($val));
	}
	$attr = $q['attr'] ?? '';
	if (strpos($attr, 'type=') === false) {
		$attr = 'type="datetime-local" ' . $attr;
	}
	?>
	<input class="form-control" name="<?= htmlspecialchars($q['key'] ?? '') ?>" <?= $attr ?> value="<?= htmlspecialchars($val) ?>" />
</div>
