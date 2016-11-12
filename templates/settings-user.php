<?php
/**
 * ownCloud - Audio Player
 *
 * @author Marcel Scherello
 * @copyright 
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
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