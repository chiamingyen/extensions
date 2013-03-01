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
{if $print eq 1}{assign var="sys_style" value="core"}{/if}
{config_load file="core_css.conf" section=$sys_style}
<html>
<head>
{if $iframe}<base target="_blank">{/if}
<title>{$folder.name} - {$t.views[$t.view].DISPLAYNAME|default:$t.view} - {if !$tree.visible}{$sys.username} - {/if}{$sys.app_title}</title>
{* You are not allowed to remove or alter the copyright. *}
<!-- 
	This website is brought to you by Simple Groupware
	Simple Groupware is an open source Groupware and Web Application Framework created by Thomas Bley and licensed under GNU GPL v2.
	Simple Groupware is copyright 2002-2012 by Thomas Bley.	Extensions and translations are copyright of their respective owners.
	More information and documentation at http://www.simple-groupware.de/
-->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="generator" content="Simple Groupware 0.743" />
<meta name="viewport" content="initial-scale=1.0; minimum-scale=1.0; maximum-scale=1.0;" />
<link media="all" href="images_php?css_style={$sys_style}&browser={$sys.browser.name}&{$smarty.const.CORE_VERSION}" rel="stylesheet" type="text/css" />
{if $t.load_css}<link media="all" href="{$t.load_css}" rel="stylesheet" type="text/css" />{/if}
{if $t.load_js}<script type="text/javascript" src="{$t.load_js}"></script>{/if}
{if !$iframe}
  <link href="ext/images/favicon.ico" rel="shortcut icon">
  <link rel="search" type="application/opensearchdescription+xml" href="images_php?search" title="{$sys.app_title}" />
  {if $t.att.ENABLE_CALENDAR}
	<link rel="alternate" type="application/atom+xml" title="{t}Today{/t} {t}Feed{/t}" href="index_php?folder={$t.folder}&view={$t.view}&export=rss&markdate=day&today=now 00:00&username=&password=">
	<link rel="alternate" type="application/atom+xml" title="{t}Week{/t} {t}Feed{/t}" href="index_php?folder={$t.folder}&view={$t.view}&export=rss&markdate=week&today=last monday&username=&password=">
	<link rel="alternate" type="application/atom+xml" title="{t}Month{/t} {t}Feed{/t}" href="index_php?folder={$t.folder}&view={$t.view}&export=rss&markdate=month&today={$smarty.now|modify::dateformat:"Y-m"}-01&username=&password=">
  {else}
	<link rel="alternate" type="application/atom+xml" title="{t}Feed{/t}" href="index_php?folder={$t.folder}&view={$t.view}&export=rss&username=&password=">
  {/if}
{/if}
{if $sys.browser.comp.javascript && !$print && !$sys.browser.no_scrollbar}
<style>body {ldelim}overflow-y:hidden; overflow:hidden;{rdelim}</style>
{/if}
{if $print}<style>.noprint {ldelim}display: none;{rdelim}</style>{/if}
<script>
  var sys = {$sys|@json_encode|no_check};
  var rights = {$t.rights|@json_encode|no_check};
  var css_conf = {$smarty.config|@json_encode|no_check};
  var sys_time = {$smarty.const.NOW};
  var sys_style = "{$sys_style}";
  var tview = "{$t.view}";
  var schema_mode = "{$t.schema_mode}";
  var tfolder = "{$t.folder}";
  var tfolders = {$t.folders|@array_keys|@json_encode|no_check};
  var tname = "{$t.title}";
  var anchor = "{$t.anchor}";
  var isdbfolder = "{$t.isdbfolder}";
  var dblclick = "{$t.doubleclick}";
  var no_folder_operations = "{$t.att.DISABLE_FOLDER_OPERATIONS}";
  var tfolder_name = "{$folder.name}";
  var ftype = "{$folder.type}";
  var cal_firstday = {$smarty.const.WEEKSTART|default:0};
  var tree_visible = "{$tree.visible}";
  var popup = "{$popup}";
  var iframe = "{$iframe}";
  var preview = "{$preview}";
  var urladdon = "{$urladdon}";
  var debug_js = "{$smarty.const.DEBUG_JS}";
  var debug = "{$smarty.const.DEBUG}";
  var hist = {$sys.history|@array_values|@json_encode|no_check};
  var warning = "";
  var notification = "";
  {if count($t.warning) eq 1}warning = "{$t.warning|@array_shift|truncate:100|regex_replace:"/[\r\n]/":" "}";
  {elseif count($t.notification) eq 1}notification = "{$t.notification|@array_shift|regex_replace:"/[\r\n]/":" "}";{/if}
