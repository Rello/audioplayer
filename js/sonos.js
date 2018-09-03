/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2018 Marcel Scherello
 */

Audios.prototype.PlaySonos = function (liIndex) {

    var playIndicator = $('#sonos_play');
    var fileids = [];

    $( '.albumwrapper li' ).each(function() {
        fileid = $(this).data('fileid');
        fileids.push(fileid);
    });

    playIndicator.addClass('playing');

    $.ajax({
        type: 'POST',
        url: OC.generateUrl('apps/audioplayer/sonosqueue'),
        data: {
            'fileArray': fileids,
            'fileIndex': liIndex
        }
    });
};

$(document).ready(function () {
    $('#sonos_play').on('click', function () {
        var playIndicator = $('#sonos_play');
        var action;

        if (playIndicator.hasClass('playing')) {
            playIndicator.removeClass('playing');
            action = 'pause';
        } else {
            playIndicator.addClass('playing');
            action = 'play';
        }

        $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/audioplayer/sonoscontrol'),
            data: {
                'action': action
            }
        });
    });
    $('#sonos_prev').on('click', function () {
        $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/audioplayer/sonoscontrol'),
            data: {
                'action': 'previous'
            }
        });
    });
    $('#sonos_next').on('click', function () {
        $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/audioplayer/sonoscontrol'),
            data: {
                'action': 'next'
            }
        });
    });
    $('#sonos_up').on('click', function () {
        $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/audioplayer/sonoscontrol'),
            data: {
                'action': 'up'
            }
        });
    });
    $('#sonos_down').on('click', function () {
        $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/audioplayer/sonoscontrol'),
            data: {
                'action': 'down'
            }
        });
    });

});
