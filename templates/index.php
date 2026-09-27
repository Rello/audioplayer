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

use OCP\Util;

Util::addStyle('audioplayer', 'bar-ui');
Util::addStyle('audioplayer', 'style');
Util::addStyle('audioplayer', 'icons');
//Util::addStyle('files', 'detailsView');
Util::addScript('audioplayer', 'app');
Util::addScript('audioplayer', 'library');
Util::addStyle('audioplayer', 'library');
Util::addScript('audioplayer', 'sidebar');
Util::addScript('audioplayer', 'userGuidance');
Util::addScript('audioplayer', 'settings/settings');
if ($_['audioplayer_sonos'] !== 'checked') {
	Util::addScript('audioplayer', 'player');
}

?>
<input type="hidden" name="id" value="">
<input type="hidden" id="libraryView" value="<?php p($_['audioplayer_libraryView']); ?>">
<input type="hidden" id="libraryPath" value="<?php p($_['audioplayer_path']); ?>">
<input type="hidden" id="audioplayer_volume" value="<?php p($_['audioplayer_volume']); ?>">
<input type="hidden" id="audioplayer_sonos" value="<?php p($_['audioplayer_sonos']); ?>">
<input type="hidden" id="audioplayer_repeat" value="<?php p($_['audioplayer_repeat']); ?>">
<input type="hidden" id="audioplayer_speed" value="<?php p($_['audioplayer_speed']); ?>">

<div id="app-navigation" <?php if ($_['audioplayer_navigationShown'] === 'false') echo 'class="hidden"'; ?>>
	<button type="button" id="library-navigation-close" class="icon-close" aria-label="<?php p($l->t('Close music navigation')); ?>" hidden></button>

	<?php print_unescaped($this->inc('part.navigation')); ?>

	<?php print_unescaped($this->inc('settings/part.settings')); ?>

</div>

<div id="app-content">
    <div id="library-navigation-backdrop" aria-hidden="true" hidden></div>
    <div id="loading">
        <span class="ap-icon ap-icon-spinner" aria-hidden="true"></span>
    </div>

	<?php if ($_['audioplayer_sonos'] !== 'checked') print_unescaped($this->inc('part.audio')); ?>
	<?php if ($_['audioplayer_sonos'] === 'checked') print_unescaped($this->inc('part.sonos-bar')); ?>

    <header id="library-heading">
        <button type="button" id="library-menu" class="icon-menu" aria-label="<?php p($l->t('Open music navigation')); ?>" aria-controls="app-navigation" aria-expanded="false"></button>
        <h2 id="library-title"><?php p($l->t('Albums')); ?></h2>
        <div id="view-toggle" class="icon-toggle-<?php p($_['audioplayer_view']); ?>" role="group" aria-label="<?php p($l->t('View')); ?>" hidden>
            <?php foreach (['pictures' => ['Album Covers', 'M3 3h7v7H3Z M14 3h7v7h-7Z M3 14h7v7H3Z M14 14h7v7h-7Z'], 'filelist' => ['List View', 'M8 5h13M8 12h13M8 19h13M3 5h.01M3 12h.01M3 19h.01']] as $view => $control): ?>
            <button type="button" data-view="<?php p($view); ?>" aria-label="<?php p($l->t($control[0])); ?>" title="<?php p($l->t($control[0])); ?>" aria-pressed="<?php p($_['audioplayer_view'] === $view ? 'true' : 'false'); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="<?php p($control[1]); ?>" /></svg></button>
            <?php endforeach; ?>
        </div>
        <button type="button" id="library-add-tracks" class="button ap-action-button" hidden><?php p($l->t('Add tracks')); ?></button>
    </header>
    <div id="searchresults" class="hidden" data-appfilter="audioplayer"></div>

	<?php print_unescaped($this->inc('part.container')); ?>

</div>

<div id="app-sidebar" class="app-sidebar details-view scroll-container disappear" data-trackid="">
	<?php print_unescaped($this->inc('part.sidebar')); ?>
</div>

<template id="templateScanDialog">
    <div id="audios_import_dialog" title="Scan for audio files">
        <div id="audios_import_form" style="display: block">
            <p><?php p($l->t('Choose a music folder, then scan to add its audio files to your library.')); ?></p>
            <p id="scanFolderPath" class="ap-folder-path"></p>
            <button type="button" id="scanChooseFolder" class="button ap-action-button"><?php p($l->t('Choose music folder')); ?></button>
            <input id="audios_import_submit" type="button" class="button" value="Start scanning …">
        </div>
        <div id="audios_import_process" style="display:none;">
            <div id="audios_import_process_progress"></div>
            <div id="audios_import_process_message"></div>
            <br>
            <input id="audios_import_progress_cancel" type="button" class="button" value="Cancel">
        </div>
        <div id="audios_import_done" style="display:none;">
            <div id="audios_import_done_message" class="hint"></div>
            <br>
            <input id="audios_import_done_close" type="button" class="button" value="Close">
        </div>
    </div>
</template>
