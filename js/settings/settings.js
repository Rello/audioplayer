/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2019 Marcel Scherello
 */

/* global Audios */
// OK because ./js/app.js is sourced before in html

'use strict';

document.addEventListener('DOMContentLoaded', function () {

    var settings_link;
    if (OC.config.versionstring.split('.')[0] <= 10) //ownCloud
    {
        settings_link = OC.generateUrl('settings/personal?sectionid=audioplayer');
    } else { //Nextcloud
        settings_link = OC.generateUrl('settings/user/audioplayer');
    }

    $('#sonos').on('click', function () {
        document.location = settings_link;
    });

    $('#audioplayerSettings').on('click', function () {
        document.location = settings_link;
    });

    $(document).on('click', '#scanAudios, #scanAudiosFirst', function () {
        OCA.Audioplayer.audiosInstance.openScannerDialog();
    }.bind(OCA.Audioplayer.audiosInstance));

    $(document).on('click', '#resetAudios', function () {
        OCA.Audioplayer.audiosInstance.openResetDialog();
    }.bind(OCA.Audioplayer.audiosInstance));
});

Audios.prototype.openResetDialog = function () {
    OC.dialogs.message(
        t('audioplayer', 'Are you sure?') + ' ' + t('audioplayer', 'All library entries will be deleted!'),
        t('audioplayer', 'Reset library'),
        'notice',
        OCdialogs.YES_NO_BUTTONS,
        function (e) {
            if (e === true) {
                this.resetLibrary();
            }
        }.bind(this),
        true
    );
};

Audios.prototype.resetLibrary = function () {
    if ($('.sm2-bar-ui').hasClass('playing')) {
        this.AudioPlayer.actions.play(0);
        this.AudioPlayer.actions.stop();
    }

    this.showInitScreen();

    $('#category_selector').val('');
    this.setUserValue('category', this.CategorySelectors[0] + '-');
    $('#myCategory').html('');
    $('#alben').addClass('active');
    $('#individual-playlist').remove();
    $('#individual-playlist-info').hide();
    $('#individual-playlist-header').hide();
    $('.coverrow').remove();
    $('.songcontainer').remove();
    $('#activePlaylist').html('');
    $('.sm2-playlist-target').html('');
    $('.sm2-playlist-cover').css('background-color', '#ffffff').html('');

    $.ajax({
        type: 'GET',
        url: OC.generateUrl('apps/audioplayer/resetmedialibrary'),
        success: function (jsondata) {
            if (jsondata.status === 'success') {
                OC.Notification.showTemporary(t('audioplayer', 'Resetting finished!'));
            }
        }
    });
};

Audios.prototype.openScannerDialog = function () {
    $('body').append('<div id="audios_import"></div>');
    $('#audios_import').load(OC.generateUrl('apps/audioplayer/getimporttpl'), function () {
        this.scanInit();
    }.bind(this));
};

Audios.prototype.scanInit = function () {

    $('#audios_import_dialog').ocdialog({
        width: 500,
        modal: true,
        resizable: false,
        close: function () {
            this.scanStop();
            $('#audios_import_dialog').ocdialog('destroy');
            $('#audios_import').remove();
        }.bind(this)
    });

    $('#audios_import_done_close').click(function () {
        this.percentage = 0;
        $('#audios_import_dialog').ocdialog('close');
    }.bind(this));

    $('#audios_import_progress_cancel').click(function () {
        this.scanStop();
    }.bind(this));

    $('#audios_import_submit').click(function () {
        this.processScan();
    }.bind(this));

    $('#audios_import_progressbar').progressbar({value: 0});
};

Audios.prototype.processScan = function () {
    $('#audios_import_form').css('display', 'none');
    $('#audios_import_process').css('display', 'block');

    this.scanSend();
    window.setTimeout(function () {
        this.scanUpdate();
    }.bind(this), 1500);
};

Audios.prototype.scanSend = function () {
    $.post(OC.generateUrl('apps/audioplayer/scanforaudiofiles'),
        {}, function (data) {
            if (data.status === 'success') {
                $('#audios_import_process').css('display', 'none');
                $('#audios_import_done').css('display', 'block');
                $('#audios_import_done_message').html(data.message);
                this.init();
            } else {
                $('#audios_import_progressbar').progressbar('option', 'value', 100);
                $('#audios_import_done_message').html(data.message);
            }
        }.bind(this));
};

Audios.prototype.scanStop = function () {
    this.percentage = 0;
    $.ajax({
        type: 'POST',
        url: OC.generateUrl('apps/audioplayer/scanforaudiofiles'),
        data: {
            'scanstop': true
        },
        success: function () {
        }
    });
};

Audios.prototype.scanUpdate = function () {

    $.post(OC.generateUrl('apps/audioplayer/getprogress'),
        {}, function (data) {
            if (data.status === 'success') {
                this.percentage = parseInt(data.percent);
                $('#audios_import_progressbar').progressbar('option', 'value', parseInt(data.percent));
                $('#audios_import_process_progress').text(data.prog);
                $('#audios_import_process_message').text(data.msg);
                if (data.percent < 100) {
                    window.setTimeout(function () {
                        this.scanUpdate();
                    }.bind(this), 1500);
                } else {
                    $('#audios_import_process').css('display', 'none');
                    $('#audios_import_done').css('display', 'block');
                }
            } else {
                //alert("getprogress error");
            }
        }.bind(this));
    return 0;
};
