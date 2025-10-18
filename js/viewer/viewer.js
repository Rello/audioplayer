/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2021 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */
'use strict';

let audioPlayer = {
    mime: null,
    file: null,
    location: null,
    player: null,
    dir: null
};

function playFile(file, data) {
    file = encodeURIComponent(file);
    audioPlayer.file = file;
    audioPlayer.dir = data.dir;
    let sharingToken = document.getElementById('sharingToken');
    let token = (sharingToken && sharingToken.value !== undefined) ? sharingToken.value : '';
    let dirLoad = data.dir.substring(1);
    if (dirLoad !== '') {
        dirLoad = dirLoad + '/';
    }
    if (token !== '') {
        audioPlayer.location = OC.generateUrl('apps/audioplayer/getpublicaudiostream?token={token}&file={file}', {
            'token': token,
            'file': dirLoad + file
        }, {escape: false});
    } else {
        audioPlayer.location = OC.generateUrl('apps/audioplayer/getaudiostream?file={file}', {'file': dirLoad + file}, {escape: true});
    }
    let fileRow = data.$file && data.$file[0] ? data.$file[0] : data.file;
    audioPlayer.mime = fileRow.getAttribute('data-mime');
    let thumb = fileRow.querySelector('.thumbnail');
    if (thumb) {
        thumb.innerHTML = '<span class="ap-icon ap-icon-volume-up" style="color:#fff;margin-left:5px;display:inline-block;width:24px;height:24px;filter: drop-shadow(-1px 0 0 black) drop-shadow(0 1px 0 black) drop-shadow(1px 0 0 black) drop-shadow(0 -1px 0 black);"></span>';
    }

    if (audioPlayer.player === null) {
        audioPlayer.player = document.createElement('audio');
        audioPlayer.player.setAttribute('src', audioPlayer.location);
        audioPlayer.player.load();
        audioPlayer.player.play();
    } else {
        audioPlayer.player.pause();
        document.querySelectorAll('#filestable .thumbnail .ap-icon-volume-up').forEach(function (el) {
            el.style.display = 'none';
        });
        audioPlayer.player = null;
    }
}

function registerFileActions() {
    let mimeTypes = ['audio/mpeg', 'audio/mp4', 'audio/m4b', 'audio/ogg', 'audio/wav', 'audio/flac', 'audio/x-aiff', 'audio/aac'];
    let icon_url = OC.imagePath('core', 'actions/sound');
    const audio = document.createElement('audio');

    mimeTypes.forEach((element) => {
        if (audio.canPlayType(element)) {
            OCA.Files.fileActions.registerAction({
                name: 'audio',
                displayName: 'Play',
                mime: element,
                permissions: OC.PERMISSION_READ,
                icon: icon_url,
                actionHandler: playFile
            });
            OCA.Files.fileActions.setDefault(element, 'audio');
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    let header = document.getElementById('header');
    if (typeof OCA !== 'undefined' && typeof OCA.Files !== 'undefined' && typeof OCA.Files.fileActions !== 'undefined' && header && !header.classList.contains('share-file')) {
        registerFileActions();
    }
    return true;
});