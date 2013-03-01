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
  <link media="all" href="images.php?css_style=core&{$smarty.const.CORE_VERSION}" rel="stylesheet" type="text/css" />
  <script type="text/javascript" src="ext/lib/json/json.js"></script>
  <script type="text/javascript" src="ext/js/functions_sql.js?{$smarty.const.CORE_VERSION}"></script>
  <style>
{literal}
body {
  overflow-y:hidden;
}
#output {
  position:absolute;
  width:60%;
  height:100%;
  top:0px;
  bottom:0px;
  overflow:auto;
}
#code {
  float:right;
  width:40%;
  margin-top:2px;
  z-index:1;
}
.codebox {
  width:100%;
  margin-bottom:2px;
  font-size:13px;
}
#selectbox {
  clear:both;
  width:100%;
  margin-bottom:2px;
}
input {
  margin-bottom:4px;
}
table td {
  font-size:12px;
  padding:2px;
}
#showbody {
  position:absolute;
  width:24px;
  right:16px;
  background-color:inherit;
  border-bottom:1px solid #C0C0C0;
  border-left:1px solid #C0C0C0;
  z-index:1;
}
{/literal}
  </style>
  <title>{$title}</title>
{* You are not allowed to remove or alter the copyright. *}
<!-- 
	This website is brought to you by Simple Groupware
	Simple Groupware is an open source Groupware and Web Application Framework created by Thomas Bley and licensed under GNU GPL v2.
	Simple Groupware is copyright 2002-2012 by Thomas Bley.	Extensions and translations are copyright of their respective owners.
	More information and documentation at http://www.simple-groupware.de/
-->
</head>
<body onload="start(); resizeit();" onresize="resizeit();">
  {if $content neq ""}<div id="showbody" style="display:none;">&nbsp;<a href="#" onclick="show('code'); hide('showbody'); resizeit();">&lt;=</a></div>{/if}
  <div id="code" style="{if $content eq ""}width:100%;{/if}">
	{if $content neq ""}<div style="position:absolute;">&nbsp;<a href="#" onclick="hide('code'); show('showbody'); resizeit();">=&gt;</a></div>{/if}
	<div style="text-align:center;">
	  <a href="?console=sql" {if $console eq "sql"}class="bold"{/if}>SQL</a> - 
	  <a href="?console=php" {if $console eq "php"}class="bold"{/if}>PHP</a> - 
	  <a href="?console=sys" {if $console eq "sys"}class="bold"{/if}>SYS</a>
	</div>
	<form method="post" action="console.php">
	<input type="hidden" name="token" value="{""|modify::get_form_token}">
	{if $console eq "sql" && $auto_complete}
	  <input type="hidden" id="database" value="{$database|escape:"html"}" />
	  <textarea name="code" id="codebox" class="codebox" spellcheck="false">{$code|escape:"html"}</textarea><br>
	  <select size="2" id="selectbox" ondblclick="select_insert(obj('codebox'),obj(this.id));"></select>
	{else}
	  <textarea name="code" id="codebox" class="codebox" spellcheck="false">{$code|escape:"html"}</textarea>
	{/if}
	<div style="text-align:center;" id="buttons">
	  <input type="submit" value="    {t}Execute{/t}  [ Alt+e ]    " accesskey="e">&nbsp;
	  {if $console eq "sql"}
		<input type="submit" name="full_texts" value=" {t}Execute{/t} {t}Full texts{/t}  [ Alt+f ] " accesskey="f">&nbsp;
		<input type="submit" name="vertical" value=" {t}Execute{/t} {t}vertical{/t}  [ Alt+v ] " accesskey="v">&nbsp;
	  {/if}
	  <input type="button" value=" {t}Clear{/t} " onclick="obj('codebox').value=''; obj('codebox').focus();">
	  <input type="hidden" name="console" value="{$console|escape:"html"}">&nbsp;
	  {if $console neq "sql"}{t}Time limit{/t} ({t}seconds{/t}): <input type="text" name="tlimit" value="{$tlimit|escape:"html"}" style="width:40px;" />&nbsp;{/if}
	  {t}Memory limit{/t} (MB): <input type="text" name="mlimit" value="{$mlimit|escape:"html"}" style="width:34px;" />
	</div>
	</form>
  </div>
  {if $content neq ""}<div id="output"><div style="padding:4px;">{$content}</div><div>{/if}
</body>
</html>