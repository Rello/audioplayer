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

$(document).ready(function() {
    if ($('#header').hasClass('share-file')) {
        var mime_array = ['audio/mpeg', 'audio/mp4', 'audio/m4b', 'audio/ogg', 'audio/wav', 'audio/flac'];

        var token = ($('#sharingToken').val() !== undefined) ? $('#sharingToken').val() : '';
        var mimeType=$('#mimetype').val();
        if (mime_array.indexOf(mimeType) !== -1){

            setTimeout(function(){ $('.publicpreview').css({'margin-top':'0px'}); }, 2000);
            $('#imgframe').css({'max-width':'250px'});
            $('#imgframe').css({'margin-bottom':'0px'});

            if( $('#previewSupported').val() !== 'true'){
                $('#imgframe').hide();
            }
            var fileName = $('#filename').val();
            fileName = encodeURIComponent(fileName);
            var audioUrl= OC.generateUrl('apps/audioplayer/getpublicaudiostream?token={token}&file={file}',{'token':token, 'file':fileName},{escape:false});
            var audioContainer=$('<div/>').attr('id','sm2-container');

            $('#preview').before(audioContainer);
            var audioOuterDiv=$('<div>').addClass('outer-audioplayer').css('clear','both');
            var audioInnerDiv=$('<div>').addClass('ui360 ui360-vis');
            var audioLink=$('<a/>').attr({'href':audioUrl}).text($('#filename').val()).css('visibility','hidden');
            audioInnerDiv.append(audioLink);
            audioOuterDiv.append(audioInnerDiv);
            audioContainer.append(audioOuterDiv);

            soundManager.setup({
                url:'./apps/audioplayer/js/',
                onready: function() {
                    var can_play = soundManager.html5;
                    if (can_play[mimeType] !== true) {
                        $('.outer-audioplayer').html('<b><i>' + t('audioplayer','MIME type not supported by browser') + '</i></b>');
                    }
                }
            });

            $('#imgframe').before($('<div/>').attr('id','id3'));
            url = OC.generateUrl('apps/audioplayer/getpublicaudioinfo?token={token}',{'token':token},{escape:false});

            $.ajax({
                type : 'GET',
                url : url,
                success : function(jsondata) {
                    if(jsondata.status === 'success'){
                        var playlistsdata=jsondata.data;
                        $('#content-wrapper').css({'padding-top':'0px'});
                        var $id3 = $('#id3');
                        $id3.append('<div>&nbsp;</div>');
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer','Title')+': ')).append($('<span/>').text(jsondata.data.title)));
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer','Subtitle')+': ')).append($('<span/>').text(jsondata.data.subtitle)));
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer','Artist')+': ')).append($('<span/>').text(jsondata.data.artist)));
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer','Composer')+': ')).append($('<span/>').text(jsondata.data.composer)));
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer','Album')+': ')).append($('<span/>').text(jsondata.data.album)));
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer','Genre')+': ')).append($('<span/>').text(jsondata.data.genre)));
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer','Year')+': ')).append($('<span/>').text(jsondata.data.year)));
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer','Disc')+'-'+t('audioplayer','Track')+': ')).append($('<span/>').text(jsondata.data.disc +'-'+ jsondata.data.number)));
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer','Length')+': ')).append($('<span/>').text(jsondata.data.length)));
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer','Bitrate')+': ')).append($('<span/>').text(jsondata.data.bitrate+' kbps')));
                        $id3.append($('<div/>').append($('<b/>').text(t('audioplayer','MIME type')+': ')).append($('<span/>').text(jsondata.data.mimetype)));
                        $('#imgframe').css({'padding-top':'20px'});
                        $('#imgframe').css({'padding-bottom':'0px'});
                        $('.publicpreview').css({'max-width':'0px !important'});
                        $('.directDownload').css({'padding-top':'20px'});
                        $('.directLink').css({'padding-top':'0px'});
                    }
                }
            });
        }
    }
});