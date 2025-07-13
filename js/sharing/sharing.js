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
    var mime_array = ['audio/mpeg', 'audio/mp4', 'audio/m4b', 'audio/ogg', 'audio/wav', 'audio/flac', 'audio/x-aiff', 'audio/aac'];
    var mimeType = document.getElementById('mimetype').value;
    var sharingToken = document.getElementById('sharingToken');

    var token = sharingToken ? sharingToken.value : '';
    if (mime_array.indexOf(mimeType) !== -1) {
        var imgFrame = document.getElementById('imgframe');
        imgFrame.style.maxWidth = '450px';

        if (imgFrame.querySelectorAll('audio').length === 0) {
            var downloadURL = document.getElementById('downloadURL').value;
            var audioTag = document.createElement('audio');
            audioTag.tabIndex = 0;
            audioTag.controls = true;
            audioTag.preload = 'none';
            audioTag.style.width = '100%';
            var source = document.createElement('source');
            source.src = downloadURL;
            source.type = mimeType;
            audioTag.appendChild(source);
            imgFrame.appendChild(audioTag);
        }

        var id3 = document.createElement('div');
        id3.id = 'id3';
        imgFrame.insertAdjacentElement('afterend', id3);
        var ajaxurl = OC.generateUrl('apps/audioplayer/getpublicaudioinfo?token={token}', { 'token': token }, { escape: false });

        fetch(ajaxurl)
            .then(function (response) { return response.json(); })
            .then(function (jsondata) {
                if (jsondata.status === 'success') {
                    var id3elem = document.getElementById('id3');
                    var addRow = function (label, value) {
                        if (value !== '') {
                            var div = document.createElement('div');
                            var bold = document.createElement('b');
                            bold.textContent = label + ': ';
                            var span = document.createElement('span');
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
                        var div = document.createElement('div');
                        var span1 = document.createElement('span');
                        span1.textContent = t('audioplayer', 'Copyright') + ' © ';
                        var span2 = document.createElement('span');
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
