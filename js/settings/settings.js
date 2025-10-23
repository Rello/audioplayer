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
    scanId: null,
    pollingTimer: null,
    pollingInterval: 5000,

    openResetDialog: function () {
        OCA.Audioplayer.Notification.confirm(
            t('audioplayer', 'Reset library'),
            t('analytics', 'Are you sure?') + ' ' + t('audioplayer', 'All library entries will be deleted!'),
            function () {
                OCA.Audioplayer.Settings.resetLibrary();
                OCA.Audioplayer.Notification.dialogClose();
            }
        );
    },

    resetLibrary: function () {
        let bar = document.querySelector('.sm2-bar-ui');
        if (bar && bar.classList.contains('playing')) {
            OCA.Audioplayer.Player.currentTrackIndex = 0;
            OCA.Audioplayer.Player.stop();
        }

        OCA.Audioplayer.UI.showInitScreen();

        let category = document.getElementById('category_selector');
        if (category) {
            category.value = '';
        }
        OCA.Audioplayer.Backend.setUserValue('category', OCA.Audioplayer.Core.CategorySelectors[0] + '-');

        let myCategory = document.getElementById('myCategory');
        if (myCategory) {
            myCategory.innerHTML = '';
        }
        let alben = document.getElementById('alben');
        if (alben) {
            alben.classList.add('active');
        }
        let indPlaylist = document.getElementById('individual-playlist');
        if (indPlaylist) {
            indPlaylist.remove();
        }
        let info = document.getElementById('individual-playlist-info');
        if (info) {
            info.style.display = 'none';
        }
        let header = document.getElementById('individual-playlist-header');
        if (header) {
            header.style.display = 'none';
        }
        document.querySelectorAll('.coverrow').forEach(function (el) {
            el.remove();
        });
        document.querySelectorAll('.songcontainer').forEach(function (el) {
            el.remove();
        });
        let active = document.getElementById('activePlaylist');
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
        let template = document.getElementById('templateScanDialog');
        if (!template) {
            template = document.createElement('template');
            template.id = 'templateScanDialog';
            template.innerHTML = `
                <div id="audios_import_dialog" title="${t('audioplayer', 'Scan for audio files')}">
                    <div id="audios_import_form">
                        <input id="audios_import_submit" type="button" class="button" value="${t('audioplayer', 'Start scanning …')}">
                    </div>
                    <div id="audios_import_process" style="display:none;">
                        <div id="audios_import_process_progress"></div>
                        <div id="audios_import_process_message"></div>
                        <br>
                        <div id="audios_import_progressbar"></div>
                        <br>
                        <input id="audios_import_progress_cancel" type="button" class="button" value="${t('audioplayer', 'Cancel')}">
                    </div>
                    <div id="audios_import_done" style="display:none;">
                        <div id="audios_import_done_message" class="hint"></div>
                        <br>
                        <input id="audios_import_done_close" type="button" class="button" value="${t('audioplayer', 'Close')}">
                    </div>
                </div>`;
            document.body.appendChild(template);
        }

        OCA.Audioplayer.Settings.openScanDialog();
    },

    openScanDialog: function () {
        OCA.Audioplayer.Notification.htmlDialogInitiate(
            t('analytics', 'Scan for audio files'),
            null
        );

        // remove the normal dialog buttons as this dialog is special
        let analyticsElem = document.querySelector('.analyticsDialogButtonrow');
        if (analyticsElem) {
            analyticsElem.remove();
        }

        const container = document.importNode(document.getElementById('templateScanDialog').content, true);

        let closeBtn = container.getElementById('audios_import_done_close');
        closeBtn.addEventListener('click', function () {
            OCA.Audioplayer.Settings.percentage = 0;
            OCA.Audioplayer.Settings.stopScan();
            OCA.Audioplayer.Notification.dialogClose();
        });


        let cancelBtn = container.getElementById('audios_import_progress_cancel');
        cancelBtn.addEventListener('click', function () {
            OCA.Audioplayer.Settings.stopScan();
        });

        let submitBtn = container.getElementById('audios_import_submit');
        submitBtn.addEventListener('click', function () {
            OCA.Audioplayer.Settings.processScan();
        });

        let progressBar = container.getElementById('audios_import_progressbar');
        if (progressBar) {
            progressBar.value = 0;
        }

        let process = container.getElementById('audios_import_process');
        if (process) {
            process.style.display = 'none';
        }

        let done = container.getElementById('audios_import_done');
        if (done) {
            done.style.display = 'none';
        }

        OCA.Audioplayer.Notification.htmlDialogUpdate(
            container,
            ''
        );

    },

    processScan: function () {
        let form = document.getElementById('audios_import_form');
        let process = document.getElementById('audios_import_process');
        let done = document.getElementById('audios_import_done');
        if (form) {
            form.style.display = 'none';
        }
        if (process) {
            process.style.display = 'block';
        }
        if (done) {
            done.style.display = 'none';
        }
        let doneMessage = document.getElementById('audios_import_done_message');
        if (doneMessage) {
            doneMessage.innerHTML = '';
        }
        let progress = document.getElementById('audios_import_process_progress');
        if (progress) {
            progress.textContent = '';
        }
        let messageBox = document.getElementById('audios_import_process_message');
        if (messageBox) {
            messageBox.textContent = '';
        }
        OCA.Audioplayer.Settings.startScan();
    },

    startScan: function () {
        let scanUrl = OC.generateUrl('apps/audioplayer/scanforaudiofiles');
        OCA.Audioplayer.Settings.scanId = OC.requestToken + '-' + Date.now();
        OCA.Audioplayer.Settings.startPolling();
        fetch(scanUrl + '?scanToken=' + encodeURIComponent(OCA.Audioplayer.Settings.scanId), {
            method: 'GET',
            headers: OCA.Audioplayer.headers()
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.status === 'error') {
                    OCA.Audioplayer.Settings.scanError(data);
                } else if (data.status === 'stopped') {
                    OCA.Audioplayer.Settings.scanStopped(data);
                } else {
                    OCA.Audioplayer.Settings.scanDone(data);
                }
            })
            .catch(function (error) {
                OCA.Audioplayer.Settings.scanError({message: error.message});
            });
    },

    stopScan: function () {
        OCA.Audioplayer.Settings.percentage = 0;
        OCA.Audioplayer.Settings.stopPolling();
        if (!OCA.Audioplayer.Settings.scanId) {
            return;
        }
        let url = OC.generateUrl('apps/audioplayer/scanforaudiofiles') + '?scanstop=true&scanToken=' + encodeURIComponent(OCA.Audioplayer.Settings.scanId);
        fetch(url, {method: 'GET'});
    },

    startPolling: function () {
        OCA.Audioplayer.Settings.stopPolling();
        OCA.Audioplayer.Settings.pollScanProgress();
        OCA.Audioplayer.Settings.pollingTimer = window.setInterval(OCA.Audioplayer.Settings.pollScanProgress, OCA.Audioplayer.Settings.pollingInterval);
    },

    stopPolling: function () {
        if (OCA.Audioplayer.Settings.pollingTimer) {
            window.clearInterval(OCA.Audioplayer.Settings.pollingTimer);
            OCA.Audioplayer.Settings.pollingTimer = null;
        }
    },

    pollScanProgress: function () {
        if (!OCA.Audioplayer.Settings.scanId) {
            return;
        }
        let url = OC.generateUrl('apps/audioplayer/scanprogress') + '?scanToken=' + encodeURIComponent(OCA.Audioplayer.Settings.scanId);
        fetch(url, {
            method: 'GET',
            headers: OCA.Audioplayer.headers()
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                OCA.Audioplayer.Settings.handleProgressResponse(data);
            })
            .catch(function () {
                // ignore polling errors, next interval will retry
            });
    },

    handleProgressResponse: function (data) {
        if (!data || !data.status) {
            return;
        }
        if (data.status === 'running') {
            OCA.Audioplayer.Settings.updateScanProgress(data);
        } else if (data.status === 'done') {
            OCA.Audioplayer.Settings.scanDone(data);
        } else if (data.status === 'stopped') {
            OCA.Audioplayer.Settings.scanStopped(data);
        } else if (data.status === 'error') {
            OCA.Audioplayer.Settings.scanError(data);
        }
    },

    updateScanProgress: function (data) {
        if (!data) {
            return;
        }
        if (data.filesTotal > 0) {
            OCA.Audioplayer.Settings.percentage = data.filesProcessed / data.filesTotal * 100;
        } else {
            OCA.Audioplayer.Settings.percentage = 0;
        }
        let progressBar = document.getElementById('audios_import_progressbar');
        if (progressBar) {
            progressBar.value = OCA.Audioplayer.Settings.percentage;
        }
        let progress = document.getElementById('audios_import_process_progress');
        if (progress) {
            if (data.filesTotal) {
                progress.textContent = `${data.filesProcessed}/${data.filesTotal}`;
            } else {
                progress.textContent = '';
            }
        }
        let messageBox = document.getElementById('audios_import_process_message');
        if (messageBox) {
            messageBox.textContent = data.currentFile || '';
        }
    },

    scanDone: function (data) {
        OCA.Audioplayer.Settings.stopPolling();
        OCA.Audioplayer.Settings.scanId = null;
        let process = document.getElementById('audios_import_process');
        let done = document.getElementById('audios_import_done');
        if (process) {
            process.style.display = 'none';
        }
        if (done) {
            done.style.display = 'block';
        }
        let messageNew = document.getElementById('audios_import_done_message');
        if (messageNew && data && data.message) {
            messageNew.innerHTML = data.message;
        }
        OCA.Audioplayer.Core.init();
    },

    scanStopped: function (data) {
        OCA.Audioplayer.Settings.stopPolling();
        OCA.Audioplayer.Settings.scanId = null;
        let process = document.getElementById('audios_import_process');
        let done = document.getElementById('audios_import_done');
        if (process) {
            process.style.display = 'none';
        }
        if (done) {
            done.style.display = 'block';
        }
        let msg = document.getElementById('audios_import_done_message');
        if (msg) {
            msg.textContent = data && data.message ? data.message : t('audioplayer', 'Scanning was cancelled.');
        }
    },

    scanError: function (data) {
        OCA.Audioplayer.Settings.stopPolling();
        OCA.Audioplayer.Settings.scanId = null;
        let progressBar = document.getElementById('audios_import_progressbar');
        if (progressBar) {
            progressBar.value = 100;
        }
        let msg = document.getElementById('audios_import_done_message');
        if (msg) {
            msg.textContent = data && data.message ? data.message : t('audioplayer', 'An error occurred while scanning.');
        }
        let process = document.getElementById('audios_import_process');
        if (process) {
            process.style.display = 'none';
        }
        let done = document.getElementById('audios_import_done');
        if (done) {
            done.style.display = 'block';
        }
    },
};

document.addEventListener('DOMContentLoaded', function () {

    let settings_link;
    if (OC.config.versionstring.split('.')[0] <= 10) //ownCloud
    {
        settings_link = OC.generateUrl('settings/personal?sectionid=audioplayer');
    } else { //Nextcloud
        settings_link = OC.generateUrl('settings/user/audioplayer');
    }

    let sonos = document.getElementById('sonos');
    if (sonos) {
        sonos.addEventListener('click', function () {
            document.location = settings_link;
        });
    }

    let settingsBtn = document.getElementById('audioplayerSettings');
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