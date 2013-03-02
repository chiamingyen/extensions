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
{config_load file="core_css.conf" section=$style}

<form onsubmit="tree_scroll(0); ajax('folder_rename',[tfolder,val('frenametitle'),val('ftype'),val('fdescription'),val('ficon'),val('fnotification')], locate_folder); return false;">
  <a style="float:right;" onclick="hide('tree_info');">X</a>
  <div class="tree_subpane">Rename folder</div>
  <table class="tree2" border="0" cellpadding="0" cellspacing="2">
	<tr>
	<td><label for="frenametitle">Name</label></td>
	<td><input id="frenametitle" name="first" type="Text" maxlength="40" style="width:100%;" value="{$folder.name}" required="true"></td>
	</tr>
	{if $isdbfolder && $folder.assets eq 0}
	<tr>
	<td><label for="ftype">Module</label></td>
	<td>
	  <select id="ftype" style="width:100%;" required="true">
	  {foreach key=key item=item from=$sys_schemas}
		{if $item[0] eq " "}<optgroup label="{$item}"/>{else}<option value="{$key}" {if $key eq $folder.type}selected{/if}>{$item}{/if}
	  {/foreach}
	  </select>
	</td>
	</tr>
	{/if}
	{if $isdbfolder}
	<tr>
	<td><label for="ficon">Icon</label> (<a href="#" onclick="nWin('ext/modules/folder_icons.php?obj=ficon'); return false;">?</a>)</td>
	<td>
	  <select id="ficon" style="width:100%;">
	  <option value=""> Default
	  {foreach key=key item=item from=$sys_icons}
		<option value="{$key}" {if $key eq $folder.icon}selected{/if}>{$item}
	  {/foreach}
	  </select>
	</td>
	</tr>
	<tr>
	<td style="white-space:nowrap;"><label for="fdescription">Description&nbsp;</label></td>
	<td style="width:100%;"><textarea id="fdescription" rows="4" style="width:100%; height:65px;">{$folder.description}</textarea></td>
	</tr>
	<tr>
	<td style="white-space:nowrap;">
	  <label for="fnotification">Notification&nbsp;<br/>
	  (E-mail) <a href="#" onclick="sys_alert('Syntax:\nabc@doecorp.com, cc:abcd@doecorp.com, bcc:abcde@diecorp.com,\n@Group, cc:@Group1, bcc:@Group2');">(?)</a></label>
	</td>
	<td style="width:100%;"><textarea id="fnotification" rows="2" style="width:100%; height:30px;">{$folder.notification}</textarea></td>
	</tr>
	{/if}
	<tr><td></td><td><input type="submit" value="Ok" style="width:50px;"></td></tr>
  </table>
</form>
<div style="border-top: {#border#}; margin-top:5px; margin-bottom:10px;"></div>

<form onsubmit="tree_scroll(0); ajax('folder_create',[val('cmultiple')?val('fmultiple'):tfolder,val('ftitle'),val('ftype_new'),val('fdescription_new'),val('ficon_new'),val('ffirst')], locate_folder); return false;">
  <div class="tree_subpane">New folder</div>
  <table class="tree2" border="0" cellpadding="0" cellspacing="2">
	<tr>
	<td><label for="ftitle">Name</label></td>
	<td><input id="ftitle" type="Text" maxlength="40" style="width:100%;" value="" required="true"></td>
	</tr>
	{if $isdbfolder}
	<tr>
	<td><label for="ftype_new">Module</label></td>
	<td>
	  <select id="ftype_new" style="width:100%;" required="true">
	  {foreach key=key item=item from=$sys_schemas}
		{if $item[0] eq " "}<optgroup label="{$item}"/>{else}<option value="{$key}" {if $key eq $folder.type}selected{/if}>{$item}{/if}
	  {/foreach}
	  </select>
	</td>
	</tr>
	<tr>
	<td><label for="ficon_new">Icon</label> (<a href="#" onclick="nWin('ext/modules/folder_icons.php?obj=ficon_new'); return false;">?</a>)</td>
	<td>
	  <select id="ficon_new" style="width:100%;">
	  <option value=""> Default
	  {foreach key=key item=item from=$sys_icons}
		<option value="{$key}" {if $key eq $folder.icon}selected{/if}> {$item}
	  {/foreach}
	  </select>
	</td>
	</tr>
	<tr>
	<td style="white-space:nowrap;"><label for="fdescription_new">Description&nbsp;</label></td>
	<td style="width:100%;"><textarea id="fdescription_new" rows="4" style="width:100%; height:65px;"></textarea></td>
	</tr>
	<tr>
	<td><label for="ffirst">First in list</label></td>
	<td><input id="ffirst" type="checkbox" value="1" checked class="checkbox"></td>
	</tr>
	<tr>
	<td><label for="cmultiple">Multiple</label></td>
	<td><input id="cmultiple" type="checkbox" value="1" class="checkbox" onchange="showhide('fmultiple_line');">
	</td>
	</tr>
	<tr id="fmultiple_line" style="display:none;">
	<td><label for="fmultiple">Parent folder</label></td>
	<td><input id="fmultiple" type="Text" style="width:100%;" value="{$folder.id|modify::getpathfull:false:"/"}/*/">
	</td>
	</tr>
	{/if}
	<tr><td></td><td><input type="submit" value="Ok" style="width:50px;"></td></tr>
  </table>
</form>
<div style="border-top: {#border#}; margin-top:5px; margin-bottom:5px;"></div>