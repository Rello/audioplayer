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

if (!OCA.Audioplayer) {
    /**
     * @namespace
     */
    OCA.Audioplayer = {};
    /**
     * Build common request headers for backend calls
     */
    OCA.Audioplayer.headers = function () {
        let headers = new Headers();
        headers.append('requesttoken', OC.requestToken);
        headers.append('OCS-APIREQUEST', 'true');
        headers.append('Content-Type', 'application/json');
        return headers;
    };
}

document.addEventListener('DOMContentLoaded', function () {
    let cyrillic = document.getElementById('cyrillic_user');
    cyrillic.addEventListener('click', function () {
        let user_value = cyrillic.checked ? 'checked' : '';
        let params = new URLSearchParams({ type: 'cyrillic', value: user_value });
        fetch(OC.generateUrl('apps/audioplayer/setvalue') + '?' + params.toString(), {
            method: 'GET', headers: OCA.Audioplayer.headers()
        }).then(function () {
            OCP.Toast.success(t('audioplayer', 'Saved'));
        });
    });

    /*
 * Collection path
 */
    let pathInput = document.getElementById('audio-path');
    pathInput.addEventListener('click', function () {
        OC.dialogs.filepicker(
            t('audioplayer', 'Select a single folder with audio files'),
            function (path) {
                if (pathInput.value !== path) {
                    pathInput.value = path;
                    fetch(OC.generateUrl('apps/audioplayer/userpath'), {
                        method: 'POST',
                        headers: OCA.Audioplayer.headers(),
                        body: JSON.stringify({value: path})
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
            true,
            1
        );
    });

    let supported_types = '';
    let nsupported_types = '';
    let mimeTypes = ['audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav', 'audio/flac', 'audio/x-aiff', 'audio/aac'];
    let audio = document.createElement('audio');

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
