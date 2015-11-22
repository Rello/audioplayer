/**
 * ownCloud - Audios
 *
 * @author Sebastian Doell
 * @copyright 2015 sebastian doell sebastian@libasys.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see http://www.gnu.org/licenses.
 *
 */

var Audios = function(){
	 this.AudioPlayer=null;
	 this.AlbumContainer=$('#audios-audioscontainer');
	 this.PlaylistContainer=$('#individual-playlist-container');
	 this.aSongIdsPlaylist=[];
	 this.albums=[];
	 this.imgSrc = false;
	 this.imgMimeType = 'image/jpeg';
	 this.percentage = 0;
	 this.progresskey = '';
};

Audios.prototype.init = function() {
	 this.loadPlaylists();
	this.loadAlbums();
   
	this.initKeyListener();
	this.initPhotoDialog();
	$('.toolTip').tipsy({
		html : true
	});
	
};

Audios.prototype.initPhotoDialog = function(){
	/* Initialize the photo edit dialog */
	
	$('input#pinphoto_fileupload').fileupload({
		dataType : 'json',
		url : OC.generateUrl('apps/audios/uploadphoto'),
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
				/*
				if($('#app-navigation-toggle').is(':visible')){
					
					if(OC.Snapper.state().state == 'left'){
						//OC.Snapper.close();
					} else {
						OC.Snapper.open('left');
					}
				}*/
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
					$('.sm2-playlist-cover').click(function(){
						window.location.href='#show-'+myCover.data('album');
					});
				}else{
					$('.sm2-playlist-cover').text('').css({'background-image':myCover.css('background-image')});
					$('.sm2-playlist-cover').click(function(){
						window.location.href='#show-'+myCover.data('album');
					});
				}
			}
			
			if($('.albumwrapper.isPlaylist li.isActive').length === 1 && !$(this).closest('li').hasClass('isActive')){
				$('.albumwrapper.isPlaylist li').removeClass('isActive');
				$('.albumwrapper.isPlaylist li i.ioc').hide();
			}
			if(!$(this).closest('li').hasClass('isActive')){
				$(this).closest('li').addClass('isActive');
				$this.AudioPlayer.actions.play($(this).closest('li').index());
			}else{
				if($('.sm2-bar-ui').hasClass('playing')){
					$this.AudioPlayer.actions.stop();
				}else{
					$this.AudioPlayer.actions.play();
				}
			}
		
			
			return false;
					 
		});
		
	});

};
Audios.prototype.AlbumClickHandler = function(event){
		var AlbumId = '';
		
		if(event.albumId !== undefined){
			AlbumId=event.albumId;
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
	 	 	window.location.href = '#'+AlbumId;
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
	 	 	
	 	 	window.location.href = '#'+AlbumId;
	 	 	
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
			    window.location.href = '#';
			    //$('.rowlist').removeClass('margin-bottom');
			    $(activeAlbum).parent('.rowlist').css('margin-bottom',0);
		  });
		}
		
 	 }
};

