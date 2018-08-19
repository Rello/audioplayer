/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2018 Marcel Scherello
 */

/** global: OCA */
/** global: net */


(function () {

    /**
     * @constructs Widget
     */
    var Widget = function () {

        var widget = {

            divClock: null,

            init: function () {
                widget.divClock = $('#widget-audioplayer');
                widget.displayTime();


            },

            displayTime: function () {

                widget.divClock.text("Hello World from Audio Player");
            }

        };

        $.extend(Widget.prototype, widget);
    };

    OCA.AudioPlayer.Widget = Widget;
    OCA.AudioPlayer.widget = new Widget();

})();