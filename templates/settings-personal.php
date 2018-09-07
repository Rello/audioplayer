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

script('audioplayer', 'settings-personal');
script('audioplayer', 'soundmanager2-nodebug-jsmin');
?>

<div class="section" id="audioplayer">
    <h2><?php p($l->t('Scanner Settings')); ?></h2>
    <div>
        <label for="audio-path"><?php p($l->t('Search for audio files in ')); ?>:</label>
        <input type="text" id="audio-path" value="<?php p($_['audioplayer_path']); ?>"/>
        <p>
            <em><?php p($l->t('This setting specifies which folder is scanned for audio files. Without a selection, the whole data folder is scanned.')); ?></em>
        </p>
        <p>
            <em><?php p($l->t('To exclude a folder, you have to create a .noaudio file inside that folder. This is also necessary in subfolders, to exclude them.')); ?></em>
        </p>
        <br>
    </div>
    <div>
        <label for="cyrillic_user"><?php p($l->t('Cyrillic support:')); ?></label>
        <input type="checkbox" id="cyrillic_user" <?php p($_['audioplayer_cyrillic']); ?>/>
        <p>
            <em><?php p($l->t('Activate this setting if cyrillic characters are not recognized correctly. This makes the indexing slower!')); ?></em>
        </p>
        <p><em><a href="https://github.com/Rello/audioplayer/wiki/Cyrillic-symbol-handling"
                  target="_blank"><?php p($l->t('More information…')); ?></a></em></p>
        <br>
    </div>
    <div>
        <label for="browser_support"><?php p($l->t('Formats supported by the browser')); ?>:</label>
        &nbsp;<em id="browser_yes"></em>
        <br>
        <label for="browser_support"><?php p($l->t('Formats not supported by the browser')); ?>:</label>
        &nbsp;<em id="browser_no"></em>
        <p><em><a href="https://github.com/Rello/audioplayer/wiki/Audio-Files-and-MIME-Types#browser-support"
                  target="_blank"><?php p($l->t('More information…')); ?></a></em></p>
    </div>
    <br>
    <br>
    <h2><?php p($l->t('SONOS Player Plugin')); ?></h2>
    <div>
        <label for="sonos_controller"><?php p($l->t('SONOS Player:')); ?></label>
        <select id="sonos_controller" />
        </select>
        <input type="hidden" id="sonos_current" value="<?php p($_['audioplayer_sonos_controller']); ?>">
        <input type="submit" id="sonos_controller_submit" value="<?php p($l->t('Save')); ?>">
        <p><em><?php p($l->t('Name of the SONOS player or group')); ?></em></p>
        <br>
    </div>
    <div>
        <label for="sonos_smb_path"><?php p($l->t('local SMB Path:')); ?></label>
        <input type="text" id="sonos_smb_path" value="<?php p($_['audioplayer_sonos_smb_path']); ?>"/>
        <input type="submit" id="sonos_smb_path_submit" value="<?php p($l->t('Save')); ?>">
        <p>
            <em><?php p($l->t('Server path to the SMB directory where all audio files are located')); ?></em><br>
            <em>e.g.: qnap/Multimedia/iTunes/Music/</em>
        </p>
        <br>
    </div>
    <div>
        <label for="sonos"><?php p($l->t('SONOS Playback')); ?></label>
        <input type="checkbox" id="sonos" <?php p($_['audioplayer_sonos']); ?>/>
        <p><em><?php p($l->t('All titles will be played on your SONOS speaker')); ?></em></p>
        <p><em><a href="https://github.com/Rello/audioplayer/wiki/SONOS"
                  target="_blank"><?php p($l->t('More information…')); ?></a></em></p>
        <br>
    </div>
</div>