/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2019 Marcel Scherello
 */

Audios.prototype.showSidebar = function (evt) {

    var trackid = $(evt.target).closest('li').attr('data-trackid');
    var $appsidebar = $("#app-sidebar");

    if ($appsidebar.data('trackid') === trackid) {
        $this.hideSidebar();
    } else {
        var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
        var trackData = $("li[data-trackid='" + trackid + "']");
        var cover = trackData.attr('data-cover');
        var sidebarThumbnail = $('#sidebarThumbnail');

        if (cover !== '') {
            //$('.thumbnailContainer').addClass('large');
            sidebarThumbnail.attr({
                'style': 'background-imafavoriteUpdatege:url(' + getcoverUrl + cover + ')'
            });
            if ($this.PlaylistContainer.width() < 850) {
                sidebarThumbnail.addClass('larger');
            } else {
                sidebarThumbnail.addClass('full');
            }
        } else {
            //$('.thumbnailContainer').removeClass('large');
            sidebarThumbnail.attr({
                'style': 'display: none;'
            }).removeClass('larger').removeClass('full');
        }

        $('#sidebarTitle').html(decodeURIComponent(trackData.attr('data-path')));
        $('#sidebarMime').html(trackData.attr('data-mimetype'));

        var starIcon = $('#sidebarFavorite').attr({'data-trackid': trackid});
        starIcon.off();
        starIcon.on('click', $this.favoriteUpdate.bind($this));

        if ($appsidebar.data('trackid') === '') {
            $(".tabHeaders").empty();
            $(".tabsContainer").empty();
            $('#sidebarClose').on('click', $this.hideSidebar.bind($this));
            $this.registerAudioplayerTab();
            $this.registerID3EditorTab();
            $this.registerPlaylistsTab();
            if ($('#audioplayer_sonos').val() === 'checked') {
                $this.registerSONOSTab();
            }
            // noinspection JSUnresolvedFunction
            OC.Apps.showAppSidebar();
        }

        $appsidebar.data('trackid', trackid);
        $('.tabHeader.selected').click();
    }
};

Audios.prototype.registerPlaylistsTab = function () {
    var li = $('<li/>').addClass('tabHeader')
        .attr({
            'id': 'tabHeaderPlaylists',
            'data-tabid': '3',
            'data-tabindex': '3'
        });
    var atag = $('<a/>').text(t('audioplayer', 'Playlists'));
    li.append(atag);
    $('.tabHeaders').append(li);

    var div = $('<div/>').addClass('tab playlistsTabView')
        .attr({
            'id': 'playlistsTabView'
        });
    $('.tabsContainer').append(div);

    $('#tabHeaderPlaylists').on('click', $this.playlistsTabView.bind($this));
};

Audios.prototype.registerAudioplayerTab = function () {
    var li = $('<li/>').addClass('tabHeader selected')
        .attr({
            'id': 'tabHeaderAudiplayer',
            'data-tabid': '1',
            'data-tabindex': '1'
        });
    var atag = $('<a/>').text(t('audioplayer', 'Metadata'));
    li.append(atag);
    $('.tabHeaders').append(li);

    var div = $('<div/>').addClass('tab audioplayerTabView')
        .attr({
            'id': 'audioplayerTabView'
        });
    $('.tabsContainer').append(div);

    $('#tabHeaderAudiplayer').on('click', $this.audioplayerTabView.bind($this));
};

Audios.prototype.registerID3EditorTab = function () {
    var li = $('<li/>').addClass('tabHeader')
        .attr({
            'id': 'tabHeaderID3Editor',
            'data-tabid': '2',
            'data-tabindex': '2'
        });
    var atag = $('<a/>').text(t('audioplayer', 'ID3 Editor'));
    li.append(atag);
    $('.tabHeaders').append(li);

    var div = $('<div/>').addClass('tab ID3EditorTabView')
        .attr({
            'id': 'ID3EditorTabView'
        });
    $('.tabsContainer').append(div);

    if ($('#audioplayer_editor').val() === 'true') {
        $('#tabHeaderID3Editor').on('click', $this.APEditorTabView.bind($this));
    } else {
        $('#tabHeaderID3Editor').on('click', $this.ID3EditorTabView.bind($this));
    }
};

