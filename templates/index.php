<?php 
	style('audioplayer', '3rdparty/fontello/css/animation');	
	style('audioplayer', '3rdparty/fontello/css/fontello');
	style('audioplayer', 'jquery.Jcrop');	
	style('audioplayer','bar-ui');
	style('audioplayer', 'style');
	script('files', 'jquery.fileupload');
	script('audioplayer', 'jquery.Jcrop');
	script('core','tags');
	script('audioplayer', 'soundmanager2-nodebug-jsmin'); 
	script('audioplayer', 'bar-ui');
	script('audioplayer', 'app');
?>
<form style="display:none;" class="float" id="file_upload_form" action="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute('audioplayer.photo.uploadPhoto')); ?>" method="post" enctype="multipart/form-data" target="file_upload_target">
	<input type="hidden" name="id" value="">
	<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php p($_['uploadMaxFilesize']) ?>" id="max_upload">
	<input type="hidden" class="max_human_file_size" value="(max <?php p($_['uploadMaxHumanFilesize']); ?>)">
	<input id="pinphoto_fileupload" type="file" accept="image/*" name="imagefile" />
</form>
<iframe style="display:none;" name="file_upload_target" id='file_upload_target' src=""></iframe>

<div id="app-navigation" class="mp3_hide">
	<div class="innerNav">
		<h3></h3>
		<ul id="albenoverview">
			<li>
				<span id="alben" style="vertical-align: top; font-size: 15px;">
				<img class="svg" src="<?php echo \OC::$server->getURLGenerator()->imagePath('audioplayer','albums.svg'); ?>" style="width: 18px; padding-top: 3px;">
				<?php p($l->t('Albums'));?></span>  
				<i class="ioc ioc-delete toolTip" title="<?php p($l->t('Reset music library'));?>" id="resetAudios"></i>
				<i class="ioc ioc-refresh toolTip" title="<?php p($l->t('Scan for new audio files'));?>" id="scanAudios"></i>
			</li>
		</ul>
		<br>&nbsp;<br>
		<select id="category_selector">
	  		<option value=""selected><?php p($l->t('Selection'));?></option>
 	 		<option value="Playlist"><?php p($l->t('Playlists'));?></option>
 	 		<option value="Artist"><?php p($l->t('Artists'));?></option>
 	 		<option value="Album"><?php p($l->t('Albums'));?></option>
 	 		<option value="Title"><?php p($l->t('Titles'));?></option>
 	 		<option value="Genre"><?php p($l->t('Genres'));?></option>
 	 		<option value="Year"><?php p($l->t('Years'));?></option>
 	 		<option value="Folder"><?php p($l->t('Folders'));?></option>
		</select>
		<button  class="icon-add mp3_hide" id="addPlaylist"></button>
		<ul id="myCategory"></ul>	
		<!--my playlist clone -->	
		<li class="app-navigation-entry-edit plclone" id="pl-clone" data-pl="">
			<input type="text" name="playlist" id="playlist" value=""  />
				<button class="icon-checkmark"></button>
				<button class="icon-close"></button>
		</li>	
		<!--my playlist clone -->
		<div class="app-navigation-entry-edit mp3_hide" id="newPlaylist">
			<input type="text" name="newPlaylistTxt" id="newPlaylistTxt" placeholder="<?php p($l->t('Create new playlist'));?>" /> 
			<button class="icon-checkmark" id="newPlaylistBtn_ok"></button>
			<button class="icon-close" id="newPlaylistBtn_cancel"></button>
		</div>
	</div>
