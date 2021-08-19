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
    //if ($('#header').hasClass('share-file')) {
    var mime_array = ['audio/mpeg', 'audio/mp4', 'audio/m4b', 'audio/ogg', 'audio/wav', 'audio/flac', 'audio/x-aiff', 'audio/aac'];
    var mimeType = $('#mimetype').val();
    var sharingToken = $('#sharingToken');

    var token = (sharingToken.val() !== undefined) ? sharingToken.val() : '';
    if (mime_array.indexOf(mimeType) !== -1) {

        var imgFrame = $('#imgframe');
        imgFrame.css({'max-width': '450px'});

        if (imgFrame.find('audio').length === 0) {
            var downloadURL = $('#downloadURL').val();
            var audioTag = '<audio tabindex="0" controls preload="none" style="width: 100%;"> <source src="' + downloadURL + '" type="' + mimeType + '"/> </audio>';
            imgFrame.append(audioTag);
        }

        imgFrame.after($('<div/>').attr('id', 'id3'));
        var ajaxurl = OC.generateUrl('apps/audioplayer/getpublicaudioinfo?token={token}', {'token': token}, {escape: false});

        $.ajax({
            type: 'GET',
            url: ajaxurl,
            success: function (jsondata) {
                if (jsondata.status === 'success') {
                    var $id3 = $('#id3');
                    if (jsondata.data.title !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'Title') + ': ')).append($('<span/>').text(jsondata.data.title)));
                    }
                    if (jsondata.data.subtitle !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'Subtitle') + ': ')).append($('<span/>').text(jsondata.data.subtitle)));
                    }
                    if (jsondata.data.artist !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'Artist') + ': ')).append($('<span/>').text(jsondata.data.artist)));
                    }
                    if (jsondata.data.albumartist !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'Album Artist') + ': ')).append($('<span/>').text(jsondata.data.albumartist)));
                    }
                    if (jsondata.data.composer !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'Composer') + ': ')).append($('<span/>').text(jsondata.data.composer)));
                    }
                    if (jsondata.data.album !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'Album') + ': ')).append($('<span/>').text(jsondata.data.album)));
                    }
                    if (jsondata.data.genre !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'Genre') + ': ')).append($('<span/>').text(jsondata.data.genre)));
                    }
                    if (jsondata.data.year !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'Year') + ': ')).append($('<span/>').text(jsondata.data.year)));
                    }
                    if (jsondata.data.number !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'Disc') + '-' + t('audioplayer', 'Track') + ': ')).append($('<span/>').text(jsondata.data.disc + '-' + jsondata.data.number)));
                    }
                    if (jsondata.data.length !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'Length') + ': ')).append($('<span/>').text(jsondata.data.length)));
                    }
                    if (jsondata.data.bitrate !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'Bitrate') + ': ')).append($('<span/>').text(jsondata.data.bitrate + ' kbps')));
                    }
                    if (jsondata.data.mimetype !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'MIME type') + ': ')).append($('<span/>').text(jsondata.data.mimetype)));
                    }
                    if (jsondata.data.isrc !== '') {
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer', 'ISRC') + ': ')).append($('<span/>').text(jsondata.data.isrc)));
                    }
                    if (jsondata.data.copyright !== '') {
                        $id3.append($('<div/>').append($('<span/>').text(t('audioplayer', 'Copyright') + ' © ')).append($('<span/>').text(jsondata.data.copyright)));
                    }
                    $('.directDownload').css({'padding-top': '20px'});
                    $('.publicpreview').css({'padding-top': '20px'});
                }
            }
        });
    }
    //}
});
