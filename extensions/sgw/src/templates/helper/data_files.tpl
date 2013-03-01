{*
	/**************************************************************************\
	* Simple Groupware 0.743                                                   *
	* http://www.simple-groupware.de                                           *
	* Copyright (C) 2002-2012 by Thomas Bley                                   *
	* ------------------------------------------------------------------------ *
	*  This program is free software; you can redistribute it and/or           *
	*  modify it under the terms of the GNU General Public License Version 2   *
	*  as published by the Free Software Foundation; only version 2            *
	*  of the License, no later version.                                       *
	*                                                                          *
	*  This program is distributed in the hope that it will be useful,         *
	*  but WITHOUT ANY WARRANTY; without even the implied warranty of          *
	*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            *
	*  GNU General Public License for more details.                            *
	*                                                                          *
	*  You should have received a copy of the GNU General Public License       *
	*  Version 2 along with this program; if not, write to the Free Software   *
	*  Foundation, Inc., 59 Temple Place - Suite 330, Boston,                  *
	*  MA  02111-1307, USA.                                                    *
	\**************************************************************************/
*}
<a target="_top" href="download.php?filename={$item.filter[$key_filter]|escape:"url"}&field={$curr_id}&item[]={$data_item._id|escape:"url"}&subitem={$key_filter}&dispo=noinline">
<img src="ext/icons/download.gif" title="{t}Download{/t}" style="margin-bottom:2px; margin-right:2px;"></a>&nbsp;

{if $t.links[$curr_id][1] && !$t.links[$curr_id][3]}
  <a target="{$t.links[$curr_id][0]|modify::target}" href="{$t.links[$curr_id][1]|modify::link:$data_item:$key_filter:$urladdon}">
  {if $t.links[$curr_id][0] neq "_top"}
    <img src="ext/icons/{$t.links[$curr_id][2]|default:"link_ext.gif"}" style="margin-bottom:1px;">
  {else}
    <img src="ext/icons/{$t.links[$curr_id][2]|default:"link.gif"}" style="margin-bottom:1px;">
  {/if}
  </a>&nbsp;
{/if}

{if $item.locked[$key_filter] && !($t.rights.write && $item.can_unlock[$key_filter])}
  <img src="ext/icons/lock.gif" style="margin-bottom:1px;">&nbsp;
  ({t}Locked by{/t} {$item.data[$key_filter]|sys_get_lock})&nbsp;
{elseif $t.rights.write && $t.isdbfolder && !$iframe}
  {if $item.can_unlock[$key_filter]}
    <a href="#" onclick="file_func('file_unlock', '{$data_item._id}', '{$curr_id}', '{$key_filter}'); return false;">
	<img src="ext/icons/lock.gif" title="{t}Unlock{/t}" style="margin-bottom:1px;"></a>&nbsp;
  {else}
    <a href="#" onclick="file_func('file_lock', '{$data_item._id}', '{$curr_id}', '{$key_filter}'); return false;">
	<img src="ext/icons/lock_open.gif" title="{t}Lock{/t}" style="margin-bottom:1px;"></a>&nbsp;
  {/if}
{/if}
{if $t.linkstext[$curr_id][1]}
  {assign var="link_data" value=$t.linkstext[$curr_id][1]|modify::link:$data_item:$key_filter:$urladdon}
  {if $link_data neq ""}<a target="{$t.linkstext[$curr_id][0]|modify::target}" id="linktext" href="{$link_data}">{/if}
{elseif $t.views[$t.view].SHOW_PREVIEW && count($item.data)>1}
  {assign var="link_text" value=1}
  <a target="_top" id="linktext" title="{t}Preview{/t}" href="index.php?item[]={$data_item._id}&subitem={$key_filter}" onmousedown="event.cancelBubble=true;">
{/if}

{$item.filter[$key_filter]}

{if ($t.linkstext[$curr_id] && $link_data neq "") || $link_text}</a>{/if}

{if file_exists($item.data[$key_filter])}&nbsp;({$item.data[$key_filter]|modify::filesize}, {$item.data[$key_filter]|modify::filemtime}){/if}
		  
{if $t.links[$curr_id][1] && $t.links[$curr_id][3]}
  &nbsp;<a target="{$t.links[$curr_id][0]|modify::target}" href="{$t.links[$curr_id][1]|modify::link:$data_item:$key_filter:$urladdon}">
  {if $t.links[$curr_id][0] neq "_top"}
    <img src="ext/icons/{$t.links[$curr_id][2]|default:"link_ext.gif"}" style="margin-bottom:1px;">
  {else}
    <img src="ext/icons/{$t.links[$curr_id][2]|default:"link.gif"}" style="margin-bottom:1px;">
  {/if}
  </a>
{/if}

