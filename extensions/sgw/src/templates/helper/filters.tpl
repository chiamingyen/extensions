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
{if !$t.schema_mode && !$iframe && ($t.maxdatasets neq 0 || $t.sqlfilters)}
{capture name=filters}
<table cellspacing="0" class="data noprint" style="border-top:0px; margin-bottom:2px;">
  <tr class="summary2">
  <td>{t}Filter{/t}:</td>
  <td style="vertical-align:top; width:90%;">
	<form action="index.php?" method="get" onsubmit="asset_filter_submit(); return false;">
    {foreach item=filter from=$t.sqlfilters}
	  <div style="margin-bottom:2px;">
		<select class="filter_field">
		{foreach key=curr_id item=field from=$t.fields}
		  {if !$t.fields.$curr_id.HIDDENIN[$t.view] && !$t.fields.$curr_id.HIDDENIN.all}
			<option value="{$curr_id}" {if $filter.field eq $curr_id}selected{/if}> {$field.DISPLAYNAME|default:$curr_id|replace:"_":" "}
		  {/if}
		{/foreach}
		</select>
		<select class="filter_type">
		  <option value="like" {if $filter.type eq "like"}selected{/if}> {t}contains{/t}
		  <option value="nlike" {if $filter.type eq "nlike"}selected{/if}> {t}not contains{/t}
		  <option value="starts" {if $filter.type eq "starts"}selected{/if}> {t}starts with{/t}
		  <option value="eq" {if $filter.type eq "eq"}selected{/if}> {t}equal{/t}
		  <option value="neq" {if $filter.type eq "neq"}selected{/if}> {t}not equal{/t}
		  <option value="oneof" {if $filter.type eq "oneof"}selected{/if}> {t}one of{/t}
		  <option value="lt" {if $filter.type eq "lt"}selected{/if}> {t}lesser than{/t}
		  <option value="gt" {if $filter.type eq "gt"}selected{/if}> {t}greater than{/t}
		</select>
		<input type="text" class="filter_value" value="{$filter.value}" style="width:100px;">
		<input type="button" value="X" onclick="set_html(getFirstParentByName(this,'div'),''); asset_filter_submit();">
		&nbsp;{t}and{/t}
      </div>
	{/foreach}
	<div id="filter_rows"></div>
	<span id="filter_row">
	  <select class="filter_field">
      {foreach key=curr_id item=field from=$t.fields}
		{if !$t.fields.$curr_id.HIDDENIN[$t.view] && !$t.fields.$curr_id.HIDDENIN.all}
		  <option value="{$curr_id}"> {$field.DISPLAYNAME|default:$curr_id|replace:"_":" "}
		{/if}
	  {/foreach}
	  </select>
	  <select class="filter_type">
		<option value="like"> {t}contains{/t}
		<option value="nlike"> {t}not contains{/t}
		<option value="starts"> {t}starts with{/t}
		<option value="eq"> {t}equal{/t}
		<option value="neq"> {t}not equal{/t}
		<option value="oneof"> {t}one of{/t}
		<option value="lt"> {t}lesser than{/t}
		<option value="gt"> {t}greater than{/t}
	  </select>
	  <input type="text" class="filter_value" style="width:100px;">
	</span>
	<input type="submit" value="{t}Ok{/t}">
	{if $t.sqlfilters}
	  <input type="button" value="{t}Clear{/t}" onclick="locate('index.php?folder='+escape(tfolder)+'&view='+tview+'&filters=');">
	{/if}
	<input type="button" value="+" onclick="append_html('filter_rows','<div style=\'margin-bottom:2px;\'>'+getObj('filter_row').innerHTML+' &nbsp;{t}and{/t}</div>')">
	</form>
  </td>
  </tr>
</table>
{/capture}
{if !$sys.fixed_footer}{$smarty.capture.filters|no_check}{/if}
{/if}