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
<html>
<head>
  <title>{$sys.app_title} - Offline folders</title>
{* You are not allowed to remove or alter the copyright. *}
<!-- 
	This website is brought to you by Simple Groupware
	Simple Groupware is an open source Groupware and Web Application Framework created by Thomas Bley and licensed under GNU GPL v2.
	Simple Groupware is copyright 2002-2012 by Thomas Bley.	Extensions and translations are copyright of their respective owners.
	More information and documentation at http://www.simple-groupware.de/
-->
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="generator" content="Simple Groupware 0.743" />
  <link media="all" href="images_php?css_style={$sys.style}&browser={$sys.browser.name}&{$smarty.const.CORE_VERSION}" rel="stylesheet" type="text/css" />
  <script>
    {literal}
	function update_status() {
	  if (navigator.onLine) show("back"); else hide("back");
	  set_html("status", "Status: " + (navigator.onLine ? "<b>online</b>" : "<b>offline</b>"));
	}
	function change_size(id, size) {	
	  var obj = getObj(id);
  	  if (obj.offsetHeight + size > 0) obj.style.height = (obj.offsetHeight + size) + "px";
	  if (!obj.contentWindow) return;
	  try {
		if (size<0) {
		  obj.contentWindow.hide(".tfields");
		} else {
		  obj.contentWindow.show2(".tfields");
		}
	  } catch (e) {}
	}
    {/literal}
  </script>
  <script type="text/javascript" src="ext/js/functions.js?{$smarty.const.CORE_VERSION}"></script>
  <style>body {ldelim}margin:10px;{rdelim}</style>
</head>
<body onload="update_status();">
  <div style="white-space:nowrap; border-bottom: 1px solid black; letter-spacing: 2px; font-size: 18px; font-weight: bold;">{$sys.app_title} - Offline folders</div>
  <br>
  <div id="back"><a href="index.php">Back</a> | <a href="index.php?folder=^offline_{$sys.username}">Manage offline folders</a><br><br></div>
  <div id="status"></div>
  <script>
	document.body.addEventListener("offline", update_status, false);
	document.body.addEventListener("online", update_status, false);
  </script>
  <div>&nbsp;</div>
  <div style="margin-bottom:10px;"><b>Folders:</b></div>
  {foreach key=key item=row from=$rows}
	{$row.path}
	{if $row.view neq "display"}<small>({$row.view})</small>{/if} &nbsp;
	<span>
	  <a href="#" onclick="change_size('iframe_{$key}',60); return false;"> + </a>/
	  <a href="#" onclick="change_size('iframe_{$key}',-60); return false;"> &ndash;&nbsp;</a>
	</span><br>
	<table cellpadding="0" cellspacing="0" style="width:100%;"><tr><td style="padding-bottom:4px;">
	  <iframe src="{$row.url}" id="iframe_{$key}" name="iframe_{$key}" style="width:100%; height:350px; border:0px; margin-top:5px; margin-bottom:9px;"></iframe>
	</td></tr></table>
  {foreachelse}
    No entries found. (Offline folders)<br>
  {/foreach}
  <br>
  <span style="float:right;">{"m/d/y g:i:s a"|sys_date}</span>
  <div style="white-space:nowrap; border-top: 1px solid black;">  
    <a href="http://www.simple-groupware.de" class="lnotice" target="_blank" onmouseover="set_html(this,'Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.');" onmouseout="set_html(this,'Powered by Simple Groupware.');">Powered by Simple Groupware.</a>
  </div>
</body>
</html>