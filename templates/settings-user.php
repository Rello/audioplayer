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
<div class="section" id="audioplayer">
	<h2><?php p($l->t('Audio Player')); ?></h2>
	<div>
		<label for="audio-path"><?php p($l->t('Search for audio files in ')); ?>:</label>
		<input type="text" id="audio-path" value="<?php p($_['path']); ?>" />
		<p><em><?php p($l->t('This setting specifies which folder is scanned for audio files. Without a selection, the whole data folder is scanned.')); ?></em></p>
		<p><em><?php p($l->t('To exclude a folder, you have to create a .noaudio file inside that folder. This is also necessary in subfolders, to exclude them.')); ?></em></p>
		<br>
	</div>
	<div>
		<label for="cyrillic_user"><?php p($l->t('Cyrillic Support:')); ?></label>
		<input type="checkbox" id="cyrillic_user" <?php p($_['cyrillic']); ?>/>
		<p><em><?php p($l->t('Activate this setting if cyrillic characters are not recognized correctly. This makes the indexing slower!')); ?></em></p>
		<p><em><a href="https://github.com/Rello/audioplayer/wiki/Cyrillic-symbol-handling" target="_blank"><?php p($l->t('Read more')); ?></a></em></p>
	</div>
</div>
