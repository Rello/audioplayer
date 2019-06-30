/*jslint plusplus: true, white: true, nomen: true */
/* global soundManager, document, console, window */

(function(window) {

    /**
     * SoundManager 2: "Bar UI" player
     * Copyright (c) 2007, Scott Schiller. All rights reserved.
     * http://www.schillmania.com/projects/soundmanager2/
     * Code provided under BSD license.
     * http://schillmania.com/projects/soundmanager2/license.txt
     */

    'use strict';

    var Player,
        players = [],
        // CSS selector that will get us the top-level DOM node for the player UI.
        playerSelector = '.sm2-bar-ui',
        utils;

    soundManager.setup({
        // trade-off: higher UI responsiveness (play/progress bar), but may use more CPU.
        html5PollingInterval: 50,
        //flashVersion: 9
    });

    soundManager.onready(function() {
        /*
            var nodes, i, j;

            nodes = utils.dom.getAll(playerSelector);

            if (nodes && nodes.length) {
              for (i=0, j=nodes.length; i<j; i++) {
                players.push(new Player(nodes[i]));
              }
            }
          */
    });

    utils = {

        array: (function() {

            function compare(property) {

                var result;

                return function(a, b) {

                    if (a[property] < b[property]) {
                        result = -1;
                    } else if (a[property] > b[property]) {
                        result = 1;
                    } else {
                        result = 0;
                    }
                    return result;
                };

            }

            function shuffle(array) {

                // Fisher-Yates shuffle algo

                var i, j, temp;

                for (i = array.length - 1; i > 0; i--) {
                    j = Math.floor(Math.random() * (i+1));
                    temp = array[i];
                    array[i] = array[j];
                    array[j] = temp;
                }

                return array;

            }

            return {
                compare: compare,
                shuffle: shuffle
            };

        }()),

        css: (function() {

            function hasClass(o, cStr) {

                return (o.className !== undefined ? new RegExp('(^|\\s)' + cStr + '(\\s|$)').test(o.className) : false);

            }

            function addClass(o, cStr) {

                if (!o || !cStr || hasClass(o, cStr)) {
                    return false; // safety net
                }
                o.className = (o.className ? o.className + ' ' : '') + cStr;

            }

            function removeClass(o, cStr) {

                if (!o || !cStr || !hasClass(o, cStr)) {
                    return false;
                }
                o.className = o.className.replace(new RegExp('( ' + cStr + ')|(' + cStr + ')', 'g'), '');

            }

            function swapClass(o, cStr1, cStr2) {

                var tmpClass = {
                    className: o.className
                };

                removeClass(tmpClass, cStr1);
                addClass(tmpClass, cStr2);

                o.className = tmpClass.className;

            }

            function toggleClass(o, cStr) {

                var found,
                    method;

                found = hasClass(o, cStr);

                method = (found ? removeClass : addClass);

                method(o, cStr);

                // indicate the new state...
                return !found;

            }

            return {
                has: hasClass,
                add: addClass,
                remove: removeClass,
                swap: swapClass,
                toggle: toggleClass
            };

        }()),

        dom: (function() {

            function getAll(/* parentNode, selector */) {

                var node,
                    selector,
                    results;

                if (arguments.length === 1) {

                    // .selector case
                    node = document.documentElement;
                    selector = arguments[0];

                } else {

                    // node, .selector
                    node = arguments[0];
                    selector = arguments[1];

                }

                // sorry, IE 7 users; IE 8+ required.
                if (node && node.querySelectorAll) {

                    results = node.querySelectorAll(selector);

                }

                return results;

            }

            function get(/* parentNode, selector */) {

                var results = getAll.apply(this, arguments);

                // hackish: if an array, return the last item.
                if (results && results.length) {
                    return results[results.length-1];
                }

                // handle "not found" case
                return results && results.length === 0 ? null : results;

            }

            return {
                get: get,
                getAll: getAll
            };

        }()),

        position: (function() {

            function getOffX(o) {
                return $(o).offset().left;
            }

            function getOffY(o) {
                return $(o).offset().top;
            }

            return {
                getOffX: getOffX,
                getOffY: getOffY
            };

        }()),

        style: (function() {

            function get(node, styleProp) {

                // http://www.quirksmode.org/dom/getstyles.html
                var value;

                if (node.currentStyle) {

                    value = node.currentStyle[styleProp];

                } else if (window.getComputedStyle) {

                    value = document.defaultView.getComputedStyle(node, null).getPropertyValue(styleProp);

                }

                return value;

            }

            return {
                get: get
            };

        }()),

        events: (function() {

            var add, remove, preventDefault;

            add = function(o, evtName, evtHandler) {
                // return an object with a convenient detach method.
                var eventObject = {
                    detach: function() {
                        return remove(o, evtName, evtHandler);
                    }
                };
                if (window.addEventListener) {
                    o.addEventListener(evtName, evtHandler, false);
                } else {
                    o.attachEvent('on' + evtName, evtHandler);
                }
                return eventObject;
            };

            remove = (window.removeEventListener !== undefined ? function(o, evtName, evtHandler) {
                return o.removeEventListener(evtName, evtHandler, false);
            } : function(o, evtName, evtHandler) {
                return o.detachEvent('on' + evtName, evtHandler);
            });

            preventDefault = function(e) {
                if (e.preventDefault) {
                    e.preventDefault();
                } else {
                    e.returnValue = false;
                    e.cancelBubble = true;
                }
                return false;
            };

            return {
                add: add,
                preventDefault: preventDefault,
                remove: remove
            };

        }()),

        features: (function() {

            var getAnimationFrame,
                localAnimationFrame,
                localFeatures,
                prop,
                styles,
                testDiv,
                transform;

            testDiv = document.createElement('div');

            /**
             * hat tip: paul irish
             * http://paulirish.com/2011/requestanimationframe-for-smart-animating/
             * https://gist.github.com/838785
             */

            localAnimationFrame = (window.requestAnimationFrame
                || window.webkitRequestAnimationFrame
                || window.mozRequestAnimationFrame
                || window.oRequestAnimationFrame
                || window.msRequestAnimationFrame
                || null);

            // apply to window, avoid "illegal invocation" errors in Chrome
            getAnimationFrame = localAnimationFrame ? function() {
                return localAnimationFrame.apply(window, arguments);
            } : null;

            function has(prop) {

                // test for feature support
                var result = testDiv.style[prop];

                return (result !== undefined ? prop : null);

            }

            // note local scope.
            localFeatures = {

                transform: {
                    ie: has('-ms-transform'),
                    moz: has('MozTransform'),
                    opera: has('OTransform'),
                    webkit: has('webkitTransform'),
                    w3: has('transform'),
                    prop: null // the normalized property value
                },

                rotate: {
                    has3D: false,
                    prop: null
                },

                getAnimationFrame: getAnimationFrame

            };

            localFeatures.transform.prop = (
                localFeatures.transform.w3 ||
                localFeatures.transform.moz ||
                localFeatures.transform.webkit ||
                localFeatures.transform.ie ||
                localFeatures.transform.opera
            );

            function attempt(style) {

                try {
                    testDiv.style[transform] = style;
                } catch(e) {
                    // that *definitely* didn't work.
                    return false;
                }
                // if we can read back the style, it should be cool.
                return !!testDiv.style[transform];

            }

            if (localFeatures.transform.prop) {

                // try to derive the rotate/3D support.
                transform = localFeatures.transform.prop;
                styles = {
                    css_2d: 'rotate(0deg)',
                    css_3d: 'rotate3d(0,0,0,0deg)'
                };

                if (attempt(styles.css_3d)) {
                    localFeatures.rotate.has3D = true;
                    prop = 'rotate3d';
                } else if (attempt(styles.css_2d)) {
                    prop = 'rotate';
                }

                localFeatures.rotate.prop = prop;

            }

            testDiv = null;

            return localFeatures;

        }())

    };

    /**
     * player bits
     */

    Player = function(playerNode) {

        var css, dom, extras, playlistController, soundObject, actions, actionData, defaultItem, defaultVolume, exports;

        css = {
            disabled: 'disabled',
            selected: 'selected',
            active: 'active',
            legacy: 'legacy',
            noVolume: 'no-volume',
            playlistOpen: 'playlist-open'
        };

        dom = {
            o: null,
            playlist: null,
            playlistTarget: null,
            coverTarget:null,
            playlistContainer: null,
            time: null,
            player: null,
            progress: null,
            progressTrack: null,
            progressBar: null,
            duration: null,
            volume: null
        };

        // prepended to tracks when a sound fails to load/play
        extras = {
            loadFailedCharacter: '<span title="Failed to load/play." class="load-error">✖</span>'
        };

        function PlaylistController() {

            var data;

            data = {

                // list of nodes?
                playlist: [],

                // shuffledIndex: [],

                // selection
                selectedIndex: 0,

                shuffleMode: false,

                loopMode: false,

                timer: null

            };

            function getPlaylist() {

                return data.playlist;

            }

            function getItem(offset) {

                var list,
                    item;

                // given the current selection (or an offset), return the current item.

                // if currently null, may be end of list case. bail.
                if (data.selectedIndex === null) {
                    return offset;
                }

                list = getPlaylist();

                // use offset if provided, otherwise take default selected.
                offset = (offset !== undefined ? offset : data.selectedIndex);

                // safety check - limit to between 0 and list length
                offset = Math.max(0, Math.min(offset, list.length));

                item = list[offset];

                return item;

            }

            function findOffsetFromItem(item) {

                // given an <li> item, find it in the playlist array and return the index.
                var list,
                    i,
                    j,
                    offset;

                offset = -1;

                list = getPlaylist();

                if (list) {

                    for (i=0, j=list.length; i<j; i++) {

                        if (list[i] === item) {
                            offset = i;

                            break;
                        }
                    }

                }

                return offset;

            }

            function getNext() {

                // don't increment if null.

                if(data.selectedIndex!==null && data.shuffleMode === true){
                    var aShuffle=[];
                    for(var i=0; i < data.playlist.length; i++){
                        aShuffle[i]=i;
                    }

                    var randIndex = utils.array.shuffle(aShuffle);
                    if(data.selectedIndex !== randIndex[0]){
                        data.selectedIndex = randIndex[0];
                    }else{
                        data.selectedIndex = randIndex[1];
                    }

                    return getItem();
                }

                if (data.selectedIndex !== null) {
                    data.selectedIndex++;

                }



                if (data.playlist.length > 1) {

                    if (data.selectedIndex >= data.playlist.length) {

                        if (data.loopMode) {

                            // loop to beginning
                            data.selectedIndex = 0;

                        } else {

                            // no change
                            data.selectedIndex--;

                            // end playback
                            // data.selectedIndex = null;

                        }

                    }


                } else {

                    data.selectedIndex = null;

                }

                return getItem();
            }

            function getPrevious() {

                data.selectedIndex--;

                if (data.selectedIndex < 0) {
                    // wrapping around beginning of list? loop or exit.
                    if (data.loopMode) {
                        data.selectedIndex = data.playlist.length - 1;
                    } else {
                        // undo
                        data.selectedIndex++;
                    }
                }

                return getItem();
            }

            function resetLastSelected() {

                // remove UI highlight(s) on selected items.
                var items,
                    i, j;

                items = utils.dom.getAll(dom.playlist, '.' + css.selected);

                for (i=0, j=items.length; i<j; i++) {
                    utils.css.remove(items[i], css.selected);
                }

            }

            function select(item) {

                var offset,
                    itemTop,
                    itemBottom,
                    containerHeight,
                    scrollTop,
                    itemPadding;

                // remove last selected, if any
                resetLastSelected();

                if (item) {

                    utils.css.add(item, css.selected);

                    itemTop = item.offsetTop;
                    itemBottom = itemTop + item.offsetHeight;
                    containerHeight = dom.playlistContainer.offsetHeight;
                    scrollTop = dom.playlist.scrollTop;
                    itemPadding = 8;

                    if (itemBottom > containerHeight + scrollTop) {
                        // bottom-align
                        dom.playlist.scrollTop = itemBottom - containerHeight + itemPadding;
                    } else if (itemTop < scrollTop) {
                        // top-align
                        dom.playlist.scrollTop = item.offsetTop - itemPadding;
                    }

                }

                // update selected offset, too.
                offset = findOffsetFromItem(item);

                data.selectedIndex = offset;

            }

            /*
            function selectOffset(offset) {

              var item;

              item = getItem(offset);

              if (item) {
                select(item);
              }

            }

            function playItem(item) {

              // given an item (<li> or <a>), find it in the playlist array, select and play it.

              var list, offset;

              list = getPlaylist();

              if (list) {

                offset = findOffsetFromItem(item);

                if (offset !== -1) {

                  select(offset);

                }

              }

            }

            */

            function playItemByOffset(offset) {

                var item;

                offset = (offset || 0);

                item = getItem(offset);

                if (item) {
                    playLink(item.getElementsByTagName('a')[0]);
                }

            }

            function getURL() {

                // return URL of currently-selected item
                var item, url;

                item = getItem();

                if (item) {
                    url = item.getElementsByTagName('a')[0].href;
                }

                return url;

            }

            function refreshDOM() {

                // get / update playlist from DOM

                if (!dom.playlist) {
                    if (window.console && console.warn) {
                        console.warn('refreshDOM(): playlist node not found?');
                    }
                    return false;
                }

                data.playlist = dom.playlist.getElementsByTagName('li');

            }

            function initDOM() {

                dom.playlistTarget = utils.dom.get(dom.o, '.sm2-playlist-target');
                dom.playlistContainer = utils.dom.get(dom.o, '.sm2-playlist-drawer');
                dom.playlist = utils.dom.get(dom.o, '.sm2-playlist-bd');

            }

            function init() {

                // inherit the default SM2 volume
                defaultVolume = soundManager.defaultOptions.volume;

                initDOM();
                refreshDOM();

                // animate playlist open, if HTML classname indicates so.
                if (utils.css.has(dom.o, css.playlistOpen)) {
                    actions.menu(true);
                }

            }

            init();

            return {
                data: data,
                refresh: refreshDOM,
                getNext: getNext,
                getPrevious: getPrevious,
                getItem: getItem,
                getURL: getURL,
                playItemByOffset: playItemByOffset,
                select: select
            };

        }

        function getTime(msec, useString) {

            // convert milliseconds to hh:mm:ss, return as object literal or string

            var nSec = Math.floor(msec/1000),
                hh = Math.floor(nSec/3600),
                min = Math.floor(nSec/60) - Math.floor(hh * 60),
                sec = Math.floor(nSec -(hh*3600) -(min*60));

            // if (min === 0 && sec === 0) return null; // return 0:00 as null

            return (useString ? ((hh ? hh + ':' : '') + (hh && min < 10 ? '0' + min : min) + ':' + ( sec < 10 ? '0' + sec : sec ) ) : { 'min': min, 'sec': sec });

        }

        function setTitle(item) {

            // given a link, update the "now playing" UI.

            // if this is an <li> with an inner link, grab and use the text from that.
            var links = item.getElementsByTagName('a');

            if (links.length) {
                item = links[0];
            }

            // remove any failed character sequence, also
            dom.playlistTarget.innerHTML = '<ul class="sm2-playlist-bd"><li><b>' + $(item).parent().attr('data-title').replace(extras.loadFailedCharacter, '') + '</b> (' + $(item).parent().attr('data-artist').replace(extras.loadFailedCharacter, '') + ')</li></ul>';



            if (dom.playlistTarget.getElementsByTagName('li')[0].scrollWidth > dom.playlistTarget.offsetWidth) {
                // this item can use <marquee>, in fact.
                dom.playlistTarget.innerHTML = '<ul class="sm2-playlist-bd"><li><marquee>' + item.innerHTML + '</marquee></li></ul>';
            }

        }

        function makeSound(url) {

            var sound = soundManager.createSound({

                url: url,
                type: 'audio/mp3',

                volume: defaultVolume,

                whileplaying: function() {

                    var progressMaxLeft = 100,
                        left,
                        width;

                    left = Math.min(progressMaxLeft, Math.max(0, (progressMaxLeft * (this.position / this.durationEstimate)))) + '%';
                    width = Math.min(100, Math.max(0, (100 * this.position / this.durationEstimate))) + '%';

                    if (this.duration) {

                        dom.progress.style.left = left;
                        dom.progressBar.style.width = width;

                        dom.time.innerHTML = getTime(this.position, true);

                    }

                },

                onbufferchange: function(isBuffering) {

                    if (isBuffering) {
                        utils.css.add(dom.o, 'buffering');
                    } else {
                        utils.css.remove(dom.o, 'buffering');
                    }

                },

                onplay: function() {
                    utils.css.swap(dom.o, 'paused', 'playing');
                    var ap = soundManager.audiosInstance;
                    if (ap.PlaylistContainer.data('playlist') === ap.ActivePlaylist.data('playlist')) {
                        $('.albumwrapper li').removeClass('isActive');
                        $('.albumwrapper li i.ioc').hide();
                        $('.albumwrapper li i.icon').show();
                        $('.albumwrapper li i.ioc').eq(playlistController.data.selectedIndex).removeClass('ioc-volume-off').addClass('ioc-volume-up').show();
                        $('.albumwrapper li i.icon').eq(playlistController.data.selectedIndex).hide();
                        $('.albumwrapper li').eq(playlistController.data.selectedIndex).addClass('isActive');
                    }

                },

                onpause: function() {
                    utils.css.swap(dom.o, 'playing', 'paused');
                    var ap = soundManager.audiosInstance;
                    if (ap.PlaylistContainer.data('playlist') === ap.ActivePlaylist.data('playlist')) {
                        $('.albumwrapper li i.icon').eq(playlistController.data.selectedIndex).hide();
                        $('.albumwrapper li i.ioc').eq(playlistController.data.selectedIndex).removeClass('ioc-volume-up').addClass('ioc-volume-off').show();
                    }
                },

                onresume: function() {
                    utils.css.swap(dom.o, 'paused', 'playing');
                    var ap = soundManager.audiosInstance;
                    if (ap.PlaylistContainer.data('playlist') === ap.ActivePlaylist.data('playlist')) {
                        $('.albumwrapper li i.icon').eq(playlistController.data.selectedIndex).hide();
                        $('.albumwrapper li i.ioc').eq(playlistController.data.selectedIndex).removeClass('ioc-volume-off').addClass('ioc-volume-up').show();
                    }
                },

                whileloading: function() {

                    if (!this.isHTML5) {
                        dom.duration.innerHTML = getTime(this.durationEstimate, true);
                    }

                },

                onload: function(ok) {

                    if (ok) {

                        dom.duration.innerHTML = getTime(this.duration, true);

                    } else if (this._iO && this._iO.onerror) {

                        this._iO.onerror();

                    }

                },

                onerror: function() {

                    // sound failed to load.
                    var item, element, html;

                    item = playlistController.getItem();

                    if (item) {

                        // note error, delay 2 seconds and advance?
                        // playlistTarget.innerHTML = '<ul class="sm2-playlist-bd"><li>' + item.innerHTML + '</li></ul>';

                        if (extras.loadFailedCharacter) {
                            dom.playlistTarget.innerHTML = dom.playlistTarget.innerHTML.replace('<li>' ,'<li>' + extras.loadFailedCharacter + ' ');
                            if (playlistController.data.playlist && playlistController.data.playlist[playlistController.data.selectedIndex]) {
                                element = playlistController.data.playlist[playlistController.data.selectedIndex].getElementsByTagName('a')[0];
                                html = element.innerHTML;
                                if (html.indexOf(extras.loadFailedCharacter) === -1) {
                                    element.innerHTML = extras.loadFailedCharacter + ' ' + html;
                                }
                            }
                        }

                    }

                    // load next, possibly with delay.

                    if (navigator.userAgent.match(/mobile/i)) {
                        // mobile will likely block the next play() call if there is a setTimeout() - so don't use one here.
                        actions.next();
                    } else {
                        if (playlistController.data.timer) {
                            window.clearTimeout(playlistController.data.timer);
                        }
                        playlistController.data.timer = window.setTimeout(actions.next, 2000);
                    }

                },

                onstop: function() {

                    utils.css.remove(dom.o, 'playing');

                },

                onfinish: function() {

                    var lastIndex, item;

                    utils.css.remove(dom.o, 'playing');

                    dom.progress.style.left = '0%';

                    lastIndex = playlistController.data.selectedIndex;

                    // next track?
                    item = playlistController.getNext();

                    // don't play the same item over and over again, if at end of playlist etc.
                    if (item && (playlistController.data.selectedIndex !== lastIndex || (playlistController.data.playlist.length === 1 && playlistController.data.loopMode))) {
                        playlistController.select(item);

                        setTitle(item);

                        // **********************************************************
                        // Notify Audio Player about next track e.g. for cover updates
                        // **********************************************************
                        soundManager.audiosInstance.soundmanagerCallback('onfinish');

                        // play next
                        this.play({
                            url: playlistController.getURL()
                        });

                    } else {

                        // explicitly stop
                        this.stop();

                    }

                }

            });

            return sound;

        }

        function isRightClick(e) {

            // only pay attention to left clicks. old IE differs where there's no e.which, but e.button is 1 on left click.
            if (e && ((e.which && e.which === 2) || (e.which === undefined && e.button !== 1))) {
                // http://www.quirksmode.org/js/events_properties.html#button
                return true;
            }

        }

        function getActionData(target) {

            // DOM measurements for volume slider

            if (!target) {
                return false;
            }

            actionData.volume.x = utils.position.getOffX(target);
            actionData.volume.y = utils.position.getOffY(target);

            actionData.volume.width = target.offsetWidth;
            actionData.volume.height = target.offsetHeight;

            // potentially dangerous: this should, but may not be a percentage-based value.
            actionData.volume.backgroundSize = parseInt(utils.style.get(target, 'background-size'), 10);

            // IE gives pixels even if background-size specified as % in CSS. Boourns.
            if (window.navigator.userAgent.match(/msie|trident/i)) {
                actionData.volume.backgroundSize = (actionData.volume.backgroundSize / actionData.volume.width) * 100;
            }

        }

        function handleMouseDown(e) {

            var links,
                target;

            target = e.target || e.srcElement;

            if (isRightClick(e)) {
                return true;
            }

            // normalize to <a>, if applicable.
            if (target.nodeName.toLowerCase() !== 'a') {

                links = target.getElementsByTagName('a');
                if (links && links.length) {
                    target = target.getElementsByTagName('a')[0];
                }

            }

            if (utils.css.has(target, 'sm2-volume-control')) {

                // drag case for volume

                getActionData(target);

                utils.events.add(document, 'mousemove', actions.adjustVolume);
                utils.events.add(document, 'mouseup', actions.releaseVolume);

                // and apply right away
                return actions.adjustVolume(e);

            }

        }

        function playLink(link) {

            // if a link is OK, play it.

            if (soundManager.canPlayLink(link)) {

                // if there's a timer due to failure to play one track, cancel it.
                // catches case when user may use previous/next after an error.
                if (playlistController.data.timer) {
                    window.clearTimeout(playlistController.data.timer);
                    playlistController.data.timer = null;
                }

                if (!soundObject) {
                    soundObject = makeSound(link.href);
                }

                // required to reset pause/play state on iOS so whileplaying() works? odd.
                soundObject.stop();

                playlistController.select(link.parentNode);

                setTitle(link.parentNode);

                // reset the UI
                dom.progress.style.left = '0px';
                dom.progressBar.style.width = '0px';

                soundObject.play({
                    url: link.href,
                    position: 0
                });

            }

        }

        function handleClick(e) {

            var evt,
                target,
                offset,
                targetNodeName,
                methodName,
                href,
                handled;

            evt = (e || window.event);

            target = evt.target || evt.srcElement;

            if (target && target.nodeName) {

                targetNodeName = target.nodeName.toLowerCase();

                if (targetNodeName !== 'a') {

                    // old IE (IE 8) might return nested elements inside the <a>, eg., <b> etc. Try to find the parent <a>.

                    if (target.parentNode) {

                        do {
                            target = target.parentNode;
                            targetNodeName = target.nodeName.toLowerCase();
                        } while (targetNodeName !== 'a' && target.parentNode);

                        if (!target) {
                            // something went wrong. bail.
                            return false;
                        }

                    }

                }

                if (targetNodeName === 'a') {

                    // yep, it's a link.

                    href = target.href;

                    if (soundManager.canPlayURL(href)) {

                        // not excluded
                        if (!utils.css.has(target, 'sm2-exclude')) {

                            // find this in the playlist

                            playLink(target);

                            handled = true;

                        }

                    } else {

                        // is this one of the action buttons, eg., play/pause, volume, etc.?
                        offset = target.href.lastIndexOf('#');

                        if (offset !== -1) {

                            methodName = target.href.substr(offset+1);

                            if (methodName && actions[methodName]) {
                                handled = true;
                                actions[methodName](e);
                            }

                        }

                    }

                    // fall-through case

                    if (handled) {
                        // prevent browser fall-through
                        return utils.events.preventDefault(evt);
                    }

                }

            }

        }

        function handleMouse(e) {

            var target, barX, barWidth, x, newPosition, sound;

            target = dom.progressTrack;

            barX = utils.position.getOffX(target);
            barWidth = target.offsetWidth;

            x = (e.clientX - barX);

            newPosition = (x / barWidth);

            sound = soundObject;

            if (sound && sound.duration) {

                sound.setPosition(sound.duration * newPosition);

                // a little hackish: ensure UI updates immediately with current position, even if audio is buffering and hasn't moved there yet.
                if (sound._iO && sound._iO.whileplaying) {
                    sound._iO.whileplaying.apply(sound);
                }

            }

            if (e.preventDefault) {
                e.preventDefault();
            }

            return false;

        }

        function releaseMouse(e) {

            utils.events.remove(document, 'mousemove', handleMouse);

            utils.css.remove(dom.o, 'grabbing');

            utils.events.remove(document, 'mouseup', releaseMouse);

            utils.events.preventDefault(e);

            return false;

        }

        function init() {

            // init DOM?

            if (!playerNode) {
                console.warn('init(): No playerNode element?');
            }

            dom.o = playerNode;

            // are we dealing with a crap browser? apply legacy CSS if so.
            if (window.navigator.userAgent.match(/msie [678]/i)) {
                utils.css.add(dom.o, css.legacy);
            }

            if (window.navigator.userAgent.match(/mobile/i)) {
                // majority of mobile devices don't let HTML5 audio set volume.
                utils.css.add(dom.o, css.noVolume);
            }

            dom.progress = utils.dom.get(dom.o, '.sm2-progress-ball');

            dom.progressTrack = utils.dom.get(dom.o, '.sm2-progress-track');

            dom.progressBar = utils.dom.get(dom.o, '.sm2-progress-bar');

            dom.volume = utils.dom.get(dom.o, 'a.sm2-volume-control');

            // measure volume control dimensions
            if (dom.volume) {
                getActionData(dom.volume);
            }

            dom.duration = utils.dom.get(dom.o, '.sm2-inline-duration');

            dom.time = utils.dom.get(dom.o, '.sm2-inline-time');

            playlistController = new PlaylistController();

            defaultItem = playlistController.getItem(0);
            if(defaultItem !== undefined){
                playlistController.select(defaultItem);

                setTitle(defaultItem);
            }

            utils.events.add(dom.o, 'mousedown', handleMouseDown);

            utils.events.add(dom.o, 'click', handleClick);

            utils.events.add(dom.progressTrack, 'mousedown', function(e) {

                if (isRightClick(e)) {
                    return true;
                }

                utils.css.add(dom.o, 'grabbing');
                utils.events.add(document, 'mousemove', handleMouse);
                utils.events.add(document, 'mouseup', releaseMouse);

                return handleMouse(e);

            });

        }

        // ---

        actionData = {

            volume: {
                x: 0,
                y: 0,
                width: 0,
                height: 0,
                backgroundSize: 0
            }

        };

        actions = {

            getVolume: function() {
                return defaultVolume;
            },

            play: function(eventOrOffset) {

                /**
                 * This is an overloaded function that takes mouse/touch events or offset-based item indices.
                 * Remember, "auto-play" will not work on mobile devices unless this function is called immediately from a touch or click event.
                 * If you have the link but not the offset, you can also pass a fake event object with a target of an <a> inside the playlist - e.g. { target: someMP3Link }
                 */

                var target,
                    href,
                    e;

                if (eventOrOffset !== undefined && !isNaN(eventOrOffset)) {
                    // smells like a number.
                    return playlistController.playItemByOffset(eventOrOffset);
                }

                // DRY things a bit
                e = eventOrOffset;

                if (e && e.target) {

                    target = e.target || e.srcElement;

                    href = target.href;

                }

                // haaaack - if null due to no event, OR '#' due to play/pause link, get first link from playlist
                if (!href || href.indexOf('#') !== -1) {
                    href = dom.playlist.getElementsByTagName('a')[0].href;
                }

                if (!soundObject) {
                    soundObject = makeSound(href);
                }

                soundObject.togglePause();

                // special case: clear "play next" timeout, if one exists.
                // edge case: user pauses after a song failed to load.
                if (soundObject.paused && playlistController.data.timer) {
                    window.clearTimeout(playlistController.data.timer);
                    playlistController.data.timer = null;
                }

            },

            pause: function() {

                if (soundObject && soundObject.readyState) {
                    soundObject.pause();
                }

            },

            resume: function() {

                if (soundObject && soundObject.readyState) {
                    soundObject.resume();
                }

            },

            stop: function() {

                // just an alias for pause, really.
                // don't actually stop because that will mess up some UI state, i.e., dragging the slider.
                return actions.pause();

            },

            next: function(/* e */) {

                var item, lastIndex;
                if($('#activePlaylist li').length > 0){
                    // special case: clear "play next" timeout, if one exists.
                    if (playlistController.data.timer) {
                        window.clearTimeout(playlistController.data.timer);
                        playlistController.data.timer = null;
                    }

                    lastIndex = playlistController.data.selectedIndex;

                    item = playlistController.getNext(true);

                    // don't play the same item again
                    if (item && playlistController.data.selectedIndex !== lastIndex) {
                        playLink(item.getElementsByTagName('a')[0]);
                        // **********************************************************
                        // Notify Audio Player about next track e.g. for cover updates
                        // **********************************************************
                        soundManager.audiosInstance.soundmanagerCallback('next');
                    }
                }
            },

            prev: function(/* e */) {

                var item, lastIndex;
                if($('#activePlaylist li').length > 0){
                    lastIndex = playlistController.data.selectedIndex;

                    item = playlistController.getPrevious();

                    // don't play the same item again
                    if (item && playlistController.data.selectedIndex !== lastIndex) {
                        playLink(item.getElementsByTagName('a')[0]);
                        // **********************************************************
                        // Notify Audio Player about next track e.g. for cover updates
                        // **********************************************************
                        soundManager.audiosInstance.soundmanagerCallback('prev');
                    }
                }
            },

            shuffle: function(e) {

                // NOTE: not implemented yet.

                var target = (e ? e.target || e.srcElement : utils.dom.get(dom.o, '.shuffle'));

                if (target && !utils.css.has(target, css.disabled)) {
                    utils.css.toggle(target.parentNode, css.active);
                    playlistController.data.shuffleMode = !playlistController.data.shuffleMode;

                }

            },

            repeat: function(e) {

                var target = (e ? e.target || e.srcElement : utils.dom.get(dom.o, '.repeat'));

                if (target && !utils.css.has(target, css.disabled)) {
                    utils.css.toggle(target.parentNode, css.active);
                    playlistController.data.loopMode = !playlistController.data.loopMode;
                }

            },

            menu: function(ignoreToggle) {

                var isOpen;

                isOpen = utils.css.has(dom.o, css.playlistOpen);

                // sniff out booleans from mouse events, as this is referenced directly by event handlers.
                if (typeof ignoreToggle !== 'boolean' || !ignoreToggle) {

                    if (!isOpen) {
                        // explicitly set height:0, so the first closed -> open animation runs properly
                        dom.playlistContainer.style.height = '0px';
                    }

                    isOpen = utils.css.toggle(dom.o, css.playlistOpen);

                }

                // playlist
                dom.playlistContainer.style.height = (isOpen ? dom.playlistContainer.scrollHeight : 0) + 'px';

            },

            adjustVolume: function(e) {

                /**
                 * NOTE: this is the mousemove() event handler version.
                 * Use setVolume(50), etc., to assign volume directly.
                 */

                var backgroundMargin,
                    pixelMargin,
                    target,
                    value,
                    volume;

                value = 0;

                target = dom.volume;

                // safety net
                if (e === undefined) {
                    return false;
                }

                if (e.clientX === undefined) {
                    // called directly or with a non-mouseEvent object, etc.
                    // proxy to the proper method.
                    if (arguments[0] !== undefined && window.console && window.console.warn) {
                        console.warn('Bar UI: call setVolume(' + arguments[0] + ') instead of adjustVolume(' + arguments[0] + ').');
                    }
                    return actions.setVolume.apply(this, arguments);
                }

                // based on getStyle() result
                // figure out spacing around background image based on background size, eg. 60% background size.
                // 60% wide means 20% margin on each side.
                backgroundMargin = (100 - actionData.volume.backgroundSize) / 2;

                // relative position of mouse over element
                value = Math.max(0, Math.min(1, (e.clientX - actionData.volume.x) / actionData.volume.width));

                target.style.clip = 'rect(0px, ' + (actionData.volume.width * value) + 'px, ' + actionData.volume.height + 'px, ' + (actionData.volume.width * (backgroundMargin/100)) + 'px)';

                // determine logical volume, including background margin
                pixelMargin = ((backgroundMargin/100) * actionData.volume.width);

                volume = Math.max(0, Math.min(1, ((e.clientX - actionData.volume.x) - pixelMargin) / (actionData.volume.width - (pixelMargin*2)))) * 100;

                // set volume
                if (soundObject) {
                    soundObject.setVolume(volume);
                }

                defaultVolume = volume;
                // **********************************************************
                // Notify Audio Player about next track e.g. for cover updates
                // **********************************************************
                soundManager.audiosInstance.soundmanagerCallback('setVolume');

                return utils.events.preventDefault(e);

            },

            releaseVolume: function(/* e */) {

                utils.events.remove(document, 'mousemove', actions.adjustVolume);
                utils.events.remove(document, 'mouseup', actions.releaseVolume);

            },

            setVolume: function(volume) {

                // set volume (0-100) and update volume slider UI.

                var backgroundSize,
                    backgroundMargin,
                    backgroundOffset,
                    pixelMargin,
                    target,
                    from,
                    to;

                if (volume === undefined || isNaN(volume)) {
                    return;
                }

                if (dom.volume) {

                    target = dom.volume;

                    // based on getStyle() result
                    backgroundSize = actionData.volume.backgroundSize;

                    // figure out spacing around background image based on background size, eg. 60% background size.
                    // 60% wide means 20% margin on each side.
                    backgroundMargin = (100 - backgroundSize) / 2;

                    // margin as pixel value relative to width
                    backgroundOffset = actionData.volume.width * (backgroundMargin/100);

                    from = backgroundOffset;
                    to = from + ((actionData.volume.width - (backgroundOffset*2)) * (volume/100));

                    target.style.clip = 'rect(0px, ' + to + 'px, ' + actionData.volume.height + 'px, ' + from + 'px)';

                }

                // apply volume to sound, as applicable
                if (soundObject) {
                    soundObject.setVolume(volume);
                }

                defaultVolume = volume;

            }

        };

        init();


        exports = {
            actions: actions,
            dom: dom,
            playlistController: playlistController
        };

        return exports;

    };

    // ---

    // expose to global
    window.sm2BarPlayers = players;
    window.SM2BarPlayer = Player;

}(window));