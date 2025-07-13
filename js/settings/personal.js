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
    var cyrillic = document.getElementById('cyrillic_user');
    cyrillic.addEventListener('click', function () {
        var user_value = cyrillic.checked ? 'checked' : '';
        var params = new URLSearchParams({ type: 'cyrillic', value: user_value });
        fetch(OC.generateUrl('apps/audioplayer/setvalue') + '?' + params.toString(), {
            method: 'GET'
        }).then(function () {
            OCP.Toast.success(t('audioplayer', 'Saved'));
        });
    });

    /*
 * Collection path
 */
    var pathInput = document.getElementById('audio-path');
    pathInput.addEventListener('click', function () {
        OC.dialogs.filepicker(
            t('audioplayer', 'Select a single folder with audio files'),
            function (path) {
                if (pathInput.value !== path) {
                    pathInput.value = path;
                    fetch(OC.generateUrl('apps/audioplayer/userpath'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ value: path })
                    }).then(function (response) { return response.json(); }).then(function (data) {
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
    var audio = document.createElement('audio');

    mimeTypes.forEach(function (element) {
        if (audio.canPlayType(element)) {
            supported_types += element + ', ';
        } else {
            nsupported_types += element + ', ';
        }
    });

    document.getElementById('browser_yes').innerHTML = supported_types;
    document.getElementById('browser_no').innerHTML = nsupported_types;
});
