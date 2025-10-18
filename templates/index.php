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
Util::addScript('audioplayer', 'sidebar');
Util::addScript('audioplayer', 'userGuidance');
Util::addScript('audioplayer', 'settings/settings');
if ($_['audioplayer_sonos'] !== 'checked') {
    Util::addScript('audioplayer', 'player');
}

?>
<input type="hidden" name="id" value="">
<input type="hidden" id="audioplayer_volume" value="<?php p($_['audioplayer_volume']); ?>">
<input type="hidden" id="audioplayer_sonos" value="<?php p($_['audioplayer_sonos']); ?>">
<input type="hidden" id="audioplayer_repeat" value="<?php p($_['audioplayer_repeat']); ?>">
<input type="hidden" id="audioplayer_speed" value="<?php p($_['audioplayer_speed']); ?>">

<div id="app-navigation" <?php if ($_['audioplayer_navigationShown'] === 'false') echo 'class="hidden"'; ?>>

    <?php print_unescaped($this->inc('part.navigation')); ?>

    <?php print_unescaped($this->inc('settings/part.settings')); ?>

</div>

<div id="app-content">
    <div id="loading">
        <span class="ap-icon ap-icon-spinner" aria-hidden="true"></span>
    </div>

    <?php if ($_['audioplayer_sonos'] !== 'checked') print_unescaped($this->inc('part.audio')); ?>
    <?php if ($_['audioplayer_sonos'] === 'checked') print_unescaped($this->inc('part.sonos-bar')); ?>

    <div id="searchresults" class="hidden" data-appfilter="audioplayer"></div>

    <?php print_unescaped($this->inc('part.container')); ?>

</div>

<div id="app-sidebar" class="app-sidebar details-view scroll-container disappear" data-trackid="">
    <?php print_unescaped($this->inc('part.sidebar')); ?>
</div>