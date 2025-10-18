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

<div id="app-settings">
    <div id="app-settings-header">
        <button name="app settings"  id="appSettingsButton"
                class="settings-button"
                data-apps-slide-toggle="#app-settings-content">
            <?php p($l->t('Settings')); ?>
        </button>
    </div>

    <div id="app-settings-content">
        <ul id="audio-settings">
            <li class="audio-settings-item icon-search">
                <a href="#" title="<?php p($l->t('Scan for audio files')); ?>" id="scanAudios" style="padding: 0 25px;">
                    <?php p($l->t('Scan for audio files')); ?>
                </a>
            </li>
            <li class="audio-settings-item icon-delete">
                <a href="#" title="<?php p($l->t('Reset library')); ?>" id="resetAudios" style="padding: 0 25px;">
                    <?php p($l->t('Reset library')); ?>
                </a>
            </li>
            <li class="audio-settings-item">
                <input class="checkbox" type="checkbox" id="sonos" <?php p($_['audioplayer_sonos']) ?>/>
                <label for="sonos">&nbsp;<?php p($l->t('SONOS Playback')); ?></label>
            </li>
            <li class="audio-settings-item icon-settings">
                <a href="#" title="<?php p($l->t('Advanced Settings')); ?>" id="audioplayerSettings" style="padding: 0 25px;">
                    <?php p($l->t('Advanced Settings')); ?>
                </a>
            </li>
            <li class="audio-settings-item icon-external">
                <a href="https://github.com/rello/audioplayer/wiki/donate" title="<?php p($l->t('Do you like this app?')); ?>" target="_blank" style="padding: 0 25px;">
                    <?php p($l->t('Do you like this app?')); ?>
                </a>
            </li>
            <li class="audio-settings-item icon-info">
                <a href="https://github.com/rello/audioplayer/wiki" target="_blank" style="padding: 0 25px;">
                    <?php p($l->t('More information …')); ?>
                </a>
            </li>
        </ul>
    </div>
</div>