Audios.prototype.registerSONOSTab = function () {
    var li = $('<li/>').addClass('tabHeader')
        .attr({
            'id': 'tabHeaderSONOS',
            'data-tabid': '4',
            'data-tabindex': '4'
        });
    var atag = $('<a/>').text(t('audioplayer', 'SONOS'));
    li.append(atag);
    $('.tabHeaders').append(li);

    var div = $('<div/>').addClass('tab SONOSTabView')
        .attr({
            'id': 'SONOSTabView'
        });
    $('.tabsContainer').append(div);

    $('#tabHeaderSONOS').on('click', $this.SONOSTabView.bind($this));
};

Audios.prototype.hideSidebar = function () {
    // noinspection JSUnresolvedFunction
    $("#app-sidebar").data('trackid', '');
    OC.Apps.hideAppSidebar();
    $(".tabHeaders").empty();
    $(".tabsContainer").empty();
};

Audios.prototype.audioplayerTabView = function () {
    var trackid = $("#app-sidebar").data('trackid');

    $this.resetView();
    $('#tabHeaderAudiplayer').addClass('selected');
    $('#audioplayerTabView').removeClass('hidden').html('<div style="text-align:center; word-wrap:break-word;" class="get-metadata"><p><img src="' + OC.imagePath('core', 'loading.gif') + '"><br><br></p><p>' + t('audioplayer', 'Reading data') + '</p></div>');

    $.ajax({
        type: 'GET',
        url: OC.generateUrl('apps/audioplayer/getaudioinfo'),
        data: {trackid: trackid},
        success: function (jsondata) {
            var table;
            if (jsondata.status === 'success') {

                table = $('<div>').css('display', 'table').addClass('table');
                var tablerow;
                var m;
                var tablekey;
                var tablevalue;

                var audioinfo = jsondata.data;
                for (m in audioinfo) {
                    tablerow = $('<div>').css('display', 'table-row');
                    tablekey = $('<div>').addClass('key').text(t('audioplayer', m));
                    tablevalue = $('<div>').addClass('value')
                        .text(audioinfo[m]);
                    tablerow.append(tablekey).append(tablevalue);

                    if (m === 'fav' && audioinfo[m] === 't') {
                        $('#sidebarFavorite').removeClass('icon-star')
                            .addClass('icon-starred')
                            .prop('title', t('files', 'Favorited'));
                        audioinfo[m] = '';
                    } else if (m === 'fav') {
                        $('#sidebarFavorite').removeClass('icon-starred')
                            .addClass('icon-star')
                            .prop('title', t('files', 'Favorite'));
                        audioinfo[m] = '';
                    }

                    if (audioinfo[m] !== '' && audioinfo[m] !== null) {
                        table.append(tablerow);
                    }
                }
            } else {
                table = '<div style="margin-left: 2em;" class="get-metadata"><p>' + t('audioplayer', 'No data') + '</p></div>';
            }

            $('#audioplayerTabView').html(table);
        }
    });
};

