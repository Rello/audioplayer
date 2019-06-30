/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2019 Marcel Scherello
 */

'use strict';

(function () {

    /**
     * @constructs Widget
     */
    var Widget = function () {

        var widget = {

            divClock: null,

            init: function () {
                widget.divClock = $('#widget-audioplayer');

                var PlaylistId = 'X3';
                var title = $('<div>/').text('Recently Played:').addClass('widget-audioplayer-header');
                var html = widget.getTitles(PlaylistId);
                widget.divClock.append(title);

                //var PlaylistId = 'X2';
                //title = $('<div>/').text('Recently Added:').addClass('widget-audioplayer-header');
                //var html = widget.getTitles(PlaylistId);
                //widget.divClock.append(title);
            },

            getTitles: function (PlaylistId) {

                widget.divClock = $('#widget-audioplayer');
                var category = 'Playlist';
                $.ajax({
                    type: 'GET',
                    url: OC.generateUrl('apps/audioplayer/getcategoryitems'),
                    data: {category: category, categoryId: PlaylistId},
                    success: function (jsondata) {
                        var $html;
                        if (jsondata.status === 'success') {
                            $(jsondata.data).each(function (i, el) {
                                var li = $('<li/>').attr({
                                    'data-trackid': el.id,
                                    'data-mimetype': el.mim,
                                    'mimetype': el.mim,
                                    'data-title': el.cl1,
                                    'data-artist': el.cl2,
                                    'data-album': el.cl3,
                                    'data-cover': el.cid,
                                    'data-path': el.lin,
                                    'class': 'dragable'
                                }).addClass('widget-audioplayer-item');
                                var spanTitle = $('<span/>').attr({'class': 'widget-audioplayer-title'}).html(el.cl1);

                                var coverID = el.cid;
                                var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
                                if (coverID === '') {
                                    var addCss = 'background-color: #D3D3D3;color: #333333;';
                                    var addDescr = el.cl1.substring(0, 1);
                                } else {
                                    addDescr = '';
                                    addCss = 'background-image:url(' + getcoverUrl + coverID + ');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
                                }
                                if (i === 0) $('.sm2-playlist-cover').attr({'style': addCss}).text(addDescr);

                                var spanInterpret = $('<span>').attr({'class': 'widget-audioplayer-interpret'});
                                spanInterpret = spanInterpret.html('&nbsp;-&nbsp;'+el.cl2);

                                li.append(spanTitle);
                                li.append(spanInterpret);
                                widget.divClock.append(li);
                            }); // end each loop
                        }

                    }
                });
            }

        };

        $.extend(Widget.prototype, widget);
    };

    OCA.DashBoard.Widget = Widget;
    OCA.DashBoard.widget = new Widget();

})();