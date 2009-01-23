<?php

/*
Plugin Name: MP3 Tag
Plugin URI: http://narcanti.keyboardsamurais.de/mp3-tag.html
Plugin Version: 1.1
Description: Allows to include a downloadlink or player for mp3 files via a tag.
Author: M. Serhat Cinar
Author URI: http://narcanti.keyboardsamurais.de
License: GPL
*/
/*
Changes v1.1:

 - Instead of escaping only whitespaces a full urlencode is done (when activated)
 - Some more information and examples on configuration page
 - Display of a test example to show current configuration
 - Added debugging info
 - Predefined values for MP3 player URI with javascript for different mp3 players
 - Added %%mp3name as placeholder for MP3 player URI
*/

global $msc_mp3tag_debug_info, $msc_mp3tag_debug;

if (!function_exists('mp3_get_without_path')){
  function mp3_get_without_path($withpath){
    global $msc_mp3tag_debug_info, $msc_mp3tag_debug;
    if ($msc_mp3tag_debug){
      $msc_mp3tag_debug_info .= "mp3_get_without_path(withpath=".$withpath.")\n";
    }
    $ret = "";
    if (!strpos($withpath, "/")){
      $ret = $withpath;
    }
    else{
      $parts = Explode('/', $withpath);
      $ret = $parts[count($parts)-1];
    }
    if ($msc_mp3tag_debug){
      $msc_mp3tag_debug_info .= "mp3_get_without_path(...): ".$ret."\n";
    }

    return $ret;
  }
}

if (!function_exists('mp3_get_path')){
  function mp3_get_path($withpath){
    global $msc_mp3tag_debug_info, $msc_mp3tag_debug;
    if ($msc_mp3tag_debug){
      $msc_mp3tag_debug_info .= "mp3_get_path(withpath=".$withpath.")\n";
    }
    $ret = "";
    if (!strpos($withpath, "/")){
      $ret = "";
    }
    else{
      $parts = Explode('/', $withpath);
      for ($i=0; $i<count($parts)-1; $i++){
        $ret .= $parts[$i]."/";
      }
    }
    if ($msc_mp3tag_debug){
      $msc_mp3tag_debug_info .= "mp3_get_path(...): ".$ret."\n";
    }

    return $ret;
  }
}

