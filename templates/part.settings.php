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
	<ul id="audio-settings" class="with-icon">
		<li>
			<a href="#" class="icon-search" title="<?php p($l->t('Scan for audio files')); ?>" id="scanAudios">
				<?php p($l->t('Scan for audio files')); ?>
			</a>
		</li>
		<li>
			<a href="#" class="icon-delete" title="<?php p($l->t('Reset music library')); ?>" id="resetAudios">
				<?php p($l->t('Reset music library')); ?>
			</a>
		</li>
		<li class="audio-settings-item">
			<input class="checkbox" type="checkbox" id="cyrillic_user" <?php p($_['cyrillic']) ?>/>
			<label for="cyrillic_user">&nbsp;&nbsp;&nbsp;<?php p($l->t('Cyrillic Support')); ?></label>
		</li>
		<li class="audio-settings-item">
			<label for="audio-path"><?php p($l->t('Search for audio files in')); ?>:</label>
			<input type="text" id="audio-path" value="<?php p($_['path']) ?>" />
		</li>
		<li class="audio-settings-item">
			<label for="browser_support"><?php p($l->t('Formats NOT supported by your current browser')); ?>:</label>
			&nbsp;<em id="browser_no"></em>
		</li>
		<li class="audio-settings-item">
		</li>
		<li>
			<a href="https://github.com/rello/audioplayer/wiki/cyrillic-symbol-handling"  target="_blank" class="icon-info">
				<?php p($l->t('Read more')); ?>
			</a>
		</li>
	</ul>
</div>
</div>
