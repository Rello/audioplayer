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
?>

<div class="detailFileInfoContainer">
    <div class="mainFileInfoView">
        <div class="thumbnailContainer">
            <a id="sidebarThumbnail" href="#" class="thumbnail action-default">
                <div class="stretcher"></div>
            </a>
        </div>
        <div class="file-details-container">
            <div class="fileName"><h3 id="sidebarTitle" class="ellipsis" >Rolltreppenmax</h3><a
                        class="permalink" href="https://testserver/owncloud/f/2285" title=""
                        data-original-title="Copy direct link (only works for users who have access to this file/folder)"><span
                            class="icon icon-clippy"></span><span class="hidden-visually">Copy direct link (only works for users who have access to this file/folder)</span></a>
            </div>
            <div class="file-details ellipsis">
                <a href="#" class="action action-favorite favorite permanent">
                    <span class="icon icon-star" title="" data-original-title="Favorite"></span>
                </a>
                <span id="sidebarMime"></span>
            </div>
        </div>
    </div>
</div>
<ul class="tabHeaders">
    <li id="tabHeaderAudiplayer" class="tabHeader selected" data-tabid="1" data-tabindex="1">
        <a href="#">Audio Player</a>
    </li>
    <li id="tabHeaderID3Editor" class="tabHeader" data-tabid="2" data-tabindex="2">
        <a href="#">ID3 Editor</a>
    </li>
</ul>
<div class="tabsContainer">
    <div id="audioplayerTabView" class="tab audioplayerTabView">
    </div>
    <div id="ID3EditorTabView" class="tab ID3EditorTabView hidden">
    </div>
</div>
<a id="sidebarClose" class="close icon-close" href="#"></a>