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
 	<div id="albums-container">
	</div>
	<div id="playlist-container" class="albumwrapper" data-playlist="">
		<span id="individual-playlist-info"></span>
	  	<span id="individual-playlist-header">
 	 		<span class="header-indi">
 	 			<span class="header-num"></span>
  				<span class="header-title"><?php p($l->t('Title')); ?></span>
  				<span class="header-artist"><?php p($l->t('Artist')); ?></span>
  				<span class="header-album"><?php p($l->t('Album')); ?></span>
				<span class="header-time"><?php p($l->t('Length')); ?></span>
  				<span class="header-opt">&nbsp;</span>
  			</span>
  		</span>
  		<br style="clear:both;" />
  		<ul id="individual-playlist"></ul>
  	</div>