Audios.prototype.playlistsTabView = function () {
    var trackid = $("#app-sidebar").data('trackid');

    $this.resetView();
    $('#tabHeaderPlaylists').addClass('selected');
    $('#playlistsTabView').removeClass('hidden').html('<div style="text-align:center; word-wrap:break-word;" class="get-metadata"><p><img src="' + OC.imagePath('core', 'loading.gif') + '"><br><br></p><p>' + t('audioplayer', 'Reading data') + '</p></div>');

    $.ajax({
        type: 'POST',
        url: OC.generateUrl('apps/audioplayer/getplaylists'),
        data: {trackid: trackid},
        success: function (jsondata) {
            var table;
            if (jsondata.status === 'success') {

                table = $('<div>').css('display', 'table').addClass('table');
                var tablerow;
                var m;
                var tablekey;
                var tablevalue;

                var audioinfo = jsondata.data;
                for (m in audioinfo) {
                    var spanDelete = $('<a/>').attr({
                        'class': 'icon icon-delete toolTip',
                        'data-listid': audioinfo[m].playlist_id,
                        'data-trackid': trackid,
                        'title': t('audioplayer', 'Remove')
                    }).on('click', $this.removeSongFromPlaylist.bind($this));

                    tablerow = $('<div>').css('display', 'table-row').attr({'data-id': audioinfo[m].playlist_id});
                    tablekey = $('<div>').addClass('key').append(spanDelete);

                    tablevalue = $('<div>').addClass('value')
                        .text(audioinfo[m].name);
                    tablerow.append(tablekey).append(tablevalue);
                    table.append(tablerow);
                }
            } else {
                table = '<div style="margin-left: 2em;" class="get-metadata"><p>' + t('audioplayer', 'No playlist entry') + '</p></div>';
            }

            $('#playlistsTabView').html(table);
        }
    });

};

Audios.prototype.ID3EditorTabView = function () {
    $this.resetView();
    $('#tabHeaderID3Editor').addClass('selected');
    var html = '<div style="margin-left: 2em; background-position: initial;" class="icon-info">';
    html += '<a href="https://github.com/rello/audioplayer_editor"  target="_blank" >';
    html += '<p style="margin-left: 2em;">' + t('audioplayer', 'No ID3 editor installed') + '</p>';
    html += '</a></div>';
    $('#ID3EditorTabView').removeClass('hidden').html(html);
};

Audios.prototype.SONOSTabView = function () {
    var trackid = $("#app-sidebar").data('trackid');
    $this.resetView();
    $('#tabHeaderSONOS').addClass('selected');

    var html = '<div style="margin-left: 2em; background-position: initial;" class="icon-info">';
    html += '<p style="margin-left: 2em;">' + t('audioplayer', 'Details for error analysis') + '</p>';
    html += '<br>';
    html += '</div>';
    $('#SONOSTabView').removeClass('hidden').html(html);

    $.ajax({
        type: 'POST',
        url: OC.generateUrl('apps/audioplayer/sonosdebug'),
        data: {'trackid': trackid},
        success: function (jsondata) {
            html = $('#SONOSTabView').html();
            html += '<p style="margin-left: 2em;">' + t('audioplayer', 'SMB link from user settings:') + '</p>';
            html += '<p style="margin-left: 2em;">' + jsondata.smb + '</p>';
            html += '<br>';
            html += '<p style="margin-left: 2em;">' + t('audioplayer', 'Combined link for your SONOS controller:') + '</p>';
            html += '<p style="margin-left: 2em;">' + jsondata.sonos + '</p>';
            $('#SONOSTabView').html(html);
        }
    });


};

Audios.prototype.resetView = function () {
    $('#tabHeaderAudiplayer').removeClass('selected');
    $('#tabHeaderPlaylists').removeClass('selected');
    $('#tabHeaderID3Editor').removeClass('selected');
    $('#tabHeaderSONOS').removeClass('selected');
    $('#audioplayerTabView').addClass('hidden');
    $('#playlistsTabView').addClass('hidden');
    $('#ID3EditorTabView').addClass('hidden');
    $('#SONOSTabView').addClass('hidden');
};

Audios.prototype.removeSongFromPlaylist = function (evt) {

    var trackid = $(evt.target).attr('data-trackid');
    var playlist = $(evt.target).attr('data-listid');

    $.ajax({
        type: 'POST',
        url: OC.generateUrl('apps/audioplayer/removetrackfromplaylist'),
        data: {
            'playlistid': playlist,
            'songid': trackid
        },
        success: function (jsondata) {
            if (jsondata === true) {
                var currentCount = $('#myCategory li[data-id="' + playlist + '"]').find('.counter');
                currentCount.text(currentCount.text() - 1);
                $('#playlistsTabView div[data-id="' + playlist + '"]').remove();
            }
        }
    });
};