</script>
{if ($sys.browser.name neq "firefox" || $sys.browser.ver lt 35) && ($sys.browser.name neq "safari" || $sys.browser.ver lt 530)}
  <script type="text/javascript" src="ext/lib/json/json.js"></script>
{/if}
<script type="text/javascript" src="ext/js/functions.js?{$smarty.const.CORE_VERSION}"></script>
</head>
<body {if $print eq 1}onload="window.print();"{/if} onresize="resizeit();">

{if $tree.visible && !$print}
  <div class="sgslogo cursor" onclick="nWin('{#logo_link#}');"><img id="sgslogo" src="{#logo#}"></div>
{/if}
{if #bg_full# neq ""}<div class="bg_full"><img src="{#bg_full#}" style="width:100%; height:100%;"></div>{/if}

{if !$print}<noscript>{t}Please enable Javascript in your browser.{/t}<br><br></noscript>{/if}

{if !$print && !$popup && !$iframe && !$preview && $tree.visible}<div id="menu" style="height:29px;"></div>{/if}
<div id="calendar" class="hidden"><iframe id="calendar_iframe" class="calendar_iframe" src="about:blank" style="width:100%; height:100%;"></iframe></div>
<div id="console" class="hidden"><textarea id="tconsole" style="width:100%; height:100%;"></textarea></div>
<div id="login" class="hidden"><iframe id="login_iframe" name="login_iframe" src="about:blank" style="width:100%; height:100%; border:0px;"></iframe></div>

<div id="main" style="{if !$iframe}margin-top:2px;{/if}">

<div id="login_reminder" class="hidden" style="text-align:center;">
  {t}Your session has timed out.{/t}<br>
  <form method="post" target="login_iframe" action="index.php?" onsubmit="hide('login_reminder'); show('login');">
	<input type="hidden" name="loginform" value="true">
	<input type="text" id="username" name="username" value="{$sys.username}">
	<input type="password" id="password" name="password">
	<input type="Submit" value=" {t}L o g i n{/t} ">
  </form>
</div>

<table border="0" cellpadding="0" cellspacing="0" style="width:100%;" {if !$iframe}class="main2"{/if}>
  <tbody>
  <tr>
	{if $tree.visible && !$print}
	<td style="text-align:center; {if #direction#}padding-left:2px;{else}padding-right:2px;{/if}" valign="top" id="tree">
	  <script>getObj("tree").style.width = tree_width+"px";</script>
	  {include file="helper/tree.tpl"}
	</td>
    {/if}
    <td valign="top" style="padding-left:0px;" id="content">
	  <script>getObj("content").style.width = (screen_width-tree_width)+"px";</script>
	  {if $print eq 1 || (!$tree.visible && !$iframe && !$preview)}{include file="helper/paths.tpl"}{/if}
	  {if !$print && !$iframe}{include file="helper/views.tpl"}{/if}
	  {if !$t.disable_tabs && (!$t.hidedata || (!$t.data_day && !$t.data_month))}{include file="helper/tabs.tpl"}{/if}

	  {if !$iframe && !$preview && !$popup}
	    <div id="content_pane2" style="display:none; float:right;" class="noprint">
		  <iframe name="pane2" id="pane2" src="about:blank" onmousedown="start_drag(resize_pane2);" onload="show_pane2();"></iframe>
	    </div>
	  {/if}
	  {if $folder.description && ($sys.fdesc_in_content || $print eq 1 || (!$tree.visible && !$iframe && !$preview))}
		<div style="margin:2px; border-top: {#border#};"></div>
		<div class="tree_box" style="border:0px; padding:0px;">{$folder.description|modify::nl2br}</div>
		<div style="margin:2px; margin-bottom:4px; border-top: {#border#};"></div>
	  {/if}
	  <div {if !$print}id="content_def"{/if} class="overflow">
	  <table id="content_def_table" border="0" cellpadding="0" cellspacing="0" style="width:100%; height:100%;">
	  <tr><td valign="top">
	  {if count($t.notification)>0}
		<table cellspacing="0" class="data" align="center" border="0" style="{if $sys.browser.is_mobile}width:auto;{else}width:40%;{/if} margin-top:4px;">
		<tr class="id"><td>{t}Notification{/t}</td></tr>
		{foreach item=item from=$t.notification}
		  <tr class="summary"><td style="text-align:center;"><div class="default10">{$item}</div></td></tr>
	    {/foreach}
		</table>
	  {/if}
	  {if count($t.warning)>0}
		<table cellspacing="0" class="data" align="center" border="0" style="{if $sys.browser.is_mobile}width:auto;{else}width:40%;{/if} margin-top:4px;">
		<tr class="id"><td>{t}Warning{/t}</td></tr>
		{foreach item=item from=$t.warning}
		  <tr class="summary"><td style="text-align:center;"><div class="default10">{$item}</div></td></tr>
	    {/foreach}
		</table>
	  {/if}

	  {if ($t.datasets eq 0 && $t.schema_mode neq "new" && $t.schema_mode neq "static") || ($t.vright && !$t.rights[$t.vright])}
		<table cellspacing="0" class="data" style="margin-bottom:{if $iframe && !$t.data_day && !$t.data_month}0{else}2{/if}px;">
		  <tr><td style="padding-top:0px; padding-bottom:0px;">
		    {if $t.vright && !$t.rights[$t.vright]}{t}Access denied.{/t}{else}{t}No entries found.{/t}{/if}
		  </td></tr>
		</table>
	  {/if}
	  {if !$t.vright || $t.rights[$t.vright]}
	    {if !$t.function}
		  {include file=$t.template}
 	    {else}
		  <table class="data data_page"><tr><td>
		  {$t.function|modify::callfunc:$t.folder:$t.view|modify::htmlfield}
		  </td></tr></table>
		{/if}
	  {/if}

	  {if $sys.is_superadmin && !$popup && !$iframe && $t.schema_mode neq "static" && $folder.type neq "sys_custom_fields"}
		{include file="helper/customize.tpl"}
	  {/if}

	  {if !$iframe && $t.schema_mode neq "static"}
	    {include file="helper/filters.tpl"}
	    {include file="helper/pages.tpl"}
	  {/if}
	  </td></tr>
	  {if !$iframe && !$preview}
	    <tr><td valign="bottom" style="text-align:right;">
  		  {* You are not allowed to remove, alter or hide the copyright. *}
		  <div class="notice"><a href="http://www.simple-groupware.de" class="lnotice" target="_blank" onmouseover="set_html(this,'Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.');" onmouseout="set_html(this,'Powered by Simple Groupware.');">Powered by Simple Groupware.</a></div>
	    </td></tr>
	  {/if}
	  </table>
	  </div>
	  {if !$iframe && !$preview && !$popup}
	    <div id="content_pane" style="display:none;" class="noprint">
		  <div onmousedown="start_drag(resize_pane);" class="pane_spacer"></div>
		  <iframe name="pane" id="pane" src="about:blank" onload="show_pane();" style="width:100%; height:100%; border:1px;"></iframe>
	    </div>
	  {/if}
	  {if $sys.fixed_footer}
	  <div id="fixed_footer" style="padding-top:1px;">
		{$smarty.capture.footer|no_check}
		{$smarty.capture.filters|no_check}
		{$smarty.capture.pages|no_check}
	  </div>
	  {/if}
	</td>
  </tr>
</table>
</div>
</body>
</html>