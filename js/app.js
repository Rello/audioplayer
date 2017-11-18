/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2017 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

var Audios = function () {
    this.AudioPlayer = null;
    this.AlbumContainer = $('#albums-container');
    this.PlaylistContainer = $('#playlist-container');
    this.ActivePlaylist = $('#activePlaylist');
    this.albums = [];
    this.imgSrc = false;
    this.imgMimeType = 'image/jpeg';
    this.percentage = 0;
    this.progresskey = '';
    this.category_selectors = [];
    this.ajax_call_status = null;
};

Audios.prototype.init = function () {
    $this = this;

    var searchresult = decodeURI(location.hash).substr(1);
    if(searchresult !== '') {
        var locHashTemp = searchresult.split('-');
    }

    myAudios.get_uservalue('category', function() {
        // Category View
        if ($this.category_selectors[0] && $this.category_selectors[0]!== 'Albums') {
            window.location.href='#';
            if(searchresult !== '') $this.category_selectors = locHashTemp;
            $("#category_selector").val($this.category_selectors[0]);
            myAudios.loadCategory();
            // Album View
        } else {
            // Searchresult != Album will still trigger the category view
            if(searchresult !== '' && locHashTemp[0] !== 'Album') {
                window.location.href='#';
                $this.category_selectors = locHashTemp;
                $("#category_selector").val($this.category_selectors[0]);
                myAudios.loadCategory();
                // Searchresult = Album will select the album
            } else {
                $this.loadAlbums();
            }
        }
    });

    this.initKeyListener();
    $('.toolTip').tooltip();

};

Audios.prototype.initKeyListener=function(){
    $(document).keyup( function(evt) {
        if(this.AudioPlayer!== null && $('#activePlaylist li').length > 0){

            if (evt.target) {
                var nodeName=evt.target.nodeName.toUpperCase();
                //don't activate shortcuts when the user is in an input, textarea or select element
                if (nodeName === "INPUT" || nodeName === "TEXTAREA" || nodeName == "SELECT"){
                    return;
                }
            }

            var currentVolume;
            var newVolume;
            if (evt.keyCode === 32) {//Space pause/play
                if($('.sm2-bar-ui').hasClass('playing')){
                    this.AudioPlayer.actions.stop();
                }else{
                    this.AudioPlayer.actions.play();
                }
            }else if (evt.keyCode === 39) {// right
                this.AudioPlayer.actions.next();
            }else if (evt.keyCode === 37) {//left
                this.AudioPlayer.actions.prev();
            }else if (evt.keyCode === 38) {//up sound up
                currentVolume = this.AudioPlayer.actions.getVolume();
                if(currentVolume > 0 && currentVolume <=100){
                    newVolume = currentVolume+10;
                    if(newVolume >= 100){
                        newVolume=100;
                    }
                    this.AudioPlayer.actions.setVolume(newVolume);
                }
            }else if (evt.keyCode === 40) {//up sound down
                //this.AudioPlayer.actions.setVolume(0);
                currentVolume = this.AudioPlayer.actions.getVolume();

                if(currentVolume > 0 && currentVolume <=100){
                    newVolume = currentVolume-10;
                    if(newVolume <= 0){
                        newVolume=10;
                    }
                    this.AudioPlayer.actions.setVolume(newVolume);
                }
            }
        }
    }.bind(this));
};

Audios.prototype.AlbumSongs = function(){
    var $this = this;

    $('.albumSelect li').each(function(i,el){

        $(el).draggable({
            appendTo : "body",
            helper : $this.DragElement,
            cursor : "move",
            delay : 500,
            start : function(event, ui) {
                ui.helper.addClass('draggingSong');
            },
            stop:function(){
                //OC.Snapper.close();
            }
        });

        $(el).find('.title').on('click',function(){
            var myWrapper=$(this).parent().closest('.albumwrapper');

            if(!myWrapper.hasClass('isPlaylist')){
                if($this.PlaylistContainer.hasClass('isPlaylist')){
                    $this.PlaylistContainer.removeClass('isPlaylist');
                    $this.PlaylistContainer.html();
                }
                if(	$('.sm2-bar-ui').hasClass('playing')){
                    $this.AudioPlayer.actions.stop();
                }
                $('.albumwrapper').removeClass('isPlaylist');
                $('.albumSelect li').removeClass('isActive');
                $('.albumSelect li i.ioc').hide();

                myWrapper.addClass('isPlaylist');
                if($this.AudioPlayer === null){
                    $this.AudioPlayer = new SM2BarPlayer($('.sm2-bar-ui')[0]);
                }

                var ClonePlaylist = myWrapper.find('li').clone();
                $this.ActivePlaylist.html('');
                $this.ActivePlaylist.append(ClonePlaylist);
                $('#activePlaylist span.actionsSong').remove();
                $('#activePlaylist span.number').remove();
                $('#activePlaylist span.time').remove();
                $('#activePlaylist span.edit').remove();
                $('#activePlaylist li.noPlaylist').remove();

                var myCover=$('.album.is-active .albumcover');
                if(myCover.css('background-image') === 'none'){
                    $('.sm2-playlist-cover').text(myCover.text()).css({'background-color':myCover.css('background-color'),'color':myCover.css('color'),'background-image':'none'});
                }else{
                    $('.sm2-playlist-cover').text('').css({'background-image':myCover.css('background-image')});
                }
            }

            if($('.albumwrapper.isPlaylist li.isActive').length === 1 && !$(this).closest('li').hasClass('isActive')){
                $('.albumwrapper.isPlaylist li').removeClass('isActive');
                $('.albumwrapper.isPlaylist li i.ioc').hide();
            }
            if(!$(this).closest('li').hasClass('isActive')){
                $(this).closest('li').addClass('isActive');
                if ($this.AudioPlayer.playlistController.data.selectedIndex === null) $this.AudioPlayer.playlistController.data.selectedIndex = 0;
                $this.AudioPlayer.actions.play($(this).closest('li').index());
                $this.set_statistics();
            }else{
                if($('.sm2-bar-ui').hasClass('playing')){
                    $this.AudioPlayer.actions.stop();
                }else{
                    $this.AudioPlayer.actions.play();
                    $this.set_statistics();
                }
            }
            return false;
        });
    });
};

