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
 ?>

<div id="app-settings">
<div id="app-settings-header">
	<button name="app settings"
		class="settings-button"
		data-apps-slide-toggle="#app-settings-content">
		<?php p($l->t('Settings')); ?>
	</button>
</div>

<div id="app-settings-content">
	<ul id="audio-settings">
		<li class="audio-settings-item icon-search">
			<a href="#" title="<?php p($l->t('Scan for audio files')); ?>" id="scanAudios" style="padding: 0 20px;">
				<?php p($l->t('Scan for audio files')); ?>
			</a>
		</li>
		<li class="audio-settings-item icon-delete">
			<a href="#" title="<?php p($l->t('Reset library')); ?>" id="resetAudios" style="padding: 0 20px;">
				<?php p($l->t('Reset library')); ?>
			</a>
		</li>
		<li class="audio-settings-item" style="padding-top: 10px;">
			<input class="checkbox" type="checkbox" id="cyrillic_user" <?php p($_['cyrillic']) ?>/>
			<label for="cyrillic_user">&nbsp;&nbsp;&nbsp;<?php p($l->t('Cyrillic support')); ?></label>
		</li>
		<li class="audio-settings-item" style="padding-top: 10px;">
			<label for="audio-path"><?php p($l->t('Search for audio files in')); ?>:</label>
			<input type="text" id="audio-path" value="<?php p($_['path']) ?>" />
		</li>
		<li class="audio-settings-item" style="padding-top: 10px;">
			<label for="browser_support"><?php p($l->t('Formats not supported by the browser')); ?>:</label>
			&nbsp;<em id="browser_no"></em>
		</li>
		<li class="audio-settings-item icon-info" style="padding-top: 10px;">
			<a href="https://github.com/rello/audioplayer/wiki"  target="_blank" style="padding: 0 20px;">
				<?php p($l->t('More information…')); ?>
			</a>
		</li>
	</ul>
</div>
</div>