Audios.prototype.buildAlbumRows = function(aAlbums){
				var divAlbum = [];
				
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
				 	
				 	if(album.cover === ''){	
						var addCss='background-color:'+album.backgroundColor+';color:'+album.titlecolor+';';
						var addDescr=album.name.substring(0,1);	
					}else{
						var addDescr='';
						var addCss='background-image:url('+album.cover+');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
					}
					
				 	 divAlbum[i] = $('<div/>').addClass('album').css('margin-left',marginLeft+'px')
				 	 .attr({
				 	 	'data-album':'album-'+album.id,
				 	 	'data-bgcolor':album.backgroundColor,
				 	 	'data-color':album.titlecolor
				 	 }).click($this.AlbumClickHandler);
				 	
				 	 
				 	 
				 	var divAlbumCover = $('<div/>').addClass('albumcover').attr({
				 		'data-album':'album-'+album.id,
				 		'style':addCss
				 	}).text(addDescr);	
				 	divAlbum[i].append(divAlbumCover);
				 	
			 		
			 		var divAlbumDescr= $('<div/>').addClass('albumdescr').html('<span class="albumname">'+album.name+'</span><span class="artist">'+album.artist+'</span>');
			 		
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
		url : OC.generateUrl('apps/audios/getmusic'),
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
			  	 
			 $.each($this.albums,function(i,album){
			 		//Songs into hidden div
			 		divSongContainer[i] = $('<div/>').addClass('songcontainer').attr({
				 	 	'data-album':'album-'+album.id
				 	 });
				 	var divArrow = $('<i/>').addClass('open-arrow'); 
				 	divSongContainer[i] .append(divArrow);
			 		var divSongContainerInner = $('<div/>').addClass('songcontainer-inner');
			 		divSongContainer[i] .append(divSongContainerInner);
			 		
			 		if(album.cover === ''){	
						var addCss='background-color:'+album.backgroundColor+';color:'+album.titlecolor+';';
						var addDescr=album.name.substring(0,1);	
					}else{
						var addDescr='';
						var addCss='background-image:url('+album.cover+');-webkit-background-size:cover;-moz-background-size:cover;background-size:cover;';
					}
			 		
			 		var divSongContainerCover = $('<div/>').addClass('songcontainer-cover').attr({
				 		'style':addCss
				 	}).text(addDescr);
				 	if($this.AlbumContainer.width() < 850){
				 		divSongContainerCover.addClass('cover-small');
				 	}
			 		divSongContainerInner.append(divSongContainerCover);
			 		var h2SongHeader=$('<h2/>').text(album.name);
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
							 var myCover=$('.album.is-active .albumcover');
							  if(myCover.css('background-image') == 'none'){
								$('.sm2-playlist-cover').text(myCover.text()).css({'background-color':myCover.css('background-color'),'color':myCover.css('color'),'background-image':'none'});
								$('.sm2-playlist-cover').click(function(){
									window.location.href='#show-'+myCover.data('album');
								});
							}else{
								$('.sm2-playlist-cover').text('').css({'background-image':myCover.css('background-image')});
								$('.sm2-playlist-cover').click(function(){
									window.location.href='#show-'+myCover.data('album');
								});
							}
						 }
			 		});
			 		h2SongHeader.prepend(spanPlay);
			 		
			 		divSongContainerInner.append(h2SongHeader);
			 		
			 		var aYear='';
			 		if(album.year != 0){
			 			aYear= ' (' +album.year+')';
			 		}
			 		var aGenre='';
			 		if(album.genrename !== null){
			 			aGenre= ' /' +album.genrename;
			 		}
			 		var h3SongSubHeader=$('<h3/>').text(album.artist+aYear+aGenre);
			 		divSongContainerInner.append(h3SongSubHeader);
			 		var divSongsContainer = $('<div/>').addClass('songlist albumwrapper');
			 		if($this.AlbumContainer.width() < 850){
				 		divSongsContainer.addClass('one-column');
				 	}else{
				 		divSongsContainer.addClass('two-column');
				 	}
			 		divSongContainerInner.append(divSongsContainer);
			 		var listAlbumSelect=$('<ul/>').addClass('albumSelect').attr('data-album',album.name);
			 		divSongsContainer.append(listAlbumSelect);
			 		
			 		var aSongs=[];
			 		if(songs[album.id]){
				 		$.each(songs[album.id],function(ii,songs){
				 			aSongs[ii] = $this.loadSongsRow(songs, album.name);
				 		});
			 		}else{
			 			console.warn('Could not find songs for album:', album.name, album);
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
				
			}else{
				  $this.AlbumContainer.show();
				  $this.AlbumContainer.html('<span class="no-songs-found"><i class="ioc ioc-refresh" title="'+t('audios','Scan for new audio files')+'" id="scanAudiosFirst"></i> '+t('audios','Add new Songs to playlist')+'</span>');
			}
		
		
		//LIBASYS
		var locHash = decodeURI(location.hash).substr(1);
		var AlbumId='';
		var PlaylistId='';
		if(locHash !== ''){
			var locHashTemp = locHash.split('playlist-');
			if(locHashTemp[1] !== undefined){
				PlaylistId = locHashTemp[1];
			}else{
				locHashTemp = locHash.split('album-');
				if(locHashTemp[1] !== undefined){
					AlbumId = 'album-'+locHashTemp[1];
				}
			}
		}
		
		if(PlaylistId !=='' && PlaylistId > 0){
			$('#myPlayList li[data-id="'+PlaylistId+'"]').addClass('activeIndiPlaylist');
			$this.loadIndividualPlaylist();
		}
		
		if(AlbumId !== ''){
			evt={};
			evt.albumId = AlbumId;
			$this.AlbumClickHandler(evt);
			
		}
		
		
		}
	});
};

Audios.prototype.loadSongsRow = function(elem,albumName){
	
				var li = $('<li/>').attr({
					'data-id' : elem.id,
					'data-fileid' : elem.file_id,
					'data-album' : albumName,
					'data-artist' : elem.artistname,
					'class' : 'dragable'
				});
				
				var spanAction = $('<span/>').addClass('actionsSong').html('<i class="ioc ioc-volume-off"></i>&nbsp;');
				li.append(spanAction);
				
				
				var spanNr = $('<span/>').addClass('number').text(elem.number);
				li.append(spanNr);
				var link = $('<a/>').addClass('link-full').attr('href',elem.link);
				var spanTitle = $('<span/>').attr({'data-title':elem.title,'title':elem.title}).addClass('title').text(elem.title);
				link.append(spanTitle);
				li.append(link);
				var spanTime = $('<span/>').addClass('time').text(elem.length);
				li.append(spanTime);
				var spanEdit=$('<a/>').addClass('edit-song icon-rename').attr({'data-id':elem.id,'data-fileid':elem.file_id,'title':t('audios','Edit Song from Playlist')}).click(this.editSong.bind(this));
				li.append(spanEdit);
				
				return li;
				
};