Audios.prototype.AlbumClickHandler = function(event){
    var AlbumId = '';
    var activeAlbumContainer;
    var scrollTop;

    if(event.albumId !== undefined){
        AlbumId='album-' + event.albumId;
    }else{
        event.preventDefault();
        AlbumId=$(this).attr('data-album');
    }

    var iArrowLeft =  72;
    var iTop = 80;
    var iScroll = 120;
    var iAnimateTime = 200;
    var iSlideDown = 200;
    var iSlideUp = 200;

    if($('.rowlist:first-child .album').length === 2){
        iTop = 50;
        iArrowLeft =  75;
    }

    var activeAlbum='.album[data-album="'+AlbumId+'"]';

    if($('.album.is-active').length === 0){
        scrollTop = $('#app-content').scrollTop();
        activeAlbumContainer = '.songcontainer[data-album="'+AlbumId+'"]';
        $(activeAlbumContainer+' .open-arrow').css('left',$(activeAlbum).position().left+iArrowLeft);
        $(activeAlbum).addClass('is-active');
        $(activeAlbum).find('.artist').hide();

        $(activeAlbumContainer).css({
            'top':scrollTop+$(activeAlbum).offset().top+iTop,
            'background-color':$(activeAlbum).data('bgcolor'),
            'color':$(activeAlbum).data('color'),
        });

        $(activeAlbumContainer+' li span').css({
            'color':$(activeAlbum).data('color')
        });

        $('#app-content').animate({
            'scrollTop': scrollTop + $(activeAlbum).offset().top - iScroll
        }, iAnimateTime, 'linear',function(){
            $(activeAlbum).parent('.rowlist').css('margin-bottom',$(activeAlbumContainer).height()+20);
            $(activeAlbumContainer).slideDown(iSlideDown);
        });

    }else{
        activeAlbumContainer = '.songcontainer[data-album="'+AlbumId+'"]';
        if(!$(activeAlbum).hasClass('is-active')){
            var indexOfRowIsOpen = $('.album.is-active').parent().index('.rowlist');
            var indexOfRow = $(activeAlbum).parent().index('.rowlist');

            $('.rowlist').css('margin-bottom',0);
            $('.songcontainer').hide();
            $('.album').removeClass('is-active');
            $('.album').find('.artist').show();

            scrollTop = $('#app-content').scrollTop();

            $(activeAlbumContainer+' .open-arrow').css('left',$(activeAlbum).position().left+iArrowLeft);
            $(activeAlbum).addClass('is-active');

            $(activeAlbum).find('.artist').hide();

            $(activeAlbumContainer).css({
                'top':scrollTop+$(activeAlbum).offset().top+iTop,
                'background-color':$(activeAlbum).data('bgcolor'),
                'color':$(activeAlbum).data('color'),
            });
            $(activeAlbumContainer+' li span').css({
                'color':$(activeAlbum).data('color')
            });
            if(indexOfRowIsOpen !== indexOfRow){

                $('#app-content').animate({
                    'scrollTop': scrollTop+$(activeAlbum).offset().top -iScroll
                }, iAnimateTime, 'linear',function(){
                    $(activeAlbum).parent('.rowlist').css('margin-bottom',$(activeAlbumContainer).height()+20);
                    $(activeAlbumContainer).slideDown(iSlideDown);

                });

            }else{
                $(activeAlbum).parent('.rowlist').css('margin-bottom',$(activeAlbumContainer).height()+20);
                $(activeAlbumContainer).show();
            }

        }else{
            $(activeAlbumContainer).slideUp(iSlideUp, function() {
                $('.album').removeClass('is-active');
                $('.album').find('.artist').show();
                //$('.rowlist').removeClass('margin-bottom');
                $(activeAlbum).parent('.rowlist').css('margin-bottom',0);
            });
        }

    }
};

Audios.prototype.buildAlbumRows = function(aAlbums){
    var divAlbum = [];
    var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');

    var counter=0;
    var maxNeben = 5;
    var marginLeft=20;
    var audioContainerWidth = this.AlbumContainer.width();
    $this = this;

    maxNeben = audioContainerWidth / 150;
    maxNeben = Math.floor(maxNeben) - 1;

    marginLeft = (audioContainerWidth) - (maxNeben * 150);
    marginLeft = marginLeft - 150;
    marginLeft = (marginLeft / maxNeben) / 2;
    marginLeft = Math.floor(marginLeft);

    if(marginLeft <= 8){
        maxNeben= maxNeben - 1;
        marginLeft = (audioContainerWidth) - (maxNeben * 150);
        marginLeft = marginLeft - 150;
        marginLeft = (marginLeft / maxNeben) / 2;
        marginLeft = Math.floor(marginLeft)+3;
    }
    if(maxNeben === 1){
        marginLeft = 15;
    }

    $.each(aAlbums,function(i,album){

        var addCss;
        var addDescr;
        if(album.cov === ''){
            addCss='background-color: #D3D3D3;color: #333333;';
            addDescr=album.nam.substring(0,1);
        }else{
            addDescr='';
            addCss='background-image:url('+getcoverUrl+album.id+');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
        }

        divAlbum[i] = $('<div/>').addClass('album').css('margin-left',marginLeft+'px')
            .attr({
                'data-album':'album-'+album.id,
                'data-bgcolor':'#D3D3D3',
                'data-color':'#333333'
            }).click($this.AlbumClickHandler);

        var divAlbumCover = $('<div/>').addClass('albumcover').attr({
            'data-album':'album-'+album.id,
            'style': addCss
        }).text(addDescr);
        divAlbum[i].append(divAlbumCover);


        var divAlbumDescr= $('<div/>').addClass('albumdescr').html('<span class="albumname">'+album.nam+'</span><span class="artist">'+album.art+'</span>');

        divAlbum[i].append(divAlbumDescr);

        if(counter === maxNeben || (i === (aAlbums.length-1))){

            var divRow=$('<div />').addClass('rowlist');
            divRow.append(divAlbum);
            $this.AlbumContainer.append(divRow);
            divAlbum = null;
            divAlbum = [];
            counter = -1;
        }

        counter++;
    });

};

