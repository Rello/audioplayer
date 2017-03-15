<div id="audios_import_dialog" title="<?php p($l->t("Scan for audio files"));?>">
<div id="audios_import_form">
	<form action=" " onsubmit="return false;" >
		<input type="hidden" id="audios_import_progresskey" value="<?php p('audioplayer-scan-' .time()) ?>">
			<br><br>
		<input id="audios_import_submit" type="button" class="button" value="<?php p($l->t('Start scanning ...')); ?>" id="startimport">
	<form>
</div>
<div id="audios_import_process">
	<div id="audios_import_process_progress"></div>
	<div id="audios_import_process_message"></div>
	<br>
	<div id="audios_import_progressbar"></div>
	<br>
	<input id="audios_import_progress_cancel" type="button" class="button" value="<?php p($l->t('Cancel')); ?>">
</div>
<div id="audios_import_done">
	<div id="audios_import_done_message" class="hint"></div>
	<br>
	<input id="audios_import_done_close" type="button" class="button" value="<?php p($l->t('Close')); ?>">
</div>

</div>
