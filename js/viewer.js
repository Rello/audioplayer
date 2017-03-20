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

var audioPlayer = {
	mime : null,
	file : null,
	location : null,
	player : null,
	dir: null,
	onView : function(file, data) {
		file = encodeURIComponent(file);
		audioPlayer.file = file;
		audioPlayer.dir = data.dir;
		//sharingToken
		var token = ($('#sharingToken').val() !== undefined) ? $('#sharingToken').val() : '';
		//.thumbnail
		var dirLoad=data.dir.substr(1);
		if(dirLoad!=''){
			dirLoad=dirLoad+'/';
		}
		if(token !== ''){
			audioPlayer.location = OC.generateUrl('apps/audioplayer/getpublicaudiostream?token={token}&file={file}',{'token':token, 'file':dirLoad+file},{escape:false});
		}else{
			audioPlayer.location = OC.generateUrl('apps/audioplayer/getaudiostream?file={file}',{'file':dirLoad+file},{escape:false});

		}
		audioPlayer.mime = data.$file.attr('data-mime');
		data.$file.find('.thumbnail').html('<i class="ioc ioc-volume-up"  style="color:#fff;margin-left:5px; text-align:center;line-height:32px;text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;font-size: 24px;"></i>');
			
		if(audioPlayer.player == null){
			soundManager.setup({
			  url:OC.filePath('audioplayer', 'js', 'soundmanager2.swf'),
		  onready: function() {
			    audioPlayer.player = soundManager.createSound({
			      id:data.$file.attr('data-id'),
			      url: audioPlayer.location
			    });
			    
			    	audioPlayer.player.play();
			  
			  },
			  ontimeout: function() {
			    // Hrmm, SM2 could not start. Missing SWF? Flash blocked? Show an error, etc.?
			  }
			});
		}else{
			audioPlayer.player.stop();
			$('#filestable').find('.thumbnail i.ioc-volume-up').hide();
			//$('#filestable').find('.thumbnail i.ioc-play').show();
			audioPlayer.player=null;
		}
		
		
		
	},
};
$(document).ready(function() {	
	if (OCA.Files && OCA.Files.fileActions) {
		
	var mime_array = ['audio/mpeg', 'audio/mp4', 'audio/m4b', 'audio/ogg', 'audio/wav'];
		
		for (var i=0; i<mime_array.length; i++) {
				
			OCA.Files.fileActions.registerAction({
				name: 'audioplayer play',
				displayName: t('audioplayer', 'Play'),
				mime: mime_array[i],
				permissions: OC.PERMISSION_READ,
				icon: function () {return OC.imagePath('core', 'actions/sound');},
				actionHandler: audioPlayer.onView
			});

			OCA.Files.fileActions.register(mime_array[i], 'View', OC.PERMISSION_READ, '', audioPlayer.onView);
			OCA.Files.fileActions.setDefault(mime_array[i], 'View');

		}//end mime-loop

		if($('#header').hasClass('share-file')){
			
			var token = ($('#sharingToken').val() !== undefined) ? $('#sharingToken').val() : '';
			var mimeType=$('#mimetype').val();
			if(mime_array.indexOf(mimeType) !== -1){
			
			OC.addStyle('audioplayer','360player');
			OC.addStyle('audioplayer','360player-visualization');	
			
			setTimeout(function(){ $('.publicpreview').css({'margin-top':'0px'}); }, 2000);	
			$('#imgframe').css({'max-width':'250px'});
			$('#imgframe').css({'margin-bottom':'0px'});
			
			if( $('#previewSupported').val() !== 'true'){
				$('#imgframe').hide();
			}
			var fileName=$('#filename').val();
			fileName = encodeURIComponent(fileName);
			var audioUrl= OC.generateUrl('apps/audioplayer/getpublicaudiostream?token={token}&file={file}',{'token':token, 'file':fileName},{escape:false});
			var audioContainer=$('<div/>').attr('id','sm2-container');
			
			$('#preview').before(audioContainer);
			var audioOuterDiv=$('<div>').addClass('outer-audioplayer').css('clear','both');
			var audioInnerDiv=$('<div>').addClass('ui360 ui360-vis');
			var audioLink=$('<a/>').attr({
				'href':audioUrl
			}).text($('#filename').val()).css('visibility','hidden');
			audioInnerDiv.append(audioLink);
			audioOuterDiv.append(audioInnerDiv);
			audioContainer.append(audioOuterDiv);
			OC.addScript('audioplayer','berniecode-animator',function(){
				OC.addScript('audioplayer','360player',function(){
					soundManager.setup({
					  url:'./apps/audioplayer/js/',
					 });
					 
				});
			});
			
			$('#imgframe').before($('<div/>').attr('id','id3'));
				url = OC.generateUrl('apps/audioplayer/getpublicaudioinfo{file}?token={token}',{'file':fileName,'token':token},{escape:false});
				
				$.ajax({
					type : 'GET',
					url : url,
					success : function(jsondata) {
						if(jsondata.status == 'success'){
							var playlistsdata=jsondata.data;
							//$(".directLink").remove();
							//$(".directDownload").remove();
							$('#content-wrapper').css({'padding-top':'0px'});
							$('#id3').append('<div>&nbsp;</div>');
							$('#id3').append('<div><b>'+t('audioplayer','Title')+':</b>&nbsp;'+ jsondata.data.title +'</div>');
							$('#id3').append('<div><b>'+t('audioplayer','Artist')+':</b>&nbsp;'+ jsondata.data.artist +'</div>');
							$('#id3').append('<div><b>'+t('audioplayer','Album')+':</b>&nbsp;'+ jsondata.data.album +'</div>');
							$('#id3').append('<div><b>'+t('audioplayer','Genre')+':</b>&nbsp;'+ jsondata.data.genre +'</div>');
							$('#id3').append('<div><b>'+t('audioplayer','Year')+':</b>&nbsp;'+ jsondata.data.year +'</div>');
							$('#id3').append('<div><b>'+t('audioplayer','Length')+':</b>&nbsp;'+ jsondata.data.length +'</div>');
							$('#id3').append('<div><b>'+t('audioplayer','Bitrate')+':</b>&nbsp;'+ jsondata.data.bitrate +'&nbsp;kbps</div>');
							//$('#id3').append('<div><b>'+t('audioplayer','Composer')+':</b>&nbsp;'+ jsondata.data.composer +'</div>');
							$('#imgframe').css({'padding-top':'20px'});
							$('#imgframe').css({'padding-bottom':'0px'});
							$('.directDownload').css({'padding-top':'20px'});
							$('.directLink').css({'padding-top':'0px'});
						}
					}
				});
		}
			
	
			 
		}
		//$(document).keydown(videoCoolViewer.onKeyDown);
	}
});