Audios.prototype.loadPlaylists = function(){

	var $this = this;
	$('#myPlayList').html('');
	$('.toolTip').tipsy('hide');
	$.ajax({
				type : 'GET',
				url : OC.generateUrl('apps/audios/getplaylists'),
				data : {},
				success : function(jsondata) {
					if(jsondata.status == 'success'){
						
						var playlistsdata=jsondata.data;
						
						if(playlistsdata !== 'nodata'){
							var aPlaylists=[];
							
							$(playlistsdata.playlists).each(function(i,el){
								
								$this.aSongIdsPlaylist[el.info.id]=el.songids;
								
								var li = $('<li/>')
								.attr({'data-id':el.info.id,'data-name':el.info.name})
								.droppable({
									activeClass : "activeHover",
									hoverClass : "dropHover",
									accept : 'li.dragable',
									over : function(event, ui) {
									},
									drop : function(event, ui) {
											$this.addSongToPlaylist($(this).attr('data-id'), ui.draggable.attr('data-id'));
									}
								})	;
								var spanPlaylistInfo=$('<span/>')
								.attr('class','info-cover').css({'background-color':el.info.backgroundColor,'color':el.info.color})
								.text(el.info.name.substring(0, 1));
								
								var spanName=$('<span/>')
								.attr({'data-plid':el.info.id,'class':'pl-name'})
								.text(el.info.name)
								.click($this.loadIndividualPlaylist.bind($this));
								
								var span=$('<span/>').attr('class','counter').text(el.songids.length);
								var iSort=$('<i/>').attr({'class':'ioc ioc-sort toolTip','data-sortid':el.info.id,'title':t('audios','Sort Playlist')}).click($this.sortPlaylist.bind($this));
								var iEdit=$('<a/>').attr({'class':'icon icon-rename toolTip','data-name':el.info.name,'data-editid':el.info.id,'title':t('audios','Rename Playlist')}).click($this.renamePlaylist.bind($this));
	
								var iDelete=$('<i/>').attr({'class':'ioc ioc-delete toolTip','data-deleteid':el.info.id,'title':t('audios','Delete Playlist')}).click($this.deletePlaylist.bind($this));
			
								li.append(spanPlaylistInfo);
								li.append(spanName);
								li.append(span);
								li.append(iEdit);
								li.append(iSort);
								li.append(iDelete);
								
								aPlaylists[i]=li;
							});
							
							
							$('#myPlayList').append(aPlaylists);
							$('.toolTip').tipsy({
								html : true
							});
						}
						
					}
				}
		});
};
Audios.prototype.loadIndividualPlaylist = function(evt) {
	var EventTarget=null;
	var bRreload = false;
	if(typeof evt === 'undefined'){
		EventTarget=$('#myPlayList li.activeIndiPlaylist span.pl-name');		
		bRreload = true;
	}else{
		EventTarget = $(evt.target);
	}
	
	var PlaylistId = EventTarget.attr('data-plid');
	
	window.location.href='#playlist-'+PlaylistId;
	
	var $this = this;
	
	if(!EventTarget.parent('li').hasClass('activeIndiPlaylist') || bRreload === true){
	
	$('#myPlayList li').removeClass('activeIndiPlaylist');
	
	EventTarget.parent('li').addClass('activeIndiPlaylist');
	$('#alben').removeClass('bAktiv');
	
	var aPlayList=this.aSongIdsPlaylist[PlaylistId];
	
	$this.AlbumContainer.hide();
	$this.PlaylistContainer.show();
	$('#individual-playlist').html('');
		 
	
	 if(aPlayList.length > 0){
		var aPlaylistOutput=[];
		var aPlaylistOutput1=[];
		
		
		$(aPlayList).each(function(i,el){
			if($('ul.albumSelect li[data-id="'+el+'"]').length ===1){
				var myClone=$('ul.albumSelect li[data-id="'+el+'"]').clone();
				myClone.find('a.edit-song').remove();
				var li =$('<li/>').attr({'data-trackid':myClone.attr('data-id'),'data-album':myClone.attr('data-album'),'data-artist':myClone.attr('data-artist')});
				var a = $('<a/>').attr({'href':myClone.find('a').attr('href')}).html('<span class="title">'+myClone.find('span.title').text()+'</span>');
				li.append(a);
				
			aPlaylistOutput1[i]=li;
			
			myClone.find('.number').text((i+1));
			var interpret=$('<span>').attr({'class':'interpret'}).text(myClone.attr('data-artist'));
			myClone.append(interpret);
			var album=$('<span>').attr({'class':'album-indi'}).text(myClone.attr('data-album'));
			myClone.append(album);
			myClone.find('span').css('color','#555');
			myClone.find('span.title').on('click',function(){
				var disabled = $("#individual-playlist").sortable( "option", "disabled" );
				if(disabled === true){
					
					var albumPlaylistActive=$('#audios-audioscontainer .albumwrapper.isPlaylist');
					var playlistActive=$('#myPlayList li.activeIndiPlaylist');
					var indiPlaylistId =$this.PlaylistContainer.data('playlist');
					
					if(indiPlaylistId === '' || (indiPlaylistId !== playlistActive.data('id'))  || albumPlaylistActive.length > 0){
						if(albumPlaylistActive.length > 0){
							albumPlaylistActive.find('.albumSelect li').removeClass('isActive');
							albumPlaylistActive.find('.albumSelect li i').hide();
							$('#audios-audioscontainer .albumwrapper').removeClass('isPlaylist');
						}
						$this.PlaylistContainer.data('playlist',playlistActive.data('id'));
						
						   var parent = $('#myPlayList li.activeIndiPlaylist span.pl-name').parent();
							$('.sm2-playlist-cover').text(parent.find('.info-cover').text()).css({'background-color':parent.find('.info-cover').css('background-color'),'color':parent.find('.info-cover').css('color'),'background-image':''});
							$('.sm2-playlist-target').text('');
							$('.sm2-playlist-cover').click(function(){
								window.location.href='#show-playlist-'+$this.PlaylistContainer.data('playlist');
							});
						
						$this.PlaylistContainer.addClass('isPlaylist');
						if($this.AudioPlayer == null){
							$this.AudioPlayer = new SM2BarPlayer($('.sm2-bar-ui')[0]);
						}
						$('#activePlaylist').html(aPlaylistOutput1);
					}
					var activeLi=$(this).closest('li');
					
					if($this.PlaylistContainer.find('.isPlaylist li.isActive').length === 1 && !activeLi.hasClass('isActive')){
						$('#individual-playlist li').removeClass('isActive');
						$('#individual-playlist li i.ioc').hide();
					}
					if(!activeLi.hasClass('isActive')){
						$('#individual-playlist li').removeClass('isActive');
						$('#individual-playlist li i.ioc').hide();
						activeLi.addClass('isActive');
						$this.AudioPlayer.actions.play(activeLi.index());
					}else{
						//$this.AudioPlayer.actions.stop();
						if($('.sm2-bar-ui').hasClass('playing')){
							$this.AudioPlayer.actions.stop();
						}else{
							$this.AudioPlayer.actions.play();
						}
					}
					
				}
				return false;
				});
				
				var span=$('<span/>').attr({'class':'ioc ioc-delete', 'data-id':myClone.attr('data-id'),'title':t('audios','Delete Song from Playlist')}).click($this.removeSongFromPlaylist.bind($this));
				myClone.append(span);
				
				var spanEdit=$('<a/>').addClass('edit-song icon-rename').attr({'data-id':myClone.attr('data-id'),'data-fileid':myClone.attr('data-fileid'),'title':t('audios','Edit Song from Playlist')}).click($this.editSong.bind($this));
				myClone.append(spanEdit);
				
				
			 aPlaylistOutput[i]=myClone;
		}else{
			
			var actPlid = $('#myPlayList li.activeIndiPlaylist').attr('data-id');
			var actCounter=parseInt($('#myPlayList li.activeIndiPlaylist span.counter').text());
			$('#myPlayList li.activeIndiPlaylist span.counter').text(actCounter-1);
			//OC.Tags.unTag(el,actPl,'audios');
			var evt={};
			evt.target = el;
			
			aPlayList = jQuery.grep(aPlayList, function(value) {
			  	return value != el;
			});
			$this.removeSongFromPlaylist(evt);
		}
	});
	
	$("#individual-playlist").sortable({
			items: "li",
			axis: "y",
			disabled: true,
			placeholder: "ui-state-highlight",
			stop: function( event, ui ) {
			}
	});
	
	
	
	$('#individual-playlist').append(aPlaylistOutput);
	$('#individual-playlist li i.ioc').hide();
		if($this.PlaylistContainer.hasClass('isPlaylist')){
			var activeSongSel=$('#individual-playlist li[data-id="'+$('#activePlaylist li.selected').data('trackid')+'"] i.ioc');
			$('#individual-playlist li[data-id="'+$('#activePlaylist li.selected').data('trackid')+'"]').addClass('isActive');
			activeSongSel.removeClass('ioc-volume-off');
			activeSongSel.addClass('ioc-volume-up');
			activeSongSel.show();
		}else{
				$('#individual-playlist li').removeClass('isActive');
		}
	
 	}else{
 		$('#individual-playlist').html('<span class="no-songs-found-pl">'+t('audios','No Songs found in current Playlist! Add new Songs per Drag & Drop from album view')+'</span>');
 	}
	}else{
		$this.AlbumContainer.hide();
		if($this.PlaylistContainer.hasClass('isPlaylist')){
			var activeSongSel=$('#individual-playlist li[data-id="'+$('#activePlaylist li.selected').data('trackid')+'"] i.ioc');
			$('#individual-playlist li[data-id="'+$('#activePlaylist li.selected').data('trackid')+'"]').addClass('isActive');
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

Audios.prototype.editSong = function(evt){
	if(typeof evt.target === 'string'){
		var songId = evt.target;
		
	}else{
		var songId = $(evt.target).attr('data-id');
		var fileId = $(evt.target).attr('data-fileid');
	}
	//var plId = $('#myPlayList li.activeIndiPlaylist').attr('data-id');
	$this = this;
	$.getJSON(OC.generateUrl('apps/audios/editaudiofile'), {
		songFileId: fileId
	},function(jsondata){
		if(jsondata.status === 'success'){
			
			var posterImg='<div id="noimage">'+t('audios', 'Drag Image Here!')+'</div>';
		
			if(jsondata.data.isPhoto === '1'){
					
					$this.imgSrc = jsondata.data.poster;
					$this.imgMimeType = jsondata.data.mimeType;
					posterImg = '';
					$this.loadPhoto();
			}
			
			var posterAction='<span class="labelPhoto" id="pinPhoto">'+posterImg
  							+'<div class="tip" id="pin_details_photo_wrapper" title="'+t('audios','Drop Photo')+'" data-element="PHOTO">'
							+'<ul id="phototools" class="transparent hidden">'
							+'<li><a class="delete" title="'+t('audios','Delete')+'"><img style="height:26px;" class="svg" src="'+OC.imagePath('core', 'actions/delete.svg')+'"></a></li>'
							+'<li><a class="edit" title="'+t('audios','Edit')+'"><img style="height:26px;" class="svg" src="'+OC.imagePath('core', 'actions/rename.svg')+'"></a></li>'
							+'<li><a class="svg upload" title="'+t('audios','Upload')+'"><img style="height:26px;" class="svg" src="'+OC.imagePath('core', 'actions/upload.svg')+'"></a></li>'
							+'<li><a class="svg cloud" title="'+t('audios','Select from cloud')+'"><img style="height:26px;" class="svg" src="'+OC.imagePath('core', 'actions/public.svg')+'"></a></li>'
							+'</ul></div>'
							+'<iframe name="file_upload_target" id="file_upload_target" src=""></iframe>'
						 	+'</span>';
						 
			html = $('<div/>').html(
				'<input type="hidden" name="isphoto" id="isphoto" value="'+jsondata.data.isPhoto+'" />'
				+'<input type="hidden" name="id" id="photoId" value="'+fileId+'" />'
			   +'<input type="hidden" name="tmpkey" id="tmpkey" value="'+jsondata.data.tmpkey+'" />'
			   +'<textarea id="imgsrc" name="imgsrc" style="display:none;">'+jsondata.data.poster+'</textarea>'
			   +'<input type="hidden" name="imgmimetype" id="imgmimetype" value="'+jsondata.data.mimeType+'" />'	
				+'<div class="edit-left"><label class="editDescr">'+t('audios','Title')+'</label> <input type="text" placeholder="'+t('audios','Title')+'" id="sTitle" style="width:45%;" value="' + jsondata.data.title + '" /><br />' 
				+'<label class="editDescr">'+t('audios','File')+'</label> <input type="text" placeholder="'+t('audios','File')+'"  style="width:45%;" value="' + jsondata.data.localPath + '" readonly /><br />' 
				+'<label class="editDescr">'+t('audios','Track')+'</label> <input type="text" placeholder="'+t('audios','Track')+'" id="sTrack" maxlength="2" style="width:10%;" value="' + jsondata.data.track + '" /> '+t('audios','of')+' <input type="text" placeholder="'+t('audios','Total')+'" id="sTracktotal" maxlength="2" style="width:10%;" value="' + jsondata.data.tracktotal + '" /><br />' 
				+'<label class="editDescr">'+t('audios','Existing Interprets')+'</label><select style="width:45%;" id="eArtist"></select>' 
				+'<label class="editDescr">'+t('audios','New Interpret')+'</label> <input type="text" placeholder="'+t('audios','Interpret')+'" id="sArtist" style="width:45%;" value="" />' 
				+'<label class="editDescr">'+t('audios','Existing Albums')+'</label><select style="width:45%;" id="eAlbum"></select>' 
				+'<label class="editDescr">'+t('audios','New Album')+'</label> <input type="text" placeholder="'+t('audios','Album')+'" id="sAlbum" style="width:45%;" value="" />' 
				+'<label class="editDescr">'+t('audios','Genre')+'</label><select style="width:45%;" id="sGenre"></select>' 
				+'<label class="editDescr">'+t('audios','Year')+'</label> <input type="text" placeholder="'+t('audios','Year')+'" id="sYear" maxlength="4" style="width:10%;" value="' + jsondata.data.year + '" /><br />' 
				+'<label class="editDescr" style="width:190px;">'+t('audios','Add as Albumcover')+'</label> <input type="checkbox"  id="sAlbumCover" maxlength="4" style="width:10%;"  />' 
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
			
			$.each(jsondata.data.genres,function(i,el){
				if(jsondata.data.genre == el.name){
					optgenres[i] = $('<option />').attr({'value':el.name,'selected':'selected'}).text(el.name);
				}else{
					optgenres[i] = $('<option />').attr('value',el.name).text(el.name);
				}
			});
			$('#sGenre').append(optgenres);
			
			$this.loadActionPhotoHandlers();
			$this.loadPhotoHandlers();
			
			$('#phototools li a').click(function() {
					$(this).tipsy('hide');
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
				title : t('audios', 'Edit Song Information (ID3)'),
				width : 600,
				modal : true,
				buttons : [{
					text : t('audios', 'Close'),
				click : function() {
						$("#dialogSmall").html('');
						$(this).dialog("close");
					}
				}, {
					text : t('audios', 'Save'),
					click : function() {
						var oDialog = $(this);
					
						$.ajax({
								type : 'POST',
								url : OC.generateUrl('apps/audios/saveaudiofiledata'),
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
									genre: $('#sGenre').val()
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
										}
								}
						});
						
					}
				}],
			});
			return false;
		}
		if(jsondata.status === 'error'){
			$('#notification').text(t('audios','Missing Permissions for editing ID3 Tags of song!'));
			 $('#notification').slideDown();
			window.setTimeout(function(){$('#notification').slideUp();}, 3000);
		}
	});
};

Audios.prototype.removeSongFromPlaylist=function(evt){
	
	if(typeof evt.target === 'string'){
		var songId = evt.target;
	}else{
		var songId = $(evt.target).attr('data-id');
	}
	var plId = $('#myPlayList li.activeIndiPlaylist').attr('data-id');
	
	return $.getJSON(OC.generateUrl('apps/audios/removetrackfromplaylist'), {
		playlistid : plId,
		songid: songId
	}).then(function(data) {
		this.aSongIdsPlaylist[plId] = jQuery.grep(this.aSongIdsPlaylist[plId], function(value) {
		  return value != songId;
		});
		$('#myPlayList li.activeIndiPlaylist').find('.counter').text(this.aSongIdsPlaylist[plId].length);
		$('#individual-playlist li[data-id="'+songId+'"]').remove();
		$('#activePlaylist li[data-trackid="'+songId+'"]').remove();
		
	}.bind(this));
	
	
};

Audios.prototype.addSongToPlaylist = function(plId,songId) {
	
	var sort = parseInt($('#myPlayList li[data-id="'+plId+'"]').find('.counter').text());
	return $.getJSON(OC.generateUrl('apps/audios/addtracktoplaylist'), {
		playlistid : plId,
		songid: songId,
		sorting : (sort + 1)
	}).then(function(data) {
		$('#myPlayList').html('');
		$('.toolTip').tipsy('hide');
		this.loadPlaylists();
	}.bind(this));
	
	
};

Audios.prototype.newPlaylist = function(plName){
	$this=this;
	$.ajax({
		type : 'GET',
		url : OC.generateUrl('apps/audios/addplaylist'),
		data : {'playlist':plName},
		success : function(jsondata) {
				if(jsondata.status === 'success'){
					$this.loadPlaylists();
				}
				if(jsondata.status === 'error'){
					 $('#notification').text(t('audios','No Playlist selected!'));
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
		
		$('#myPlayList li[data-id="'+plId+'"]').after(myClone);
		myClone.attr('data-pl',plId).show();
		$('#myPlayList li[data-id="'+plId+'"]').hide();
		
		myClone.find('input[name="playlist"]')
		.bind('keydown', function(event){
			if (event.which == 13){
				if(myClone.find('input[name="playlist"]').val()!==''){
					var saveForm = $('.plclone[data-pl="'+plId+'"]');
					var plname = saveForm.find('input[name="playlist"]').val();
					
					$.getJSON(OC.generateUrl('apps/audios/updateplaylist'), {
						plId:plId,
						newname:plname
					}, function(jsondata) {
						if(jsondata.status == 'success'){
							$this.loadPlaylists();
							myClone.remove();
						}
						if(jsondata.status == 'error'){
							alert('could not update playlist');
						}
						
						});
					
				}else{
					myClone.remove();
					$('#myPlayList li[data-id="'+plId+'"]').show();
				}
			}
		})
		.val(plistName).focus();
		
		
		myClone.on('keyup',function(evt){
			if (evt.keyCode===27){
				myClone.remove();
				$('#myPlayList li[data-id="'+plId+'"]').show();
			}
		});
		myClone.find('button.icon-checkmark').on('click',function(){
			var saveForm = $('.plclone[data-pl="'+plId+'"]');
			var plname = saveForm.find('input[name="playlist"]').val();
			if(myClone.find('input[name="playlist"]').val()!==''){
				$.getJSON(OC.generateUrl('apps/audios/updateplaylist'), {
					plId:plId,
					newname:plname
				}, function(jsondata) {
					if(jsondata.status == 'success'){
						$this.loadPlaylists();
						myClone.remove();
					}
					if(jsondata.status == 'error'){
						alert('could not update playlist');
					}
					
				});
			}
			
		});
	}
};

Audios.prototype.sortPlaylist = function(evt){
	var eventTarget=$(evt.target);
	if($('#myPlayList li').hasClass('activeIndiPlaylist')){
		var plId = eventTarget.attr('data-sortid');
		if(eventTarget.hasClass('sortActive')){
		   
			var idsInOrder = $("#individual-playlist").sortable('toArray', {attribute: 'data-id'});
			this.aSongIdsPlaylist[plId]=idsInOrder;
			 $.getJSON(OC.generateUrl('apps/audios/sortplaylist'), {
					playlistid : plId,
					songids: idsInOrder.join(';')
				},function(jsondata){
					if(jsondata.status === 'success'){
						
						this.loadIndividualPlaylist();
						eventTarget.removeClass('sortActive');
						$("#individual-playlist").sortable("disable");
						 $('#notification').text(jsondata.msg);
						 $('#notification').slideDown();
						window.setTimeout(function(){$('#notification').slideUp();}, 3000);
					}
				}.bind(this));
			
		}else{
			
			 $('#notification').text(t('audios','Sort modus active'));
			 $('#notification').slideDown();
			window.setTimeout(function(){$('#notification').slideUp();}, 3000);
					
			$("#individual-playlist").sortable("enable");
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
	$("#dialogSmall").text(t('audios', 'Are you sure?'));
	$("#dialogSmall").dialog({
		resizable : false,
		title : t('audios', 'Delete Playlist'),
		width : 210,
		modal : true,
		buttons : [{
			text : t('audios', 'No'),
		click : function() {
				$("#dialogSmall").html('');
				$(this).dialog("close");
			}
		}, {
			text : t('audios', 'Yes'),
			click : function() {
				var oDialog = $(this);
				$.ajax({
						type : 'GET',
						url : OC.generateUrl('apps/audios/removeplaylist'),
						data : {'playlistid':plId},
						success : function(jsondata) {
								if(jsondata.status === 'success'){
									$this.loadPlaylists();
									 $('#notification').text(t('audios','Delete playlist success!'));
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
		$('#phototools li a').tipsy('hide');
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
		phototools.find('li a').tipsy('hide');
		phototools.find('li a').tipsy();
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
				$(this).tipsy('hide');
				$('#pinContainer').addClass('forceOpen');
				this.deletePhoto();
				$(this).hide();
			}.bind(this));

			phototools.find('.edit').click(function() {
				$(this).tipsy('hide');
				$('#pinContainer').addClass('forceOpen');
				this.editCurrentPhoto();
			}.bind(this));
			
		phototools.find('.upload').click(function() {
			$(this).tipsy('hide');
			$('#pinContainer').addClass('forceOpen');
			$('#pinphoto_fileupload').trigger('click');
		});

		phototools.find('.cloud').click(function() {
			$(this).tipsy('hide');
			//$('#pinContainer').addClass('forceOpen');
			var mimeparts = ['image/jpeg', 'httpd/unix-directory'];
			OC.dialogs.filepicker(t('audios', 'Select photo'), this.cloudPhotoSelected.bind(this), false, mimeparts, true);
		}.bind(this));
			
};

Audios.prototype.cloudPhotoSelected = function(path) {
	$.getJSON(OC.generateUrl('apps/audios/getimagefromcloud'), {
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
				OC.dialogs.alert(jsondata.message, t('audios', 'Error'));
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
			url : OC.generateUrl('apps/audios/cropphoto'),
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
					minSize : [230, 140],
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
            $('#noimage').text(t('audios', 'Picture generating, wait ...')).addClass('icon-loading');
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
				OC.dialogs.alert(response.message, t('audios', 'Error'));
				wrapper.removeClass('wait');
			}
		}.bind(this));
};

Audios.prototype.deletePhoto = function() {
			
		$('#isphoto').val('0');
		this.imgSrc = false;
		$('#pin_details_photo').remove();
		$('<div/>').attr('id', 'noimage').text(t('audios', 'Drag Image Here!')).prependTo($(' #pinPhoto'));
		$('#imgsrc').val('');
		this.loadPhotoHandlers();
	
};

Audios.prototype.openImportDialog = function() {
			
		$('body').append('<div id="audios_import"></div>');
			$('#audios_import').load(OC.generateUrl('apps/audios/getimporttpl'),function(){
					this.scanInit();
			}.bind(this));
	
};
Audios.prototype.scanInit = function() {
	
	var $this = this;
	$('#audios_import_dialog').dialog({
		width : 500,
		resizable: false,
		close : function() {
			//OC.ContactsPlus.Import.Dialog.close();
			$this.progresskey = '';
			$this.percentage = 0;
			$('#audios_import_dialog').dialog('destroy').remove();
			$('#audios_import_dialog').remove();
		}
	});
	
	$('#audios_import_done').click(function(){
		$this.progresskey = '';
		$this.percentage = 0;
		$('#audios_import_dialog').dialog('destroy');
		$('#audios_import_dialog').remove();
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
	window.setTimeout('myAudios.scanUpdate()', 100);
};
Audios.prototype.scanSend = function() {
	
	$.post(OC.generateUrl('apps/audios/scanforaudiofiles'),
		{progresskey: this.progresskey},  function(data){
			if(data.status == 'success'){
				$('#audios_import_progressbar').progressbar('option', 'value', 100);
				$('#audios_import_progressbar > div').css('background-color', '#FF2626');
				this.percentage = 100;
				$('#audios_import_progressbar').hide();
				$('#audios_import_done').css('display', 'block');
				$('#audios_import_status').html(data.message);
				$('#audios_import_process_message').text('').hide();
				this.loadAlbums();
			}else{
				$('#audios_import_progressbar').progressbar('option', 'value', 100);
				$('#audios_import_progressbar > div').css('background-color', '#FF2626');
				$('#audios_import_status').html(data.message);
			}
		}.bind(this));
};
Audios.prototype.scanUpdate = function() {
	if(this.percentage === 100){
		
		return false;
	}
	
	$.post(OC.generateUrl('apps/audios/scanforaudiofiles'),
	 {progresskey: this.progresskey, getprogress: true}, function(data){
		if(data.status == 'success'){
			if(data.percent === null){
				return false;
			}
			
			this.percentage = parseInt(data.percent);
			$('#audios_import_progressbar').progressbar('option', 'value', parseInt(data.percent));
			$('#audios_import_progressbar > div').css('background-color', '#FF2626');
			$('#audios_import_process_message').text(data.currentmsg);
			if(data.percent < 100 ){
				window.setTimeout('myAudios.scanUpdate()', 100);
				
			}else{
				$('#audios_import_progressbar').progressbar('option', 'value', 100);
				$('#audios_import_progressbar > div').css('background-color', '#FF2626');
				$('#audios_import_done').css('display', 'block');
				
			}
		}else{
		
			$('#audios_import_progressbar').progressbar('option', 'value', 100);
			$('#audios_import_progressbar > div').css('background-color', '#FF2626');
			$('#audios_import_status').html(data.message);
			
		}
	}.bind(this));
	return 0;
};

var resizeTimeout = null;
$(window).resize(_.debounce(function() {
	if (resizeTimeout)
		clearTimeout(resizeTimeout);
	resizeTimeout = setTimeout(function() {
		if($(window).width()>768){
			$('.sm2-bar-ui.fixed').width(myAudios.AlbumContainer.width());
		}else{
			$('.sm2-bar-ui.fixed').width(myAudios.AlbumContainer.width()-45);
		}
		
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
	var AlbumId='';
	var PlaylistId='';
	if(locHash !== ''){
		var locHashTemp = locHash.split('show-playlist-');
		if(locHashTemp[1] !== undefined){
			PlaylistId = locHashTemp[1];
		}else{
			locHashTemp = locHash.split('show-album-');
			if(locHashTemp[1] !== undefined){
				AlbumId = 'album-'+locHashTemp[1];
			}
		}
	}
	
	if(PlaylistId !=='' && PlaylistId > 0){
		$('#myPlayList li').removeClass('activeIndiPlaylist');
		$('#myPlayList li[data-id="'+PlaylistId+'"]').addClass('activeIndiPlaylist');
			myAudios.loadIndividualPlaylist();
	
	}
	
	if(AlbumId !== ''){
		evt={};
		evt.albumId = AlbumId;
		myAudios.AlbumContainer.show();
		myAudios.PlaylistContainer.hide();
		myAudios.AlbumClickHandler(evt);
		
	}

};	



$(document).ready(function() {

		myAudios = new Audios();
		myAudios.init();
		
		if($(window).width()>768){
			$('.sm2-bar-ui.fixed').width(myAudios.AlbumContainer.width());
		}else{
			$('.sm2-bar-ui.fixed').width(myAudios.AlbumContainer.width()-45);
		}
	
	
	
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
			url : OC.generateUrl('apps/audios/clearphotocache'),
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
	
		  
	$('#newPlaylist').on('click',function(){
		if($('#newPlaylistTxt').val() != ''){
			myAudios.newPlaylist($('#newPlaylistTxt').val());
			$('#newPlaylistTxt').val('');
		}
	});
	
	$('#newPlaylistTxt').bind('keydown', function(event){
		if (event.which == 13 && $('#newPlaylistTxt').val() != ''){
			myAudios.newPlaylist($('#newPlaylistTxt').val());
			$('#newPlaylistTxt').val('');
			$('#newPlaylistTxt').focus();
		}
	});
	
	
	$('#alben').addClass('bAktiv');
	$('#alben').on('click',function(){
		if(	$('.sm2-bar-ui').hasClass('playing')){
			//myAudios.AudioPlayer.actions.play(0);
			//myAudios.AudioPlayer.actions.stop();
		}
		$(this).addClass('bAktiv');
		myAudios.AlbumContainer.show();
		myAudios.PlaylistContainer.hide();
		window.location.href='#';
	});
	$(document).on('click', '#resetAudios', function () {
		$("#dialogSmall").text(t('audios', 'Are you sure? All music database entries will be deleted!'));
		$("#dialogSmall").dialog({
			resizable : false,
			title : t('audios', 'Reset Media Library'),
			width : 250,
			modal : true,
			buttons : [{
				text : t('audios', 'No'),
			click : function() {
					$("#dialogSmall").html('');
					$(this).dialog("close");
				}
			}, {
				text : t('audios', 'Yes'),
				click : function() {
					var oDialog = $(this);
					
					if(	$('.sm2-bar-ui').hasClass('playing')){
						myAudios.AudioPlayer.actions.play(0);
						myAudios.AudioPlayer.actions.stop();
					}
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
					$('#notification').text(t('audios','Start deleting and resetting media library ...'));
					$('#notification').slideDown();
					
					$.ajax({
							type : 'GET',
							url : OC.generateUrl('apps/audios/resetmedialibrary'),
							success : function(jsondata) {
									if(jsondata.status === 'success'){
										myAudios.loadAlbums();
										$('#notification').text(t('audios','Resetting finished!'));
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
	});
	
	$(document).on('click', '#scanAudios, #scanAudiosFirst', function () {
		
		if(	$('.sm2-bar-ui').hasClass('playing')){
			myAudios.AudioPlayer.actions.play(0);
			myAudios.AudioPlayer.actions.stop();
		}
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
		//$('#notification').text(t('audios','Start scanning ...'));
		//$('#notification').slideDown();
		
		myAudios.openImportDialog();
		/*
		$.ajax({
				type : 'GET',
				url : OC.generateUrl('apps/audios/scanforaudiofiles'),
				success : function(jsondata) {
					 if(jsondata.status === 'success'){
						 var count = jsondata.counter;
						 myAudios.loadAlbums();
						 $('#notification').text(t('audios','Scanning finished! New Audios found!'+' ('+count+')'));
						window.setTimeout(function(){$('#notification').slideUp();}, 3000);
					}
				}
		});*/
		
		return false;
	});
	
	
	
});

