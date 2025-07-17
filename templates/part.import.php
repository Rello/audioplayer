<template id="templateScanDialog">
    <div id="audios_import_dialog" title="<?php p($l->t("Scan for audio files")); ?>">
        <div id="audios_import_form">
                <input id="audios_import_submit" type="button" class="button"
                       value="<?php p($l->t('Start scanning …')); ?>" id="startimport">
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
</template>