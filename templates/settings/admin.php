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

script('audioplayer', 'settings/admin');
?>

<div class="section" id="audioplayer"><br>
    <h2><?php p($l->t('SONOS Player Plugin')); ?></h2>
    <div>
        <p>
            <em><?php p($l->t('The SONOS player needs to be enabled globally by the admin')); ?></em>
        </p>
        <br>
        <label for="sonos"><?php p($l->t('SONOS Playback')); ?>&nbsp;</label>
        <input type="checkbox" id="sonos" <?php p($_['audioplayer_sonos_admin']); ?>/>
        <p><em><a href="https://github.com/Rello/audioplayer/wiki/SONOS"
                  target="_blank"><?php p($l->t('More information…')); ?></a></em></p>
        <br>
    </div>
</div>