Audios.prototype.loadAlbums = function(){
    $('.sm2-bar-ui').hide();
    $this.AlbumContainer.hide();
    $('#loading').show();
    $.ajax({
        type : 'GET',
        url : OC.generateUrl('apps/audioplayer/getmusic'),
        success : function(jsondata) {
            $('#loading').hide();
            if(jsondata.status === 'success' && jsondata.data !== 'nodata'){
                $this.albums = jsondata.data.albums;
                var songs = jsondata.data.songs;
                $this.AlbumContainer.show();
                $this.AlbumContainer.html('');
                $('.sm2-bar-ui').show();

                $this.buildAlbumRows($this.albums);

                var divSongContainer = [];
                var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');

                $.each($this.albums,function(i,album){
                    //Songs into hidden div
                    divSongContainer[i] = $('<div/>').addClass('songcontainer').attr({
                        'data-album':'album-'+album.id
                    });
                    var divArrow = $('<i/>').addClass('open-arrow');
                    divSongContainer[i] .append(divArrow);
                    var divSongContainerInner = $('<div/>').addClass('songcontainer-inner');
                    divSongContainer[i] .append(divSongContainerInner);

                    var addCss;
                    var addDescr;
                    if(album.cov === ''){
                        addCss='background-color: #D3D3D3;color: #333333;';
                        addDescr=album.nam.substring(0,1);
                    }else{
                        addDescr='';
                        addCss='background-image:url('+getcoverUrl+album.id+');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
                    }

                    var divSongContainerCover = $('<div/>').addClass('songcontainer-cover').attr({
                        'style':addCss
                    }).text(addDescr);
                    if($this.AlbumContainer.width() < 850){
                        divSongContainerCover.addClass('cover-small');
                    }
                    divSongContainerInner.append(divSongContainerCover);
                    var h2SongHeader=$('<h2/>').text(album.nam);
                    var spanPlay=$('<span />').addClass('ioc ioc-play').click(function(){

                        var myWrapper=$(this).parent().parent().find('.albumwrapper');

                        if(!myWrapper.hasClass('isPlaylist')){
                            if($this.PlaylistContainer.hasClass('isPlaylist')){
                                $this.PlaylistContainer.removeClass('isPlaylist');
                                $this.PlaylistContainer.html('');
                            }
                            if(	$('.sm2-bar-ui').hasClass('playing')){
                                $this.AudioPlayer.actions.stop();
                            }
                            $('.albumwrapper').removeClass('isPlaylist');
                            $('.albumSelect li').removeClass('isActive');
                            $('.albumSelect li i.ioc').hide();
                            myWrapper.addClass('isPlaylist');
                            var objCloneActivePlaylist= $(this).parent().parent().find('.albumSelect li').clone();
                            $this.ActivePlaylist.html('');
                            $this.ActivePlaylist.append(objCloneActivePlaylist);
                            $('#activePlaylist span').remove();
                            $('#activePlaylist li.noPlaylist').remove();
                            if($this.AudioPlayer === null){
                                $this.AudioPlayer = new SM2BarPlayer($('.sm2-bar-ui')[0]);
                                $(this).parent().parent().find('.albumSelect li:first-child').addClass('isActive');
                                $this.AudioPlayer.actions.play(0);
                            }else{
                                $(this).parent().parent().find('.albumSelect li:first-child').addClass('isActive');
                                $this.AudioPlayer.actions.play(0);
                            }
                            $this.set_statistics();
                            var myCover=$('.album.is-active .albumcover');
                            if(myCover.css('background-image') == 'none'){
                                $('.sm2-playlist-cover').text(myCover.text()).css({'background-color':myCover.css('background-color'),'color':myCover.css('color'),'background-image':'none'});
                            }else{
                                $('.sm2-playlist-cover').text('').css({'background-image':myCover.css('background-image')});
                            }
                        }
                    });
                    h2SongHeader.prepend(spanPlay);

                    divSongContainerInner.append(h2SongHeader);

                    divSongContainerInner.append('<br/>');
                    var divSongsContainer = $('<div/>').addClass('songlist albumwrapper');
                    if($this.AlbumContainer.width() < 850){
                        divSongsContainer.addClass('one-column');
                    }else{
                        divSongsContainer.addClass('two-column');
                    }
                    divSongContainerInner.append(divSongsContainer);
                    var listAlbumSelect=$('<ul/>').addClass('albumSelect').attr('data-album',album.nam);
                    divSongsContainer.append(listAlbumSelect);

                    var aSongs=[];
                    var li = $('<li/>');
                    var spanNr = $('<span/>').addClass('number').text('\u00A0');
                    li.append(spanNr);
                    if(songs[album.id]){
                        var songcounter = 0;
                        $.each(songs[album.id],function(ii,songs){
                            aSongs[ii] = $this.loadSongsRow(songs);
                            songcounter++;
                        });
                        if (songcounter % 2 !==0) {
                            li.addClass('noPlaylist');
                            aSongs.push(li); //add a blank row in case of uneven records=>avoid a Chrome bug to strangely split the records across columns
                        }
                    }else{
                        console.warn('Could not find songs for album:', album.nam, album);
                    }

                    listAlbumSelect.append(aSongs);
                    var br = $('<br />').css('clear','both');
                    divSongContainerInner .append(br);
                    var aClose = $('<a />').attr('href','#').addClass('close ioc ioc-close').click(function(evt){
                        var activeAlbum=$(this).parent('.songcontainer');
                        $(activeAlbum).slideUp(200, function() {
                            $('.album').removeClass('is-active');
                            $('.album').find('.artist').show();
                            $('.rowlist').css('margin-bottom',0);
                            return false;
                        });
                    });
                    divSongContainer[i].append(aClose);
                });
                $this.AlbumContainer.append(divSongContainer);
                $this.AlbumSongs();

                var searchresult = decodeURI(location.hash).substr(1);
                if(searchresult !== '') {
                    var locHashTemp = searchresult.split('-');
                    evt={};
                    evt.albumId = locHashTemp[1];
                    window.location.href='#';
                    myAudios.AlbumClickHandler(evt);
                }

            }else{
                $this.AlbumContainer.show();
                $this.AlbumContainer.html('<span class="no-songs-found">'+t('audioplayer','Welcome to')+' '+t('audioplayer','Audio Player')+'</span>');
                $this.AlbumContainer.append('<span class="no-songs-found-pl"><i class="ioc ioc-refresh" title="'+t('audioplayer','Scan for new audio files')+'" id="scanAudiosFirst"></i> '+t('audioplayer','Add new tracks to library')+'</span>');
                $this.AlbumContainer.append('<a class="no-songs-found-pl" href="https://github.com/rello/audioplayer/wiki" target="_blank">'+t('audioplayer','Help')+'</a>');
                $('#app-navigation').removeClass('ap_hidden');
            }
        }
    });
};

Audios.prototype.loadSongsRow = function(elem){

    var getAudiostreamUrl = OC.generateUrl('apps/audioplayer/getaudiostream');
    var can_play = soundManager.html5;

    var li = $('<li/>').attr({
        'data-trackid' : elem.id,
        'data-fileid' : elem.fid,
        'data-title' : elem.tit,
        'data-artist' : elem.art,
        'mimetype': elem.mim,
        'class' : 'dragable'
    });

    var spanAction = $('<span/>').addClass('actionsSong').html('<i class="ioc ioc-volume-off"></i>&nbsp;');
    var spanNr = $('<span/>').addClass('number').text(elem.num);
    var spanTime = $('<span/>').addClass('time').text(elem.len);
    var streamUrl = $('<a/>').addClass('link-full').attr({'href': getAudiostreamUrl + elem.lin});
    var spanEdit = $('<a/>').addClass('edit-song icon-rename').attr({'data-id':elem.id,'data-fileid':elem.fid,'title':t('audioplayer','Edit metadata')}).click(this.editSong.bind($this));

    if (can_play[elem.mim] === true) {
        spanTitle = $('<span/>').addClass('title').text(elem.tit);
    } else {
        spanTitle = $('<span/>').addClass('title').html('<i>'+elem.tit+'</i>');
    }

    li.append(spanAction);
    li.append(spanNr);
    streamUrl.append(spanTitle);
    li.append(streamUrl);
    li.append(spanTime);
    li.append(spanEdit);

    return li;
};

