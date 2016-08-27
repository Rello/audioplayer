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
 <div class="section" id="music-user">
	<h2><?php p($l->t('Audioplayer')); ?></h2>
	<div>
		<label for="cyrillic_user"><?php p($l->t('Cyrillic Support:')); ?></label>
		<input type="checkbox" id="cyrillic_user" <?php p($_['cyrillic']); ?>/>
		<p><em><?php p($l->t('Activate this setting if cyrillic characters are not recognized correctly. This makes the indexing slower!')); ?></em></p>
		<p><em><a href="https://github.com/Rello/audioplayer/wiki/Cyrillic-symbol-handling" target="_blank"><?php p($l->t('==> Details')); ?></a></em></p>
	</div>
</div>