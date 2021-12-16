/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2021 Marcel Scherello
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    $('#cyrillic_user').on('click', function () {
        var user_value;
        if ($('#cyrillic_user').prop('checked')) {
            user_value = 'checked';
        } else {
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
                OCP.Toast.success(t('audioplayer', 'Saved'));
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
                            OCP.Toast.error(t('audioplayer', 'Invalid path!'));
                        } else {
                            OCP.Toast.success(t('audioplayer', 'Saved'));
                        }
                    });
                }
            },
            false,
            'httpd/unix-directory',
            true
        );
    });

    var supported_types = '';
    var nsupported_types = '';
    var mimeTypes = ['audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav', 'audio/flac', 'audio/x-aiff', 'audio/aac'];
    const audio = document.createElement('audio');

    mimeTypes.forEach((element) => {
        if (audio.canPlayType(element)) {
            supported_types = supported_types + element + ', ';
        } else {
            nsupported_types = nsupported_types + element + ', ';
        }
    });

    $('#browser_yes').html(supported_types);
    $('#browser_no').html(nsupported_types);
});
