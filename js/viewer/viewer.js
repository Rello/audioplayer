/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2017 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

var audioPlayer = {
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
    var token = ($('#sharingToken').val() !== undefined) ? $('#sharingToken').val() : '';
    var dirLoad = data.dir.substr(1);
    if (dirLoad !== '') {
        dirLoad = dirLoad + '/';
    }
    if (token !== '') {
        audioPlayer.location = OC.generateUrl('apps/audioplayer/getpublicaudiostream?token={token}&file={file}', {
            'token': token,
            'file': dirLoad + file
        }, {escape: false});
    } else {
        audioPlayer.location = OC.generateUrl('apps/audioplayer/getaudiostream?file={file}', {'file': dirLoad + file}, {escape: false});
    }
    audioPlayer.mime = data.$file.attr('data-mime');
    data.$file.find('.thumbnail').html('<i class="ioc ioc-volume-up"  style="color:#fff;margin-left:5px; text-align:center;line-height:32px;text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;font-size: 24px;"></i>');

    if (audioPlayer.player === null) {
        soundManager.setup({
            url: OC.filePath('audioplayer', 'js', 'soundmanager2.swf'),
            onready: function () {
                audioPlayer.player = soundManager.createSound({
                    id: data.$file.attr('data-id'),
                    url: audioPlayer.location
                });
                audioPlayer.player.play();
            }
        });
    } else {
        audioPlayer.player.stop();
        $('#filestable').find('.thumbnail i.ioc-volume-up').hide();
        //$('#filestable').find('.thumbnail i.ioc-play').show();
        audioPlayer.player = null;
    }
}

function registerFileActions() {
    var mime_array = ['audio/mpeg', 'audio/mp4', 'audio/m4b', 'audio/ogg', 'audio/wav', 'audio/flac'];
    //var stream_array = ['audio/mpegurl', 'audio/x-scpls', 'application/xspf+xml'];
    //mime_array = mime_array.concat(stream_array);

    soundManager.setup({
        url: OC.filePath('audioplayer', 'js', 'soundmanager2.swf'),
        onready: function () {
            audioPlayer.player = soundManager.createSound({});

            var can_play = soundManager.html5;
            var mime;
            var icon_url = OC.imagePath('core', 'actions/sound');
            for (var i = 0; i < mime_array.length; i++) {
                if (can_play[mime_array[i]] === true) {
                    mime = mime_array[i];
                    OCA.Files.fileActions.registerAction({
                        name: 'audio',
                        displayName: 'audio',
                        mime: mime,
                        permissions: OC.PERMISSION_READ,
                        icon: icon_url,
                        actionHandler: playFile
                    });
                    OCA.Files.fileActions.setDefault(mime, 'audio');
                }
            }
            audioPlayer.player = null;
        }
    });
}

$(document).ready(function () {
    if (typeof OCA !== 'undefined' && typeof OCA.Files !== 'undefined' && typeof OCA.Files.fileActions !== 'undefined' && $('#header').hasClass('share-file') === false) {
        registerFileActions();
    }
    return true;
});