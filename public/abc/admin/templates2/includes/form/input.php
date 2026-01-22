<div class="form-group <?=$q['class']?>">
	<label<?=$q['title']?' title="'.$q['title'].'"':''?>>
		<span><?=$q['name']?></span>
		<?=html_array('form/help',$q)?>
	</label>
	<input class="form-control" name="<?=$q['key']?>" <?=@$q['attr']?> value="<?=(isset($q['no_escape']) && $q['no_escape']) ? $q['value'] : ($q['value'] ? htmlspecialchars($q['value']) : '')?>" />
</div>