Audios.prototype.loadCategory = function(){
    var $this = this;
    var category = $this.category_selectors[0];
    $('#addPlaylist').addClass('ap_hidden');
    $('#myCategory').html('');
    $('.toolTip').tooltip('hide');
    $.ajax({
        type : 'GET',
        url : OC.generateUrl('apps/audioplayer/getcategory'),
        data : {category: category},
        success : function(jsondata) {
            if(jsondata.status === 'success'){
                $(jsondata.data).each(function(i,el){
                    var li = $('<li/>').attr({'data-id':el.id,'data-name':el.name});
                    var spanCounter = $('<span/>').attr('class','counter').text(el.counter);
                    var spanName;

                    if (category === 'Playlist' && el.id.toString()[0] !== 'X' && el.id !== '' && el.id.toString()[0] !== 'S'){
                        spanName = $('<span/>').attr({'class':'pl-name-play'}).text(el.name).click($this.loadIndividualCategory.bind($this));
                        var spanSort = $('<i/>').attr({'class':'ioc ioc-sort toolTip','data-sortid':el.id,'title':t('audioplayer','Sort playlist')}).click($this.sortPlaylist.bind($this));
                        var spanEdit = $('<i/>').attr({'class':'icon icon-rename toolTip','data-name':el.name,'data-editid':el.id,'title':t('audioplayer','Rename playlist')}).click($this.renamePlaylist.bind($this));
                        var spanDelete = $('<i/>').attr({'class':'ioc ioc-delete toolTip','data-deleteid':el.id,'title':t('audioplayer','Delete playlist')}).click($this.deletePlaylist.bind($this));
                        li.droppable({
                            activeClass : "activeHover",
                            hoverClass : "dropHover",
                            accept : 'li.dragable',
                            over : function() {},
                            drop : function(event, ui) {
                                $this.addSongToPlaylist($(this).attr('data-id'), ui.draggable.attr('data-trackid'));
                            }
                        });
                        li.append(spanName);
                        li.append(spanEdit);
                        li.append(spanSort);
                        li.append(spanDelete);
                        li.append(spanCounter);
                    } else if (el.id === ''){
                        spanName = $('<span/>').text(el.name).css({'float':'left', 'min-height': '10px'});
                        li.append(spanName);
                        li.append(spanCounter);
                    } else {
                        spanName = $('<span/>').attr({'class':'pl-name'}).text(el.name).click($this.loadIndividualCategory.bind($this));
                        li.append(spanName);
                        li.append(spanCounter);
                    }
                    $('#myCategory').append(li);
                });

                $('.toolTip').tooltip();
                if ($('#category_selector').val() === category && $this.category_selectors[1] && $this.category_selectors[1] != 'undefined') {
                    $('#myCategory li[data-id="'+$this.category_selectors[1]+'"]').addClass('active');
                    $("#app-navigation").scrollTop($("#app-navigation").scrollTop()+$('#myCategory li.active').first().position().top - 25);
                    $this.loadIndividualCategory();
                }
            }else{
                $('.sm2-bar-ui').hide();
                $this.PlaylistContainer.hide();
                $this.AlbumContainer.show();
                $this.AlbumContainer.html('<span class="no-songs-found">'+t('audioplayer','Welcome to')+' '+t('audioplayer','Audio Player')+'</span>');
                $this.AlbumContainer.append('<span class="no-songs-found-pl"><i class="ioc ioc-refresh" title="'+t('audioplayer','Scan for new audio files')+'" id="scanAudiosFirst"></i> '+t('audioplayer','Add new tracks to library')+'</span>');
                $this.AlbumContainer.append('<a class="no-songs-found-pl" href="https://github.com/rello/audioplayer/wiki" target="_blank">'+t('audioplayer','Help')+'</a>');
            }
        }
    });
    if (category === 'Playlist' ){
        $('#addPlaylist').removeClass('ap_hidden');
    }
};

