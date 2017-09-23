/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2017 Marcel Scherello
 */

$(document).ready(function() {

	$('#cyrillic_user').on('click', function() {
		if ($('#cyrillic_user').prop('checked')) {
			var user_value = 'checked';
		}
		else {
			var user_value = '';
		}
    		$.ajax({ 
				type : 'GET',
				url : OC.generateUrl('apps/audioplayer/setvalue'),
				data : {'type': 'cyrillic',
						'value': user_value},
				success : function(ajax_data) {
					$('#notification').text('saved');
					$('#notification').slideDown();
					window.setTimeout(function(){$('#notification').slideUp();}, 3000);	
				}
			});
	});
	
		/*
	 * Collection path
	 */
	var $path = $('#audio-path');
	$path.on('click', function() {
		OC.dialogs.filepicker(
			t('audioplayer', 'Single folder of audio files'),
			function (path) {
				if ($path.val() !== path) {
					$path.val(path);
					$.post(OC.generateUrl('apps/audioplayer/userpath'), { value: path }, function(data) {
						if (!data.success) {
							$('#notification').text('Invalid Path');
							$('#notification').slideDown();
							window.setTimeout(function(){$('#notification').slideUp();}, 3000);	
						} else {
							$('#notification').text('saved');
							$('#notification').slideDown();
							window.setTimeout(function(){$('#notification').slideUp();}, 3000);	
						}
					});
				}
			},
			false,
			'httpd/unix-directory',
			true
		);
	});

	$(document).on('click', '#scanAudios, #scanAudiosFirst', function () {
		$('#newPlaylist').addClass('ap_hidden');
		if(	$('.sm2-bar-ui').hasClass('playing')){
			myAudios.AudioPlayer.actions.play(0);
			myAudios.AudioPlayer.actions.stop();
		}
		$("#category_selector").val('');
		$('#myCategory').html('');
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
					$('#alben').addClass('active');
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

	var audioPlayer = {}
	soundManager.setup({
		url:OC.filePath('audioplayer', 'js', 'soundmanager2.swf'),
		onready: function() {
			audioPlayer.player = soundManager.createSound({});
			var can_play = soundManager.html5;
			var supported_types = '';
			var nsupported_types = '';
			for (var mtype in can_play){
		   		var mtype_check = can_play[mtype];
				if (mtype.substring(5, 6) !== '/' && mtype !== 'usingFlash' && mtype !== 'canPlayType') {

					if (mtype_check === true) {
						var supported_types = supported_types + mtype + ', ';
					} else {
						var nsupported_types = nsupported_types + mtype + ', ';
					}
				}
			}
			$('#browser_yes').html(supported_types);
			$('#browser_no').html(nsupported_types);
		},
	});

    $.ajax({
		type : 'GET',
		url : OC.generateUrl('apps/audioplayer/getvalue'),
		data : {'type':'path'},
		success : function(jsondata) {
			if(jsondata.status === 'success') {
				$('#audio-path').val(jsondata.value);
			}
		}
	});
	    $.ajax({
		type : 'GET',
		url : OC.generateUrl('apps/audioplayer/getvalue'),
		data : {'type':'cyrillic'},
		success : function(jsondata) {
			if(jsondata.status === 'success' && jsondata.value === 'checked') {
				$('#cyrillic_user').prop('checked', true);
			}
		}
	});
	
});