if( !function_exists( 'mp3_tag_handler' ) ){
  function mp3_tag_handler($path, $isplayer="true", $linktitle=""){
    global $msc_mp3tag_debug_info, $msc_mp3tag_debug;
    if ($msc_mp3tag_debug){
      $msc_mp3tag_debug_info .= "mp3_tag_handler(path=".$path.", isplayer=".$isplayer.", linktitle=".$linktitle.")\n";
    }
    $mp3files_uri = mp3_tag_get_option("files_uri");
    if ($msc_mp3tag_debug){
      $msc_mp3tag_debug_info .= "mp3files_uri from options: ".$mp3files_uri."\n";
    }

    // fuegt am ende mp3 ein, falls im tag nicht angegben

    if (strcmp(substr($path, -4), ".mp3")!=0){
      $path = $path.'.mp3';
      if ($msc_mp3tag_debug){
        $msc_mp3tag_debug_info .= "path after adding mp3 extension".$path."\n";
      }
    }

    // pfad von anfrage auf root ermitteln
    if ($msc_mp3tag_debug){
      $msc_mp3tag_debug_info .= "_SERVER['REQUEST_URI']: ".$_SERVER['REQUEST_URI']."\n";
    }
    $path_deepth = sizeof(explode("/", $_SERVER['REQUEST_URI']));
    if ($msc_mp3tag_debug){
      $msc_mp3tag_debug_info .= "path_deepth: ".$path_deepth."\n";
    }

    // letzter teil des pfades ist unwichtig, da zielseite
    if (strncmp($_SERVER['REQUEST_URI'], "/", strlen($_SERVER['REQUEST_URI'])-1)!=0){
      $path_deepth = $path_deepth-1;
      if ($msc_mp3tag_debug){
        $msc_mp3tag_debug_info .= "path_deepth after removing trailing /: ".$path_deepth."\n";
      }
    }
    // erster teil des pfades ist unwichtig, da leer
    if (strncmp($_SERVER['REQUEST_URI'], "/", 1)==0){
      $path_deepth = $path_deepth-1;
      if ($msc_mp3tag_debug){
        $msc_mp3tag_debug_info .= "path_deepth after removing prefixing /: ".$path_deepth."\n";
      }
    }

    // zurück zum root
    $root_relative = "";
    for ($i=0; $i<$path_deepth; $i++){
      $root_relative = $root_relative."../";
    }
    if ($msc_mp3tag_debug){
      $msc_mp3tag_debug_info .= "root_relative: ".$root_relative."\n";
    }




    // player variante
    // player: [mp3player:8bit/silver spyder sam - puup'n'peep]
    if (strcmp($isplayer, "true")==0){
      $player_uri = mp3_tag_get_option("player_uri");
      $player_mp3_uri_escape = mp3_tag_get_option("escape_uri_for_player");
      $player_template = stripslashes(mp3_tag_get_option("player_template"));
      if ($msc_mp3tag_debug){
        $msc_mp3tag_debug_info .= "creating player\n";
        $msc_mp3tag_debug_info .= "player uri from options: ".$player_uri."\n";
        $msc_mp3tag_debug_info .= "player mp3 urlencode from options: ".$player_mp3_uri_escape."\n";
        $msc_mp3tag_debug_info .= "player template from options: ".$player_template."\n";
      }

      if (strcmp($player_mp3_uri_escape, "true")==0){
        $path = urlencode($path);
        if ($msc_mp3tag_debug){
          $msc_mp3tag_debug_info .= "path after urlencode: ".$path."\n";
        }
      }

      $mp3_name_parts = explode("/", $path);
      $mp3_name = $mp3_name_parts[sizeof($mp3_name_parts)-1];
      $player_uri = preg_replace('/\%\%mp3name/i', $mp3_name, $player_uri);

      // %%root_relative%%player_uri%%root_relative%%mp3files_uri/%%mp3file
      $player_template = preg_replace('/\%\%root_relative/i', $root_relative, $player_template);
      $player_template = preg_replace('/\%\%player_uri/i', $player_uri, $player_template);
      $player_template = preg_replace('/\%\%mp3files_uri/i', $mp3files_uri, $player_template);
      $player_template = preg_replace('/\%\%mp3file/i', $path, $player_template);

      return $player_template;

    }
    // downloadlink variante
    // download link: [mp3download:8bit/silver spyder sam - puup'n'peep]
    else{
      $mp3files_uri = mp3_tag_get_option("files_uri");
      $mp3_name_remove_extension = mp3_tag_get_option("remove_extension");
      $download_template = stripslashes(mp3_tag_get_option("download_template"));

      if ($msc_mp3tag_debug){
        $msc_mp3tag_debug_info .= "creating download link\n";
        $msc_mp3tag_debug_info .= "files uri from options: ".$mp3files_uri."\n";
        $msc_mp3tag_debug_info .= "mp3_name_remove_extension: ".$mp3_name_remove_extension."\n";
        $msc_mp3tag_debug_info .= "download template from options: ".$download_template."\n";
      }

      $mp3_name_parts = explode("/", $path);
      $mp3_name = $mp3_name_parts[sizeof($mp3_name_parts)-1];
      if ($msc_mp3tag_debug){
        $msc_mp3tag_debug_info .= "found mp3 name: ".$mp3_name."\n";
      }
      if (strcmp($mp3_name_remove_extension, "true")==0){
        $mp3_name = str_replace(".mp3", "", $mp3_name);
        if ($msc_mp3tag_debug){
          $msc_mp3tag_debug_info .= "mp3 name after removing extension: ".$mp3_name."\n";
        }
      }

      if (strlen($linktitle)>0){
        $mp3_name=$linktitle;
      }
      
      $download_template = preg_replace('/\%\%root_relative/i', $root_relative, $download_template);
      $download_template = preg_replace('/\%\%mp3files_uri/i', $mp3files_uri, $download_template);
      $download_template = preg_replace('/\%\%mp3file/i', $path, $download_template);
      $download_template = preg_replace('/\%\%mp3_name/i', $mp3_name, $download_template);
      return $download_template;
    }

  }
}

