/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2019 Marcel Scherello
 */

Audios.prototype.PlaySonos = function (liIndex) {

    var playIndicator = $('#sonos_play');
    var trackids = [];

    $( '.albumwrapper li' ).each(function() {
        trackid = $(this).data('trackid');
        trackids.push(trackid);
    });

    $.ajax({
        type: 'POST',
        url: OC.generateUrl('apps/audioplayer/sonosqueue'),
        data: {
            '$trackArray': trackids,
            'fileIndex': liIndex
        },
        success: function (jsondata) {
            if (jsondata === false) {
                myAudios.SonosGone();
            }
            playIndicator.addClass('playing');
        },
        error: function(){
            myAudios.SonosGone();
        },
        timeout: 3000
    });
};

Audios.prototype.SonosGone = function () {
    OC.dialogs.alert(t('audioplayer', 'SONOS Player not availble.'), t('audioplayer', 'Error'), function(){
        window.location = OC.linkTo('settings','user/audioplayer');
    });
};

Audios.prototype.SonosAction = function (action) {
    $.ajax({
        type: 'POST',
        url: OC.generateUrl('apps/audioplayer/sonosaction'),
        data: {
            'action': action
        },
        success: function (jsondata) {
            if (jsondata === false) {
                myAudios.SonosGone();
            }
            return true;
        },
        error: function(){
            myAudios.SonosGone();
        },
        timeout: 3000
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
            action = 'play';
        }
        if(myAudios.SonosAction(action)) playIndicator.addClass('playing');
    });

    $('#sonos_prev').on('click', function () {
        myAudios.SonosAction('previous');
    });

    $('#sonos_next').on('click', function () {
        myAudios.SonosAction('next');
    });

    $('#sonos_up').on('click', function () {
        myAudios.SonosAction('up');
    });

    $('#sonos_down').on('click', function () {
        myAudios.SonosAction('down');
    });

});
