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
<div id="sm2-bar-ui" class="sm2-bar-ui full-width redesign">
    <audio id="html5Audio" preload="auto" hidden></audio>
    <audio id="audioPreload" preload="auto" hidden></audio>

    <div class="bd sm2-main-controls">

        <div id="toggle_alternative" class="menu-icon" hidden>
            <div id="app-navigation-toggle_alternative" class="icon-menu"></div>
        </div>

        <div class="ap-left">
            <div class="sm2-playlist-cover"></div>
            <div class="ap-track-info">
                <div id="nowPlayingTitle"></div>
                <div id="nowPlayingArtist"></div>
            </div>
        </div>

        <div class="ap-middle">
            <div class="ap-controls">
                <div id="playerRepeat" class="sm2-button-bd sm2-inline-button repeat" title="<?php p($l->t('Repeat title/list')); ?>"></div>
                <div id="playerPrev" class="sm2-button-bd sm2-inline-button previous" title="<?php p($l->t('Previous track')); ?>"></div>
                <div id="playerPlay" class="sm2-button-bd sm2-inline-button play-pause" title="<?php p($l->t('Play/Pause')); ?>"></div>
                <div id="playerNext" class="sm2-button-bd sm2-inline-button next" title="<?php p($l->t('Next track')); ?>"></div>
                <div id="playerShuffle" class="sm2-button-bd sm2-inline-button shuffle" title="<?php p($l->t('Shuffle playlist')); ?>"></div>
            </div>
            <div class="ap-progress">
                <div id="startTime" class="sm2-inline-time">0:00</div>
                <div id="progressContainer">
                    <canvas id="progressBar" height="20" style="width: 100%; border-radius: var(--border-radius); border: 0 none; height: 20px; cursor: pointer;"></canvas>
                </div>
                <div id="endTime" class="sm2-inline-time">0:00</div>
            </div>
        </div>

        <div class="ap-right">
            <input id="playerVolume" type="range" class="sm2-button-bd sm2-inline-button volume-slider" min="0" max="1" step="0.02" value="1" title="<?php p($l->t('Volume')); ?>">
            <div id="playerSpeed" class="sm2-button-bd sm2-inline-button speed" title="<?php p($l->t('Playback speed')); ?>">1x</div>
        </div>
    </div>

</div>
