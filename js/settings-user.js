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

});
