/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2021 Marcel Scherello
 */

/** global: OCA */
/** global: OCP */
/** global: OC */
'use strict';

if (!OCA.Audioplayer) {
    /**
     * @namespace
     */
    OCA.Audioplayer = {};
}

/**
 * @namespace OCA.Audioplayer.WhatsNew
 */
OCA.Audioplayer.WhatsNew = {

    whatsnew: function (options) {
        options = options || {}

        let xhr = new XMLHttpRequest();
        xhr.open('GET', OC.generateUrl('apps/analytics/whatsnew?format=json'), true);
        xhr.setRequestHeader('requesttoken', OC.requestToken);
        xhr.setRequestHeader('OCS-APIREQUEST', 'true');

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status !== 204) {
                let data = JSON.parse(xhr.response);
                OCA.Audioplayer.WhatsNew.show(data, xhr);
            }
        };
        xhr.send();
    },

    dismiss: function (version) {
        let params = 'version=' + encodeURIComponent(version);
        let xhr = new XMLHttpRequest();
        xhr.open('POST', OC.generateUrl('apps/analytics/whatsnew'), true);
        xhr.setRequestHeader('requesttoken', OC.requestToken);
        xhr.setRequestHeader('OCS-APIREQUEST', 'true');
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.send(params);
        $('.whatsNewPopover').remove();
    },

    show: function (data, xhr) {
        if (xhr.status !== 200) {
            return
        }

        let item, menuItem, text, icon

        const div = document.createElement('div')
        div.classList.add('popovermenu', 'open', 'whatsNewPopover', 'menu-left')

        const list = document.createElement('ul')

        // header
        item = document.createElement('li')
        menuItem = document.createElement('span')
        menuItem.className = 'menuitem'

        text = document.createElement('span')
        text.innerText = t('core', 'New in') + ' ' + data['product']
        text.className = 'caption'
        menuItem.appendChild(text)

        icon = document.createElement('span')
        icon.className = 'icon-close'
        icon.onclick = function () {
            OCA.Audioplayer.WhatsNew.dismiss(data['version'])
        }
        menuItem.appendChild(icon)

        item.appendChild(menuItem)
        list.appendChild(item)

        // Highlights
        for (const i in data['whatsNew']['regular']) {
            const whatsNewTextItem = data['whatsNew']['regular'][i]
            item = document.createElement('li')

            menuItem = document.createElement('span')
            menuItem.className = 'menuitem'

            icon = document.createElement('span')
            icon.className = 'icon-checkmark'
            menuItem.appendChild(icon)

            text = document.createElement('p')
            text.innerHTML = _.escape(whatsNewTextItem)
            menuItem.appendChild(text)

            item.appendChild(menuItem)
            list.appendChild(item)
        }

        // Changelog URL
        if (!_.isUndefined(data['changelogURL'])) {
            item = document.createElement('li')

            menuItem = document.createElement('a')
            menuItem.href = data['changelogURL']
            menuItem.rel = 'noreferrer noopener'
            menuItem.target = '_blank'

            icon = document.createElement('span')
            icon.className = 'icon-link'
            menuItem.appendChild(icon)

            text = document.createElement('span')
            text.innerText = t('core', 'View changelog')
            menuItem.appendChild(text)

            item.appendChild(menuItem)
            list.appendChild(item)
        }

        div.appendChild(list)
        document.body.appendChild(div)
    },
}
/**
 * @namespace OCA.Audioplayer.Notification
 */
