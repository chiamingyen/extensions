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

<form id="tree_mountpoint_form" rel="{$mountpoint}" onsubmit="tree_scroll(0); ajax('folder_set_mountpoint',[tfolder,mountpoint_build()],locate_folder); return false;">
  <a style="float:right;" onclick="hide('tree_info');">X</a>
  <div class="tree_subpane">{t}Mountpoint{/t}</div>
  <table class="tree2" border="0" cellpadding="0" cellspacing="2">
	<tr><td>{t}Type{/t}&nbsp;</td><td>
      <select name="first" id="mount_proto" onchange="mountpoint_show(this.value);" style="width:100%;">
		<option value=""> {t}none{/t}
		{foreach key=key item=item from=$mountpoints}
		  <option value="{$key|replace:"sys_nodb_":""}">{$item}
		{/foreach}
	  </select>
	</td>
	</tr>
	<tbody id="mount_auth" style="display:none;">
	  <tr><td>{t}Username{/t}&nbsp;</td><td><input id="mount_user" type="Text" maxlength="255" style="width:100%;" value="" class="mp"></td></tr>
	  <tr><td>{t}Password{/t}</td><td><input id="mount_pass" type="password" maxlength="255" style="width:100%;" value="" class="mp"><br/>
	  <input id="mount_pass_check" type="checkbox" onclick="getObj('mount_pass').type = this.checked ? 'text':'password';"><label for="mount_pass_check">{t}Show password{/t}</label></td></tr>
	</tbody>
	<tbody id="mount_details" style="display:none;">
	  <tr><td>{t}Host{/t}</td><td><input id="mount_host" type="Text" maxlength="255" style="width:100%;" value="" class="mp"></td></tr>
	  <tr><td>{t}Port{/t}</td><td>
		<input id="mount_port" type="Text" maxlength="255" style="width:50%;" value="" class="mp mp2">&nbsp;
		<select id="mount_enc" style="width:45%;"><option value=""> {t}none{/t} <option value="ssl"> SSL <option value="tls"> TLS</select>
	  </td></tr>
	  <tr><td>{t}Options{/t}</td><td><input id="mount_options" type="Text" maxlength="255" style="width:100%;" value="" class="mp mp2"></td></tr>
	</tbody>
	<tr><td>{t}Path{/t}</td><td><input id="mount_path" type="Text" maxlength="255" style="width:100%;" value=""></td></tr>
	<tr><td></td><td><input type="Submit" value="{t}Ok{/t}" style="width:50px;"></td></tr>
  </table>
  <div style="border-top: {#border#}; margin-top:5px; margin-bottom:5px;"></div>
</form>