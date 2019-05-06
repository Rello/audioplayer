
(function(window){"use strict";var Player,players=[],playerSelector='.sm2-bar-ui',utils;soundManager.setup({html5PollingInterval:50,});soundManager.onready(function(){});utils={array:(function(){function compare(property){var result;return function(a,b){if(a[property]<b[property]){result=-1;}else if(a[property]>b[property]){result=1;}else{result=0;}
        return result;};}
        function shuffle(array){var i,j,temp;for(i=array.length-1;i>0;i--){j=Math.floor(Math.random()*(i+1));temp=array[i];array[i]=array[j];array[j]=temp;}
            return array;}
        return{compare:compare,shuffle:shuffle};}()),css:(function(){function hasClass(o,cStr){return(o.className!==undefined?new RegExp('(^|\\s)'+cStr+'(\\s|$)').test(o.className):false);}
        function addClass(o,cStr){if(!o||!cStr||hasClass(o,cStr)){return false;}
            o.className=(o.className?o.className+' ':'')+cStr;}
        function removeClass(o,cStr){if(!o||!cStr||!hasClass(o,cStr)){return false;}
            o.className=o.className.replace(new RegExp('( '+cStr+')|('+cStr+')','g'),'');}
        function swapClass(o,cStr1,cStr2){var tmpClass={className:o.className};removeClass(tmpClass,cStr1);addClass(tmpClass,cStr2);o.className=tmpClass.className;}
        function toggleClass(o,cStr){var found,method;found=hasClass(o,cStr);method=(found?removeClass:addClass);method(o,cStr);return!found;}
        return{has:hasClass,add:addClass,remove:removeClass,swap:swapClass,toggle:toggleClass};}()),dom:(function(){function getAll(){var node,selector,results;if(arguments.length===1){node=document.documentElement;selector=arguments[0];}else{node=arguments[0];selector=arguments[1];}
        if(node&&node.querySelectorAll){results=node.querySelectorAll(selector);}
        return results;}
        function get(){var results=getAll.apply(this,arguments);if(results&&results.length){return results[results.length-1];}
            return results&&results.length===0?null:results;}
        return{get:get,getAll:getAll};}()),position:(function(){function getOffX(o){return $(o).offset().left;}
        function getOffY(o){return $(o).offset().top;}
        return{getOffX:getOffX,getOffY:getOffY};}()),style:(function(){function get(node,styleProp){var value;if(node.currentStyle){value=node.currentStyle[styleProp];}else if(window.getComputedStyle){value=document.defaultView.getComputedStyle(node,null).getPropertyValue(styleProp);}
        return value;}
        return{get:get};}()),events:(function(){var add,remove,preventDefault;add=function(o,evtName,evtHandler){var eventObject={detach:function(){return remove(o,evtName,evtHandler);}};if(window.addEventListener){o.addEventListener(evtName,evtHandler,false);}else{o.attachEvent('on'+evtName,evtHandler);}
        return eventObject;};remove=(window.removeEventListener!==undefined?function(o,evtName,evtHandler){return o.removeEventListener(evtName,evtHandler,false);}:function(o,evtName,evtHandler){return o.detachEvent('on'+evtName,evtHandler);});preventDefault=function(e){if(e.preventDefault){e.preventDefault();}else{e.returnValue=false;e.cancelBubble=true;}
        return false;};return{add:add,preventDefault:preventDefault,remove:remove};}()),features:(function(){var getAnimationFrame,localAnimationFrame,localFeatures,prop,styles,testDiv,transform;testDiv=document.createElement('div');localAnimationFrame=(window.requestAnimationFrame||window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||window.oRequestAnimationFrame||window.msRequestAnimationFrame||null);getAnimationFrame=localAnimationFrame?function(){return localAnimationFrame.apply(window,arguments);}:null;function has(prop){var result=testDiv.style[prop];return(result!==undefined?prop:null);}
        localFeatures={transform:{ie:has('-ms-transform'),moz:has('MozTransform'),opera:has('OTransform'),webkit:has('webkitTransform'),w3:has('transform'),prop:null},rotate:{has3D:false,prop:null},getAnimationFrame:getAnimationFrame};localFeatures.transform.prop=(localFeatures.transform.w3||localFeatures.transform.moz||localFeatures.transform.webkit||localFeatures.transform.ie||localFeatures.transform.opera);function attempt(style){try{testDiv.style[transform]=style;}catch(e){return false;}
            return!!testDiv.style[transform];}
        if(localFeatures.transform.prop){transform=localFeatures.transform.prop;styles={css_2d:'rotate(0deg)',css_3d:'rotate3d(0,0,0,0deg)'};if(attempt(styles.css_3d)){localFeatures.rotate.has3D=true;prop='rotate3d';}else if(attempt(styles.css_2d)){prop='rotate';}
            localFeatures.rotate.prop=prop;}
        testDiv=null;return localFeatures;}())};Player=function(playerNode){var css,dom,extras,playlistController,soundObject,actions,actionData,defaultItem,defaultVolume,exports;css={disabled:'disabled',selected:'selected',active:'active',legacy:'legacy',noVolume:'no-volume',playlistOpen:'playlist-open'};dom={o:null,playlist:null,playlistTarget:null,coverTarget:null,playlistContainer:null,time:null,player:null,progress:null,progressTrack:null,progressBar:null,duration:null,volume:null};extras={loadFailedCharacter:'<span title="Failed to load/play." class="load-error">✖</span>'};function PlaylistController(){var data;data={playlist:[],selectedIndex:0,shuffleMode:false,loopMode:false,timer:null};function getPlaylist(){return data.playlist;}
    function getItem(offset){var list,item;if(data.selectedIndex===null){return offset;}
        list=getPlaylist();offset=(offset!==undefined?offset:data.selectedIndex);offset=Math.max(0,Math.min(offset,list.length));item=list[offset];return item;}
    function findOffsetFromItem(item){var list,i,j,offset;offset=-1;list=getPlaylist();if(list){for(i=0,j=list.length;i<j;i++){if(list[i]===item){offset=i;break;}}}
        return offset;}
    function getNext(){if(data.selectedIndex!==null&&data.shuffleMode===true){var aShuffle=[];for(var i=0;i<data.playlist.length;i++){aShuffle[i]=i;}
        var randIndex=utils.array.shuffle(aShuffle);if(data.selectedIndex!==randIndex[0]){data.selectedIndex=randIndex[0];}else{data.selectedIndex=randIndex[1];}
        return getItem();
    }
        if(data.selectedIndex!==null){data.selectedIndex++;}
        if(data.playlist.length>1){if(data.selectedIndex>=data.playlist.length){if(data.loopMode){data.selectedIndex=0;}else{data.selectedIndex--;}}}else{data.selectedIndex=null;}
        return getItem();}
    function getPrevious(){data.selectedIndex--;if(data.selectedIndex<0){if(data.loopMode){data.selectedIndex=data.playlist.length-1;}else{data.selectedIndex++;}}
        return getItem();}
    function resetLastSelected(){var items,i,j;items=utils.dom.getAll(dom.playlist,'.'+css.selected);for(i=0,j=items.length;i<j;i++){utils.css.remove(items[i],css.selected);}}
    function select(item){var offset,itemTop,itemBottom,containerHeight,scrollTop,itemPadding;resetLastSelected();if(item){utils.css.add(item,css.selected);itemTop=item.offsetTop;itemBottom=itemTop+item.offsetHeight;containerHeight=dom.playlistContainer.offsetHeight;scrollTop=dom.playlist.scrollTop;itemPadding=8;if(itemBottom>containerHeight+scrollTop){dom.playlist.scrollTop=itemBottom-containerHeight+itemPadding;}else if(itemTop<scrollTop){dom.playlist.scrollTop=item.offsetTop-itemPadding;}}
        offset=findOffsetFromItem(item);data.selectedIndex=offset;}
    function playItemByOffset(offset){var item;offset=(offset||0);item=getItem(offset);if(item){playLink(item.getElementsByTagName('a')[0]);}}
    function getURL(){var item,url;item=getItem();if(item){url=item.getElementsByTagName('a')[0].href;}
        return url;}
    function refreshDOM(){if(!dom.playlist){if(window.console&&console.warn){console.warn('refreshDOM(): playlist node not found?');}
        return false;}
        data.playlist=dom.playlist.getElementsByTagName('li');}
    function initDOM(){dom.playlistTarget=utils.dom.get(dom.o,'.sm2-playlist-target');dom.playlistContainer=utils.dom.get(dom.o,'.sm2-playlist-drawer');dom.playlist=utils.dom.get(dom.o,'.sm2-playlist-bd');}
    function init(){defaultVolume=soundManager.defaultOptions.volume;initDOM();refreshDOM();if(utils.css.has(dom.o,css.playlistOpen)){actions.menu(true);}}
    init();return{data:data,refresh:refreshDOM,getNext:getNext,getPrevious:getPrevious,getItem:getItem,getURL:getURL,playItemByOffset:playItemByOffset,select:select};}
    function getTime(msec,useString){var nSec=Math.floor(msec/1000),hh=Math.floor(nSec/3600),min=Math.floor(nSec/60)-Math.floor(hh*60),sec=Math.floor(nSec-(hh*3600)-(min*60));return(useString?((hh?hh+':':'')+(hh&&min<10?'0'+min:min)+':'+(sec<10?'0'+sec:sec)):{'min':min,'sec':sec});}
    function setTitle(item){var links=item.getElementsByTagName('a');if(links.length){item=links[0];}
        dom.playlistTarget.innerHTML='<ul class="sm2-playlist-bd"><li><b>'+$(item).parent().attr('data-title').replace(extras.loadFailedCharacter,'')+'</b> ('+$(item).parent().attr('data-album').replace(extras.loadFailedCharacter,'')+')</li></ul>';if(dom.playlistTarget.getElementsByTagName('li')[0].scrollWidth>dom.playlistTarget.offsetWidth){dom.playlistTarget.innerHTML='<ul class="sm2-playlist-bd"><li><marquee>'+item.innerHTML+'</marquee></li></ul>';}}

    function makeSound(url) {
        var sound = soundManager.createSound({
            url: url,
            type: 'audio/mp3',
            volume: defaultVolume,
            whileplaying: function () {
                var progressMaxLeft = 100, left, width;
                left = Math.min(progressMaxLeft, Math.max(0, (progressMaxLeft * (this.position / this.durationEstimate)))) + '%';
                width = Math.min(100, Math.max(0, (100 * this.position / this.durationEstimate))) + '%';
                if (this.duration) {
                    dom.progress.style.left = left;
                    dom.progressBar.style.width = width;
                    dom.time.innerHTML = getTime(this.position, true);
                }
            },
            onbufferchange: function (isBuffering) {
                if (isBuffering) {
                    utils.css.add(dom.o, 'buffering');
                } else {
                    utils.css.remove(dom.o, 'buffering');
                }
            },
            onplay: function () {
                utils.css.swap(dom.o, 'paused', 'playing');
                if ($this.PlaylistContainer.data('playlist') === $this.ActivePlaylist.data('playlist')) {
                    $('.albumwrapper li').removeClass('isActive');
                    $('.albumwrapper li i.ioc').hide();
                    $('.albumwrapper li i.icon').show();
                    $('.albumwrapper li i.ioc').eq(playlistController.data.selectedIndex).removeClass('ioc-volume-off').addClass('ioc-volume-up').show();
                    $('.albumwrapper li i.icon').eq(playlistController.data.selectedIndex).hide();
                    $('.albumwrapper li').eq(playlistController.data.selectedIndex).addClass('isActive');
                }
            },
            onpause: function () {
                utils.css.swap(dom.o, 'playing', 'paused');
                if ($this.PlaylistContainer.data('playlist') === $this.ActivePlaylist.data('playlist')) {
                    $('.albumwrapper li i.icon').eq(playlistController.data.selectedIndex).hide();
                    $('.albumwrapper li i.ioc').eq(playlistController.data.selectedIndex).removeClass('ioc-volume-up').addClass('ioc-volume-off').show();
                }
            },
            onresume: function () {
                utils.css.swap(dom.o, 'paused', 'playing');
                if ($this.PlaylistContainer.data('playlist') === $this.ActivePlaylist.data('playlist')) {
                    $('.albumwrapper li i.icon').eq(playlistController.data.selectedIndex).hide();
                    $('.albumwrapper li i.ioc').eq(playlistController.data.selectedIndex).removeClass('ioc-volume-off').addClass('ioc-volume-up').show();
                }
            },
            whileloading: function () {
                if (!this.isHTML5) {
                    dom.duration.innerHTML = getTime(this.durationEstimate, true);
                }
            },
            onload: function (ok) {
                if (ok) {
                    dom.duration.innerHTML = getTime(this.duration, true);
                } else if (this._iO && this._iO.onerror) {
                    this._iO.onerror();
                }
            },
            onerror: function () {
                var item, element, html;
                item = playlistController.getItem();
                if (item) {
                    if (extras.loadFailedCharacter) {
                        dom.playlistTarget.innerHTML = dom.playlistTarget.innerHTML.replace('<li>', '<li>' + extras.loadFailedCharacter + ' ');
                        if (playlistController.data.playlist && playlistController.data.playlist[playlistController.data.selectedIndex]) {
                            element = playlistController.data.playlist[playlistController.data.selectedIndex].getElementsByTagName('a')[0];
                            html = element.innerHTML;
                            if (html.indexOf(extras.loadFailedCharacter) === -1) {
                                element.innerHTML = extras.loadFailedCharacter + ' ' + html;
                            }
                        }
                    }
                }
            if(navigator.userAgent.match(/mobile/i)){actions.next();}else{if(playlistController.data.timer){window.clearTimeout(playlistController.data.timer);}
                playlistController.data.timer=window.setTimeout(actions.next,2000);}},onstop:function(){utils.css.remove(dom.o,'playing');},onfinish:function(){var lastIndex,item;utils.css.remove(dom.o,'playing');dom.progress.style.left='0%';lastIndex=playlistController.data.selectedIndex;item=playlistController.getNext();if(item&&(playlistController.data.selectedIndex!==lastIndex||(playlistController.data.playlist.length===1&&playlistController.data.loopMode))){playlistController.select(item);setTitle(item);$this.soundmanager_callback('onfinish');this.play({url:playlistController.getURL()});}else{this.stop();}}});return sound;}
    function isRightClick(e){if(e&&((e.which&&e.which===2)||(e.which===undefined&&e.button!==1))){return true;}}
    function getActionData(target){if(!target){return false;}
        actionData.volume.x=utils.position.getOffX(target);actionData.volume.y=utils.position.getOffY(target);actionData.volume.width=target.offsetWidth;actionData.volume.height=target.offsetHeight;actionData.volume.backgroundSize=parseInt(utils.style.get(target,'background-size'),10);if(window.navigator.userAgent.match(/msie|trident/i)){actionData.volume.backgroundSize=(actionData.volume.backgroundSize/actionData.volume.width)*100;}}
    function handleMouseDown(e){var links,target;target=e.target||e.srcElement;if(isRightClick(e)){return true;}
        if(target.nodeName.toLowerCase()!=='a'){links=target.getElementsByTagName('a');if(links&&links.length){target=target.getElementsByTagName('a')[0];}}
        if(utils.css.has(target,'sm2-volume-control')){getActionData(target);utils.events.add(document,'mousemove',actions.adjustVolume);utils.events.add(document,'mouseup',actions.releaseVolume);return actions.adjustVolume(e);}}
    function playLink(link){if(soundManager.canPlayLink(link)){if(playlistController.data.timer){window.clearTimeout(playlistController.data.timer);playlistController.data.timer=null;}
        if(!soundObject){soundObject=makeSound(link.href);}
        soundObject.stop();playlistController.select(link.parentNode);setTitle(link.parentNode);dom.progress.style.left='0px';dom.progressBar.style.width='0px';soundObject.play({url:link.href,position:0});}}
    function handleClick(e){var evt,target,offset,targetNodeName,methodName,href,handled;evt=(e||window.event);target=evt.target||evt.srcElement;if(target&&target.nodeName){targetNodeName=target.nodeName.toLowerCase();if(targetNodeName!=='a'){if(target.parentNode){do{target=target.parentNode;targetNodeName=target.nodeName.toLowerCase();}while(targetNodeName!=='a'&&target.parentNode);if(!target){return false;}}}
        if(targetNodeName==='a'){href=target.href;if(soundManager.canPlayURL(href)){if(!utils.css.has(target,'sm2-exclude')){playLink(target);handled=true;}}else{offset=target.href.lastIndexOf('#');if(offset!==-1){methodName=target.href.substr(offset+1);if(methodName&&actions[methodName]){handled=true;actions[methodName](e);}}}
            if(handled){return utils.events.preventDefault(evt);}}}}
    function handleMouse(e){var target,barX,barWidth,x,newPosition,sound;target=dom.progressTrack;barX=utils.position.getOffX(target);barWidth=target.offsetWidth;x=(e.clientX-barX);newPosition=(x/barWidth);sound=soundObject;if(sound&&sound.duration){sound.setPosition(sound.duration*newPosition);if(sound._iO&&sound._iO.whileplaying){sound._iO.whileplaying.apply(sound);}}
        if(e.preventDefault){e.preventDefault();}
        return false;}
    function releaseMouse(e){utils.events.remove(document,'mousemove',handleMouse);utils.css.remove(dom.o,'grabbing');utils.events.remove(document,'mouseup',releaseMouse);utils.events.preventDefault(e);return false;}
    function init(){if(!playerNode){console.warn('init(): No playerNode element?');}
        dom.o=playerNode;if(window.navigator.userAgent.match(/msie [678]/i)){utils.css.add(dom.o,css.legacy);}
        if(window.navigator.userAgent.match(/mobile/i)){utils.css.add(dom.o,css.noVolume);}
        dom.progress=utils.dom.get(dom.o,'.sm2-progress-ball');dom.progressTrack=utils.dom.get(dom.o,'.sm2-progress-track');dom.progressBar=utils.dom.get(dom.o,'.sm2-progress-bar');dom.volume=utils.dom.get(dom.o,'a.sm2-volume-control');if(dom.volume){getActionData(dom.volume);}
        dom.duration=utils.dom.get(dom.o,'.sm2-inline-duration');dom.time=utils.dom.get(dom.o,'.sm2-inline-time');playlistController=new PlaylistController();defaultItem=playlistController.getItem(0);if(defaultItem!==undefined){playlistController.select(defaultItem);setTitle(defaultItem);}
        utils.events.add(dom.o,'mousedown',handleMouseDown);utils.events.add(dom.o,'click',handleClick);utils.events.add(dom.progressTrack,'mousedown',function(e){if(isRightClick(e)){return true;}
            utils.css.add(dom.o,'grabbing');utils.events.add(document,'mousemove',handleMouse);utils.events.add(document,'mouseup',releaseMouse);return handleMouse(e);});}
    actionData={volume:{x:0,y:0,width:0,height:0,backgroundSize:0}};actions={getVolume:function(){return defaultVolume;},play:function(eventOrOffset){var target,href,e;if(eventOrOffset!==undefined&&!isNaN(eventOrOffset)){return playlistController.playItemByOffset(eventOrOffset);}
            e=eventOrOffset;if(e&&e.target){target=e.target||e.srcElement;href=target.href;}
            if(!href||href.indexOf('#')!==-1){href=dom.playlist.getElementsByTagName('a')[0].href;}
            if(!soundObject){soundObject=makeSound(href);}
            soundObject.togglePause();if(soundObject.paused&&playlistController.data.timer){window.clearTimeout(playlistController.data.timer);playlistController.data.timer=null;}},pause:function(){if(soundObject&&soundObject.readyState){soundObject.pause();}},resume:function(){if(soundObject&&soundObject.readyState){soundObject.resume();}},stop:function(){return actions.pause();},next:function(){var item,lastIndex;if($('#activePlaylist li').length>0){if(playlistController.data.timer){window.clearTimeout(playlistController.data.timer);playlistController.data.timer=null;}
            lastIndex=playlistController.data.selectedIndex;item=playlistController.getNext(true);if(item&&playlistController.data.selectedIndex!==lastIndex){playLink(item.getElementsByTagName('a')[0]);$this.soundmanager_callback('next');}}},prev:function(){var item,lastIndex;if($('#activePlaylist li').length>0){lastIndex=playlistController.data.selectedIndex;item=playlistController.getPrevious();if(item&&playlistController.data.selectedIndex!==lastIndex){playLink(item.getElementsByTagName('a')[0]);$this.soundmanager_callback('prev');}}},shuffle:function(e){var target=(e?e.target||e.srcElement:utils.dom.get(dom.o,'.shuffle'));if(target&&!utils.css.has(target,css.disabled)){utils.css.toggle(target.parentNode,css.active);playlistController.data.shuffleMode=!playlistController.data.shuffleMode;}},repeat:function(e){var target=(e?e.target||e.srcElement:utils.dom.get(dom.o,'.repeat'));if(target&&!utils.css.has(target,css.disabled)){utils.css.toggle(target.parentNode,css.active);playlistController.data.loopMode=!playlistController.data.loopMode;}},menu:function(ignoreToggle){var isOpen;isOpen=utils.css.has(dom.o,css.playlistOpen);if(typeof ignoreToggle!=='boolean'||!ignoreToggle){if(!isOpen){dom.playlistContainer.style.height='0px';}
            isOpen=utils.css.toggle(dom.o,css.playlistOpen);}
            dom.playlistContainer.style.height=(isOpen?dom.playlistContainer.scrollHeight:0)+'px';},adjustVolume:function(e){var backgroundMargin,pixelMargin,target,value,volume;value=0;target=dom.volume;if(e===undefined){return false;}
            if(e.clientX===undefined){if(arguments[0]!==undefined&&window.console&&window.console.warn){console.warn('Bar UI: call setVolume('+arguments[0]+') instead of adjustVolume('+arguments[0]+').');}
                return actions.setVolume.apply(this,arguments);}
            backgroundMargin=(100-actionData.volume.backgroundSize)/2;value=Math.max(0,Math.min(1,(e.clientX-actionData.volume.x)/actionData.volume.width));target.style.clip='rect(0px, '+(actionData.volume.width*value)+'px, '+actionData.volume.height+'px, '+(actionData.volume.width*(backgroundMargin/100))+'px)';pixelMargin=((backgroundMargin/100)*actionData.volume.width);volume=Math.max(0,Math.min(1,((e.clientX-actionData.volume.x)-pixelMargin)/(actionData.volume.width-(pixelMargin*2))))*100;if(soundObject){soundObject.setVolume(volume);}
            defaultVolume=volume;$this.soundmanager_callback('setVolume');return utils.events.preventDefault(e);},releaseVolume:function(){utils.events.remove(document,'mousemove',actions.adjustVolume);utils.events.remove(document,'mouseup',actions.releaseVolume);},setVolume:function(volume){var backgroundSize,backgroundMargin,backgroundOffset,pixelMargin,target,from,to;if(volume===undefined||isNaN(volume)){return;}
            if(dom.volume){target=dom.volume;backgroundSize=actionData.volume.backgroundSize;backgroundMargin=(100-backgroundSize)/2;backgroundOffset=actionData.volume.width*(backgroundMargin/100);from=backgroundOffset;to=from+((actionData.volume.width-(backgroundOffset*2))*(volume/100));target.style.clip='rect(0px, '+to+'px, '+actionData.volume.height+'px, '+from+'px)';}
            if(soundObject){soundObject.setVolume(volume);}
            defaultVolume=volume;}};init();exports={actions:actions,dom:dom,playlistController:playlistController};return exports;};window.sm2BarPlayers=players;window.SM2BarPlayer=Player;}(window));