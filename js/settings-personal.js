/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2018 Marcel Scherello
 */

$(document).ready(function () {
    $('#cyrillic_user').on('click', function () {
        var user_value;
        if ($('#cyrillic_user').prop('checked')) {
            user_value = 'checked';
        }
        else {
            user_value = '';
        }
        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/setvalue'),
            data: {
                'type': 'cyrillic',
                'value': user_value
            },
            success: function () {
                OC.Notification.showTemporary(t('audioplayer', 'saved'));
            }
        });
    });

    $('#sonos').on('click', function () {
        var user_value;
        if ($('#sonos').prop('checked')) {
            user_value = 'checked';
        }
        else {
            user_value = '';
        }
        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/setvalue'),
            data: {
                'type': 'sonos',
                'value': user_value
            },
            success: function () {
                OC.Notification.showTemporary(t('audioplayer', 'saved'));
            }
        });
    });

    $('#sonos_controller_submit').on('click', function () {
        var user_value = $('#sonos_controller').val();
        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/setvalue'),
            data: {
                'type': 'sonos_controller',
                'value': user_value
            },
            success: function () {
                OC.Notification.showTemporary(t('audioplayer', 'saved'));
            }
        });
    });

    $('#sonos_smb_path_submit').on('click', function () {
        var user_value = $('#sonos_smb_path').val();
        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/audioplayer/setvalue'),
            data: {
                'type': 'sonos_smb_path',
                'value': user_value
            },
            success: function () {
                OC.Notification.showTemporary(t('audioplayer', 'saved'));
            }
        });
    });

    /*
 * Collection path
 */
    var $path = $('#audio-path');
    $path.on('click', function () {
        OC.dialogs.filepicker(
            t('audioplayer', 'Select a single folder with audio files'),
            function (path) {
                if ($path.val() !== path) {
                    $path.val(path);
                    $.post(OC.generateUrl('apps/audioplayer/userpath'), {value: path}, function (data) {
                        if (!data.success) {
                            OC.Notification.showTemporary(t('audioplayer', 'Invalid path!'));
                        } else {
                            OC.Notification.showTemporary(t('audioplayer', 'saved'));
                        }
                    });
                }
            },
            false,
            'httpd/unix-directory',
            true
        );
    });

    var audioPlayer = {};
    soundManager.setup({
        url: OC.filePath('audioplayer', 'js', 'soundmanager2.swf'),
        onready: function () {
            audioPlayer.player = soundManager.createSound({});
            var can_play = soundManager.html5;
            var supported_types = '';
            var nsupported_types = '';
            for (var mtype in can_play) {
                var mtype_check = can_play[mtype];
                if (mtype.substring(5, 6) !== '/' && mtype !== 'usingFlash' && mtype !== 'canPlayType') {

                    if (mtype_check === true) {
                        supported_types = supported_types + mtype + ', ';
                    } else {
                        nsupported_types = nsupported_types + mtype + ', ';
                    }
                }
            }
            $('#browser_yes').html(supported_types);
            $('#browser_no').html(nsupported_types);
        }
    });
});