if( !function_exists( 'mp3_tag' ) ){
  function mp3_tag($text){
    global $msc_mp3tag_debug_info, $msc_mp3tag_debug;
    if ($msc_mp3tag_debug){
      $msc_mp3tag_debug_info .= "mp3_tag(text=".$text.")\n";
    }
  	return 
          preg_replace(
            "/(?:\[mp3download:([^:\]]+)(?::([^\]]+)){0,1}\])/ismeU", 
            "mp3_tag_handler('\\1', 'false', '\\2')",
            preg_replace(
              "/(?:\[mp3player:([^\]]+)\])/ismeU",
              "mp3_tag_handler('\\1', 'true')", 
              $text
            )
          );
  }
}

if( !function_exists( 'mp3_tag_update_option' ) ){
  function mp3_tag_update_option($option_name, $option_value){
    update_option('mp3_tag_'.$option_name, $option_value);
  }
}

if( !function_exists( 'mp3_tag_get_option' ) ){
  function mp3_tag_get_option($option_name){
    global $mp3_tag_options_initialized;
    if ($mp3_tag_options_initialized == 0 && strlen(get_option("mp3_tag_initialized"))<=0) {
	add_option('mp3_tag_player_uri', 'mp3/dewplayer.swf?son=');
        add_option('mp3_tag_files_uri', 'mp3');
	add_option('mp3_tag_remove_extension', 'true');
	add_option('mp3_tag_escape_uri_for_player', 'true');
        add_option('mp3_tag_player_template', '<object type="application/x-shockwave-flash" data="%%root_relative%%player_uri%%root_relative%%mp3files_uri/%%mp3file" width="200" height="20">
<param name="movie" value="%%root_relative%%player_uri%%root_relative%%mp3files_uri/%%mp3file" /></object>');
        add_option('mp3_tag_download_template', '<a href="%%root_relative%%mp3files_uri/%%mp3file">%%mp3_name</a>');
        add_option('mp3_tag_remove_initialized', 'true');
        add_option('mp3_tag_example_file', '');
        add_option('mp3_tag_debug', 'true');     
	$mp3_tag_options_initialized = 1;
    }
    return get_option("mp3_tag_".$option_name);
  }
}

// the options page
if( !function_exists( 'mp3_tag_options_page' ) ){
  function mp3_tag_options_page(){
    global $msc_mp3tag_debug_info, $msc_mp3tag_debug;
    $msc_mp3tag_debug_info = "";
    
    // predefined payer configurations
    $playernames = array();
    $player_uris_by_playername = array();
    $player_homepages_by_playername = array();

    array_push($playernames, "dewplayer");
    $player_uris_by_playername["dewplayer"] = "dewplayer.swf?son=";
    $player_homepages_by_playername["dewplayer"] = "www.alsacreations.fr/dewplayer-en";

    array_push($playernames, "EMFF standard");
    $player_uris_by_playername["EMFF standard"] = "emff_standard.swf?src=";
    $player_homepages_by_playername["EMFF standard"] = "emff.sourceforge.net";

    array_push($playernames, "EMFF easy glaze");
    $player_uris_by_playername["EMFF easy glaze"] = "emff_easy_glaze.swf?src=";
    $player_homepages_by_playername["EMFF easy glaze"] = "emff.sourceforge.net";
    
    array_push($playernames, "EMFF easy glaze small");
    $player_uris_by_playername["EMFF easy glaze small"] = "emff_easy_glaze_small.swf?src=";
    $player_homepages_by_playername["EMFF easy glaze small"] = "emff.sourceforge.net";

    array_push($playernames, "EMFF lila");
    $player_uris_by_playername["EMFF lila"] = "emff_lila.swf?src=";
    $player_homepages_by_playername["EMFF lila"] = "emff.sourceforge.net";

    array_push($playernames, "EMFF silk");
    $player_uris_by_playername["EMFF silk"] = "emff_silk.swf?src=";
    $player_homepages_by_playername["EMFF silk"] = "emff.sourceforge.net";

    array_push($playernames, "EMFF stuttgart");
    $player_uris_by_playername["EMFF stuttgart"] = "emff_stuttgart.swf?src=";
    $player_homepages_by_playername["EMFF stuttgart"] = "emff.sourceforge.net";
    
    array_push($playernames, "EMFF wooden");
    $player_uris_by_playername["EMFF wooden"] = "emff_wooden.swf?src=";
    $player_homepages_by_playername["EMFF wooden"] = "emff.sourceforge.net";

    array_push($playernames, "video-flash.de");
    $player_uris_by_playername["video-flash.de"] = "flashaudioplayer.swf?autoplay=false&loop=false&audio==";
    $player_homepages_by_playername["video-flash.de"] = "www.video-flash.de/index/open-source-mp3-player";

    array_push($playernames, "premium beat mini");
    $player_uris_by_playername["premium beat mini"] = "playerMini.swf?autoPlay=no&soundPath=";
    $player_homepages_by_playername["premium beat mini"] = "www.premiumbeat.com/flash_resources/free_flash_music_player/mini_flash_mp3_player.php";

    array_push($playernames, "XSPF slim");
    $player_uris_by_playername["XSPF slim"] = "xspf_player_slim.swf?song_title=%%mp3name&song_url=";
    $player_homepages_by_playername["XSPF slim"] = "musicplayer.sourceforge.net";

    array_push($playernames, "XSPF button");
    $player_uris_by_playername["XSPF button"] = "musicplayer.swf?song_title=%%mp3name&song_url=";
    $player_homepages_by_playername["XSPF button"] = "musicplayer.sourceforge.net";

    array_push($playernames, "XSPF extended");
    $player_uris_by_playername["XSPF extended"] = "xspf_player.swf?song_title=%%mp3name&song_url=";
    $player_homepages_by_playername["XSPF extended"] = "musicplayer.sourceforge.net";
    
    // check for updates options
    if (!empty($_POST['debug'])){
      if (strcasecmp('true', $_POST['debug'])==0){
        mp3_tag_update_option('debug', 'true');
        $msc_mp3tag_debug = true;
      }
      else{
        mp3_tag_update_option('debug', 'false');
        $msc_mp3tag_debug = false;
      }
    }
    if (!empty($_POST['player_uri'])){
      mp3_tag_update_option('player_uri', $_POST['player_uri']);
    }
    if (!empty($_POST['files_uri'])){
      mp3_tag_update_option('files_uri', $_POST['files_uri']);
    }
    if (!empty($_POST['remove_extension'])){
      mp3_tag_update_option('remove_extension', $_POST['remove_extension']);
    }
    if (!empty($_POST['escape_uri_for_player'])){
      mp3_tag_update_option('escape_uri_for_player', $_POST['escape_uri_for_player']);
    }
    if (!empty($_POST['player_template'])){
      mp3_tag_update_option('player_template', $_POST['player_template']);
    }
    if (!empty($_POST['download_template'])){
      mp3_tag_update_option('download_template', $_POST['download_template']);
    }
    if (!empty($_POST['example_file'])){
      mp3_tag_update_option('example_file', $_POST['example_file']);
    }
?>
    <!-- some simple css -->
    <style type="text/css">
      .mp3 {text-align: left; vertical-align: top;}
      .mp3odd{background-color: #D4D4D4;}
      .mp3even{background-color: #EEEEEE;}
      .mp3eg{font-family: monospace; font-size: 1.1em;}
    </style>
    <!-- some simple javascript -->
    <script type="text/javascript">
    <!--
    function mscGetObjectById( id ){
      var returnVar;
      if (document.getElementById){
        returnVar = document.getElementById(id);
      }
      else if (document.all){
        returnVar = document.all[id];
      }
      else if (document.layers){
        returnVar = document.layers[id];
      }
      return returnVar;
    }
    function mscSetValue(id, value){
      var object = mscGetObjectById(id);
      object.value=value;
    }
    //-->
    </script>

   <?php
     $mp3player_without_path = mp3_get_without_path(mp3_tag_get_option('player_uri'));
     $mp3player_path = mp3_get_path(mp3_tag_get_option('player_uri'));
     
     $mp3testsong = "myalbum/mysong.mp3";
     $mp3testsong_from_user = false;
     $mp3configured_testsong = mp3_tag_get_option('example_file');
     if (isset($mp3configured_testsong) && '' != $mp3configured_testsong){
       $mp3testsong_from_user = true;
       $mp3testsong = $mp3configured_testsong;
     }
   ?>

    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=msc_mp3_tag.php">
      <table width="100%" cellspacing="2" cellpadding="5">
        <tr>
          <th colspan="2"><h3>General options</h3></th>
        </tr>
        <tr class="mp3odd">
          <th class="mp3" width="30%">MP3 files root URI</th>
          <td class="mp3">
            <input name="files_uri" type="text" value="<?php echo mp3_tag_get_option('files_uri'); ?>" size="60" />
            <p/>
            This URI is the absolute path starting after the domainname pointing to the rootfolder of MP3 files. For example when the MP3 files are located in a folder "http://myblog/mp3" and it's subfolders, the URI is <span class="mp3eg">mp3</span>.
          </td>
        </tr>
        <tr class="mp3even">
          <th class="mp3" width="30%">MP3 file for tests</th>
          <td class="mp3">
            <input name="example_file" type="text" value="<?php echo mp3_tag_get_option('example_file'); ?>" size="60" />
            <p/>
            Enter an existing MP3 here to show your player configuration below with a real mp3. The value for this field should be the same as in a mp3player or mp3download tag, eg. <span class="mp3eg">myalbum/mysong.mp3</span>.
          </td>
        </tr>
        <tr class="mp3odd">
          <th class="mp3">Debug</th>
          <td class="mp3">
            <select name="debug" size="1">
              <option<?php if (mp3_tag_get_option('debug')=='true'){echo ' selected="selected"';} ?> value="true">true</option><br/>
              <option<?php if (mp3_tag_get_option('debug')!='true'){echo ' selected="selected"';} ?> value="false">false</option>
            </select>
            <p/>
            If this option is activated, this configuration page will show a detailed debug trace of what the plugin made.
          </td>
        </tr>
        <tr>
          <th colspan="2"><h3>MP3 player options</h3></th>
        </tr>
        <tr class="mp3even">
          <th class="mp3">Preconfigured MP3 player URIs</th>
          <td class="mp3">
            Select one of the players below to set preconfiguration on MP3 player URI<br/>
            <?php
              foreach ($playernames as $player){
                echo '<a href="javascript: mscSetValue('."'player_uri', '".$mp3player_path.$player_uris_by_playername[$player]."'".')">'.$player."</a> from <a href='http://". $player_homepages_by_playername[$player]."'>here</a><br/>";
              }
            ?>
          </td>
        </tr>
        <tr class="mp3odd">
          <th class="mp3">MP3 player URI</th>
          <td class="mp3">
            <input name="player_uri" id="player_uri" type="text" value="<?php echo mp3_tag_get_option('player_uri'); ?>" size="60" />
            <p/>
            The URI is a absolute path starting after the domainname and contains the MP3 player with parameters.
            For example if the player (like the <a href="http://www.alsacreations.fr/dewplayer">dewplayer</a>) is at the location "http://myblog/mp3/dewplayer.swf" the URI is <span class="mp3eg">mp3/dewplayer.swf?son=</span> (including the static parameter-name "son=" which references to the MP3 file).<br/>
Also the placeholder <span class="mp3eg">%%mp3name</span> may be used for the name of the mp3, for example with XSPF Player: <span class="mp3eg">mp3/xspf_player_slim.swf?song_title=%%mp3name&song_url=</span>.
          </td>
        </tr>
        <tr class="mp3even">
          <th class="mp3">URL-encode URI for player</th>
          <td class="mp3">
            <select name="escape_uri_for_player" size="1">
              <option<?php if (mp3_tag_get_option('escape_uri_for_player')=='true'){echo ' selected="selected"';} ?> value="true">true</option><br/>
              <option<?php if (mp3_tag_get_option('escape_uri_for_player')=='false'){echo ' selected="selected"';} ?> value="false">false</option>
            </select>
            <p/>
            If this option is activated, the name of the mp3 will be URL-encoded. Some MP3 players prefer data="mp3/dewplayer.swf?son=mp3/silver+spyder+sam+-+blind+cat.mp3" instead of
data="mp3/dewplayer.swf?son=mp3/silver spyder sam - blind cat.mp3", where "+" is the spacecharacter in urlencoded form.
          </td>
        </tr>
        <tr class="mp3odd">
          <th class="mp3">Player template</th>
          <td class="mp3">
            <textarea name="player_template" id="player_template" cols="50" rows="5" style="width: 98%; font-size: 12px;"><?php echo stripslashes(htmlspecialchars(mp3_tag_get_option('player_template'))); ?></textarea>
            <p/>
            Used template-tags are:<br/>
            <table>
              <tr><th class="mp3">%%root_relative</th><td>&nbsp;</td><td>Path from actual page to root, e.g. "../"</td></tr>
              <tr><th class="mp3">%%player_uri</th><td>&nbsp;</td><td>The configured player URI</td></tr>
              <tr><th class="mp3">%%mp3files_uri</th><td>&nbsp;</td><td>The configured URI to mp3 files</td></tr>
              <tr><th class="mp3">%%mp3file</th><td>&nbsp;</td><td>The mp3file (incl. subpaths from mp3 files root) as defined in the tag</td></tr>
            </table>
            <p/>
            Example for dewplayer:<br/>
<div class="mp3eg">
&lt;object type="application/x-shockwave-flash" data="%%root_relative%%player_uri%%root_relative%%mp3files_uri/%%mp3file" width="200" height="20"&gt;
&lt;param name="movie" value="%%root_relative%%player_uri%%root_relative%%mp3files_uri/%%mp3file" /&gt;&lt;/object&gt;
</div>
          </td>
        </tr>
        <tr class="mp3even">
          <th class="mp3">Usage examples</th>
          <td class="mp3">
            <span class="mp3eg">[mp3player:myalbum/mymp3.mp3]</span> will generate a player for the song located at "http://myblog/mp3/myalbum/mymp3.mp3" (when the option "MP3 files root URI" is set to "mp3"). You also can skip the ".mp3" extension within the tag, which then will automatically be added: <span class="mp3eg">[mp3player:myalbum/mymp3]</span>.
          </td>
        </tr>
        <tr class="mp3odd">
          <th class="mp3">Example for current configuration</th>
          <td class="mp3">
            <span class="mp3eg">[mp3player:<?php echo $mp3testsong; ?>]</span> will render to<br/>
            <span class="mp3eg"><?php echo str_replace(">", "&gt;", str_replace("<", "&lt;", mp3_tag("[mp3player:".$mp3testsong."]"))); ?></span>
            <?php if ($mp3testsong_from_user){
              echo "<br/>\n".mp3_tag("[mp3player:".$mp3testsong."]");
            } ?>
          </td>
        </tr>
        <tr>
          <th colspan="2"><h3>MP3 download link options</h3></th>
        </tr>
        <tr class="mp3odd">
          <th class="mp3">Remove Extensions for linktext</th>
          <td class="mp3">
            <select name="remove_extension" size="1">
              <option<?php if (mp3_tag_get_option('remove_extension')=='true'){echo ' selected="selected"';} ?> value="true">true</option><br/>
              <option<?php if (mp3_tag_get_option('remove_extension')=='false'){echo ' selected="selected"';} ?> value="false">false</option>
            </select>
            <p/>
            If this is activated and a mp3download tag doesn't contain an alternative title, uses the filename of the mp3 and removes the ".mp3" extension.
          </td>
        </tr>
        <tr class="mp3even">
          <th class="mp3">Download template</th>
          <td class="mp3">
            <textarea name="download_template" cols="50" rows="5" style="width: 98%; font-size: 12px;"><?php echo stripslashes(htmlspecialchars(mp3_tag_get_option('download_template'))); ?></textarea>
            <p/>
            Used template-tags are:<br/>
            <table>
              <tr><th class="mp3">%%root_relative</th><td>&nbsp;</td><td class="mp3">Path from actual page to root, e.g. "../"</td></tr>
              <tr><th class="mp3">%%mp3files_uri</th><td>&nbsp;</td><td class="mp3">The configured URI to mp3 files</td></tr>
              <tr><th class="mp3">%%mp3file</th><td>&nbsp;</td><td class="mp3">The mp3file (incl. subpaths from mp3 files root) as defined in the tag</td></tr>
              <tr><th class="mp3">%%mp3_name</th><td>&nbsp;</td><td class="mp3">The text for the link as defined in the tag or generated from %%mp3file</td></tr>
            </table>
            <p/>
            Example:<br/>
 <div class="mp3eg">&lt;a href="%%root_relative%%mp3files_uri/%%mp3file"&gt;%%mp3_name&lt;/a&gt;</div>
          </td>
        </tr>
        <tr class="mp3odd">
          <th class="mp3">Usage examples</th>
          <td class="mp3">
            <span class="mp3eg">[mp3download:myalbum/mymp3.mp3]</span> will generate a link with the text "mymp3" if option "Remove Extensions for Linktext" is activated, else "mymp3.mp3".<br/>
            <span class="mp3eg">[mp3download:myalbum/mymp3.mp3:mySong]</span> will generate a link with the text "mySong". You also can skip the ".mp3" extension within the tag, which then will automatically be added: <span class="mp3eg">[mp3download:myalbum/mymp3]</span>.
          </td>
        </tr>
        <tr class="mp3even">
          <th class="mp3">Example for current configuration</th>
<td class="mp3">
            <span class="mp3eg">[mp3download:<?php echo $mp3testsong; ?>]</span> will render to<br/>
            <span class="mp3eg"><?php echo str_replace(">", "&gt;", str_replace("<", "&lt;", mp3_tag("[mp3download:".$mp3testsong."]"))); ?></span>
            <?php if ($mp3testsong_from_user){
              echo "<br/>\n".mp3_tag("[mp3download:".$mp3testsong."]");
            } ?>
          </td>
        </tr>
        <tr>
          <td colspan="2" style="text-align: center;"><input type="submit" name="Submit" value="<?php _e('Update Options'); ?> &raquo;" /></td>
        </tr>
        <?php if ($msc_mp3tag_debug){ ?>
         <tr>
           <th colspan="2">Debug information</th>
         </tr>
         <tr>
           <td class="mp3 mp3eg" colspan="2"><?php echo str_replace("\n", "<br/>", str_replace("<", "&lt;", str_replace(">", "&gt;", $msc_mp3tag_debug_info))); ?></td>
         </tr>
       <?php } ?>
      </table>
    </form>
    <p/>
    <div style="text-align: center;">This plugin was brought to you by <a href="http://narcanti.keyboardsamurais.de">M. Serhat Cinar</a>
    <p/>
    Want to donate? Do it <a href="http://www.supportunicef.org/">here</a>
    </div>
    <p/>
<?php
  }
}

if( !function_exists( 'mp3_tag_add_options_page' ) ){
	function mp3_tag_add_options_page() {
		if (function_exists('add_options_page')) {
			// WordPress 1.5 sometimes doesn't show the options page if called in the first style
			if ( $wp_version > "1.5" ) {
				add_options_page('MP3 Tag Plugin', 'MP3 Tag', 8, basename(__FILE__), 'mp3_tag_options_page');
			}
			else {
				add_options_page('MP3 Tag Plugin', 'MP3 Tag', 8, basename(__FILE__));
			}
		}
	}
}

if ( function_exists("is_plugin_page") && is_plugin_page() ) {
	mp3_tag_options_page(); 
	return;
}

add_filter('the_content', 'mp3_tag', 10);
add_filter('the_excerpt', 'mp3_tag', 10);
add_action('admin_menu', 'mp3_tag_add_options_page');

?>