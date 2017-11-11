<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2017 Marcel Scherello
 */

	style('audioplayer', '3rdparty/fontello/css/animation');	
	style('audioplayer', '3rdparty/fontello/css/fontello');
	style('audioplayer', 'jquery.Jcrop');	
	style('audioplayer', 'bar-ui');
	style('audioplayer', 'style');
	script('files', 'jquery.fileupload');
	script('audioplayer', 'jquery.Jcrop-min');
	script('audioplayer', 'soundmanager2-nodebug-jsmin');
	script('audioplayer', 'bar-ui-min');
	script('audioplayer', 'app-min');
	script('audioplayer', 'settings');
    script('audioplayer', 'editor-min');

?>
<form style="display:none;" class="float" id="file_upload_form" action="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute('audioplayer.photo.uploadPhoto')); ?>" method="post" enctype="multipart/form-data" target="file_upload_target">
	<input type="hidden" name="id" value="">
	<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php p($_['uploadMaxFilesize']) ?>" id="max_upload">
	<input type="hidden" class="max_human_file_size" value="(max <?php p($_['uploadMaxHumanFilesize']); ?>)">
	<input type="hidden" id="audioplayer_notification" value="<?php p($_['notification']); ?>">
	<input id="pinphoto_fileupload" type="file" accept="image/*" name="imagefile" />
</form>
<iframe style="display:none;" name="file_upload_target" id='file_upload_target' src=""></iframe>

<div id="app-navigation" <?php if ($_['navigation'] === 'false') echo 'class="ap_hidden"'; ?>>

	<?php print_unescaped($this->inc('part.navigation')); ?>
	
	<?php print_unescaped($this->inc('part.settings')); ?>

</div>
			
<div id="app-content">
	<div id="loading">
		<i class="ioc-spinner ioc-spin"></i>
	</div>

	<?php print_unescaped($this->inc('part.sm2-bar')); ?>

	<div id="searchresults" class="hidden" data-appfilter="audioplayer"></div>
	
	<?php print_unescaped($this->inc('part.container')); ?>

</div>
 
<div id="dialogSmall" style="width:0;height:0;top:0;left:0;display:none;"></div>
<div id="edit_photo_dialog" style="display: none;" title="<?php p($l->t('Edit picture')); ?>">
	<div id="edit_photo_dialog_img"></div>
</div>