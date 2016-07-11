<div id="audios_import_dialog" title="<?php p($l->t("Scan for audio files"));?>">
<div id="audios_import_form">
	<form action=" " onsubmit="return false;" >
		<input type="hidden" id="audios_import_progresskey" value="<?php p('mp3_player-import-' .time()) ?>">
			<br><br>
		<input id="audios_import_submit" type="button" class="button" value="<?php p($l->t('Start scanning ...')); ?>" id="startimport">
	<form>
</div>
<div id="audios_import_process">
	<div id="audios_import_process_message"></div>
	<div  id="audios_import_progressbar"></div>
	<br>
	<div id="audios_import_status" class="hint"></div>
	<br>
	<input id="audios_import_done" type="button" value="<?php p($l->t('Close')); ?>">
</div>
</div>
