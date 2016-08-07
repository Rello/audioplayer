<?php 
     if(\OC::$server->getCache()->hasKey($_['tmpkey'])) {
	  
		$imgurl='';
	 ?>
<img id="cropbox" src="<?php print_unescaped($imgurl);?>" />
<form id="cropform"
	class="coords"
	method="post"
	enctype="multipart/form-data"
	target="crop_target"
	action="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute('audioplayer.photo.saveCropPhoto')); ?>">

	<input type="hidden" id="id" name="id" value="<?php p($_['id']); ?>" />
	<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
	<input type="hidden" id="tmpkey" name="tmpkey" value="<?php p($_['tmpkey']); ?>" />
	<fieldset id="coords">
	<input type="hidden" id="x1" name="x1" value="" />
	<input type="hidden" id="y1" name="y1" value="" />
	<input type="hidden" id="x2" name="x2" value="" />
	<input type="hidden" id="y2" name="y2" value="" />
	<input type="hidden" id="w" name="w" value="" />
	<input type="hidden" id="h" name="h" value="" />
	</fieldset>
	<iframe name="crop_target" id="crop_target" src=""></iframe>
</form>

<?php
} else {
 	p($l->t('The temporary image has been removed from cache.'));
}