Audios.prototype.loadIndividualCategory = function(evt) {
    var category = $('#category_selector').val();
    var getAudiostreamUrl = OC.generateUrl('apps/audioplayer/getaudiostream')+'?file=';

    if(typeof evt !== 'undefined'){
        $('#myCategory li').removeClass('active').removeClass('active');
        EventTarget = $(evt.target);
        EventTarget.parent('li').addClass('active').addClass('active');
    }

    var $this = this;
    var PlaylistId = $('#myCategory li.active').data('id');
    var category_title = $('#myCategory li.active').find('span').first().text();
    $('#alben').removeClass('active');

    $this.AlbumContainer.hide();
    $this.PlaylistContainer.hide();
    $this.PlaylistContainer.show();
    $this.PlaylistContainer.data('playlist',category+'-'+PlaylistId);
    $('#individual-playlist').html('');
    if ($('#individual-playlist').data('ui-sortable')) $('#individual-playlist').sortable("destroy");
    $('.header-title').data('order', '');
    $('.header-artist').data('order', '');
    $('.header-album').data('order', '');
    var can_play = soundManager.html5;
    var stream_array = ['audio/mpegurl', 'audio/x-scpls', 'application/xspf+xml'];
    for (var s=0; s<stream_array.length; s++) {
        can_play[stream_array[s]] = true;
    }

    if ($this.ajax_call_status !== null ) {
        $this.ajax_call_status.abort();
    }

    $this.ajax_call_status = $.ajax({
        type : 'GET',
        url : OC.generateUrl('apps/audioplayer/getcategoryitems'),
        data : {category: category, categoryId: PlaylistId},
        success : function(jsondata) {
            var albumcount = '';
            if(jsondata.status === 'success'){
                $('.sm2-bar-ui').show();
                $(jsondata.data).each(function(i,el){

                    var li = $('<li/>').attr({
                        'data-trackid' : el.id,
                        'data-fileid' : el.fid,
                        'mimetype':el.mim,
                        'data-title':el.cl1,
                        'data-artist':el.cl2,
                        'data-album':el.cl3,
                        'data-cover':el.cid,
                        'class' : 'dragable'
                    });
                    var fav_action;

                    if (el.fav === 't') {
                        fav_action = '<i class="fav icon-starred" style="opacity:0.3;"></i>';
                    } else {
                        fav_action = '<i class="fav icon-star"></i>';
                    }

                    var stream_type;
                    var streamUrl;
                    var spanAction;
                    var spanEdit;
                    var spanTitle;

                    if (el.mim === 'audio/mpegurl' || el.mim === 'audio/x-scpls' || el.mim === 'application/xspf+xml') {
                        stream_type = true;
                        streamUrl = $('<a/>').attr({'href': el.lin, 'type':el.mim});
                        spanAction = $('<span/>').addClass('actionsSong').html(fav_action+'<i class="ioc ioc-volume-off"></i>&nbsp;');
                    } else {
                        stream_type = false;
                        streamUrl = $('<a/>').attr({'href': getAudiostreamUrl + el.lin, 'type':el.mim});
                        spanAction = $('<span/>').addClass('actionsSong').html(fav_action+'<i class="ioc ioc-volume-off"></i>&nbsp;').click($this.favoriteUpdate.bind($this));
                    }
                    var spanInterpret = $('<span>').attr({'class':'interpret'});
                    var spanAlbum = $('<span>').attr({'class':'album-indi'});
                    var spanTime = $('<span/>').addClass('time').text(el.len);

                    if (can_play[el.mim] === true || stream_type === true) {
                        spanTitle = $('<span/>').addClass('title').text(el.cl1);
                        spanInterpret = spanInterpret.text(el.cl2);
                        spanAlbum = spanAlbum.text(el.cl3);
                        spanEdit = $('<a/>').addClass('edit-song icon-more').attr({'title':t('audioplayer','Options')}).click($this.fileActionsMenu.bind($this));
                    } else {
                        spanTitle = $('<span/>').addClass('title').html('<i>'+el.cl1+'</i>');
                        spanInterpret = spanInterpret.html('<i>'+el.cl2+'</i>');
                        spanAlbum = spanAlbum.html('<i>'+el.cl3+'</i>');
                        spanEdit = $('<a/>').addClass('edit-song ioc-close').attr({'title':t('audioplayer','MIME type not supported by browser')}).css({'opacity': 1,'text-align': 'center'}).click($this.fileActionsMenu.bind($this));
                    }

                    li.append(streamUrl);
                    li.append(spanAction);
                    li.append(spanTitle);
                    li.append(spanInterpret);
                    li.append(spanAlbum);
                    li.append(spanTime);
                    li.append(spanEdit);
                    li.find('span').css('color','#555');

                    if (can_play[el.mim] === true || stream_type === true) {
                        li.find('span.title').on('click',function(){
                            if ($('#individual-playlist').data('ui-sortable')) return false;

                            var albumPlaylistActive=$('#albums-container .albumwrapper.isPlaylist');
                            if (albumPlaylistActive.length > 0){
                                albumPlaylistActive.find('.albumSelect li').removeClass('isActive');
                                albumPlaylistActive.find('.albumSelect li i').hide();
                                $('#albums-container .albumwrapper').removeClass('isPlaylist');
                                $this.AlbumContainer.html('');
                            }

                            var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
                            var addCss;
                            var addDescr;
                            if (el.cid === ''){
                                addCss='background-color: #D3D3D3;color: #333333;';
                                addDescr=el.cl1.substring(0,1);
                            } else {
                                addDescr='';
                                addCss='background-image:url('+getcoverUrl+el.cid+');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
                            }
                            $('.sm2-playlist-cover').attr({'style':addCss}).text(addDescr);
                            $('.sm2-playlist-target').text('');

                            $this.PlaylistContainer.addClass('isPlaylist');
                            if ($this.AudioPlayer === null){
                                $this.AudioPlayer = new SM2BarPlayer($('.sm2-bar-ui')[0]);
                            }

                            var activeLi=$(this).closest('li');
                            if ($this.PlaylistContainer.find('.isPlaylist li.isActive').length === 1 && !activeLi.hasClass('isActive')) {
                                $('#individual-playlist li').removeClass('isActive');
                                $('#individual-playlist li i.ioc').hide();
                                $('#individual-playlist li i.fav').show();
                            }
                            if (!activeLi.hasClass('isActive')){
                                $('#individual-playlist li').removeClass('isActive');
                                $('#individual-playlist li i.ioc').hide();
                                $('#individual-playlist li i.fav').show();

                                if ($this.PlaylistContainer.data('playlist') !== $this.ActivePlaylist.data('playlist')) {
                                    var PlaylistId = $('#myCategory li.active').data('id');
                                    var category = $('#category_selector').val();
                                    myAudios.set_uservalue('category',category + '-' + PlaylistId);

                                    var ClonePlaylist = $this.PlaylistContainer.find('#individual-playlist li').clone();
                                    $this.ActivePlaylist.html('');
                                    $this.ActivePlaylist.append(ClonePlaylist);
                                    $this.ActivePlaylist.find('span').remove();
                                    $this.ActivePlaylist.data('playlist',category+'-'+PlaylistId);
                                }

                                if ($this.AudioPlayer.playlistController.data.selectedIndex === null) $this.AudioPlayer.playlistController.data.selectedIndex = 0;
                                $this.AudioPlayer.actions.play(activeLi.index());
                                $this.set_statistics();
                            } else {
                                if($('.sm2-bar-ui').hasClass('playing')){
                                    $this.AudioPlayer.actions.stop();
                                } else {
                                    $this.AudioPlayer.actions.play();
                                }
                            }
                        });
                    }
                    $('#individual-playlist').append(li);
                }); // end each loop

                if (category === 'Playlist' && PlaylistId.toString()[0] !== 'X' && PlaylistId !== ''){
                } else {
                    $('#individual-playlist li').each(function(i,el){
                        $(el).draggable({
                            appendTo : "body",
                            helper : $this.DragElement,
                            cursor : "move",
                            delay : 500,
                            start : function(event, ui) { ui.helper.addClass('draggingSong');},
                            stop:function(){}
                        });
                    });
                }

                $('#individual-playlist li i.ioc').hide();
                if($this.PlaylistContainer.hasClass('isPlaylist')){
                    var activeSongSel=$('#individual-playlist li[data-trackid="'+$('#activePlaylist li.selected').data('trackid')+'"] i.ioc');
                    $('#individual-playlist li[data-trackid="'+$('#activePlaylist li.selected').data('trackid')+'"]').addClass('isActive');
                    activeSongSel.removeClass('ioc-volume-off');
                    activeSongSel.addClass('ioc-volume-up');
                    $('#individual-playlist li[data-trackid="'+$('#activePlaylist li.selected').data('trackid')+'"] i.fav').hide();
                    activeSongSel.show();
                }else{
                    $('#individual-playlist li').removeClass('isActive');
                }

                $('.header-title').text(jsondata.header.col1);
                $('.header-artist').text(jsondata.header.col2);
                $('.header-album').text(jsondata.header.col3);
                $('.header-time').text(jsondata.header.col4);

                if (jsondata.albums >> 1) {
                    albumcount = ' (' + jsondata.albums + ' '+t('audioplayer','Albums')+')';
                }else{
                    albumcount = '';
                }

            } else if (PlaylistId.toString()[0] === 'X') {
                $('.sm2-bar-ui').hide();
                $this.PlaylistContainer.hide();
                $this.AlbumContainer.show();
                $this.AlbumContainer.html('<span class="no-songs-found">'+t('audioplayer','Welcome to')+' '+t('audioplayer','Audio Player')+'</span>');
            }else{
                $('.sm2-bar-ui').hide();
                $this.PlaylistContainer.hide();
                $this.AlbumContainer.show();
                $this.AlbumContainer.html('<span class="no-songs-found">'+t('audioplayer','Add new tracks to playlist by drag and drop')+'</span>');
            }

            if (category !== "Title") {
                $('#individual-playlist-info').html(t('audioplayer','Selected '+category)+': '+category_title + albumcount);
            } else {
                $('#individual-playlist-info').html(t('audioplayer','Selected')+': '+category_title + albumcount);
            }
        }
    });
};

Audios.prototype.DragElement = function(evt) {
    return $(this).clone().text($(this).find('.title').attr('data-title'));
};

Audios.prototype.favoriteUpdate = function(evt) {
    var $target = $(evt.target).closest('li');
    var fileId = $target.attr('data-fileid');
    var isFavorite = false;

    if ($(evt.target).hasClass('fav icon-starred')){
        isFavorite = true;
        $(evt.target).removeClass('fav icon-starred');
        $(evt.target).addClass('fav icon-star').removeAttr("style");
    } else {
        isFavorite = false;
        $(evt.target).removeClass('fav icon-star');
        $(evt.target).addClass('fav icon-starred').css('opacity',1);
    }

    $.ajax({
        type : 'GET',
        url : OC.generateUrl('apps/audioplayer/setfavorite'),
        data : {'fileId': fileId,
            'isFavorite': isFavorite},
        success : function(ajax_data) {
        }
    });
};

