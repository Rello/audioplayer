<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2021 Marcel Scherello
 */
 ?>
<nav aria-label="<?php p($l->t('Music library')); ?>">
    <ul id="library-navigation">
        <?php foreach ([['Playlist', 'X1', 'Favorites', 'm12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z'], ['Album', '', 'Albums', 'M4 4h16v16H4Z M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z M12 12h.01'], ['Artist', '', 'Artists', 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z M4 21v-2a8 8 0 0 1 16 0v2'], ['Playlist', '', 'Playlists', 'M3 5h18M3 12h12M3 19h12M19 11v9l4-3Z'], ['Stream', '', 'Online streams', 'M4 9h16v12H4Z M4 9l14-6 M8 15a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z M12 14h5M12 17h5'], ['Playlist', 'X2', 'Recently Added', 'M20 12a8 8 0 1 1-8-8 M12 8v5l-3 2 M19 2v6M16 5h6'], ['Title', '0', 'All Titles', 'M9 18V5l11-2v13 M9 18a3 3 0 1 1-3-3h3 M20 16a3 3 0 1 1-3-3h3']] as $entry): ?>
        <li><button type="button" <?php if ($entry[1] === '') echo 'aria-expanded="false" aria-controls="library-category-panel"'; ?> data-category="<?php p($entry[0]); ?>" data-item="<?php p($entry[1]); ?>"><svg class="ap-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="<?php p($entry[3]); ?>" /></svg><span><?php p($l->t($entry[2])); ?></span></button></li>
        <?php endforeach; ?>
<li id="library-more">
    <div id="category_area">
        <label class="ap-sr-only" for="category_selector"><?php p($l->t('Browse by')); ?></label>
        <select id="category_selector">
            <option value=""><?php p($l->t('More')); ?></option>
            <option value="Playlist" hidden><?php p($l->t('More')); ?></option>
            <option value="Stream" hidden><?php p($l->t('More')); ?></option>
            <option value="Album" hidden><?php p($l->t('More')); ?></option>
            <option value="Album Artist"><?php p($l->t('Album Artists')); ?></option>
            <option value="Artist" hidden><?php p($l->t('More')); ?></option>
            <option value="Folder"><?php p($l->t('Folders')); ?></option>
            <option value="Genre"><?php p($l->t('Genres')); ?></option>
            <option value="Title" hidden><?php p($l->t('More')); ?></option>
            <option value="Tags"><?php p($l->t('Tags')); ?></option>
            <option value="Year"><?php p($l->t('Years')); ?></option>
        </select>
    </div>
</li>
    </ul>
</nav>
<div id="library-category-panel" hidden>
    <div id="library-category-items">
        <ul id="myCategory"></ul>
        <div id="playlist-create-row">
<button type="button" class="hidden" id="addPlaylist"><span aria-hidden="true">+</span><span><?php p($l->t('Add Playlist')); ?></span></button>
<div class="ap_hidden" id="newPlaylist">
			<div id="newPlaylist_controls">
                <label class="ap-sr-only" for="newPlaylistTxt"><?php p($l->t('Playlist name')); ?></label><input type="text" name="newPlaylistTxt" id="newPlaylistTxt" placeholder="<?php p($l->t('Create new playlist')); ?>" />
				<button type="button" class="icon-checkmark" id="newPlaylistBtn_ok" aria-label="<?php p($l->t('Create new playlist')); ?>"></button>
				<button type="button" class="icon-close" id="newPlaylistBtn_cancel" aria-label="<?php p($l->t('Cancel')); ?>"></button>
			</div>
		</div>

        </div>
    </div>
</div>
        <!--my playlist clone -->
<li class="plclone" id="pl-clone" data-pl="">
			<div id="playlist_controls">
                <label class="ap-sr-only" for="playlist"><?php p($l->t('Playlist name')); ?></label><input type="text" name="playlist" id="playlist" value=""  />
				<button type="button" class="icon-checkmark" aria-label="<?php p($l->t('Save')); ?>"></button>
				<button type="button" class="icon-close" aria-label="<?php p($l->t('Cancel')); ?>"></button>
			</div>
		</li>	
		<!--my playlist clone -->
