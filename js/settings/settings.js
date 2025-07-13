/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2021 Marcel Scherello
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
        OC.dialogs.confirm(
            t('audioplayer', 'Are you sure?') + ' ' + t('audioplayer', 'All library entries will be deleted!'),
            t('audioplayer', 'Reset library'),
            function (e) {
                if (e === true) {
                    OCA.Audioplayer.Settings.resetLibrary();
                }
            },
            true
        );
    },

    resetLibrary: function () {
        var bar = document.querySelector('.sm2-bar-ui');
        if (bar && bar.classList.contains('playing')) {
            OCA.Audioplayer.Player.currentTrackIndex = 0;
            OCA.Audioplayer.Player.stop();
        }

        OCA.Audioplayer.UI.showInitScreen();

        var category = document.getElementById('category_selector');
        if (category) {
            category.value = '';
        }
        OCA.Audioplayer.Backend.setUserValue('category', OCA.Audioplayer.Core.CategorySelectors[0] + '-');

        var myCategory = document.getElementById('myCategory');
        if (myCategory) {
            myCategory.innerHTML = '';
        }
        var alben = document.getElementById('alben');
        if (alben) {
            alben.classList.add('active');
        }
        var indPlaylist = document.getElementById('individual-playlist');
        if (indPlaylist) {
            indPlaylist.remove();
        }
        var info = document.getElementById('individual-playlist-info');
        if (info) {
            info.style.display = 'none';
        }
        var header = document.getElementById('individual-playlist-header');
        if (header) {
            header.style.display = 'none';
        }
        document.querySelectorAll('.coverrow').forEach(function (el) {
            el.remove();
        });
        document.querySelectorAll('.songcontainer').forEach(function (el) {
            el.remove();
        });
        var active = document.getElementById('activePlaylist');
        if (active) {
            active.innerHTML = '';
        }
        document.querySelectorAll('.sm2-playlist-target').forEach(function (el) {
            el.innerHTML = '';
        });
        document.querySelectorAll('.sm2-playlist-cover').forEach(function (el) {
            el.style.backgroundColor = '#ffffff';
            el.innerHTML = '';
        });

        let requestUrl = OC.generateUrl('apps/audioplayer/resetmedialibrary');
        fetch(requestUrl, {
            method: 'GET',
            headers: OCA.Audioplayer.headers()
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (jsondata) {
                if (jsondata.status === 'success') {
                    OCP.Toast.success(t('audioplayer', 'Resetting finished!'));
                }
            });
    },

    prepareScanDialog: function () {
        var container = document.createElement('div');
        container.id = 'audios_import';
        document.body.appendChild(container);

        let requestUrl = OC.generateUrl('apps/audioplayer/getimporttpl');
        fetch(requestUrl, {
            method: 'GET',
            headers: OCA.Audioplayer.headers()
        })
            .then(function (response) {
                return response.text();
            })
            .then(function (html) {
                container.innerHTML = html;
                OCA.Audioplayer.Settings.openScanDialog();
            });
    },

    openScanDialog: function () {
        var dialog = document.getElementById('audios_import_dialog');
        if (!dialog) {
            return;
        }
        dialog.style.display = 'block';

        var closeBtn = document.getElementById('audios_import_done_close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                OCA.Audioplayer.Settings.percentage = 0;
                dialog.style.display = 'none';
                OCA.Audioplayer.Settings.stopScan();
                dialog.remove();
                var container = document.getElementById('audios_import');
                if (container) {
                    container.remove();
                }
            });
        }

        var cancelBtn = document.getElementById('audios_import_progress_cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                OCA.Audioplayer.Settings.stopScan();
            });
        }

        var submitBtn = document.getElementById('audios_import_submit');
        if (submitBtn) {
            submitBtn.addEventListener('click', function () {
                OCA.Audioplayer.Settings.processScan();
            });
        }

        var progressBar = document.getElementById('audios_import_progressbar');
        if (progressBar) {
            progressBar.value = 0;
        }
    },

    processScan: function () {
        var form = document.getElementById('audios_import_form');
        var process = document.getElementById('audios_import_process');
        if (form) {
            form.style.display = 'none';
        }
        if (process) {
            process.style.display = 'block';
        }
        OCA.Audioplayer.Settings.startScan();
    },

    startScan: function () {
        var scanUrl = OC.generateUrl('apps/audioplayer/scanforaudiofiles');
        var source = new OC.EventSource(scanUrl);
        source.listen('progress', OCA.Audioplayer.Settings.updateScanProgress);
        source.listen('done', OCA.Audioplayer.Settings.scanDone);
        source.listen('error', OCA.Audioplayer.Settings.scanError);
    },

    stopScan: function () {
        OCA.Audioplayer.Settings.percentage = 0;
        var url = OC.generateUrl('apps/audioplayer/scanforaudiofiles') + '?scanstop=true';
        fetch(url, {method: 'GET'});
    },

    updateScanProgress: function (message) {
        var data = JSON.parse(message);
        OCA.Audioplayer.Settings.percentage = data.filesProcessed / data.filesTotal * 100;
        var progressBar = document.getElementById('audios_import_progressbar');
        if (progressBar) {
            progressBar.value = OCA.Audioplayer.Settings.percentage;
        }
        var progress = document.getElementById('audios_import_process_progress');
        if (progress) {
            progress.textContent = `${data.filesProcessed}/${data.filesTotal}`;
        }
        var messageBox = document.getElementById('audios_import_process_message');
        if (messageBox) {
            messageBox.textContent = data.currentFile;
        }
    },

    scanDone: function (message) {
        var data = JSON.parse(message);
        var process = document.getElementById('audios_import_process');
        var done = document.getElementById('audios_import_done');
        if (process) {
            process.style.display = 'none';
        }
        if (done) {
            done.style.display = 'block';
        }
        var message = document.getElementById('audios_import_done_message');
        if (message) {
            message.innerHTML = data.message;
        }
        OCA.Audioplayer.Core.init();
    },

    scanError: function (message) {
        var data = JSON.parse(message);
        var progressBar = document.getElementById('audios_import_progressbar');
        if (progressBar) {
            progressBar.value = 100;
        }
        var msg = document.getElementById('audios_import_done_message');
        if (msg) {
            msg.textContent = data.message;
        }
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

    var sonos = document.getElementById('sonos');
    if (sonos) {
        sonos.addEventListener('click', function () {
            document.location = settings_link;
        });
    }

    var settingsBtn = document.getElementById('audioplayerSettings');
    if (settingsBtn) {
        settingsBtn.addEventListener('click', function () {
            document.location = settings_link;
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target && (e.target.id === 'scanAudios' || e.target.id === 'scanAudiosFirst')) {
            OCA.Audioplayer.Settings.prepareScanDialog();
        }
        if (e.target && e.target.id === 'resetAudios') {
            OCA.Audioplayer.Settings.openResetDialog();
        }
    });
});