Audios.prototype.fileActionsMenu = function(evt){

    var trackid = $(evt.target).closest('li').attr('data-trackid');
    var fileId = $(evt.target).closest('li').attr('data-fileid');
    var mimetype = $(evt.target).closest('li').attr('mimetype');
    if ($(".fileActionsMenu").attr('data-trackid') === trackid) {
        $(".fileActionsMenu").remove();
    } else {
        $(".fileActionsMenu").remove();

        var category = $('#category_selector').val();
        var PlaylistId = $('#myCategory li.active').data('id');
        var EventTarget = $('#myCategory li.active span');

        var html = '<div class="fileActionsMenu popovermenu bubble open menu" data-trackid="'+trackid+'"><ul>'+
            '<li><a href="#" class="menuitem"><span class="icon icon-details"></span><span>MIME: '+mimetype+'</span></a></li>';
        if (PlaylistId.toString()[0] !== 'S'){
            html = html +'<li><a href="#" class="menuitem" data-action="edit" data-trackid="'+trackid+'" data-fileid="'+fileId+'"><span class="icon icon-rename"></span><span>'+t('audioplayer','Edit metadata')+'</span></a></li>';
        }

        if (category === 'Playlist' && PlaylistId.toString()[0] !== 'X' && PlaylistId.toString()[0] !== 'S' && PlaylistId !== ''){
            html = html +'<li><a href="#" class="menuitem action action-delete permanent" data-action="delete" data-id="'+trackid+'"><span class="icon icon-delete"></span><span>'+t('audioplayer','Remove')+'</span></a></li>';
        }
        html = html +'</ul></div>';

        $(evt.target).closest('li').append(html);
        OC.showMenu(null, $(".fileActionsMenu"));
        $("a[data-action='edit']").click($this.fileActionsEvent.bind($this));
        $("a[data-action='delete']").click($this.fileActionsEvent.bind($this));
        $(".fileActionsMenu").on('afterHide', function() {
            $(".fileActionsMenu").remove();
        });

    }
};

Audios.prototype.fileActionsEvent = function(evt){
    $(".fileActionsMenu").remove();
    var $target = $(evt.target).closest('a');
    var actionName = $target.attr('data-action');

    if (actionName === "edit") myAudios.editSong($target);
    if (actionName === "delete") myAudios.removeSongFromPlaylist($target);
};

Audios.prototype.removeSongFromPlaylist=function(evt){

    var songId;
    if(typeof evt.target === 'string'){
        songId = evt.target;
    }else{
        songId = $(evt).attr('data-id');
    }
    var plId = $('#myCategory li.active').attr('data-id');

    return $.getJSON(OC.generateUrl('apps/audioplayer/removetrackfromplaylist'), {
        playlistid : plId,
        songid: songId
    }).then(function(data) {
        $('#myCategory li.active').find('.counter').text($('#myCategory li.active').find('.counter').text()-1);
        $('#individual-playlist li[data-trackid="'+songId+'"]').remove();
        $('#activePlaylist li[data-trackid="'+songId+'"]').remove();
    }.bind(this));
};

Audios.prototype.addSongToPlaylist = function(plId,songId) {
    var sort = parseInt($('#myPlayList li[data-id="'+plId+'"]').find('.counter').text());
    return $.getJSON(OC.generateUrl('apps/audioplayer/addtracktoplaylist'), {
        playlistid : plId,
        songid: songId,
        sorting : (sort + 1)
    }).then(function(data) {
        $('.toolTip').tooltip('hide');
        $this.category_selectors[0] = 'Playlist';
        myAudios.loadCategory();
    }.bind(this));
};

Audios.prototype.newPlaylist = function(plName){
    $this=this;
    $.ajax({
        type : 'GET',
        url : OC.generateUrl('apps/audioplayer/addplaylist'),
        data : {'playlist':plName},
        success : function(jsondata) {
            if(jsondata.status === 'success'){
                myAudios.loadCategory();
            }
            if(jsondata.status === 'error'){
                $('#notification').text(t('audioplayer','No playlist selected!'));
                $('#notification').slideDown();
                window.setTimeout(function(){$('#notification').slideUp();}, 3000);
            }
        }
    });
};

Audios.prototype.renamePlaylist = function(evt){
    var eventTarget=$(evt.target);
    if($('.plclone').length === 1){
        var plId = eventTarget.data('editid');
        var plistName = eventTarget.data('name');
        var myClone = $('#pl-clone').clone();
        var $this = this;

        $('#myCategory li[data-id="'+plId+'"]').after(myClone);
        myClone.attr('data-pl',plId).show();
        $('#myCategory li[data-id="'+plId+'"]').hide();

        myClone.find('input[name="playlist"]')
            .bind('keydown', function(event){
                if (event.which == 13){
                    if(myClone.find('input[name="playlist"]').val()!==''){
                        var saveForm = $('.plclone[data-pl="'+plId+'"]');
                        var plname = saveForm.find('input[name="playlist"]').val();

                        $.getJSON(OC.generateUrl('apps/audioplayer/updateplaylist'), {
                            plId:plId,
                            newname:plname
                        }, function(jsondata) {
                            if(jsondata.status == 'success'){
                                myAudios.loadCategory();
                                myClone.remove();
                            }
                            if(jsondata.status == 'error'){
                                alert('could not update playlist');
                            }

                        });

                    }else{
                        myClone.remove();
                        $('#myCategory li[data-id="'+plId+'"]').show();
                    }
                }
            })
            .val(plistName).focus();


        myClone.on('keyup',function(evt){
            if (evt.keyCode===27){
                myClone.remove();
                $('#myCategory li[data-id="'+plId+'"]').show();
            }
        });
        myClone.find('button.icon-checkmark').on('click',function(){
            var saveForm = $('.plclone[data-pl="'+plId+'"]');
            var plname = saveForm.find('input[name="playlist"]').val();
            if(myClone.find('input[name="playlist"]').val()!==''){
                $.getJSON(OC.generateUrl('apps/audioplayer/updateplaylist'), {
                    plId:plId,
                    newname:plname
                }, function(jsondata) {
                    if(jsondata.status == 'success'){
                        myAudios.loadCategory();
                        myClone.remove();
                    }
                    if(jsondata.status == 'error'){
                        alert('could not update playlist');
                    }

                });
            }

        });
        myClone.find('button.icon-close').on('click',function(){
            myAudios.loadCategory();
            myClone.remove();
        });
    }
};

