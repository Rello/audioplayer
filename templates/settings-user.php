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
	<!--<h3>&nbsp;</h3>
		<label for="timer_user"><?php p($l->t('Test for user settings')); ?>:</label>
		<input type="text" id="category" disabled value="<?php p($_['category']); ?>" />
	<h3>&nbsp;</h3>
	<h3><?php p($l->t('!!! BETA !!! Sleep Timer')); ?></h3>
		<label for="timer_user"><?php p($l->t('User')); ?>:</label>
		<input type="text" id="timer_user" value="kind" />
		<label for="timer_time"><?php p($l->t('Time')); ?>:</label>
		<input type="text" id="timer_time" value="7:25" />
		<input type="submit" id="timer_button" value="<?php p($l->t('Save Timer')); ?>" />
		<p><em><?php p($l->t('Select the Time (for the selected User) at when the Audioplayer will stop its playbacks')); ?></em></p>-->
	</div>
</div>