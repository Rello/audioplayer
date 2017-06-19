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

var Audios = function(){
	 this.AudioPlayer=null;
	 this.AlbumContainer=$('#audios-audioscontainer');
	 this.PlaylistContainer=$('#individual-playlist-container');
	 this.albums=[];
	 this.imgSrc = false;
	 this.imgMimeType = 'image/jpeg';
	 this.percentage = 0;
	 this.progresskey = '';
	 this.category_selectors = [];
};

Audios.prototype.init = function() {
	$this = this;
	
	var searchresult = decodeURI(location.hash).substr(1);
	if(searchresult !== '') {
		var locHashTemp = searchresult.split('-');
	}
		
	myAudios.get_uservalue('category', function(someElement) {
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
	myAudios.get_uservalue('navigation');

	this.initKeyListener();
	this.initPhotoDialog();
	$('.toolTip').tooltip();
	
};

Audios.prototype.initPhotoDialog = function(){
	/* Initialize the photo edit dialog */
	
	$('input#pinphoto_fileupload').fileupload({
		dataType : 'json',
		url : OC.generateUrl('apps/audioplayer/uploadphoto'),
		done : function(e, data) {
			
			this.imgSrc = data.result.imgdata;
			this.imgMimeType = data.result.mimetype;
			$('#imgsrc').val(this.imgSrc);
			$('#imgmimetype').val(this.imgMimeType);
			$('#tmpkey').val(data.result.tmp);
			this.editPhoto($('#photoId').val(), data.result.tmp);
		}.bind(this)
	});
}

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
				var currentVolume = this.AudioPlayer.actions.getVolume();
				if(currentVolume > 0 && currentVolume <=100){
					var newVolume=currentVolume+10;
					if(newVolume >= 100){
						newVolume=100;
					}
					this.AudioPlayer.actions.setVolume(newVolume);
				}
			}else if (evt.keyCode === 40) {//up sound down
				//this.AudioPlayer.actions.setVolume(0);
				var currentVolume = this.AudioPlayer.actions.getVolume();
				
				if(currentVolume > 0 && currentVolume <=100){
					var newVolume=currentVolume-10;
					if(newVolume <= 0){
						newVolume=10;
					}
					this.AudioPlayer.actions.setVolume(newVolume);
				}
			}
		}
	}.bind(this));
};