Audios.prototype.sortPlaylist = function(evt){
    var eventTarget=$(evt.target);
    if($('#myCategory li').hasClass('active')){
        var plId = eventTarget.attr('data-sortid');
        if(eventTarget.hasClass('sortActive')){

            var idsInOrder = $("#individual-playlist").sortable('toArray', {attribute: 'data-trackid'});
            $.getJSON(OC.generateUrl('apps/audioplayer/sortplaylist'), {
                playlistid : plId,
                songids: idsInOrder.join(';')
            },function(jsondata){
                if(jsondata.status === 'success'){
                    eventTarget.removeClass('sortActive');
                    $('#individual-playlist').sortable("destroy");
                    $('#notification').text(jsondata.msg);
                    $('#notification').slideDown();
                    window.setTimeout(function(){$('#notification').slideUp();}, 3000);
                }
            }.bind(this));

        }else{

            $('#notification').text(t('audioplayer','Sort modus active'));
            $('#notification').slideDown();
            window.setTimeout(function(){$('#notification').slideUp();}, 3000);

            $("#individual-playlist").sortable({
                items: "li",
                axis: "y",
                placeholder: "ui-state-highlight",
                stop: function( event, ui ) {}
            });

            eventTarget.addClass('sortActive');
            if(	$('.sm2-bar-ui').hasClass('playing')){
                this.AudioPlayer.actions.pause();
                $('#individual-playlist li').removeClass('isActive');
                $('#individual-playlist li i.ioc').hide();
            }else{
                $('#individual-playlist li').removeClass('isActive');
                $('#individual-playlist li i.ioc').hide();
            }

        }
    }
};
Audios.prototype.deletePlaylist = function(evt){
    $this=this;
    var plId = $(evt.target).attr('data-deleteid');
    $("#dialogSmall").text(t('audioplayer', 'Are you sure?'));
    $("#dialogSmall").dialog({
        resizable : false,
        title : t('audioplayer', 'Delete playlist'),
        width : 210,
        modal : true,
        buttons : [{
            text : t('audioplayer', 'No'),
            click : function() {
                $("#dialogSmall").html('');
                $(this).dialog("close");
            }
        }, {
            text : t('audioplayer', 'Yes'),
            click : function() {
                var oDialog = $(this);
                $.ajax({
                    type : 'GET',
                    url : OC.generateUrl('apps/audioplayer/removeplaylist'),
                    data : {'playlistid':plId},
                    success : function(jsondata) {
                        if(jsondata.status === 'success'){
                            myAudios.loadCategory();
                            $('#notification').text(t('audioplayer','Playlist successfully deleted!'));
                            $('#notification').slideDown();
                            window.setTimeout(function(){$('#notification').slideUp();}, 3000);
                        }
                    }
                });
                $("#dialogSmall").html('');
                oDialog.dialog("close");
            }
        }],
    });
    return false;

};

Audios.prototype.get_uservalue = function(user_type, callback) {
    $.ajax({
        type : 'GET',
        url : OC.generateUrl('apps/audioplayer/getvalue'),
        data : {'type':user_type},
        success : function(jsondata) {
            if(jsondata.status === 'success' && user_type === 'category') {
                $this.category_selectors = jsondata.value.split('-');
                callback($this.category_selectors);
            }else if(jsondata.status === 'success' && user_type === 'navigation' && jsondata.value === 'true') {
                $('#app-navigation-toggle_alternative').trigger( "click" );
            }else if(jsondata.status === 'false' && user_type === 'navigation') {
                $this.category_selectors[0] = 'Album';
                callback($this.category_selectors);
            }
        }
    });
};

Audios.prototype.set_uservalue = function(user_type,user_value) {
    if(user_type) {
        if(user_type === 'category') $this.category_selectors = user_value.split('-');
        $.ajax({
            type : 'GET',
            url : OC.generateUrl('apps/audioplayer/setvalue'),
            data : {'type': user_type,
                'value': user_value},
            success : function(ajax_data) {
            }
        });
    }
};

Audios.prototype.get_cover = function(user_type, callback) {
    $.ajax({
        type : 'GET',
        url : OC.generateUrl('apps/audioplayer/getcover'),
        data : {'album':'280'},
        success : function(jsondata) {
            //alert(jsondata);
        }
    });
};

Audios.prototype.set_statistics = function() {
    var track_id = $('#activePlaylist li.selected').data('trackid');
    if (track_id) {
        $.ajax({
            type : 'GET',
            url : OC.generateUrl('apps/audioplayer/setstatistics'),
            data : {'track_id': track_id},
            success : function(ajax_data) {
            }
        });
    }
};

Audios.prototype.sort_playlist = function(evt) {
    var column = $(evt.target).attr('class').split('-')[1];
    var order = $(evt.target).data('order');
    var factor = 1;
    var a;
    var b;

    if (order === 'descending') {
        factor = -1;
        $(evt.target).data('order', 'ascending');
    } else {
        $(evt.target).data('order', 'descending');
    }

    var elems = $('#individual-playlist').children('li').get();
    var reg_check = $(elems).first().data(column).toString().match(/^\d{1,2}\-\d{1,2}$/);
    elems.sort(function(a,b){
        a = $(a).data(column).toString();
        b = $(b).data(column).toString();
        if (reg_check) {
            a = parseInt(a.split('-')[0])*100 + parseInt(a.split('-')[1]);
            b = parseInt(b.split('-')[0])*100 + parseInt(b.split('-')[1]);
        } else {
            a = a.toLowerCase();
            b = b.toLowerCase();
        }
        return ((a < b) ? -1*factor : ((a > b) ? 1*factor : 0));
    });
    $('#individual-playlist').append(elems);

    if ($this.PlaylistContainer.data('playlist') === $this.ActivePlaylist.data('playlist')) {
        elems = $this.ActivePlaylist.children('li').get();
        elems.sort(function(a,b){
            a = $(a).data(column).toString();
            b = $(b).data(column).toString();
            if (reg_check) {
                a = parseInt(a.split('-')[0])*100 + parseInt(a.split('-')[1]);
                b = parseInt(b.split('-')[0])*100 + parseInt(b.split('-')[1]);
            } else {
                a = a.toLowerCase();
                b = b.toLowerCase();
            }
            return ((a < b) ? -1*factor : ((a > b) ? 1*factor : 0));
        });
        $this.ActivePlaylist.append(elems);
    }

    if($this.AudioPlayer){
        $this.AudioPlayer.playlistController.data.selectedIndex = $('#activePlaylist li.selected').index();
    }
};

Audios.prototype.soundmanager_callback = function(SMaction) {
    if ($('#albums-container .albumwrapper.isPlaylist').length === 0 ) {
        var cover = $('#activePlaylist li.selected').data('cover');
        var album = $('#activePlaylist li.selected').data('album');
        var addCss;
        var addDescr;
        var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');

        if(cover === ''){
            addCss='background-color: #D3D3D3;color: #333333;';
            if ($this.category_selectors[0] && $this.category_selectors[0]!== 'Albums') {
                album = $('#activePlaylist li.selected').data('title');
            } else {
                album = $('#activePlaylist li.selected').data('album');
            }
            addDescr = album.substring(0,1);
        } else {
            addDescr = '';
            addCss='background-image:url('+getcoverUrl+cover+');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
        }

        $('.sm2-playlist-cover').attr({'style':addCss}).text(addDescr);
        $this.set_statistics();
    }
};