</div>	
<div id="app-content">
	<div id="loading">
		<i class="ioc-spinner ioc-spin"></i>
	</div>

	<div class="sm2-bar-ui full-width">
		<div class="bd sm2-main-controls">
			<div class="sm2-inline-texture"></div>
			<div class="sm2-inline-gradient"></div>
  
			<div class="sm2-inline-element sm2-button-element">
				<div class="sm2-button-bd" id="toggle_alternative"></div>
			</div>

			<div class="sm2-inline-element sm2-button-element">
				<div class="sm2-button-bd">
					<a href="#prev" class="sm2-inline-button previous"><?php p($l->t('previous song'));?></a>
				</div>
			</div>
  
			<div class="sm2-inline-element sm2-button-element">
				<div class="sm2-button-bd">
					<a href="#play" class="sm2-inline-button play-pause"><?php p($l->t('play/ pause'));?></a>
				</div>
			</div>
  
			<div class="sm2-inline-element sm2-button-element">
				<div class="sm2-button-bd">
					<a href="#next" class="sm2-inline-button next"><?php p($l->t('next song'));?></a>
				</div>
			</div>
  
			<div class="sm2-inline-element sm2-button-element">
				<div class="sm2-playlist-cover"></div>
			</div>

			<div class="sm2-inline-element sm2-inline-status">
				<div class="sm2-playlist">
					<div class="sm2-playlist-target">
     					<!-- playlist <ul> + <li> markup will be injected here -->
     					<!-- if you want default / non-JS content, you can put that here. -->
     					<noscript><p>JavaScript is required.</p></noscript>
    				</div>
   				</div>

				<div class="sm2-progress">
					<div class="sm2-row">
						<div class="sm2-inline-time">0:00</div>
						<div class="sm2-progress-bd">
							<div class="sm2-progress-track">
								<div class="sm2-progress-bar"></div>
								<div class="sm2-progress-ball">
									<div class="icon-overlay"></div>
								</div>
							</div>
						</div>
						<div class="sm2-inline-duration">0:00</div>
					</div>
				</div>
			 </div>

			<div class="sm2-inline-element sm2-button-element sm2-volume">
				<div class="sm2-button-bd">
					<span class="sm2-inline-button sm2-volume-control volume-shade"></span>
					<a href="#volume" title="<?php p($l->t('Volume'));?>" class="toolTip sm2-inline-button sm2-volume-control">volume</a>
				</div>
			</div>
 
			<div class="sm2-inline-element sm2-button-element sm2-repeat">
				<div class="sm2-button-bd">
					<a href="#repeat" title="<?php p($l->t('Repeat playlist'));?>" class="toolTip sm2-inline-button repeat">&infin; repeat</a>
				</div>
			</div>
			
			<div class="sm2-inline-element sm2-button-element sm2-shuffle">
				<div class="sm2-button-bd">
					<a href="#shuffle" title="<?php p($l->t('Shuffle playlist'));?>" class="toolTip sm2-inline-button shuffle">shuffle</a>
				</div>
			</div>
		</div>

		<div class="bd sm2-playlist-drawer sm2-element">
			<div class="sm2-inline-texture">
				<div class="sm2-box-shadow"></div>
			</div>
 			<!-- playlist content is mirrored here -->
			<div class="sm2-playlist-wrapper">
				<ul class="sm2-playlist-bd " id="activePlaylist">
				</ul>
			</div>
		</div>
	</div>

	<div id="searchresults" class="hidden" data-appfilter="audioplayer"></div>

	<div id="audios-audioscontainer"></div>
	<div id="individual-playlist-container" class="albumwrapper" data-playlist="">
		<span id="individual-playlist-info"></span>
	  	<span id="individual-playlist-header">
 	 		<span class="header-indi">
 	 			<span class="header-num">#</span>
  				<span class="header-title"><?php p($l->t('Title'));?></span>
  				<span class="header-artist"><?php p($l->t('Artist'));?></span>
  				<span class="header-album"><?php p($l->t('Album'));?></span>
				<span class="header-time"><?php p($l->t('Length'));?></span>
  				<span class="header-opt">&nbsp;</span>
  			</span>
  		</span>
  		<br style="clear:both;" />
  		<ul id="individual-playlist"></ul>
  	</div>
</div>
 
<div id="dialogSmall" style="width:0;height:0;top:0;left:0;display:none;"></div>
<div id="edit_photo_dialog" title="Edit photo">
	<div id="edit_photo_dialog_img"></div>
</div>
