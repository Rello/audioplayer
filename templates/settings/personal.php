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

script('audioplayer', 'settings/personal');
?>

<div class="section" id="audioplayer">
    <h2><?php p($l->t('Scanner Settings')); ?></h2>
    <div>
        <label for="audio-path"><?php p($l->t('Search for audio files in')); ?>:</label>
        <input type="text" id="audio-path" value="<?php p($_['audioplayer_path']); ?>"/>
        <p>
            <em><?php p($l->t('This setting specifies which folder is scanned for audio files. Without a selection, the whole data folder is scanned.')); ?></em>
        </p>
        <p>
            <em><?php p($l->t('To exclude a folder, you have to create a .noaudio file inside that folder. This is also necessary in subfolders.')); ?></em>
        </p>
        <br>
    </div>
    <div>
        <input type="checkbox" class="checkbox" id="cyrillic_user" <?php p($_['audioplayer_cyrillic']); ?>/>
        <label for="cyrillic_user"><?php p($l->t('Cyrillic support')); ?></label>
        <p>
            <em><?php p($l->t('Activate this setting if cyrillic characters are not recognized correctly. This makes the indexing slower!')); ?></em>
        </p>
        <p><em><a href="https://github.com/Rello/audioplayer/wiki/Cyrillic-symbol-handling"
                  target="_blank"><?php p($l->t('More information …')); ?></a></em></p>
        <br>
    </div>
    <div>
        <label for="browser_support"><?php p($l->t('Formats supported by the browser')); ?>:</label>
        &nbsp;<em id="browser_yes"></em>
        <br>
        <label for="browser_support"><?php p($l->t('Formats not supported by the browser')); ?>:</label>
        &nbsp;<em id="browser_no"></em>
        <p><em><a href="https://github.com/Rello/audioplayer/wiki/Audio-Files-and-MIME-Types#browser-support"
                  target="_blank"><?php p($l->t('More information …')); ?></a></em></p>
    </div>
</div>
