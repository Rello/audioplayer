/**
 * ownCloud - mp3_player
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

var audioPlayer = {
	mime : null,
	file : null,
	location : null,
	player : null,
	dir: null,
	onView : function(file, data) {
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
			audioPlayer.location = OC.generateUrl('apps/mp3_player/getpublicaudiostream{file}?token={token}',{'file':dirLoad+file,'token':token},{escape:false});
		}else{
			audioPlayer.location = OC.generateUrl('apps/mp3_player/getaudiostream?file={file}',{'file':dirLoad+file},{escape:false});

		}
		audioPlayer.mime = data.$file.attr('data-mime');
		data.$file.find('.thumbnail').html('<i class="ioc ioc-pause"  style="color:#fff;margin-left:10px; text-align:center;line-height:32px;"></i><i class="ioc ioc-play"  style="display:none;color:#fff;margin-left:10px; text-align:center;line-height:32px;"></i>');
			
		if(audioPlayer.player == null){
			soundManager.setup({
			  url:OC.filePath('mp3_player', 'js', 'soundmanager2.swf'),
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
			$('#filestable').find('.thumbnail i.ioc-pause').hide();
			$('#filestable').find('.thumbnail i.ioc-play').show();
			audioPlayer.player=null;
		}
		
		
		
	},
};
$(document).ready(function() {	
	if (OCA.Files && OCA.Files.fileActions) {
		
		OCA.Files.fileActions.register('audio/mpeg', 'View', OC.PERMISSION_READ, '', audioPlayer.onView);
		OCA.Files.fileActions.setDefault('audio/mpeg', 'View');
		
		if($('#header').hasClass('share-file')){
			
			var token = ($('#sharingToken').val() !== undefined) ? $('#sharingToken').val() : '';
			var mimeType=$('#mimetype').val();
			if(mimeType === 'audio/mpeg'){
			
			OC.addStyle('mp3_player','360player');
			OC.addStyle('mp3_player','360player-visualization');	
			
				
			$('#imgframe').css({'width':'250px'});
			if( $('#previewSupported').val() !== 'true'){
				$('#imgframe').hide();
			}
			//$('#imgframe').append($('<br style="clear:both;" />'));
			var fileName=$('#filename').val();
			var audioUrl= OC.generateUrl('apps/mp3_player/getpublicaudiostream{file}?token={token}',{'file':fileName,'token':token},{escape:false});
			var audioContainer=$('<div/>').attr('id','sm2-container');
			
			$('#preview').before(audioContainer);
			var audioOuterDiv=$('<div>').addClass('outer-audioplayer').css('clear','both');
			var audioInnerDiv=$('<div>').addClass('ui360 ui360-vis');
			var audioLink=$('<a/>').attr({
				'href':audioUrl
			}).text(fileName);
			audioInnerDiv.append(audioLink);
			audioOuterDiv.append(audioInnerDiv);
			audioContainer.append(audioOuterDiv);
			OC.addScript('mp3_player','berniecode-animator',function(){
				OC.addScript('mp3_player','360player',function(){
					soundManager.setup({
					  url:'./apps/mp3_player/js/',
					 });
					 
				});
			});
				
		}
			
			
			 
		}
		//$(document).keydown(videoCoolViewer.onKeyDown);
	}
});
