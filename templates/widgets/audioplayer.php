<?php

/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2019 Marcel Scherello
 */

?>
<div class="sm2-bar-ui" style="padding-left: 10px;min-width: initial;width: 200px;">
    <div class="bd sm2-main-controls">
        <div class="sm2-inline-texture"></div>
        <div class="sm2-inline-gradient"></div>

        <div class="sm2-inline-element sm2-button-element">
            <div class="sm2-playlist-cover"></div>
        </div>

        <div class="sm2-inline-element sm2-button-element toolTipx" data-placement="right" title="<?php p($l->t('Previous track')); ?>">
            <div class="sm2-button-bd">
                <a href="#prev" class="sm2-inline-button previous">previous</a>
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element toolTipx" data-placement="right" title="<?php p($l->t('Play/Pause')); ?>">
            <div class="sm2-button-bd">
                <a href="#play" class="sm2-inline-button play-pause">play</a>
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element toolTipx" data-placement="right" title="<?php p($l->t('Next track')); ?>">
            <div class="sm2-button-bd">
                <a href="#next" class="sm2-inline-button next" data-placement="right" title="<?php p($l->t('Next track')); ?>">next</a>
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
<div class="widget-audioplayer" id="widget-audioplayer">
</div>