Audios.prototype.PlaylistSongs = function(){
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
				$('#myPlayList li').removeClass('activeIndiPlaylist');
				if(	$('.sm2-bar-ui').hasClass('playing')){
					$this.AudioPlayer.actions.stop();
				}
				/*TODO*/
				$('.albumwrapper').removeClass('isPlaylist');
				$('.albumSelect li').removeClass('isActive');
				$('.albumSelect li i.ioc').hide();
				myWrapper.addClass('isPlaylist');
				//Playlist laden
				var objLi = myWrapper.find('li').clone();
				
				$('#activePlaylist').html('');
				
				$('#activePlaylist').append(objLi);
				$('#activePlaylist span.actionsSong').remove();
				$('#activePlaylist span.number').remove();
				$('#activePlaylist span.time').remove();
				$('#activePlaylist span.edit').remove();
				
				 if($this.AudioPlayer === null){
				 	$this.AudioPlayer = new SM2BarPlayer($('.sm2-bar-ui')[0]);
				 }
				 var myCover=$('.album.is-active .albumcover');
				 if(myCover.css('background-image') == 'none'){
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
		 //	$(activeAlbum).parent('.rowlist').addClass('margin-bottom');
		 	var scrollTop = $('#app-content').scrollTop();
	 		var activeAlbumContainer='.songcontainer[data-album="'+AlbumId+'"]';
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
		        'scrollTop': scrollTop+$(activeAlbum).offset().top - iScroll
		    }, iAnimateTime, 'linear',function(){
		    	$(activeAlbum).parent('.rowlist').css('margin-bottom',$(activeAlbumContainer).height()+20);
		    	$(activeAlbumContainer).slideDown(iSlideDown);
		    });
	 	 
	 }else{
 	 	var activeAlbumContainer='.songcontainer[data-album="'+AlbumId+'"]';
 	 	if(!$(activeAlbum).hasClass('is-active')){
	 	 	 var indexOfRowIsOpen = $('.album.is-active').parent().index('.rowlist');
	 	 	 var indexOfRow = $(activeAlbum).parent().index('.rowlist');	
	 	 	  
	 	 	//$('.rowlist').removeClass('margin-bottom');
	 	 	$('.rowlist').css('margin-bottom',0);
 	 		$('.songcontainer').hide();
	 	 	$('.album').removeClass('is-active');
	 	 	$('.album').find('.artist').show();
	 	 //	$(activeAlbum).parent('.rowlist').addClass('margin-bottom');
	 	 	
	 	 	var scrollTop = $('#app-content').scrollTop();
	 	 	
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
				 	
				 	if(album.cov === ''){	
						var addCss='background-color: #D3D3D3;color: #333333;';
						var addDescr=album.nam.substring(0,1);	
					}else{
						var addDescr='';
						var addCss='background-image:url('+getcoverUrl+album.id+');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
					}
					
				 	 divAlbum[i] = $('<div/>').addClass('album').css('margin-left',marginLeft+'px')
				 	 .attr({
				 	 	'data-album':'album-'+album.id,
				 	 	'data-bgcolor':'#D3D3D3',
				 	 	'data-color':'#333333'
				 	 }).click($this.AlbumClickHandler);
				 					 	 
				 	var divAlbumCover = $('<div/>').addClass('albumcover').attr({
				 		'data-album':'album-'+album.id,
				 		'style':addCss
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
	 $this = this;
	 $('.sm2-bar-ui').hide();
	 this.AlbumContainer.hide();
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
			 		
			 		if(album.cov === ''){	
						var addCss='background-color: #D3D3D3;color: #333333;';
						var addDescr=album.nam.substring(0,1);	
					}else{
						var addDescr='';
						var addCss='background-image:url('+getcoverUrl+album.id+');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
					}
			 		
			 		var divSongContainerCover = $('<div/>').addClass('songcontainer-cover').attr({
				 		'style':addCss
				 	}).text(addDescr);
				 	if($this.AlbumContainer.width() < 850){
				 		divSongContainerCover.addClass('cover-small');
				 	}
			 		divSongContainerInner.append(divSongContainerCover);
			 		var h2SongHeader=$('<h2/>').text(album.nam);
			 		/*TODO make more*/
			 		var spanPlay=$('<span />').addClass('ioc ioc-play').click(function(){
			 			
			 			var myWrapper=$(this).parent().parent().find('.albumwrapper');
						
						if(!myWrapper.hasClass('isPlaylist')){
							if($this.PlaylistContainer.hasClass('isPlaylist')){
								$this.PlaylistContainer.removeClass('isPlaylist');
								$this.PlaylistContainer.html();
							}
							$('#myPlayList li').removeClass('activeIndiPlaylist');
							if(	$('.sm2-bar-ui').hasClass('playing')){
								$this.AudioPlayer.actions.stop();
							}
							$('.albumwrapper').removeClass('isPlaylist');
							$('.albumSelect li').removeClass('isActive');
							$('.albumSelect li i.ioc').hide();
				 			myWrapper.addClass('isPlaylist');
				 			var objCloneActivePlaylist= $(this).parent().parent().find('.albumSelect li').clone();
				 			$('#activePlaylist').html('');
							$('#activePlaylist').append(objCloneActivePlaylist);
							$('#activePlaylist span.actionsSong').remove();
							$('#activePlaylist span.number').remove();
							$('#activePlaylist span.time').remove();
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
						if (songcounter % 2 !==0) aSongs.push(li); //add a blank row in case of uneven records=>avoid a Chrome bug to strangely split the records across columns
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
				$this.PlaylistSongs();
				
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
	var spanTitle = $('<span/>').attr({'data-title':elem.tit,'title':elem.tit}).addClass('title');
	var spanTime = $('<span/>').addClass('time').text(elem.len);
	var link = $('<a/>').addClass('link-full').attr('href', getAudiostreamUrl + elem.lin);
	var spanEdit = $('<a/>').addClass('edit-song icon-rename').attr({'data-id':elem.id,'data-fileid':elem.fid,'title':t('audioplayer','Edit metadata')}).click(this.editSong.bind($this));

	if (can_play[elem.mim] === true) {
		var spanTitle = spanTitle.text(elem.tit);
	} else {
		var spanTitle = spanTitle.html('<i>'+elem.tit+'</i>');
	}

	li.append(spanAction);
	li.append(spanNr);
	link.append(spanTitle);
	li.append(link);
	li.append(spanTime);
	li.append(spanEdit);
				
	return li;
};

Audios.prototype.loadCategory = function(category){	

	var $this = this;
	var category = $this.category_selectors[0];
	var category_id = $this.category_selectors[1];
	var playlistsdata=[];
	$('.sm2-bar-ui').show();
	$('#addPlaylist').addClass('ap_hidden');
	$('#myCategory').html('');
	$('.toolTip').tooltip('hide');
	$.ajax({
				type : 'GET',
				url : OC.generateUrl('apps/audioplayer/getcategory'),
				data : {category: category},
				success : function(jsondata) {
					if(jsondata.status == 'success'){
						var playlistsdata=jsondata.data;
						if(playlistsdata !== 'nodata'){
							var aPlaylists = [];
							
							$(playlistsdata).each(function(i,el){
								var li = $('<li/>').attr({'data-id':el.id,'data-name':el.name});
								if (category === 'Playlist' && el.id.toString()[0] !== 'X' && el.id !== ''){
									li.droppable({
										activeClass : "activeHover",
										hoverClass : "dropHover",
										accept : 'li.dragable',
										over : function(event, ui) {
										},
										drop : function(event, ui) {
											$this.addSongToPlaylist($(this).attr('data-id'), ui.draggable.attr('data-trackid'));
										}
									});
								}
								
								
								var spanName=$('<span/>')
								.attr({'data-plid':el.id,'class':'pl-name-play'})
								.text(el.name)
								.click($this.loadIndividualCategory.bind($this));

								var span=$('<span/>').attr('class','counter').text(el.counter);
								var iSort=$('<i/>').attr({'class':'ioc ioc-sort toolTip','data-sortid':el.id,'title':t('audioplayer','Sort playlist')}).click($this.sortPlaylist.bind($this));
								var iEdit=$('<a/>').attr({'class':'icon icon-rename toolTip','data-name':el.name,'data-editid':el.id,'title':t('audioplayer','Rename playlist')}).click($this.renamePlaylist.bind($this));
								var iDelete=$('<i/>').attr({'class':'ioc ioc-delete toolTip','data-deleteid':el.id,'title':t('audioplayer','Delete playlist')}).click($this.deletePlaylist.bind($this));
			
								if (category === 'Playlist' && el.id.toString()[0] !== 'X' && el.id !== ''){
									var spanName=$('<span/>')
										.attr({'data-plid':el.id,'class':'pl-name-play'})
										.text(el.name)
										.click($this.loadIndividualCategory.bind($this));
									li.append(spanName);
									li.append(span);
									li.append(iEdit);
									li.append(iSort);
									li.append(iDelete);
								} else if (el.id === ''){
									var spanName=$('<span/>')
										.text(el.name)
										.css({'float':'left', 'min-height': '10px'});
									li.append(spanName);
									li.append(span);
								} else {
									var spanName=$('<span/>')
										.attr({'data-plid':el.id,'class':'pl-name'})
										.text(el.name)
										.click($this.loadIndividualCategory.bind($this));
									li.append(spanName);
									li.append(span);
								}
																
								aPlaylists[i]=li;
							});
							
							
							$('#myCategory').append(aPlaylists);
							$('.toolTip').tooltip();
							if ($('#category_selector').val() === $this.category_selectors[0] && $this.category_selectors[1] && $this.category_selectors[1]!='undefined') {
								$('#myCategory li[data-id="'+$this.category_selectors[1]+'"]').addClass('activeIndiPlaylist');
								$("#app-navigation").scrollTop($("#app-navigation").scrollTop()+$('#myCategory li.activeIndiPlaylist').first().position().top - 25);
								$this.loadIndividualCategory();
							}
						}
					}
				}
		});
		if (category === 'Playlist' ){
			$('#addPlaylist').removeClass('ap_hidden');
		}
};

Audios.prototype.loadIndividualCategory = function(evt) {
	var EventTarget=null;
	var bRreload = false;
	var category = $('#category_selector').val();
	var getAudiostreamUrl = OC.generateUrl('apps/audioplayer/getaudiostream')+'?file=';

	if(typeof evt === 'undefined'){
		EventTarget=$('#myCategory li.activeIndiPlaylist span');		
		bRreload = true;
		var PlaylistId = EventTarget.attr('data-plid');
	}else{
		EventTarget = $(evt.target);
		var PlaylistId = EventTarget.attr('data-plid');
		var cat_selector = category+'-'+PlaylistId;
		myAudios.set_uservalue('category',cat_selector);
	}
		
	var $this = this;
	
	if(!EventTarget.parent('li').hasClass('activeIndiPlaylist') || bRreload === true){
	
		$('#myPlayList li').removeClass('activeIndiPlaylist');
		$('#myCategory li').removeClass('activeIndiPlaylist');
	
		EventTarget.parent('li').addClass('activeIndiPlaylist');
		category_title = EventTarget.parent('li').find('span').first().text();
		$('#alben').removeClass('bAktiv');
	

		if (category !== "Title") {
			$('#individual-playlist-info').html(t('audioplayer','Selected '+category)+': '+category_title);
		} else {
			$('#individual-playlist-info').html(t('audioplayer','Selected')+': '+category_title);
		} 			
		
		$this.AlbumContainer.hide();
		$this.PlaylistContainer.hide();
		$this.PlaylistContainer.show();
		$('#individual-playlist').html('');
		if ($('#individual-playlist').data('ui-sortable')) $('#individual-playlist').sortable("destroy");
		$('#activePlaylist').html('');
		$('.header-title').data('order', '');
		$('.header-artist').data('order', '');
		$('.header-album').data('order', '');
		var can_play = soundManager.html5;
	
		$.ajax({
			type : 'GET',
			url : OC.generateUrl('apps/audioplayer/getcategoryitems'),
			data : {category: category, id: PlaylistId},
			success : function(jsondata) {
				if(jsondata.status == 'success'){
					var playlistsdata=jsondata.data;
					if(playlistsdata !== 'nodata'){
						var aPlaylistOutput=[];
						var aPlaylistOutput1=[];
						var democounter = 0;
						$(playlistsdata).each(function(i,el){			

							var li1 =$('<li/>').attr({
								'data-trackid':el.id,
								'data-title':el.cl1,
								'data-artist':el.cl2,
								'data-album':el.cl3,
								'data-cover':el.cid,
							});
							var a1 = $('<a/>').attr({'href': getAudiostreamUrl + el.lin});
							li1.append(a1);				
				
							var li = $('<li/>').attr({
								'data-trackid' : el.id,
								'data-fileid' : el.fid,
								'mimetype':el.mim,
								'data-title':el.cl1,
								'data-artist':el.cl2,
								'data-album':el.cl3,
								'class' : 'dragable'
							});

							if (el.fav === 't') {
								var fav_action = '<i class="fav icon-starred" style="opacity:0.3;"></i>';
							} else {
								var fav_action = '<i class="fav icon-star"></i>';
							}
		
							var spanAction = $('<span/>').addClass('actionsSong').html(fav_action+'<i class="ioc ioc-volume-off"></i>&nbsp;').click($this.favoriteUpdate.bind($this));
							var spanTitle = $('<span/>').attr({'data-title':el.cl1,'title':el.cl1}).addClass('title');
							var interpret = $('<span>').attr({'class':'interpret'});
							var album = $('<span>').attr({'class':'album-indi'}).text(el.cl3);
							var spanTime = $('<span/>').addClass('time').text(el.len);
							
							if (can_play[el.mim] === true) {
								var spanTitle = spanTitle.text(el.cl1);
								var interpret = interpret.text(el.cl2);
								var album = album.text(el.cl3);
								var spanEdit = $('<a/>').addClass('edit-song icon-more').attr({'title':t('audioplayer','Options')}).click($this.fileActionsMenu.bind($this));
							} else {
								var spanTitle = spanTitle.html('<i>'+el.cl1+'</i>');
								var interpret = interpret.html('<i>'+el.cl2+'</i>');
								var album = album.html('<i>'+el.cl3+'</i>');
								var spanEdit = $('<a/>').addClass('edit-song ioc-close').attr({'title':t('audioplayer','MIME type not supported by browser')}).css({'opacity': 1,'text-align': 'center'}).click($this.fileActionsMenu.bind($this));
							}
							
							li.append(spanAction);
							li.append(spanTitle);
							li.append(interpret);
							li.append(album);
							li.append(spanTime);
							li.append(spanEdit);
							li.find('span').css('color','#555');
							
							li.find('span.title').on('click',function(){
								if (!$('#individual-playlist').data('ui-sortable')) {
					
									var albumPlaylistActive=$('#audios-audioscontainer .albumwrapper.isPlaylist');
									var playlistActive=$('#myCategory li.activeIndiPlaylist');
									var indiPlaylistId =$this.PlaylistContainer.data('playlist');

									if(albumPlaylistActive.length > 0){
										albumPlaylistActive.find('.albumSelect li').removeClass('isActive');
										albumPlaylistActive.find('.albumSelect li i').hide();
										$('#audios-audioscontainer .albumwrapper').removeClass('isPlaylist');
									}
									$this.PlaylistContainer.data('playlist',playlistActive.data('id'));

			 						var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
			 						if(el.cid === ''){	
										var addCss='background-color: #D3D3D3;color: #333333;';
										var addDescr=el.cl1.substring(0,1);	
									}else{
										var addDescr='';
										var addCss='background-image:url('+getcoverUrl+el.cid+');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
									}

			 						$('.sm2-playlist-cover').attr({'style':addCss}).text(addDescr);
									$('.sm2-playlist-target').text('');
									$this.PlaylistContainer.addClass('isPlaylist');
									if($this.AudioPlayer == null){
										$this.AudioPlayer = new SM2BarPlayer($('.sm2-bar-ui')[0]);
									}

									var activeLi=$(this).closest('li');
					
									if($this.PlaylistContainer.find('.isPlaylist li.isActive').length === 1 && !activeLi.hasClass('isActive')){
										$('#individual-playlist li').removeClass('isActive');
										$('#individual-playlist li i.ioc').hide();
										$('#individual-playlist li i.fav').show();
									}
									if(!activeLi.hasClass('isActive')){
										$('#individual-playlist li').removeClass('isActive');
										$('#individual-playlist li i.ioc').hide();
										$('#individual-playlist li i.fav').show();
										activeLi.addClass('isActive');
										$this.AudioPlayer.actions.play(activeLi.index());
										$this.set_statistics();
									}else{
										if($('.sm2-bar-ui').hasClass('playing')){
											$this.AudioPlayer.actions.stop();
										}else{
											$this.AudioPlayer.actions.play();
											$this.set_statistics();
										}
									}
								}
								return false;
							});				
				
							aPlaylistOutput[i]=li;
							aPlaylistOutput1[i]=li1;
						});
			
						$('#activePlaylist').append(aPlaylistOutput1);
						$('#individual-playlist').append(aPlaylistOutput);
						
						if (category === 'Playlist' && PlaylistId.toString()[0] !== 'X' && PlaylistId !== ''){
						} else {
							$('#individual-playlist li').each(function(i,el){
								$(el).draggable({
									appendTo : "body",
									helper : $this.DragElement,
									cursor : "move",
									delay : 500,
									start : function(event, ui) {
										ui.helper.addClass('draggingSong');
									},
									stop:function(){
									}
								});
							});
						}
						$('#individual-playlist li i.ioc').hide();
						if($this.PlaylistContainer.hasClass('isPlaylist')){
							var activeSongSel=$('#individual-playlist li[data-trackid="'+$('#activePlaylist li.selected').data('trackid')+'"] i.ioc');
							$('#individual-playlist li[data-trackid="'+$('#activePlaylist li.selected').data('trackid')+'"]').addClass('isActive');
							activeSongSel.removeClass('ioc-volume-off');
							activeSongSel.addClass('ioc-volume-up');
							activeSongSel.show();
						}else{
							$('#individual-playlist li').removeClass('isActive');
						}
					}else{
				 		$('#individual-playlist').html('<span class="no-songs-found-pl">'+t('audioplayer','Add new tracks to playlist by drag and drop')+'</span>');
				 	}
					$('.header-title').text(jsondata.header.col1);
					$('.header-artist').text(jsondata.header.col2);
					$('.header-album').text(jsondata.header.col3);
			 	}
		 	}
		});

	}else{
		$this.AlbumContainer.hide();
		if($this.PlaylistContainer.hasClass('isPlaylist')){
			var activeSongSel=$('#individual-playlist li[data-trackid="'+$('#activePlaylist li.selected').data('trackid')+'"] i.ioc');
			$('#individual-playlist li[data-trackid="'+$('#activePlaylist li.selected').data('trackid')+'"]').addClass('isActive');
			activeSongSel.removeClass('ioc-volume-off');
			activeSongSel.addClass('ioc-volume-up');
			activeSongSel.show();
		}
		$this.PlaylistContainer.show();
	}
};

Audios.prototype.DragElement = function(evt) {
	return $(this).clone().text($(this).find('.title').attr('data-title'));
};

Audios.prototype.favoriteUpdate = function(evt) {
	var $target = $(evt.target).closest('li');
	var fileId = $target.attr('data-fileid');

	if ($(evt.target).hasClass('fav icon-starred')){
		var isFavorite = true;
		$(evt.target).removeClass('fav icon-starred');
		$(evt.target).addClass('fav icon-star').removeAttr("style");
	} else {
		var isFavorite = false;
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
		$(".fileActionsMenu").remove()
	} else {
		$(".fileActionsMenu").remove()
		
		var category = $('#category_selector').val();
		var EventTarget = $('#myCategory li.activeIndiPlaylist span');		
		var PlaylistId = EventTarget.attr('data-plid');
		
		
		var html = '<div class="fileActionsMenu popovermenu bubble open menu" data-trackid="'+trackid+'"><ul>'
				+'<li><a href="#" class="menuitem"><span class="icon icon-details"></span><span>MIME: '+mimetype+'</span></a></li>'
				+'<li><a href="#" class="menuitem" data-action="edit" data-trackid="'+trackid+'" data-fileid="'+fileId+'"><span class="icon icon-rename"></span><span>'+t('audioplayer','Edit metadata')+'</span></a></li>'

		if (category === 'Playlist' && PlaylistId.toString()[0] !== 'X' && PlaylistId !== ''){
			html = html +'<li><a href="#" class="menuitem action action-delete permanent" data-action="delete" data-id="'+trackid+'"><span class="icon icon-delete"></span><span>'+t('audioplayer','Remove')+'</span></a></li>';
		}
		html = html +'</ul></div>'

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
	$(".fileActionsMenu").remove()
	var $target = $(evt.target).closest('a');
	var actionName = $target.attr('data-action');
	
	if (actionName === "edit") myAudios.editSong($target);
	if (actionName === "delete") myAudios.removeSongFromPlaylist($target);
};

Audios.prototype.editSong = function(evt){
	if($(evt.target).attr('data-fileid')){
		var songId = $(evt.target).attr('data-trackid');
		var fileId = $(evt.target).attr('data-fileid');
	}else{
		var songId = $(evt).attr('data-id');
		var fileId = $(evt).attr('data-fileid');
	}
	$this = this;
	$.getJSON(OC.generateUrl('apps/audioplayer/editaudiofile'), {
		songFileId: fileId
	},function(jsondata){
		if(jsondata.status === 'success'){
			
			var posterImg='<div id="noimage">'+t('audioplayer', 'Drag picture here!')+'</div>';
		
			if(jsondata.data.isPhoto === '1'){
					
					$this.imgSrc = jsondata.data.poster;
					$this.imgMimeType = jsondata.data.mimeType;
					posterImg = '';
					$this.loadPhoto();
			}
			
			var posterAction='<span class="labelPhoto" id="pinPhoto">'+posterImg
  							+'<div class="tip" id="pin_details_photo_wrapper" title="'+t('audioplayer','Drop picture')+'" data-element="PHOTO">'
							+'<ul id="phototools" class="transparent hidden">'
							+'<li><a class="delete" title="'+t('audioplayer','Delete')+'"><img style="height:26px;" class="svg" src="'+OC.imagePath('core', 'actions/delete.svg')+'"></a></li>'
							+'<li><a class="edit" title="'+t('audioplayer','Edit')+'"><img style="height:26px;" class="svg" src="'+OC.imagePath('core', 'actions/rename.svg')+'"></a></li>'
							+'<li><a class="svg upload" title="'+t('audioplayer','Upload')+'"><img style="height:26px;" class="svg" src="'+OC.imagePath('core', 'actions/upload.svg')+'"></a></li>'
							+'<li><a class="svg cloud" title="'+t('audioplayer','Select from cloud')+'"><img style="height:26px;" class="svg" src="'+OC.imagePath('core', 'actions/public.svg')+'"></a></li>'
							+'</ul></div>'
							+'<iframe name="file_upload_target" id="file_upload_target" src=""></iframe>'
						 	+'</span>';
						 
			html = $('<div/>').html(
				'<input type="hidden" name="isphoto" id="isphoto" value="'+jsondata.data.isPhoto+'" />'
				+'<input type="hidden" name="id" id="photoId" value="'+fileId+'" />'
			   +'<input type="hidden" name="tmpkey" id="tmpkey" value="'+jsondata.data.tmpkey+'" />'
			   +'<textarea id="imgsrc" name="imgsrc" style="display:none;">'+jsondata.data.poster+'</textarea>'
			   +'<input type="hidden" name="imgmimetype" id="imgmimetype" value="'+jsondata.data.mimeType+'" />'	
				+'<div class="edit-left"><label class="editDescr">'+t('audioplayer','Title')+'</label> <input type="text" placeholder="'+t('audioplayer','Title')+'" id="sTitle" style="width:45%;" value="' + jsondata.data.title + '" /><br />' 
				+'<label class="editDescr">'+t('audioplayer','File')+'</label> <input type="text" placeholder="'+t('audioplayer','File')+'"  style="width:45%;" value="' + jsondata.data.localPath + '" readonly /><br />' 
				+'<label class="editDescr">'+t('audioplayer','Track')+'</label> <input type="text" placeholder="'+t('audioplayer','Track')+'" id="sTrack" maxlength="2" style="width:10%;" value="' + jsondata.data.track + '" /> '+t('audioplayer','of')+' <input type="text" placeholder="'+t('audioplayer','Total')+'" id="sTracktotal" maxlength="2" style="width:10%;" value="' + jsondata.data.tracktotal + '" /><br />' 

				+'<label class="editDescr">'+t('audioplayer','Existing Artists')+'</label><select style="width:45%;" id="eArtist"></select>' 
				+'<label class="editDescr">'+t('audioplayer','New Artist')+'</label> <input type="text" placeholder="'+t('audioplayer','Artist')+'" id="sArtist" style="width:45%;" value="" />' 

				+'<label class="editDescr">'+t('audioplayer','Existing Albums')+'</label><select style="width:45%;" id="eAlbum"></select>' 
				+'<label class="editDescr">'+t('audioplayer','New Album')+'</label> <input type="text" placeholder="'+t('audioplayer','Album')+'" id="sAlbum" style="width:45%;" value="" />' 

				+'<label class="editDescr">'+t('audioplayer','Existing Genres')+'</label><select style="width:45%;" id="eGenre"></select>' 
				+'<label class="editDescr">'+t('audioplayer','New Genre')+'</label> <input type="text" placeholder="'+t('audioplayer','Genre')+'" id="sGenre" style="width:45%;" value="" />' 

				+'<label class="editDescr">'+t('audioplayer','Year')+'</label> <input type="text" placeholder="'+t('audioplayer','Year')+'" id="sYear" maxlength="4" style="width:10%;" value="' + jsondata.data.year + '" /><br />' 
				+'<label class="editDescr">'+t('audioplayer','Composer')+'</label> <input type="text" disabled style="width:45%;" value="'+jsondata.data.composer+'" />' 
				+'<label class="editDescr">'+t('audioplayer','Subtitle')+'</label> <input type="text" disabled style="width:45%;" value="'+jsondata.data.subtitle+'" />' 
				+'<label class="editDescr" style="width:190px;">'+t('audioplayer','Add as Album Cover')+'</label> <input type="checkbox"  id="sAlbumCover" maxlength="4" style="width:10%;"  />' 
				+'</div><div class="edit-right">'+posterAction+'</div>'
			);
			$("#dialogSmall").html(html);
			
			if(jsondata.data.poster!=''){
					$this.loadPhoto();
			}
			
			var optartists=[];
			$(jsondata.data.artists).each(function(i,el){
				if(jsondata.data.artist == el.name){
					optartists[i] = $('<option />').attr({'value':el.name,'selected':'selected'}).text(el.name);
				}else{
					optartists[i] = $('<option />').attr('value',el.name).text(el.name);
				}
				
			});
			$('#eArtist').append(optartists);
			
			var optalbums=[];
			$(jsondata.data.albums).each(function(i,el){
				if(jsondata.data.album == el.name){
					optalbums[i] = $('<option />').attr({'value':el.name,'selected':'selected'}).text(el.name);
				}else{
					optalbums[i] = $('<option />').attr('value',el.name).text(el.name);
				}
				
			});
			$('#eAlbum').append(optalbums);
			
			var optgenres=[];
			$(jsondata.data.genres).each(function(i,el){
				if(jsondata.data.genre == el.name){
					optgenres[i] = $('<option />').attr({'value':el.name,'selected':'selected'}).text(el.name);
				}else{
					optgenres[i] = $('<option />').attr('value',el.name).text(el.name);
				}
			});
			$('#eGenre').append(optgenres);
			
			$this.loadActionPhotoHandlers();
			$this.loadPhotoHandlers();
			
			$('#phototools li a').click(function() {
					$(this).tooltip('hide');
				});

				$('#pinPhoto').on('mouseenter', function() {
					$('#phototools').slideDown(200);
				});
				$('#pinPhoto').on('mouseleave', function() {
					$('#phototools').slideUp(200);
				});

				$('#phototools').hover(function() {
					$(this).removeClass('transparent');
				}, function() {
					$(this).addClass('transparent');
				});
				
			$("#dialogSmall").dialog({
				resizable : false,
				title : t('audioplayer', 'Edit metadata'),
				width : 600,
				modal : true,
				buttons : [{
					text : t('audioplayer', 'Close'),
				click : function() {
						$("#dialogSmall").html('');
						$(this).dialog("close");
					}
				}, {
					text : t('audioplayer', 'Save'),
					click : function() {
						var oDialog = $(this);
					
						$.ajax({
								type : 'POST',
								url : OC.generateUrl('apps/audioplayer/saveaudiofiledata'),
								data : {
									songFileId:fileId,
									trackId:songId,
									year: $('#sYear').val(),
									title: $('#sTitle').val(),
									artist: $('#sArtist').val(),
									existartist: $('#eArtist').val(),
									album: $('#sAlbum').val(),
									existalbum: $('#eAlbum').val(),
									track: $('#sTrack').val(),
									tracktotal: $('#sTracktotal').val(),
									imgsrc: $('#imgsrc').val(),
									imgmime: $('#imgmimetype').val(),
									addcover: $('#sAlbumCover').is(':checked'),
									genre: $('#sGenre').val(),
									existgenre: $('#eGenre').val()
								},
								success : function(jsondata) {
										if(jsondata.status === 'success'){
											if(jsondata.data.albumid !== jsondata.data.oldalbumid){
												if(	$('.sm2-bar-ui').hasClass('playing')){
														$this.AudioPlayer.actions.play(0);
														$this.AudioPlayer.actions.stop();
													}
													$('#alben').addClass('bAktiv');
													$('#myPlayList li').removeClass('activeIndiPlaylist');
													$this.AlbumContainer.html('');
													$this.AlbumContainer.show();
													$this.PlaylistContainer.hide();
													$('#individual-playlist').html('');
													$('.albumwrapper').removeClass('isPlaylist');
													$('#activePlaylist').html('');
													$('.sm2-playlist-target').html('');
													$('.sm2-playlist-cover').css('background-color','#ffffff').html('');
													 $this.loadAlbums();
											}
											if(jsondata.data.imgsrc != ''){
												$('.albumcover[data-album="album-'+jsondata.data.albumid+'"]')
												.css({
													'background-image':'url('+jsondata.data.imgsrc+')',
													'-webkit-background-size':'cover',
													'-moz-background-size':'cover',
													'background-size':'cover'
													})
												.text('');
												
												$('.songcontainer[data-album="album-'+jsondata.data.albumid+'"]')
												.css({
													'background-color':jsondata.data.prefcolor,
												});
												
												$('.songcontainer[data-album="album-'+jsondata.data.albumid+'"] .songcontainer-cover')
												.css({
													'background-image':'url('+jsondata.data.imgsrc+')',
													'-webkit-background-size':'cover',
													'-moz-background-size':'cover',
													'background-size':'cover'
													})
												.text('');
												
											}
											
											$("#dialogSmall").html('');
											oDialog.dialog("close");
										}else if(jsondata.status === 'error_write'){
											$('#notification').text(t('audioplayer','Missing permission to edit metadata of track!'));
			 								$('#notification').slideDown();
											window.setTimeout(function(){$('#notification').slideUp();}, 3000);
										}else if(jsondata.status === 'error'){
											$('#notification').text(t('audioplayer',jsondata.msg));
			 								$('#notification').slideDown();
											window.setTimeout(function(){$('#notification').slideUp();}, 3000);
										}
								}
						});
						
					}
				}],
			});
			return false;
		}
		if(jsondata.status === 'error'){
			$('#notification').text(t('audioplayer','Missing permission to edit metadata of track!'));
			 $('#notification').slideDown();
			window.setTimeout(function(){$('#notification').slideUp();}, 3000);
		}
	});
};

Audios.prototype.removeSongFromPlaylist=function(evt){
	
	if(typeof evt.target === 'string'){
		var songId = evt.target;
	}else{
		var songId = $(evt).attr('data-id');
	}

	var plId = $('#myCategory li.activeIndiPlaylist').attr('data-id');
	
	return $.getJSON(OC.generateUrl('apps/audioplayer/removetrackfromplaylist'), {
		playlistid : plId,
		songid: songId
	}).then(function(data) {
		$('#myCategory li.activeIndiPlaylist').find('.counter').text($('#myCategory li.activeIndiPlaylist').find('.counter').text()-1);
		$('#individual-playlist li[data-trackid="'+songId+'"]').remove();
		$('#activePlaylist li[data-trackid="'+songId+'"]').remove();
		$this.category_selectors[0] = 'Playlist';
		myAudios.loadCategory();		
	}.bind(this));
};

Audios.prototype.addSongToPlaylist = function(plId,songId) {
	
	var sort = parseInt($('#myPlayList li[data-id="'+plId+'"]').find('.counter').text());
	return $.getJSON(OC.generateUrl('apps/audioplayer/addtracktoplaylist'), {
		playlistid : plId,
		songid: songId,
		sorting : (sort + 1)
	}).then(function(data) {
		$('#myPlayList').html('');
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
  					myAudios.loadCategory('Playlist');
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
  							myAudios.loadCategory('Playlist');
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
  						myAudios.loadCategory('Playlist');
						myClone.remove();
					}
					if(jsondata.status == 'error'){
						alert('could not update playlist');
					}
					
				});
			}
			
		});
		myClone.find('button.icon-close').on('click',function(){
  			myAudios.loadCategory('Playlist');
			myClone.remove();
		});
	}
};

Audios.prototype.sortPlaylist = function(evt){
	var eventTarget=$(evt.target);
	if($('#myCategory li').hasClass('activeIndiPlaylist')){
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
  						myAudios.loadCategory('Playlist');
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
  									myAudios.loadCategory('Playlist');
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

Audios.prototype.loadPhoto = function() {
	var refreshstr = '&refresh=' + Math.random();
		$('#phototools li a').tooltip('hide');
		$('#pin_details_photo').remove();

		var ImgSrc = '';
		if (this.imgSrc != false) {
			ImgSrc = this.imgSrc;
		}
		
		var newImg = $('<img>').attr('id', 'pin_details_photo').css({'width':'150px'}).attr('src', 'data:' + this.imgMimeType + ';base64,' + ImgSrc);
		newImg.prependTo($('#pinPhoto'));

		$('#noimage').remove();

		//$('#pinContainer').removeClass('forceOpen');
};

Audios.prototype.loadPhotoHandlers = function() {
	var phototools = $('#phototools');
		phototools.find('li a').tooltip('hide');
		phototools.find('li a').tooltip();
			if ($('#isphoto').val() === '1') {
			phototools.find('.delete').show();
			phototools.find('.edit').show();
		} else {
			phototools.find('.delete').hide();
			phototools.find('.edit').hide();
		}

		phototools.find('.upload').show();
		phototools.find('.cloud').show();

};

Audios.prototype.loadActionPhotoHandlers= function() {
	   var phototools = $('#phototools');
	   
	   phototools.find('.delete').click(function(evt) {
				$(this).tooltip('hide');
				$('#pinContainer').addClass('forceOpen');
				this.deletePhoto();
				$(this).hide();
			}.bind(this));

			phototools.find('.edit').click(function() {
				$(this).tooltip('hide');
				$('#pinContainer').addClass('forceOpen');
				this.editCurrentPhoto();
			}.bind(this));
			
		phototools.find('.upload').click(function() {
			$(this).tooltip('hide');
			$('#pinContainer').addClass('forceOpen');
			$('#pinphoto_fileupload').trigger('click');
		});

		phototools.find('.cloud').click(function() {
			$(this).tooltip('hide');
			//$('#pinContainer').addClass('forceOpen');
			var mimeparts = ['image/jpeg', 'httpd/unix-directory'];
			OC.dialogs.filepicker(t('audioplayer', 'Select picture'), this.cloudPhotoSelected.bind(this), false, mimeparts, true);
		}.bind(this));
			
};

Audios.prototype.cloudPhotoSelected = function(path) {
	$.getJSON(OC.generateUrl('apps/audioplayer/getimagefromcloud'), {
			'path' : path,
			'id' : $('#photoId').val()
		}, function(jsondata) {
			if (jsondata) {
			
				this.editPhoto(jsondata.id, jsondata.tmp);
				$('#tmpkey').val(jsondata.tmp);
				this.imgSrc = jsondata.imgdata;
				this.imgMimeType = jsondata.mimetype;

				$('#imgsrc').val(this.imgSrc);
				$('#imgmimetype').val(this.imgMimeType);
				$('#edit_photo_dialog_img').html(jsondata.page);
			} else {
				OC.dialogs.alert(jsondata.message, t('audioplayer', 'Error'));
			}
		}.bind(this));
};

Audios.prototype.showCoords= function (c) {
		$('#cropform input#x1').val(c.x);
		$('#cropform input#y1').val(c.y);
		$('#cropform input#x2').val(c.x2);
		$('#cropform input#y2').val(c.y2);
		$('#cropform input#w').val(c.w);
		$('#cropform input#h').val(c.h);
};

Audios.prototype.editCurrentPhoto = function() {
	this.editPhoto($('#photoId').val(), $('#tmpkey').val());
};

Audios.prototype.editPhoto = function(id, tmpkey) {
	 $.ajax({
			type : 'POST',
			url : OC.generateUrl('apps/audioplayer/cropphoto'),
			data : {
				'tmpkey' : tmpkey,
				'id' : id,
			},
			success : function(data) {
				 $('#edit_photo_dialog_img').html(data);
				
				$('#cropbox').attr({'src': 'data:' + this.imgMimeType + ';base64,' + this.imgSrc}).show();
                //TODO SHOWCOORDS
                
				$('#cropbox').Jcrop({
					onChange : this.showCoords,
					onSelect : this.showCoords,
					minSize : [140, 140],
					maxSize : [500, 500],
					bgColor : 'black',
					bgOpacity : .4,
					aspectRatio: 1,
					boxWidth : 500,
					boxHeight : 500,
					setSelect : [150, 150, 50, 50]//,
					//aspectRatio: 0.8
				});
			}.bind(this)
			});
		
		if ($('#edit_photo_dialog').dialog('isOpen') == true) {
			$('#edit_photo_dialog').dialog('moveToTop');
		} else {
			$('#edit_photo_dialog').dialog('open');
		}
};

Audios.prototype.savePhoto = function() {
	var target = $('#crop_target');
		var form = $('#cropform');
		var wrapper = $('#pin_details_photo_wrapper');
		var self = this;
		
		wrapper.addClass('wait');
		form.submit();
		
		target.load(function() {
            $('#noimage').text(t('audioplayer', 'Picture generating, please wait ...')).addClass('icon-loading');
			var response = jQuery.parseJSON(target.contents().text());
			if (response != undefined) {
				$('#isphoto').val('1');
				
				this.imgSrc = response.dataimg;
				this.imgMimeType = response.mimetype;
				 $('#noimage').text('').removeClass('icon-loading');
				$('#imgsrc').val(this.imgSrc);
				$('#imgmimetype').val(this.imgMimeType);
				this.loadPhoto();
				this.loadPhotoHandlers();

			} else {
				OC.dialogs.alert(response.message, t('audioplayer', 'Error'));
				wrapper.removeClass('wait');
			}
		}.bind(this));
};

Audios.prototype.deletePhoto = function() {
			
		$('#isphoto').val('0');
		this.imgSrc = false;
		$('#pin_details_photo').remove();
		$('<div/>').attr('id', 'noimage').text(t('audioplayer', 'Drag picture here!')).prependTo($(' #pinPhoto'));
		$('#imgsrc').val('');
		this.loadPhotoHandlers();
	
};

Audios.prototype.openImportDialog = function() {
			
		$('body').append('<div id="audios_import"></div>');
			$('#audios_import').load(OC.generateUrl('apps/audioplayer/getimporttpl'),function(){
					this.scanInit();
			}.bind(this));
	
};
Audios.prototype.scanInit = function() {
	
	var $this = this;
	$('#audios_import_dialog').dialog({
		width : 500,
		resizable: false,
		close : function() {
			$this.scanStop($this.progresskey);
			$this.progresskey = '';
			$this.percentage = 0;
			$('#audios_import_dialog').dialog('destroy').remove();
			$('#audios_import_dialog').remove();
		}
	});
	
	$('#audios_import_done_close').click(function(){
		$this.progresskey = '';
		$this.percentage = 0;
		$('#audios_import_dialog').dialog('destroy');
		$('#audios_import_dialog').remove();
	});

	$('#audios_import_progress_cancel').click(function(){
		$this.scanStop($this.progresskey);
		$this.progresskey = '';
		$this.percentage = 0;
	});
	
	$('#audios_import_submit').click(function(){
		$this.processScan();
	});
	
	$('#audios_import_progressbar').progressbar({value:0});
	this.progresskey = $('#audios_import_progresskey').val();
};

Audios.prototype.processScan = function() {
	$('#audios_import_form').css('display', 'none');
	$('#audios_import_process').css('display', 'block');
	
	this.scanSend();
	window.setTimeout('myAudios.scanUpdate()', 1000);
};

Audios.prototype.scanSend = function() {
	
	$.post(OC.generateUrl('apps/audioplayer/scanforaudiofiles'),
		{progresskey: this.progresskey},  function(data){
			if(data.status == 'success'){
				$this.progresskey = '';
				$('#audios_import_process').css('display', 'none');
				$('#audios_import_done').css('display', 'block');
				$('#audios_import_done_message').html(data.message);

				$this.get_uservalue('category', function(someElement) {
					if ($this.category_selectors[0] && $this.category_selectors[0]!== 'Albums') {
						$("#category_selector").val($this.category_selectors[0]);
						$this.loadCategory($this.category_selectors[0]);
					} else {
						$this.loadAlbums();
					}
				});
			}else{
				$this.progresskey = '';
				$('#audios_import_progressbar').progressbar('option', 'value', 100);
				$('#audios_import_done_message').html(data.message);
			}
		}.bind(this));
};

Audios.prototype.scanStop = function(progresskey) {
    		$.ajax({
				type : 'POST',
				url : OC.generateUrl('apps/audioplayer/scanforaudiofiles'),
				data : {'progresskey': progresskey,
						'scanstop': true},
				success : function(ajax_data) {
				}
			});
};

Audios.prototype.scanUpdate = function() {
	if(this.progresskey === ''){
		return false;
	}
	
	$.post(OC.generateUrl('apps/audioplayer/getprogress'),
	 {progresskey: this.progresskey}, function(data){
		if(data.status == 'success'){
			this.percentage = parseInt(data.percent);
			$('#audios_import_progressbar').progressbar('option', 'value', parseInt(data.percent));
			$('#audios_import_process_progress').text(data.prog);
			$('#audios_import_process_message').text(data.msg);
			if(data.percent < 100 ){
				window.setTimeout('myAudios.scanUpdate()', 500);
			}else{
				$('#audios_import_process').css('display', 'none');
				$('#audios_import_done').css('display', 'block');
			}
		}else{
			//alert("getprogress error");
		}
	}.bind(this));
	return 0;
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
    	$.ajax({
			type : 'GET',
			url : OC.generateUrl('apps/audioplayer/setstatistics'),
			data : {'track_id': track_id},
			success : function(ajax_data) {
			}
		});
};

Audios.prototype.sort_playlist = function(evt) {
	var column = $(evt.target).attr('class').split('-')[1];
	var order = $(evt.target).data('order');
	var factor = 1;
	
	if (order === 'descending') {
		var factor = -1;
		$(evt.target).data('order', 'ascending'); 
	} else { 
		$(evt.target).data('order', 'descending'); 
	}

	var elems = $('#individual-playlist').children('li').get();
	var reg_check = $(elems).first().data(column).toString().match(/^\d{1,2}\-\d{1,2}$/);
	elems.sort(function(a,b){
		var a = $(a).data(column).toString();
		var b = $(b).data(column).toString();
		if (reg_check) {
			var a = parseInt(a.split('-')[0])*100 + parseInt(a.split('-')[1]);
			var b = parseInt(b.split('-')[0])*100 + parseInt(b.split('-')[1]);
		} else { 
			var a = a.toLowerCase();
			var b = b.toLowerCase();
		}
		return ((a < b) ? -1*factor : ((a > b) ? 1*factor : 0));
	});
	$('#individual-playlist').append(elems);

	var elems = $('#activePlaylist').children('li').get();
	elems.sort(function(a,b){
		var a = $(a).data(column).toString();
		var b = $(b).data(column).toString();
		if (reg_check) {
			var a = parseInt(a.split('-')[0])*100 + parseInt(a.split('-')[1]);
			var b = parseInt(b.split('-')[0])*100 + parseInt(b.split('-')[1]);
		} else { 
			var a = a.toLowerCase();
			var b = b.toLowerCase();
		}
		return ((a < b) ? -1*factor : ((a > b) ? 1*factor : 0));
	});
	$('#activePlaylist').append(elems);

	if($this.AudioPlayer){
    	$this.AudioPlayer.playlistController.data.selectedIndex = $('#activePlaylist li.selected').index();
	}
};

Audios.prototype.soundmanager_callback = function(SMaction) {
	if ($('#audios-audioscontainer .albumwrapper.isPlaylist').length === 0 ) {
		var cover = $('#activePlaylist li.selected').data('cover');
		var album = $('#activePlaylist li.selected').data('album');
			 							
		var getcoverUrl = OC.generateUrl('apps/audioplayer/getcover/');
		if(cover === ''){	
			var addCss='background-color: #D3D3D3;color: #333333;';
			if ($this.category_selectors[0] && $this.category_selectors[0]!== 'Albums') {
				var album = $('#activePlaylist li.selected').data('title');
			} else {
				var album = $('#activePlaylist li.selected').data('album');
			}
				var addDescr=album.substring(0,1);	
		} else {
			var addDescr='';
			var addCss='background-image:url('+getcoverUrl+cover+');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
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
					if (new Date > ajax_data && new Date < ajax_data2 && $('.sm2-bar-ui').hasClass('playing')) {
						$('#notification').text('Timer Done!');
						$('#notification').slideDown();
						window.setTimeout(function(){$('#notification').slideUp();}, 3000);	
						$this.AudioPlayer.actions.stop();
					} else if (new Date < ajax_data && $('.sm2-bar-ui').hasClass('playing')){
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
					if(data === 'true'){
						$('#notification').text(t('audioplayer','New audio files available'));
						$('#notification').slideDown();
						window.setTimeout(function(){$('#notification').slideUp();}, 5000);
					}
			}
		});
};

/*
coming soon
Audios.prototype.streamPlayer = function() {
	var url = 'http://abc.mp3';
	var time = 0;
	var timer = $('#Streaming');
	var loading = $('#Loading');
	var start = new Date;
	var seconds = function(){
 	   var diff = ((new Date).getTime() - start.getTime()) / 1000;
 	   return diff + ' seconds.';
	};
	var timing = setInterval(function() {
	    timer.html(seconds());
	}, 100);
	soundManager.onready(function() {
	    soundManager.createSound({
	        id:'Radio', 
	        url:url, 
	        autoPlay: true,
	        onplay: function() {
	            clearInterval(timing);
	            loading.html('Finished loading');
	            timer.html(seconds());
	        }
	    });
	});
};*/	

var resizeTimeout = null;
$(window).resize(_.debounce(function() {
	if (resizeTimeout)
		clearTimeout(resizeTimeout);
	resizeTimeout = setTimeout(function() {
		//if($(window).width()>768){
			$('.sm2-bar-ui').width(myAudios.AlbumContainer.width());
		//}else{
		//	$('.sm2-bar-ui').width(myAudios.AlbumContainer.width()-45);
		//}
		
		if(myAudios.AlbumContainer.width() < 850){
			$('.songcontainer .songlist').addClass('one-column');
			$('.songcontainer .songlist').removeClass('two-column');
			$('.songcontainer .songcontainer-cover').addClass('cover-small');
		}else{
			$('.songcontainer .songlist').removeClass('one-column');
			$('.songcontainer .songlist').addClass('two-column');
			$('.songcontainer .songcontainer-cover').removeClass('cover-small');
		}
		
		$('#audios-audioscontainer .rowlist').remove();
		myAudios.buildAlbumRows(myAudios.albums);

	}, 500);
}));



var myPlayer=null;	

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
		}else{
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
		
		//if($(window).width()>768){
			$('.sm2-bar-ui').width(myAudios.AlbumContainer.width());
		//}else{
		//	$('.sm2-bar-ui.fixed').width(myAudios.AlbumContainer.width()-45);
		//}
	
	
	
	$('#edit_photo_dialog').dialog({
		autoOpen : false,
		modal : true,
		position : {
			my : "left top+100",
			at : "left+40% top",
			of : $('#body-user')
		},
		height : 'auto',
		width : 'auto',
		buttons:[
		{
		text : t('core', 'OK'),
		click : function() {
			
			myAudios.savePhoto(this);
			$('#coords input').val('');
			$(this).dialog('close');
		}
		},
		{
		text :  t('core', 'Cancel'),
		click : function() {
			//$('#coords input').val('');
			$.ajax({
			type : 'POST',
			url : OC.generateUrl('apps/audioplayer/clearphotocache'),
			data : {
				'tmpkey' : $('#tmpkey').val(),
			},
			success : function(data) {
				
			}
			
			});
			$(this).dialog('close');
		}
		}
		]
	});
	
		  
	$('#addPlaylist').on('click',function(){
		$('#newPlaylistTxt').val('');
		$('#newPlaylist').removeClass('ap_hidden');
	});
	

	$('#newPlaylistBtn_cancel').on('click',function(){
		$('#newPlaylistTxt').val('');
		$('#newPlaylist').addClass('ap_hidden');
	});

	$('#newPlaylistBtn_ok').on('click', function(){
		if ($('#newPlaylistTxt').val() != ''){
			myAudios.newPlaylist($('#newPlaylistTxt').val());
			$('#newPlaylistTxt').val('');
			$('#newPlaylistTxt').focus();
			$('#newPlaylist').addClass('ap_hidden');
		}
	});

	$('#newPlaylistTxt').bind('keydown', function(event){
		if (event.which == 13 && $('#newPlaylistTxt').val() != ''){
			myAudios.newPlaylist($('#newPlaylistTxt').val());
			$('#newPlaylistTxt').val('');
			$('#newPlaylistTxt').focus();
			$('#newPlaylist').addClass('ap_hidden');
		}
	});
	
	
	$('#alben').addClass('bAktiv');
	$('#alben').on('click',function(){
		$('#newPlaylist').addClass('ap_hidden');
		myAudios.PlaylistContainer.hide();
		if(	$('.sm2-bar-ui').hasClass('playing')){
			//myAudios.AudioPlayer.actions.play(0);
			//myAudios.AudioPlayer.actions.stop();
		}
		if($this.AlbumContainer.children().first().hasClass('rowlist') === false) {
			$this.loadAlbums();
		} else {
			$(this).addClass('bAktiv');
			myAudios.AlbumContainer.show();
		}
  		myAudios.set_uservalue('category','Albums');
	});
	
	$(document).on('click', '#resetAudios', function () {
		$("#dialogSmall").text(t('audioplayer','Are you sure?')+' '+t('audioplayer','All database entries will be deleted!'));
		$("#dialogSmall").dialog({
			resizable : false,
			title : t('audioplayer', 'Reset media library'),
			width : 250,
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
					
					if(	$('.sm2-bar-ui').hasClass('playing')){
						myAudios.AudioPlayer.actions.play(0);
						myAudios.AudioPlayer.actions.stop();
					}
					$("#category_selector").val('');
					$this.set_uservalue('category',$this.category_selectors[0]+'-');
					$('#myCategory').html('');
					$('#alben').addClass('bAktiv');
					$('#myPlayList li').removeClass('activeIndiPlaylist');
					myAudios.AlbumContainer.html('');
					myAudios.AlbumContainer.show();
					myAudios.PlaylistContainer.hide();
					$('#individual-playlist').html('');
					$('.albumwrapper').removeClass('isPlaylist');
					$('#activePlaylist').html('');
					$('.sm2-playlist-target').html('');
					$('.sm2-playlist-cover').css('background-color','#ffffff').html('');
					$('#notification').text(t('audioplayer','Start deleting and resetting media library ...'));
					$('#notification').slideDown();
					
					$.ajax({
							type : 'GET',
							url : OC.generateUrl('apps/audioplayer/resetmedialibrary'),
							success : function(jsondata) {
									if(jsondata.status === 'success'){
										myAudios.loadAlbums();
										$('#notification').text(t('audioplayer','Resetting finished!'));
										window.setTimeout(function(){$('#notification').slideUp();}, 3000);
									}
							}
					});
					$("#dialogSmall").html('');
					oDialog.dialog("close");
					$('#myCategory').html('');
				}
			}],
		});
		return false;
	});
	
	$(document).on('click', '#scanAudios, #scanAudiosFirst', function () {
		$('#newPlaylist').addClass('ap_hidden');
		if(	$('.sm2-bar-ui').hasClass('playing')){
			myAudios.AudioPlayer.actions.play(0);
			myAudios.AudioPlayer.actions.stop();
		}
		$("#category_selector").val('');
		$('#myCategory').html('');
		$('#alben').addClass('bAktiv');
		$('#myPlayList li').removeClass('activeIndiPlaylist');
		myAudios.AlbumContainer.html('');
		myAudios.AlbumContainer.show();
		myAudios.PlaylistContainer.hide();
		$('#individual-playlist').html('');
		$('.albumwrapper').removeClass('isPlaylist');
		$('#activePlaylist').html('');
		$('.sm2-playlist-target').html('');
		$('.sm2-playlist-cover').css('background-color','#ffffff').html('');
		
		myAudios.openImportDialog();
		
		return false;
	});
		
	$('#toggle_alternative').prepend('<div id="app-navigation-toggle_alternative" class="icon-menu" style="float: left; box-sizing: border-box;"></div>');
	
	$('#app-navigation-toggle_alternative').click(function(){
			$('#newPlaylist').addClass('ap_hidden');
		if(	$('#app-navigation').hasClass('ap_hidden')){
			$('#app-navigation').removeClass('ap_hidden');
			$('#audios-audioscontainer .rowlist').remove();
			myAudios.buildAlbumRows(myAudios.albums);
			$('.sm2-bar-ui').width(myAudios.AlbumContainer.width());
			myAudios.set_uservalue('navigation','true');
		} else {
			$('#app-navigation').addClass('ap_hidden');
			$('#audios-audioscontainer .rowlist').remove();
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
  		if ($this.category_selectors[0] != '' ) {
  			myAudios.loadCategory();
  		}
	});

	$('.header-title').click($this.sort_playlist.bind($this)).css('cursor', 'pointer');
	$('.header-artist').click($this.sort_playlist.bind($this)).css('cursor', 'pointer');
	$('.header-album').click($this.sort_playlist.bind($this)).css('cursor', 'pointer');
	
	var timer = window.setTimeout(function() {$('.sm2-bar-ui').width(myAudios.AlbumContainer.width());}, 1000);	
});
