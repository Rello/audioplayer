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

<div class="detailFileInfoContainer">
    <div class="mainFileInfoView">
        <div class="thumbnailContainer">
            <div id="sidebarThumbnail" class="thumbnail" aria-hidden="true">
                <div class="stretcher"></div>
            </div>
        </div>
        <div class="file-details-container">
            <div class="fileName"><h3 id="sidebarTitle"></h3>
            </div>
        </div>
    </div>
</div>
<ul class="tabHeaders">
</ul>
<div class="tabsContainer">
</div>
<button type="button" id="sidebarClose" class="close icon-close" aria-label="<?php p($l->t('Close')); ?>"></button>
