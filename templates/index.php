<?php 
	style('audios', '3rdparty/fontello/css/animation');	
	style('audios', '3rdparty/fontello/css/fontello');
	style('audios', 'jquery.Jcrop');	
	style('audios','bar-ui');
	style('audios', 'style');
	script('files', 'jquery.fileupload');
	script('audios', 'jquery.Jcrop');
	script('core','tags');
	script( 'audios', 'soundmanager2'); 
	script( 'audios', 'bar-ui');
	script( 'audios', 'app' );
	
?>
<form style="display:none;" class="float" id="file_upload_form" action="<?php print_unescaped(\OCP\Util::linkToRoute('audios.photo.uploadPhoto')); ?>" method="post" enctype="multipart/form-data" target="file_upload_target">
	<input type="hidden" name="id" value="">
	<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php p($_['uploadMaxFilesize']) ?>" id="max_upload">
	<input type="hidden" class="max_human_file_size" value="(max <?php p($_['uploadMaxHumanFilesize']); ?>)">
	<input id="pinphoto_fileupload" type="file" accept="image/*" name="imagefile" />
</form>
<iframe style="display:none;" name="file_upload_target" id='file_upload_target' src=""></iframe>
<div id="searchresults" class="hidden" data-appfilter="audios"></div>
<div id="loading">
	<i class="ioc-spinner ioc-spin"></i>
</div>
<div id="app-navigation">
<div class="innerNav">
<input type="text" name="newPlaylistTxt" id="newPlaylistTxt" placeholder="<?php p($l->t('Create new playlist'));?>" /> <button class="button" id="newPlaylist"><?php p($l->t('GO'));?></button>
	<h3><?php p($l->t('Music'));?></h3>
	<ul id="albenoverview">
		<li><span id="alben"><span class="info-cover">A</span><?php p($l->t('Albums'));?></span>  <i class="ioc ioc-delete" title="<?php p($l->t('Reset media library'));?>" id="resetAudios"></i><i class="ioc ioc-refresh" title="<?php p($l->t('Scan for new audio files'));?>" id="scanAudios"></i></li>
	</ul>
	<h3><?php p($l->t('Playlists'));?></h3>
	<ul id="myPlayList"></ul>	
	</div>
</div>	
<div id="app-content">


<div class="sm2-bar-ui full-width fixed">

 <div class="bd sm2-main-controls">

  <div class="sm2-inline-texture"></div>
  <div class="sm2-inline-gradient"></div>
  
<div class="sm2-inline-element sm2-button-element">
   <div class="sm2-button-bd">
    <a href="#prev" title="Previous" class="sm2-inline-button previous">prev</a>
   </div>
  </div>
  
  <div class="sm2-inline-element sm2-button-element">
   <div class="sm2-button-bd">
    <a href="#play" class="sm2-inline-button play-pause">Play / pause</a>
   </div>
  </div>
  
  <div class="sm2-inline-element sm2-button-element">
   <div class="sm2-button-bd">
    <a href="#next" title="Next" class="sm2-inline-button next">next</a>
   </div>
  </div>
  
  <div class="sm2-inline-element sm2-button-element">
	   <div class="sm2-playlist-cover">
	    
	   </div>
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
       <div class="sm2-progress-ball"><div class="icon-overlay"></div></div>
      </div>
     </div>
     <div class="sm2-inline-duration">0:00</div>
    </div>
   </div>

  </div>

  <div class="sm2-inline-element sm2-button-element sm2-volume">
   <div class="sm2-button-bd">
    <span class="sm2-inline-button sm2-volume-control volume-shade"></span>
    <a href="#volume" class="sm2-inline-button sm2-volume-control">volume</a>
   </div>
  </div>
 
 <div class="sm2-inline-element sm2-button-element sm2-repeat">
      <div class="sm2-button-bd">
     <a href="#repeat" title="<?php p($l->t('Repeat playlist'));?>" class="sm2-inline-button repeat">&infin; repeat</a>
     </div>
    </div>
<div class="sm2-inline-element sm2-button-element sm2-shuffle">
      <div class="sm2-button-bd">
     <a href="#shuffle" title="<?php p($l->t('Shuffle playlist'));?>" class="sm2-inline-button shuffle">shuffle</a>
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

<div id="audios-audioscontainer"></div>
  <div id="individual-playlist-container" class="albumwrapper" data-playlist="">
  	<span id="individual-playlist-header">
  		<span class="header-indi">
  		<span class="header-num"><?php p($l->t('Nr'));?></span>
  		<span class="header-title"><?php p($l->t('Title'));?></span>
  		<span class="header-interpret"><?php p($l->t('Interpret'));?></span>
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
