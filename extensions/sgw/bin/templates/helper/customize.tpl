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
<table cellspacing="0" class="data noprint" style="border-top:0px; margin-bottom:2px;">
  <tr class="summary2">
  <td>Customize:</td>
  <td style="vertical-align:top; width:90%;">
	<form onsubmit="customize_field('{$folder.type}'); return false;">
	  <select id="cust_field" onchange="set_val('cust_field_name', options[selectedIndex].text);">
		{foreach key=curr_id item=field from=$t.fields}
		  <option value="{$curr_id}"> {$field.DISPLAYNAME|default:$curr_id|replace:"_":" "}
		{/foreach}
	  </select>
	  <select id="cust_field_action" onchange="
	  if (val(this)!='hidden' && val(this)!='hiddenin' && val(this)!='notinall') {ldelim} show('cust_field_text'); getObj('cust_field_name').focus(); {rdelim} else hide('cust_field_text');">
		<option value="">...
		<option value="hidden">hide in all views
		<option value="hiddenin">hide in this view
		<option value="notinall">disable
		<option value="rename">rename
		<optgroup label="Prepend new field as">
		<option value="text">Text
		<option value="textarea">Textarea
		<option value="checkbox">Checkbox
		<option value="date">Date
		<option value="datetime">Date time
		<option value="int">Integer
		<option value="graphviz">Graphviz
		<option value="htmlarea">HTML area
		<option value="pmwikiarea">PmWiki area
		<option value="rating">Rating
		</optgroup>
	  </select>
	  <span id="cust_field_text" style="display:none;">
		&nbsp;Name: <input type="Text" id="cust_field_name"/>
	  </span>
	  &nbsp;<input type="checkbox" class="checkbox" id="cust_field_folder" value="1" style="margin:0px;" /> <label for="cust_field_folder" style="vertical-align:middle;">Only for this folder</label>&nbsp;
	  <input type="submit" value="Ok"> &nbsp;
	  <a title="Show all rules" href="index.php?folder=~sys_custom_fields&view=display&find=sys_custom_fields|module={$folder.type}"><img src="ext/icons/all.gif"/></a>
	  <a title="Add a special rule" href="index.php?folder=~sys_custom_fields&view=new&defaults={ldelim}&quot;module&quot;:&quot;{$folder.type}&quot;,&quot;ffolder&quot;:&quot;{$t.folder}&quot;{rdelim}">
	  <img src="ext/icons/add.gif"/></a>
	</form>

	<table><tr>
	{foreach key=curr_id item=field from=$t.fields_all}
	{if $field.CUSTOMIZED}
	  <td style="padding:4px 8px 2px 0px; border:0px;">
		{$field.DISPLAYNAME|default:$curr_id|replace:"_":" "}&nbsp;
		<a title="Details" target="pane" href="index.php?view=details&find=sys_custom_fields|module={$folder.type},field={$curr_id}&iframe=1">
		<img src="ext/icons/details.gif"/></a>&nbsp;
		<a title="Edit" target="_blank" href="index.php?view=edit&find=sys_custom_fields|module={$folder.type},field={$curr_id}">
		<img src="ext/icons/edit.gif"/></a>
	  </td>
	{/if}
	{/foreach}
	</tr></table>
	</div>
  </td>
  </tr>
</table>