/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2019 Marcel Scherello
 */

$(document).ready(function () {

    $('#sonos').on('click', function () {
        var user_value;
        if ($('#sonos').prop('checked')) {
            user_value = 'checked';
        }
        else {
            user_value = '';
        }
        $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/audioplayer/admin'),
            data: {
                'type': 'sonos',
                'value': user_value
            },
            success: function () {
                OC.Notification.showTemporary(t('audioplayer', 'saved'));
            }
        });
    });
});