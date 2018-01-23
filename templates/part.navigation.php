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
		<ul id="albenoverview">
			<li id="alben">
				<span style="vertical-align: top; font-size: 15px;">
				<?php p($l->t('Albums')); ?></span>  
			</li>
		</ul>
		<div id="category_area">
			<select id="category_selector">
				<option value=""selected><?php p($l->t('Selection')); ?></option>
				<option value="Playlist"><?php p($l->t('Playlists')); ?></option>
				<option value="Artist"><?php p($l->t('Artists')); ?></option>
				<option value="Album"><?php p($l->t('Albums')); ?></option>
				<option value="Title"><?php p($l->t('Titles')); ?></option>
				<option value="Genre"><?php p($l->t('Genres')); ?></option>
				<option value="Year"><?php p($l->t('Years')); ?></option>
				<option value="Folder"><?php p($l->t('Folders')); ?></option>
			</select>
			<button  class="icon-add ap_hidden" id="addPlaylist"></button>
		</div>
		<ul id="myCategory">
		</ul>
        <!--my playlist clone -->
		<li class="app-navigation-entry-edit plclone" id="pl-clone" data-pl="">
			<div id="playlist_controls">	
				<input type="text" name="playlist" id="playlist" value=""  />
				<button class="icon-checkmark"></button>
				<button class="icon-close"></button>
			</div>
		</li>	
		<!--my playlist clone -->
		<div class="app-navigation-entry-edit ap_hidden" id="newPlaylist">
			<div id="newPlaylist_controls">
				<input type="text" name="newPlaylistTxt" id="newPlaylistTxt" placeholder="<?php p($l->t('Create new playlist')); ?>" /> 
				<button class="icon-checkmark" id="newPlaylistBtn_ok"></button>
				<button class="icon-close" id="newPlaylistBtn_cancel"></button>
			</div>
		</div>
