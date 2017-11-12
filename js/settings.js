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
	var user_value;
		if ($('#cyrillic_user').prop('checked')) {
			user_value = 'checked';
		}
		else {
			user_value = '';
		}
    		$.ajax({ 
				type : 'GET',
				url : OC.generateUrl('apps/audioplayer/setvalue'),
				data : {'type': 'cyrillic',
						'value': user_value},
				success : function() {
                    OC.Notification.showTemporary(t('audioplayer','saved'));
				}
			});
	});
	
		/*
	 * Collection path
	 */
	var $path = $('#audio-path');
	$path.on('click', function() {
		OC.dialogs.filepicker(
			t('audioplayer', 'Select a single folder with audio files'),
			function (path) {
				if ($path.val() !== path) {
					$path.val(path);
					$.post(OC.generateUrl('apps/audioplayer/userpath'), { value: path }, function(data) {
						if (!data.success) {
                            OC.Notification.showTemporary(t('audioplayer','Invalid path!'));
						} else {
                            OC.Notification.showTemporary(t('audioplayer','saved'));
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
		myAudios.openScannerDialog();
	});

    $(document).on('click', '#resetAudios', function () {
        myAudios.openResetDialog();
    });

	var audioPlayer = {};
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
						supported_types = supported_types + mtype + ', ';
					} else {
						nsupported_types = nsupported_types + mtype + ', ';
					}
				}
			}
			$('#browser_yes').html(supported_types);
			$('#browser_no').html(nsupported_types);
		}
	});
});

Audios.prototype.openResetDialog = function() {
	OC.dialogs.message(
		t('audioplayer','Are you sure?')+' '+t('audioplayer','All library entries will be deleted!'),
        t('audioplayer', 'Reset library'),
		null,
		OCdialogs.YES_NO_BUTTONS,
		function (e) {
			if (e === true) {
				myAudios.resetLibrary();
			}
		},
		true
	);
};

Audios.prototype.resetLibrary = function() {
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
    OC.Notification.showTemporary(t('audioplayer','New audio files available'));

    $.ajax({
        type : 'GET',
        url : OC.generateUrl('apps/audioplayer/resetmedialibrary'),
        success : function(jsondata) {
            if(jsondata.status === 'success'){
                myAudios.loadAlbums();
                OC.Notification.showTemporary(t('audioplayer','Resetting finished!'));
            }
        }
    });
    $('#myCategory').html('');
};

Audios.prototype.openScannerDialog = function() {
    $('body').append('<div id="audios_import"></div>');
    $('#audios_import').load(OC.generateUrl('apps/audioplayer/getimporttpl'),function(){
        this.scanInit();
    }.bind(this));
};

Audios.prototype.scanInit = function() {

    var $this = this;
    $('#audios_import_dialog').ocdialog({
        width : 500,
        modal: true,
        resizable: false,
        close : function() {
            $this.scanStop($this.progresskey);
            $('#audios_import_dialog').ocdialog('destroy');
            $('#audios_import').remove();
        }
    });

    $('#audios_import_done_close').click(function(){
        $this.progresskey = '';
        $this.percentage = 0;
        $('#audios_import_dialog').ocdialog('close');
    });

    $('#audios_import_progress_cancel').click(function(){
        $this.scanStop($this.progresskey);
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
    window.setTimeout(myAudios.scanUpdate(), 1500);
};

Audios.prototype.scanSend = function() {
    $.post(OC.generateUrl('apps/audioplayer/scanforaudiofiles'),
        {progresskey: this.progresskey},  function(data){
            if(data.status === 'success'){
                $this.progresskey = '';
                $('#audios_import_process').css('display', 'none');
                $('#audios_import_done').css('display', 'block');
                $('#audios_import_done_message').html(data.message);

                $this.get_uservalue('category', function() {
                    if ($this.category_selectors[0] && $this.category_selectors[0]!== 'Albums') {
                        $("#category_selector").val($this.category_selectors[0]);
                        $this.loadCategory();
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
    $this.progresskey = '';
    $this.percentage = 0;
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
            if(data.status === 'success'){
                this.percentage = parseInt(data.percent);
                $('#audios_import_progressbar').progressbar('option', 'value', parseInt(data.percent));
                $('#audios_import_process_progress').text(data.prog);
                $('#audios_import_process_message').text(data.msg);
                if(data.percent < 100 ){
                    window.setTimeout(myAudios.scanUpdate(), 1500);
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