{if $t.views[$t.view].SHOW_PREVIEW && $key_filter eq $t.subitem}
  {if modify("image_file", $item.filter[$key_filter])}
    <br><br>
	<a target="_blank" title="{$item.filter[$key_filter]|escape:"url"}" href="download.php?filename={$item.filter[$key_filter]|escape:"url"}&field={$curr_id}&item[]={$data_item._id|escape:"url"}&subitem={$key_filter}">
	  <img class="cursor" src="download.php?field={$curr_id}&item[]={$data_item._id|escape:"url"}&subitem={$key_filter}&image_width={$t.views[$t.view].IMAGE_WIDTH}&image_height={$t.views[$t.view].IMAGE_HEIGHT}">
	</a>
	{if modify("exif_file", $item.filter[$key_filter])}
	  {assign var="preview_data" value=$item.data[$key_filter]|modify::previewfile:$t.title}
	  {if $preview_data neq ""}
		&nbsp;<span style="vertical-align:bottom;"><a href="#" onclick="showhide_inline(this.parentNode.lastChild); return false;">&gt;&gt;</a><div style="display:none;">
		<br><br>
		{$preview_data|modify::htmlfield_noimages}
		</div></span>
	  {/if}
	  <div></div>
	{/if}
  {elseif modify("iframe_file", $item.filter[$key_filter])}
    &nbsp;<a href="#" onclick="file_preview_change('{$data_item._id}_iframe',60); return false;"> + </a>/
    <a href="#" onclick="file_preview_change('{$data_item._id}_iframe',-60); return false;"> &ndash;&nbsp;</a>
	<table border="0" cellpadding="0" cellspacing="0" style="width:100%;"><thead><tr><td>
	  <div id="{$data_item._id}_iframe" style="width:100%; height:200px; margin-top:10px;">
      <iframe name="{$data_item._id}_iframe" src="{$item.data[$key_filter]|modify::previewlink}" style="width:100%; height:100%; border:0px;"></iframe>
	  </div>
	</td></tr></thead></table>
  {elseif modify("getfileext", $item.filter[$key_filter]) eq "mp3"}
	<div style="margin:10px;">
	  <script>has_flash();</script>
	  {assign var="url" value="download_php?folder=`$t.folder`&view=`$t.view`&field=`$curr_id`&item=`$data_item._id`&subitem=`$key_filter`"}
	  <object data="ext/lib/player/player_mp3_1.0.0.swf" width="200" height="20" type="application/x-shockwave-flash">
		<param name="movie" value="ext/lib/player/player_mp3_1.0.0.swf" />
		<param name="flashvars" value="mp3={$url|escape:"url"}&amp;showstop=1" />
	  </object>
	</div>
  {elseif modify("getfileext", $item.filter[$key_filter]) eq "flv"}
	<div style="margin:10px;">
	  <script>has_flash();</script>
	  {assign var="url" value="../../../download_php?folder=`$t.folder`&view=`$t.view`&field=`$curr_id`&item=`$data_item._id`&subitem=`$key_filter`"}
	  <object type="application/x-shockwave-flash" data="ext/lib/player/player_flv_maxi_1.6.0.swf" width="320" height="240">
		<param name="movie" value="ext/lib/player/player_flv_maxi_1.6.0.swf" />
		<param name="allowFullScreen" value="true" />
		<param name="FlashVars" value="flv={$url|escape:"url"}&amp;showstop=1&amp;showvolume=1&amp;showtime=1&amp;showfullscreen=1&amp;startimage=ext/lib/player/startimage.jpg" />
	  </object>	  
	</div>
  {else}	
	<br><br>
	{if modify("getfileext", $item.filter[$key_filter]) eq "pdf"}
	  <img style="float:left; padding-right:20px;" src="download.php?field={$curr_id}&item[]={$data_item._id|escape:"url"}&subitem={$key_filter}&image_width={$t.views[$t.view].IMAGE_WIDTH}&image_height={$t.views[$t.view].IMAGE_HEIGHT}">
	{/if}
    {$item.data[$key_filter]|modify::previewfile:$t.title|modify::htmlfield_noimages}
  {/if}
{/if}