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

document.addEventListener('DOMContentLoaded', function () {
    let mime_array = ['audio/mpeg', 'audio/mp4', 'audio/m4b', 'audio/ogg', 'audio/wav', 'audio/flac', 'audio/x-aiff', 'audio/aac'];
    let mimeType = document.getElementById('mimetype').value;
    let sharingToken = document.getElementById('sharingToken');

    let token = sharingToken ? sharingToken.value : '';
    if (mime_array.indexOf(mimeType) !== -1) {
        let imgFrame = document.getElementById('imgframe');
        imgFrame.style.maxWidth = '450px';

        if (imgFrame.querySelectorAll('audio').length === 0) {
            let downloadURL = document.getElementById('downloadURL').value;
            let audioTag = document.createElement('audio');
            audioTag.tabIndex = 0;
            audioTag.controls = true;
            audioTag.preload = 'none';
            audioTag.style.width = '100%';
            let source = document.createElement('source');
            source.src = downloadURL;
            source.type = mimeType;
            audioTag.appendChild(source);
            imgFrame.appendChild(audioTag);
        }

        let id3 = document.createElement('div');
        id3.id = 'id3';
        imgFrame.insertAdjacentElement('afterend', id3);
        let ajaxurl = OC.generateUrl('apps/audioplayer/getpublicaudioinfo?token={token}', { 'token': token }, { escape: false });

        fetch(ajaxurl)
            .then(function (response) { return response.json(); })
            .then(function (jsondata) {
                if (jsondata.status === 'success') {
                    let id3elem = document.getElementById('id3');
                    let addRow = function (label, value) {
                        if (value !== '') {
                            let div = document.createElement('div');
                            let bold = document.createElement('b');
                            bold.textContent = label + ': ';
                            let span = document.createElement('span');
                            span.textContent = value;
                            div.appendChild(bold);
                            div.appendChild(span);
                            id3elem.appendChild(div);
                        }
                    };
                    addRow(t('audioplayer', 'Title'), jsondata.data.title);
                    addRow(t('audioplayer', 'Subtitle'), jsondata.data.subtitle);
                    addRow(t('audioplayer', 'Artist'), jsondata.data.artist);
                    addRow(t('audioplayer', 'Album Artist'), jsondata.data.albumartist);
                    addRow(t('audioplayer', 'Composer'), jsondata.data.composer);
                    addRow(t('audioplayer', 'Album'), jsondata.data.album);
                    addRow(t('audioplayer', 'Genre'), jsondata.data.genre);
                    addRow(t('audioplayer', 'Year'), jsondata.data.year);
                    if (jsondata.data.number !== '') {
                        addRow(t('audioplayer', 'Disc') + '-' + t('audioplayer', 'Track'), jsondata.data.disc + '-' + jsondata.data.number);
                    }
                    addRow(t('audioplayer', 'Length'), jsondata.data.length);
                    if (jsondata.data.bitrate !== '') {
                        addRow(t('audioplayer', 'Bitrate'), jsondata.data.bitrate + ' kbps');
                    }
                    addRow(t('audioplayer', 'MIME type'), jsondata.data.mimetype);
                    addRow(t('audioplayer', 'ISRC'), jsondata.data.isrc);
                    if (jsondata.data.copyright !== '') {
                        let div = document.createElement('div');
                        let span1 = document.createElement('span');
                        span1.textContent = t('audioplayer', 'Copyright') + ' © ';
                        let span2 = document.createElement('span');
                        span2.textContent = jsondata.data.copyright;
                        div.appendChild(span1);
                        div.appendChild(span2);
                        id3elem.appendChild(div);
                    }
                    document.querySelectorAll('.directDownload').forEach(function (el) { el.style.paddingTop = '20px'; });
                    document.querySelectorAll('.publicpreview').forEach(function (el) { el.style.paddingTop = '20px'; });
                }
            });
    }
});
