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
{config_load file="core_css.conf" section=$sys.style}
<html>
<head>
<title>Simple Groupware - Login</title>
{* You are not allowed to remove or alter the copyright. *}
<!-- 
	This website is brought to you by Simple Groupware
	Simple Groupware is an open source Groupware and Web Application Framework created by Thomas Bley and licensed under GNU GPL v2.
	Simple Groupware is copyright 2002-2012 by Thomas Bley.	Extensions and translations are copyright of their respective owners.
	More information and documentation at http://www.simple-groupware.de/
-->
<link media="all" href="images_php?css_style={$sys.style}&browser={$sys.browser.name}&{$smarty.const.CORE_VERSION}" rel="stylesheet" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="generator" content="Simple Groupware 0.743" />
<meta name="viewport" content="initial-scale=1.0; minimum-scale=1.0; maximum-scale=1.0;" />
<link href="ext/images/favicon.ico" rel="shortcut icon">
{literal}
<script>
function getObj(id) {
  return document.getElementById(id);
}
function set_html(obj,txt) {
  if (!obj) return;
  obj.innerHTML = txt;
}
function load() {
  getObj("login_table_obj").style.opacity = 0.75;
  if (top.sys && top.sys.username!="anonymous") {
    getObj("username").value = top.sys.username;
    getObj("redirect").checked = false;
    getObj("password").focus();
  } else {
    document.getElementById("username").focus();
  }
}
function sys_alert(str) {
  alert(str.replace(new RegExp("{t"+"}|{/t"+"}","g"), ""));
}
function generate_password(field) {
  var keys = "abcdefghijklmnopqrstuvwxyz1234567890@";
  var temp = "";
  for (i=0;i<8;i++) {
    temp += keys.charAt(Math.floor(Math.random()*keys.length));
  }
  sys_alert("{t}The new password is{/t}: " + temp);
  getObj(field).value = temp;
  getObj(field+"_confirm").value = temp;
}
function validate_signup() {
  if (getObj('spassword').value!="" && getObj('spassword').value==getObj('spassword_confirm').value) return true;
  sys_alert("{t}password not confirmed.{/t}");
  return false;
}
</script>
<style>
body {
  overflow:hidden;
  overflow-y:hidden;
  overflow-x:hidden;
  height:1px;
}
</style>
{/literal}
</head>
<body onload="load();">
<div class="bg_full"><img src="{#bg_login#}" style="width:100%; height:100%;"></div>
<noscript>
<div style="background-color:#FFFFFF; text-align:center;">
<h2>{t}Please enable Javascript in your browser.{/t}</h2>
</div>
</noscript>

{if $alert}
<div class="login_alert" style="text-align:center">
  <table style="margin:auto; text-align:center"><tr><td>
  <div class="default10">
  {foreach item=item from=$alert}
    {$item|sys_remove_trans|modify::nl2br}<br>
  {/foreach}
  </div>
  </td></tr></table>
</div>
{/if}

<div id="login_table_obj" style="text-align:center; {if $smarty.const.SELF_REGISTRATION}{if $sys.browser.is_mobile}top:10%;{else}top:33%;{/if}{/if}">
  <table style="margin:auto;"><tr><td class="login_table" style="{if !$sys.browser.is_mobile}padding:0 75px;{/if}">
    <a target="_blank" href="{#logo_link#}"><img src="{#logo_login#}"></a><br>
    <form method="post" action="index.php?folder={$login[0]}&view={$login[1]}&find={$login[2]}&page={$page}{$login_item}">
	<input type="hidden" name="loginform" value="true">
	<input type="text" id="username" name="username" style="margin-bottom:2px;" required="true">
	<input type="password" id="password" name="password" style="margin-bottom:2px;" required="true">
	<input type="submit" value=" {t}L o g i n{/t} " style="margin-bottom:2px;">
	<div class="default" style="padding:2px; padding-top:3px;">
	{if $page eq ""}<input class="checkbox" id="redirect" type="checkbox" name="redirect" value="1" style="margin:0px; margin-bottom:2px;" {if !$login[0] && !$login[2]}checked{/if}> <label for="redirect" class="default10">{t}Redirect to home directory{/t}</label>{/if}
	</div>
	</form>
  </td></tr>
  </table>

  {if $smarty.const.SELF_REGISTRATION}
  {strip}
  <br>
  <table style="margin:auto;"><tr><td class="login_table" style="{if !$sys.browser.is_mobile}padding:0 75px;{/if}">
    <form method="post" action="index.php" onsubmit="return validate_signup();">
	<input type="hidden" name="signupform" value="true">
	<input type="hidden" name="redirect" value="1">
	<table style="width:100%;">
	<tr>
	  <td style="text-align:center;" colspan="2">{t}Self registration{/t}<hr></td>
	</tr>
	<tr>
	  <td class="default10">{t}Username{/t}</td>
	  <td><input type="text" name="username" value=""/></td>
	</tr>
	<tr>
	  <td class="default10" rowspan="2">{t}Password{/t}</td>
	  <td><input type="password" id="spassword" name="password" value=""/>{" "}
		<input type="button" value="&lt;" onclick="generate_password('spassword');"/>
	  </td>
	</tr>
	<tr>
	  <td><input type="password" id="spassword_confirm" value=""/></td>
	</tr>
	<tr>
	  <td class="default10">{t}E-mail{/t}</td>
	  <td><input type="text" name="email" value=""/></td>
	</tr>
	<tr>
	  <td></td>
	  <td><input type="submit" value=" {t}R e g i s t e r{/t} "></td>
	</tr>
	</table>
	</form>
  </td></tr>
  </table>
  {/strip}
  {/if}
</div>
<div class="notice2"><a href="{#login_notice_link#}" class="lnotice" target="_blank">{#login_notice#}</a></div>
{* You are not allowed to remove or alter the copyright. *}
<div class="notice2 notice3"><a href="http://www.simple-groupware.de" class="lnotice" target="_blank" onmouseover="set_html(this,'Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.');" onmouseout="set_html(this,'Powered by Simple Groupware.');">Powered by Simple Groupware.</a></div>
</body>
</html>