Audios.prototype.check_timer = function() {
    $.ajax({
        type : 'GET',
        url : OC.generateUrl('apps/audioplayer_timer/gettimer'),
        success : function(ajax_data) {
            ajax_data = parseInt(ajax_data);
            ajax_data2 = ajax_data + (1*3600*1000);
            timer_time = new Date(ajax_data);
            timer_time2 = new Date(ajax_data2);
            if (new Date() > ajax_data && new Date() < ajax_data2 && $('.sm2-bar-ui').hasClass('playing')) {
                $('#notification').text('Timer Done!');
                $('#notification').slideDown();
                window.setTimeout(function(){$('#notification').slideUp();}, 3000);
                $this.AudioPlayer.actions.stop();
            } else if (new Date() < ajax_data && $('.sm2-bar-ui').hasClass('playing')){
                $('#notification').text('Timer set: ' + timer_time.toLocaleString());
                $('#notification').slideDown();
                window.setTimeout(function(){$('#notification').slideUp();}, 3000);
            }
        }
    });
};

Audios.prototype.checkNewTracks = function() {
    $.ajax({
        type : 'POST',
        url : OC.generateUrl('apps/audioplayer/checknewtracks'),
        success : function(data) {
            if (data === 'true'){
                OC.Notification.showTemporary(t('audioplayer','New audio files available'));
            }
        }
    });
};

var resizeTimeout = null;
$(window).resize(_.debounce(function() {
    if (resizeTimeout) {
        clearTimeout(resizeTimeout);
    }
    resizeTimeout = setTimeout(function() {
        $('.sm2-bar-ui').width(myAudios.AlbumContainer.width());

            if(myAudios.AlbumContainer.width() < 850){
                $('.songcontainer .songlist').addClass('one-column');
                $('.songcontainer .songlist').removeClass('two-column');
                $('.songcontainer .songcontainer-cover').addClass('cover-small');
            }else{
                $('.songcontainer .songlist').removeClass('one-column');
                $('.songcontainer .songlist').addClass('two-column');
                $('.songcontainer .songcontainer-cover').removeClass('cover-small');
            }
            if(	$('#alben').hasClass('active')) {
                $('#albums-container .rowlist').remove();
                myAudios.buildAlbumRows(myAudios.albums);
            }
    }, 500);
}));

window.onhashchange = function() {
    var locHash = decodeURI(location.hash).substr(1);
    if(locHash !== ''){
        var locHashTemp = locHash.split('-');

        $('#searchresults').addClass('hidden');
        window.location.href='#';
        if (locHashTemp[0] === 'Album' && $this.category_selectors[0] === 'Albums') {
            evt={};
            evt.albumId = locHashTemp[1];
            myAudios.AlbumContainer.show();
            myAudios.PlaylistContainer.hide();
            myAudios.AlbumClickHandler(evt);
        } else if (locHashTemp[0] !== 'volume' && locHashTemp[0] !== 'repeat' && locHashTemp[0] !== 'shuffle' && locHashTemp[0] !== 'prev' && locHashTemp[0] !== 'play' && locHashTemp[0] !== 'next') {
            $this.category_selectors = locHashTemp;
            $("#category_selector").val(locHashTemp[0]);
            myAudios.loadCategory();
        }
    }
};

$(document).ready(function() {

    myAudios = new Audios();
    myAudios.init();
    myAudios.checkNewTracks();

    var notify = $('#audioplayer_notification').val();
    if( notify !== ''){
        OC.Notification.showHtml(
            notify,
            {
                type: 'error',
                isHTML: true
            }
        );
    }

    $('.sm2-bar-ui').width(myAudios.AlbumContainer.width());

    $('#addPlaylist').on('click',function(){
        $('#newPlaylistTxt').val('');
        $('#newPlaylist').removeClass('ap_hidden');
    });


    $('#newPlaylistBtn_cancel').on('click',function(){
        $('#newPlaylistTxt').val('');
        $('#newPlaylist').addClass('ap_hidden');
    });

    $('#newPlaylistBtn_ok').on('click', function(){
        if ($('#newPlaylistTxt').val() !== ''){
            myAudios.newPlaylist($('#newPlaylistTxt').val());
            $('#newPlaylistTxt').val('');
            $('#newPlaylistTxt').focus();
            $('#newPlaylist').addClass('ap_hidden');
        }
    });

    $('#newPlaylistTxt').bind('keydown', function(event){
        if (event.which == 13 && $('#newPlaylistTxt').val() !== ''){
            myAudios.newPlaylist($('#newPlaylistTxt').val());
            $('#newPlaylistTxt').val('');
            $('#newPlaylistTxt').focus();
            $('#newPlaylist').addClass('ap_hidden');
        }
    });


    $('#alben').addClass('active');
    $('#alben').on('click',function(){
        $('#myCategory li').removeClass('active');
        $('#newPlaylist').addClass('ap_hidden');
        myAudios.PlaylistContainer.hide();
        if(	$('.sm2-bar-ui').hasClass('playing')){
            //myAudios.AudioPlayer.actions.play(0);
            //myAudios.AudioPlayer.actions.stop();
        }
        if($this.AlbumContainer.children().first().hasClass('rowlist') === false) {
            $this.loadAlbums();
        } else {
            $(this).addClass('active');
            $('#albums-container .rowlist').remove();
            myAudios.AlbumContainer.show();
            myAudios.buildAlbumRows(myAudios.albums);
        }
        myAudios.set_uservalue('category','Albums');
    });


    $('#toggle_alternative').prepend('<div id="app-navigation-toggle_alternative" class="icon-menu" style="float: left; box-sizing: border-box;"></div>');

    $('#app-navigation-toggle_alternative').click(function(){
        $('#newPlaylist').addClass('ap_hidden');
        if(	$('#app-navigation').hasClass('ap_hidden')){
            $('#app-navigation').removeClass('ap_hidden');
            $('#albums-container .rowlist').remove();
            myAudios.buildAlbumRows(myAudios.albums);
            $('.sm2-bar-ui').width(myAudios.AlbumContainer.width());
            myAudios.set_uservalue('navigation','true');
        } else {
            $('#app-navigation').addClass('ap_hidden');
            $('#albums-container .rowlist').remove();
            myAudios.buildAlbumRows(myAudios.albums);
            $('.sm2-bar-ui').width(myAudios.AlbumContainer.width());
            myAudios.set_uservalue('navigation','false');
        }
    });

    $('#category_selector').change(function() {
        $('#newPlaylist').addClass('ap_hidden');
        $this.category_selectors[0] = $('#category_selector').val();
        $this.category_selectors[1] = '';
        $('#myCategory').html('');
        if ($this.category_selectors[0] !== '' ) {
            myAudios.loadCategory();
        }
    });

    $('.header-title').click($this.sort_playlist.bind($this)).css('cursor', 'pointer');
    $('.header-artist').click($this.sort_playlist.bind($this)).css('cursor', 'pointer');
    $('.header-album').click($this.sort_playlist.bind($this)).css('cursor', 'pointer');

    var timer = window.setTimeout(function() {$('.sm2-bar-ui').width(myAudios.AlbumContainer.width());}, 1000);
});