<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2018 Marcel Scherello
 */

	style('audioplayer', '3rdparty/fontello/css/animation');	
	style('audioplayer', '3rdparty/fontello/css/fontello');
style('audioplayer', 'bar-ui-min');
style('audioplayer', 'style-min');
style('files', 'detailsView');
	script('files', 'jquery.fileupload');
	script('audioplayer', 'soundmanager2-nodebug-jsmin');
script('audioplayer', 'bar-ui-min');
script('audioplayer', 'app-min');
    script('audioplayer', 'sidebar');
	script('audioplayer', 'settings');
if ($_['audioplayer_editor'] === 'true') {
    script('audioplayer_editor', 'editor');
    style('audioplayer_editor', 'style');
}

?>
	<input type="hidden" name="id" value="">
	<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php p($_['uploadMaxFilesize']) ?>" id="max_upload">
	<input type="hidden" class="max_human_file_size" value="(max <?php p($_['uploadMaxHumanFilesize']); ?>)">
	<input type="hidden" id="audioplayer_notification" value="<?php p($_['notification']); ?>">
    <input type="hidden" id="audioplayer_volume" value="<?php p($_['volume']); ?>">
    <input type="hidden" id="audioplayer_editor" value="<?php p($_['audioplayer_editor']); ?>">

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

<div id="app-sidebar" class="details-view scroll-container disappear" data-trackid="">
    <?php print_unescaped($this->inc('part.sidebar')); ?>
</div>

<div id="dialogSmall" style="width:0;height:0;top:0;left:0;display:none;"></div>