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

if (!OCA.Audioplayer) {
    /**
     * @namespace
     */
    OCA.Audioplayer = {};
}

/**
 * @namespace OCA.Audioplayer.Settings
 */
OCA.Audioplayer.Settings = {

    percentage: 0,

    openResetDialog: function () {
        OC.dialogs.message(
            t('audioplayer', 'Are you sure?') + ' ' + t('audioplayer', 'All library entries will be deleted!'),
            t('audioplayer', 'Reset library'),
            'notice',
            OCdialogs.YES_NO_BUTTONS,
            function (e) {
                if (e === true) {
                    OCA.Audioplayer.Settings.resetLibrary();
                }
            },
            true
        );
    },

    resetLibrary: function () {
        if ($('.sm2-bar-ui').hasClass('playing')) {
            this.AudioPlayer.actions.play(0);
            this.AudioPlayer.actions.stop();
        }

        OCA.Audioplayer.audiosInstance.showInitScreen();

        $('#category_selector').val('');
        OCA.Audioplayer.audiosInstance.setUserValue('category', OCA.Audioplayer.audiosInstance.CategorySelectors[0] + '-');
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
    },

    prepareScanDialog: function () {
        $('body').append('<div id="audios_import"></div>');
        $('#audios_import').load(OC.generateUrl('apps/audioplayer/getimporttpl'), function () {
            OCA.Audioplayer.Settings.openScanDialog();
        });
    },

    openScanDialog: function () {

        $('#audios_import_dialog').ocdialog({
            width: 500,
            modal: true,
            resizable: false,
            close: function () {
                OCA.Audioplayer.Settings.stopScan();
                $('#audios_import_dialog').ocdialog('destroy');
                $('#audios_import').remove();
            }
        });

        $('#audios_import_done_close').click(function () {
            OCA.Audioplayer.Settings.percentage = 0;
            $('#audios_import_dialog').ocdialog('close');
        });

        $('#audios_import_progress_cancel').click(function () {
            OCA.Audioplayer.Settings.stopScan();
        });

        $('#audios_import_submit').click(function () {
            OCA.Audioplayer.Settings.processScan();
        });

        $('#audios_import_progressbar').progressbar({value: 0});
    },

    processScan: function () {
        $('#audios_import_form').css('display', 'none');
        $('#audios_import_process').css('display', 'block');

        OCA.Audioplayer.Settings.startScan();
        window.setTimeout(function () {
            OCA.Audioplayer.Settings.updateScanProgress();
        }, 1500);
    },

    startScan: function () {
        var jqXHR = $.post(OC.generateUrl('apps/audioplayer/scanforaudiofiles'),
            {}, function (data) {
                if (data.status === 'success') {
                    $('#audios_import_process').css('display', 'none');
                    $('#audios_import_done').css('display', 'block');
                    $('#audios_import_done_message').html(data.message);
                    OCA.Audioplayer.audiosInstance.init();
                } else {
                    $('#audios_import_progressbar').progressbar('option', 'value', 100);
                    $('#audios_import_done_message').html(data.message);
                }
            });
    },

    stopScan: function () {
        OCA.Audioplayer.Settings.percentage = 0;
        $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/audioplayer/scanforaudiofiles'),
            data: {
                'scanstop': true
            },
            success: function () {
            }
        });
    },

    updateScanProgress: function () {
        $.post(OC.generateUrl('apps/audioplayer/getprogress'),
            {}, function (data) {
                if (data.status === 'success') {
                    OCA.Audioplayer.Settings.percentage = parseInt(data.percent);
                    $('#audios_import_progressbar').progressbar('option', 'value', parseInt(data.percent));
                    $('#audios_import_process_progress').text(data.prog);
                    $('#audios_import_process_message').text(data.msg);
                    if (data.percent < 100) {
                        window.setTimeout(function () {
                            OCA.Audioplayer.Settings.updateScanProgress();
                        }, 1500);
                    } else {
                        $('#audios_import_process').css('display', 'none');
                        $('#audios_import_done').css('display', 'block');
                    }
                } else {
                    //alert("getprogress error");
                }
            });
        return 0;
    },
};

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
        OCA.Audioplayer.Settings.prepareScanDialog();
    });

    $(document).on('click', '#resetAudios', function () {
        OCA.Audioplayer.Settings.openResetDialog();
    });
});