OCA.Audioplayer.Notification = {
    draggedItem: null,

    closeExistingDialog: function () {
        if (document.getElementById('analyticsDialogContainer') || document.getElementById('analyticsDialogOverlay')) {
            OCA.Audioplayer.Notification.dialogClose();
        }
    },

    info: function (header, text, guidance) {
        OCA.Audioplayer.Notification.closeExistingDialog();
        document.body.insertAdjacentHTML('beforeend',
            '<div id="analyticsDialogOverlay" class="analyticsDialogDim"></div>'
            + '<div id="analyticsDialogContainer" class="analyticsDialog">'
            + '<a class="analyticsDialogClose" id="analyticsDialogBtnClose"></a>'
            + '<div class="analyticsDialogHeader"><span class="analyticsDialogHeaderIcon"></span><span id="analyticsDialogHeader" style="margin-left: 10px;"></span></div>'
            + '<span id="analyticsDialogGuidance" class="userGuidance"></span><br><br>'
            + '<div id="analyticsDialogContent">'
            + '</div>'
            + '<div class="analyticsDialogButtonrow">'
            + '<a class="button analyticsPrimary" id="analyticsDialogBtnGo">' + t('analytics', 'OK') + '</a>'
            + '</div></div>'
        );
        document.getElementById('analyticsDialogHeader').textContent = header;
        document.getElementById('analyticsDialogGuidance').innerHTML = guidance;
        document.getElementById('analyticsDialogContent').innerHTML = text;
        document.getElementById("analyticsDialogBtnClose").addEventListener("click", OCA.Audioplayer.Notification.dialogClose);
        document.getElementById("analyticsDialogBtnGo").addEventListener("click", OCA.Audioplayer.Notification.dialogClose);
    },

    confirm: function (header, text, callback) {
        OCA.Audioplayer.Notification.closeExistingDialog();
        document.body.insertAdjacentHTML('beforeend',
            '<div id="analyticsDialogOverlay" class="analyticsDialogDim"></div>'
            + '<div id="analyticsDialogContainer" class="analyticsDialog">'
            + '<a class="analyticsDialogClose" id="analyticsDialogBtnClose"></a>'
            + '<div class="analyticsDialogHeader"><span class="analyticsDialogHeaderIcon"></span><span id="analyticsDialogHeader" style="margin-left: 10px;"></span></div>'
            + '<div id="analyticsDialogContent">'
            + '<div style="text-align:center; padding-top:100px" class="get-metadata icon-loading"></div>'
            + '</div>'
            + '<div class="analyticsDialogButtonrow">'
            + '<a class="button" id="analyticsDialogBtnCancel">' + t('analytics', 'Cancel') + '</a>'
            + '<a class="button analyticsPrimary" id="analyticsDialogBtnGo">' + t('analytics', 'OK') + '</a>'
            + '</div></div>'
        );
        document.getElementById('analyticsDialogHeader').textContent = header;
        document.getElementById('analyticsDialogContent').innerHTML = text;
        document.getElementById("analyticsDialogBtnClose").addEventListener("click", OCA.Audioplayer.Notification.dialogClose);
        document.getElementById("analyticsDialogBtnCancel").addEventListener("click", OCA.Audioplayer.Notification.dialogClose);
        document.getElementById("analyticsDialogBtnGo").addEventListener("click", callback);
    },

    /**
     * Function to display notifications.
     * @param {('info'|'success'|'error')} type - The type of the notification.
     * @param {string} message - The notification message.
     */
    notification: function (type, message) {
        if (parseInt(OC.config.versionstring.substring(0, 2)) >= 17) {
            if (type === 'success') {
                OCP.Toast.success(message)
            } else if (type === 'error') {
                OCP.Toast.error(message)
            } else {
                OCP.Toast.info(message)
            }
        } else {
            OC.Notification.showTemporary(message);
        }
    },

    /**
     * @param {string} header Popup header as text
     * @param callback Callback function of the OK button
     */
    htmlDialogInitiate: function (header, callback) {
        OCA.Audioplayer.Notification.closeExistingDialog();
        document.body.insertAdjacentHTML('beforeend',
            '<div id="analyticsDialogOverlay" class="analyticsDialogDim"></div>'
            + '<div id="analyticsDialogContainer" class="analyticsDialog">'
            + '<a class="analyticsDialogClose" id="analyticsDialogBtnClose"></a>'
            + '<div class="analyticsDialogHeader"><span class="analyticsDialogHeaderIcon"></span><span id="analyticsDialogHeader" style="margin-left: 10px;"></span></div>'
            + '<span id="analyticsDialogGuidance" class="userGuidance"></span><br><br>'
            + '<div id="analyticsDialogContent">'
            + '<div style="text-align:center; padding-top:100px" class="get-metadata icon-loading"></div>'
            + '</div>'
            + '<div class="analyticsDialogButtonrow">'
            + '<a class="button" id="analyticsDialogBtnCancel">' + t('analytics', 'Cancel') + '</a>'
            + '<a class="button analyticsPrimary" id="analyticsDialogBtnGo">' + t('analytics', 'OK') + '</a>'
            + '</div></div>'
        );

        document.getElementById('analyticsDialogHeader').textContent = header;
        document.getElementById("analyticsDialogBtnClose").addEventListener("click", OCA.Audioplayer.Notification.dialogClose);
        document.getElementById("analyticsDialogBtnCancel").addEventListener("click", OCA.Audioplayer.Notification.dialogClose);
        if (typeof callback === 'function') {
            document.getElementById("analyticsDialogBtnGo").addEventListener("click", callback);
        }
    },

    htmlDialogUpdate: function (content, guidance) {
        const contentElement = document.getElementById('analyticsDialogContent');
        const guidanceElement = document.getElementById('analyticsDialogGuidance');
        if (!contentElement || !guidanceElement) {
            return;
        }

        contentElement.innerHTML = '';
        contentElement.appendChild(content);
        guidanceElement.innerHTML = guidance;
        guidanceElement.hidden = guidance === '';
    },

    htmlDialogUpdateAdd: function (guidance) {
        document.getElementById('analyticsDialogGuidance').innerHTML += '<br>' + guidance;
    },

    dialogClose: function () {
        document.getElementById('analyticsDialogContainer')?.remove();
        document.getElementById('analyticsDialogOverlay')?.remove();
    },

    handleDragStart: function (e) {
        OCA.Audioplayer.Notification.draggedItem = this;
        e.dataTransfer.effectAllowed = "move";
    },

    handleDragOver: function (e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = "move";
        return false;
    },

    handleDrop: function (e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        if (OCA.Audioplayer.Notification.draggedItem !== this) {
            this.parentNode.insertBefore(OCA.Audioplayer.Notification.draggedItem, this);
        }
        return false;
    },
}
