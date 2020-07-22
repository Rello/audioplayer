<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2020 Marcel Scherello
 */
?>
<div id="sm2-bar-ui" class="sm2-bar-ui full-width">
    <audio id="html5Audio" hidden>
    </audio>

    <div class="bd sm2-main-controls">
        <div class="sm2-inline-texture"></div>
        <div class="sm2-inline-gradient"></div>

        <div class="sm2-inline-element sm2-button-element">
            <div class="sm2-button-bd" id="toggle_alternative">
                <div id="app-navigation-toggle_alternative" class="icon-menu"
                     style="float: left; box-sizing: border-box;"></div>
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element" data-placement="right"
             title="<?php p($l->t('Previous track')); ?>">
            <div id="playerPrev" class="sm2-button-bd sm2-inline-button previous">
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element" data-placement="right"
             title="<?php p($l->t('Play/Pause')); ?>">
            <div id="playerPlay" class="sm2-button-bd sm2-inline-button play-pause">
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element" data-placement="right"
             title="<?php p($l->t('Next track')); ?>">
            <div id="playerNext" class="sm2-button-bd sm2-inline-button next">
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element">
            <div class="sm2-playlist-cover"></div>
        </div>

        <div class="sm2-inline-element sm2-inline-status">
            <div id="nowPlayingText" class="sm2-playlist"></div>
            <div style="display: table-row;">
                <div id="startTime" class="sm2-inline-time"></div>
                <div id="endTime" class="sm2-inline-time"></div>
                <div id="progressContainer" style="padding-top: 3px;">
                    <canvas id="progressBar" height="8" style="
                    width: 100%;
    background-color: var(--color-background-dark);
    border-radius: var(--border-radius);
    border: 0 none;
    height: 8px;
    cursor: pointer;
">
                    </canvas>
                </div>
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element sm2-volume" data-placement="left"
             title="<?php p($l->t('Volume')); ?>">
            <div id="playerVolume" class="sm2-button-bd sm2-inline-button sm2-volume-control">
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element sm2-repeat" data-placement="left"
             title="<?php p($l->t('Repeat title / list')); ?>">
            <div id="playerRepeat" class="sm2-button-bd sm2-inline-button repeat">
            </div>
        </div>

        <div class="sm2-inline-element sm2-button-element sm2-shuffle" data-placement="left"
             title="<?php p($l->t('Shuffle playlist')); ?>">
            <div id="playerShuffle" class="sm2-button-bd sm2-inline-button shuffle">
            </div>
        </div>
    </div>

</div>
