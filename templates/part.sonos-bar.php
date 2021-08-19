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
<div id="sm2-bar-ui" class="sm2-bar-ui full-width">
    <div class="bd sm2-main-controls">
        <div class="sm2-inline-texture"></div>
        <div class="sm2-inline-gradient"></div>

        <div class="sm2-inline-element sm2-button-element">
            <div class="sm2-button-bd" id="toggle_alternative">
                <div id="app-navigation-toggle_alternative" class="icon-menu"
                     style="float: left; box-sizing: border-box;"></div>
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element toolTipx" data-placement="right" title="<?php p($l->t('Previous track')); ?>">
            <div class="sm2-button-bd" id="sonos_prev">
                <div class="sm2-inline-button previous">previous</div>
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element toolTipx" data-placement="right" title="<?php p($l->t('Play/Pause')); ?>">
            <div class="sm2-button-bd" id="sonos_play">
                <div class="sm2-inline-button play-pause">play</div>
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element toolTipx" data-placement="right" title="<?php p($l->t('Next track')); ?>">
            <div class="sm2-button-bd" id="sonos_next">
                <div class="sm2-inline-button next" data-placement="right" title="<?php p($l->t('Next track')); ?>">next</div>
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element">
            <div class="sm2-playlist-cover"></div>
        </div>

        <div class="sm2-inline-element sm2-inline-status">
            SONOS Mode
        </div>

        <div class="sm2-inline-element sm2-button-element sm2-repeat toolTipx" data-placement="left" title="<?php p($l->t('Volume up')); ?>">
            <div class="sm2-button-bd" id="sonos_up">
                <div class="sm2-inline-button sonos_up">up</div>
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element sm2-repeat toolTipx" data-placement="left" title="<?php p($l->t('Volume down')); ?>">
            <div class="sm2-button-bd" id="sonos_down">
                <div class="sm2-inline-button sonos_down">down</div>
            </div>
        </div>

    </div>

    <div class="bd sm2-playlist-drawer sm2-element">
        <div class="sm2-inline-texture">
            <div class="sm2-box-shadow"></div>
        </div>
        <!-- playlist content is mirrored here -->
        <div class="sm2-playlist-wrapper">
            <ul class="sm2-playlist-bd " id="activePlaylist">
            </ul>
        </div>
    </div>
</div>
