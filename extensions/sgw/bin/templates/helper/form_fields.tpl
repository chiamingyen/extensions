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
{if is_array($data_item) && $data_item._lck}
<table cellspacing="0" class="data" align="center" style="{if $sys.browser.is_mobile}width:auto;{else}width:40%;{/if} margin-top:4px;">
<tr class="id"><td class="notification">Warning</td></tr>
<tr class="summary"><td style="text-align:center;"><div class="default10">{$data_item._lck}&nbsp;</div></td></tr>
</table>
{/if}

{if $t.errors.$prefix && count($t.errors.$prefix) > 0}
<table cellspacing="0" class="data" border="0" align="center" style="{if $sys.browser.is_mobile}width:auto;{else}width:50%;{/if} margin-top:4px;">
<tr class="id"><td colspan="2">Error</td></tr>
{foreach key=key item=item from=$t.errors.$prefix}
  {foreach key=key2 item=item2 from=$item}
    {if $item2[1] neq ""}<tr><td>{$item2[0]}</td><td>{$item2[1]}</td></tr>{/if}
  {/foreach}
{/foreach}
</table>
{/if}

<table cellspacing="0" class="data" style="margin:0px;">
<tr rel="{$data_item._id|default:$data_item._id}" class="mdown id_header asset_{$data_item._id|default:$data_item._id}">
  {if $mode neq "create"}{include file="helper/selitem.tpl"}{/if}
  <td class="bold" style="width:100%;">{$tab_item.DISPLAYNAME|default:$tab_item.NAME}</td>
  <td>&nbsp;</td>
</tr></table>

<div style="display:none; padding-bottom:10px;"></div>
<div><table cellspacing="0" cellpadding="1" border="0" class="data" style="border-top:0px;">

{foreach key=key item=item from=$t.fields}
{assign var="item_name" value=$item.NAME}
{assign var="item_name" value=$prefix$item_name}

{if $item.SIMPLE_DEFAULT neq ""}
  {assign var="item_value" value=$item.SIMPLE_DEFAULT}
{else}
  {assign var="item_value" value=$item.SIMPLE_DEFAULTS.$prefix}
{/if}

{if $item.SIMPLE_TAB[0] eq $tab_key && ($item.HIDDENIN[$t.view] || $item.HIDDENIN.all) && !$item.EDITABLE && $item.SIMPLE_TYPE neq "folder"}
  <input type="hidden" name="{$item_name}" value="{$item_value|default:$item.DATA[0]}">
{elseif ($item.SIMPLE_TAB[0] eq $tab_key || $t.disable_tabs) && $item.SIMPLE_TYPE neq "id"}
  {cycle assign="cycle_dataitem" values="items_even,items_odd"}
  <tr class="{if $t.errors.$prefix[$key][0] neq ""}hl_items{else}{$cycle_dataitem}{/if}">
  {if !$sys.browser.is_mobile}
	<td style="width:15%;">
	  <label title="{$key}" for="{$item_name}" {if $item.REQUIRED}class="bold"{/if}>{$item.DISPLAYNAME|default:$key}</label>
	  {if $item.DESCRIPTION[0].VALUE}&nbsp;(<a href="#" title="{$item.DESCRIPTION[0].HINT}" onclick="{$item.DESCRIPTION[0].VALUE|replace:"@prefix@":$prefix} return false;">{$item.DESCRIPTION[0].TITLE|default:"?"}</a>){/if}
	</td>
	<td style="width:95%;">
  {else}
	<td style="width:15%; padding-top:4px; padding-bottom:2px;">
	  <label title="{$key}" for="{$item_name}" class="bold">{$item.DISPLAYNAME|default:$key}{if $item.REQUIRED}*{/if}:</label>
	  {if $item.DESCRIPTION[0].VALUE}&nbsp;(<a href="#" title="{$item.DESCRIPTION[0].HINT}" onclick="{$item.DESCRIPTION[0].VALUE|replace:"@prefix@":$prefix} return false;">{$item.DESCRIPTION[0].TITLE|default:"?"}</a>){/if}
	  &nbsp;
  {/if}

  {if $item.READONLYIN[$t.view] || $item.READONLYIN.all || (($item.HIDDENIN[$t.view] || $item.HIDDENIN.all) && !$item.EDITABLE)}
	{if $item.SIMPLE_TYPE eq "select" || $item.SIMPLE_TYPE eq "multitext"}
	  <input type="hidden" name="{$item_name}" value="{$item_value}">{$item_value}
	{else}
	  {if $item.SIMPLE_TYPE eq "folder"}
		{if count($t.folders)>1 && $mode eq "create"}
		  <select name="{$item_name}" style="width:45%;">
		    {foreach key=fkey item=fitem from=$t.folders}
		      <option value="{$fitem[0]}" {if $item_value eq $fitem[0]}selected{/if}> {$fitem[0]|modify::getpath}
			{/foreach}
		  </select>
		{else}
	  	  <input type="hidden" name="{$item_name}" value="{$item_value|default:$item.DATA[0]}">
	      {$item_value|default:$item.DATA[0]|modify::getpath}
		{/if}
	  {else}
	    <input type="hidden" name="{$item_name}" value="{$item_value|default:$item.DATA[0]}">
	    {$item_value|default:$item.DATA[0]}
	  {/if}
	{/if}
  {elseif $item.SIMPLE_TYPE eq "text"}
	<input type="Text" name="{$item_name}" id="{$item_name}" value="{$item_value}" style="width:100%;" maxlength="{$item.SIZE}" {if $item.REQUIRED}required="true"{/if}>
  {elseif $item.SIMPLE_TYPE eq "int" || $item.SIMPLE_TYPE eq "float"}
    <input type="Text" name="{$item_name}" id="{$item_name}" value="{$item_value}" style="width:45%;" maxlength="{$item.SIZE}" {if $item.REQUIRED}required="true"{/if}>
  {elseif $item.SIMPLE_TYPE eq "password"}
	<table cellpadding="0" cellspacing="0" border="0" style="width:100%;"><tr><td style="width:100%; text-align:left; padding:0px; margin:0px; border:0px;">
    <input type="Password" name="{$item_name}" id="{$item_name}" value="{$item_value}" style="width:100%;" maxlength="{$item.SIZE}" {if $item.REQUIRED}required="true"{/if}><br>
    <input type="Password" id="{$item_name}_confirm" rel="{$item.DISPLAYNAME|default:$key}" value="{$item_value}" style="width:100%;" maxlength="{$item.SIZE}" {if $item.REQUIRED}required="true"{/if}>
	</td><td style="width:45%;">
	<input type="button" value="Generate" onclick="generate_password('{$item_name}');">
	</td></tr></table>
  {elseif $item.SIMPLE_TYPE eq "select" && $item.SIMPLE_SIZE eq 1}
	<table cellpadding="0" cellspacing="0" border="0" style="width:100%;"><tr>
	<td style="padding:0px; margin:0px; border:0px; width:45%;">
	  <select name="{$item_name}" id="{$item_name}" size="1" style="width:100%;" {if $item.REQUIRED}required="true"{/if} onchange="{$item.FORM_ONCHANGE}" {if $item.FORM_ONLOAD}class="onload" onload="{$item.FORM_ONLOAD}"{/if}>
	  {if !$item.REQUIRED}<option value="{if $item.DB_TYPE eq "int"}0{/if}" {if $item_value eq ""}selected{/if}>&nbsp;{/if}
	  {foreach key=dkey item=item_data from=$item_value|@modify::explode}
	    {if $dkey neq "" && (!$item.DATA[0].$dkey || count($item.DATA) neq 1 || $item.SIMPLE_LOOKUP[0].overload)}<option value="{$dkey|trim}" selected> {$item_data}{/if}
	  {/foreach}
	  {if count($item.DATA) eq 1 && !$item.SIMPLE_LOOKUP[0].overload}
        {foreach key=dkey item=ditem from=$item.DATA[0] name=items}
	      {if $ditem[0] eq " "}<optgroup label="{$ditem}">{else}<option value="{$dkey|trim}" {if $dkey eq $item_value || ($item_value eq "" && $smarty.foreach.items.first && $item.REQUIRED)}selected{/if}>{$ditem}{/if}
        {/foreach}
	  {/if}
	  </select>
	</td><td style="text-align:center; white-space:nowrap;">
	  {foreach key=dkey item=lookup_data from=$item.SIMPLE_LOOKUP}
	    <input type="hidden" id="{$item_name}_{$dkey}_ticket" value="{$lookup_data.ticket}"/>
		<div class="tab{$item_name}2 tab{$item_name}2{$dkey}" style="{if $dkey neq 0}display:none;{/if} padding-bottom:2px;">
		  <input type="button" value="..." onclick="open_window('index.php?fschema={$lookup_data.schema}&lookup={$item_name}|{$lookup_data.ticket}&view=display&popup=1','popup',640,400);">
		  &nbsp;<input type="image" class="image" src="ext/icons/refresh.gif" onclick="return refresh_data('{$item_name}_{$dkey}');">
		</div>
	  {/foreach}
	  {if count($item.DATA)>1 || $item.SIMPLE_LOOKUP[0].overload || $item.ALLOW_CUSTOM}
	  <div style="font-size:9px;">
	    <input type="button" value="&lt;&lt;" onclick="additems('{$item_name}');">
		{if count($item.DATA)>1 || $item.SIMPLE_LOOKUP[0].overload}
		&nbsp;<input type="button" value="+" onclick="if (getObj('{$item_name}_0_box').size <= 4) showhide('{$item_name}_size_dec');
		  {foreach key=dkey item=ditem from=$item.DATA}getObj('{$item_name}_{$dkey}_box').size+=4;{/foreach}">
		{/if}
	    <div style="display:none; padding-top:2px;" id="{$item_name}_size_dec">
	      <input type="button" value="-" onclick="{foreach key=dkey item=ditem from=$item.DATA}getObj('{$item_name}_{$dkey}_box').size-=4;{/foreach}
		    if (getObj('{$item_name}_0_box').size <= 4) showhide('{$item_name}_size_dec');">
	    </div>
	  </div>
	  {/if}
	</td>
	<td style="width:45%; padding:0px;">
	  {if count($item.DATA)>1}
		<div>
	    {foreach key=dkey item=ditem from=$item.DATA}
		  <span id="tab{$item_name}{$dkey}" class="tab{$item_name} {if $dkey neq 0}tabstyle{else}tabstyle2{/if}" onclick="change_tab('tab{$item_name}','{$dkey}');">{$item.DATA_TITLE.$dkey|default:$dkey+1}</span>
		{/foreach}
		</div>
	  {/if}
	  {if count($item.DATA)>1 || $item.SIMPLE_LOOKUP[0].overload || $item.ALLOW_CUSTOM}
	    {foreach key=dkey item=item_data from=$item.DATA}
		  <div class="tab{$item_name}2 tab{$item_name}2{$dkey}" {if $dkey neq 0}style="display:none"{/if}>
		  {if count($item.DATA)>1 || $item.SIMPLE_LOOKUP[0].overload}
		    <select page="1" ondblclick="additem('{$item_name}',this)" id="{$item_name}_{$dkey}_box" class="{$item_name}_custom" size="3" style="width:100%;">
	  	    {foreach key=ikey item=ditem from=$item_data}
			  {if $ditem[0] eq " "}<optgroup label="{$ditem}">{else}<option value="{$ikey|trim}">{$ditem}{/if}
		    {/foreach}
		    </select>
		  {/if}
		  {if $item.SIMPLE_LOOKUP.$dkey.overload}
			<div style="margin-top:1px;">
			  <input type="text" value="Search" id="{$item_name}_{$dkey}_custom" {if $item.ALLOW_CUSTOM}class="{$item_name}_custom"{/if} style="width:50%;" onkeypress="if (getmykey(event)==13) return search_data('{$item_name}_{$dkey}', 1);" onfocus="searchbox(this);">
			  &nbsp;<input type="button" value="X" id="{$item_name}_{$dkey}_x" style="display:none; margin-right:4px;" onclick="return refresh_data_clear('{$item_name}_{$dkey}');">
			  <input type="button" value="&lt;&lt;" id="{$item_name}_{$dkey}_prev" style="display:none; margin-right:4px;" onclick="return page_data('{$item_name}_{$dkey}',-1);">
			  <input type="button" value="&gt;&gt;" id="{$item_name}_{$dkey}_next" onclick="return page_data('{$item_name}_{$dkey}',1);">
			</div>
	      {elseif $item.ALLOW_CUSTOM}
			<input type="text" class="{$item_name}_custom" value="Other" style="width:100%; margin-top:2px;" onkeypress="if (getmykey(event)==13) return additems('{$item_name}');" onfocus="searchbox(this);">
	      {/if}
		  </div>
	    {/foreach}
	  {/if}
	</td></tr></table>
  {elseif $item.SIMPLE_TYPE eq "select"}
	<table cellpadding="0" cellspacing="0" border="0" style="width:100%;"><tr>
	<td style="padding:0px; margin:0px; border:0px; width:45%;">
	  <select name="{$item_name}[]" id="{$item_name}" multiple size="{$item.SIMPLE_SIZE|default:4}" style="width:100%;">
	  {foreach key=key item=data_item_def from=$item_value|@modify::explode}
		{if $data_item_def[0] eq " "}<optgroup label="{$data_item_def}">{else}<option value="{$key|trim}" {if $key eq $item_value}selected{/if}>{$data_item_def}{/if}
	  {/foreach}
	  </select>
	</td><td style="text-align:center; white-space:nowrap;">
	  {foreach key=dkey item=lookup_data from=$item.SIMPLE_LOOKUP}
	    <input type="hidden" id="{$item_name}_{$dkey}_ticket" value="{$lookup_data.ticket}"/>
		<div class="tab{$item_name}2 tab{$item_name}2{$dkey}" style="{if $dkey neq 0}display:none;{/if} padding-bottom:2px;">
		  <input type="button" value="..." onclick="open_window('index.php?fschema={$lookup_data.schema}&lookup={$item_name}|{$lookup_data.ticket}&view=display&popup=1','popup',640,400);">
		  &nbsp;<input type="image" class="image" src="ext/icons/refresh.gif" onclick="return refresh_data('{$item_name}_{$dkey}');">
		</div>
	  {/foreach}	  
	  <div style="padding-bottom:2px;"><input type="button" value="&lt;&lt;" onclick="additems('{$item_name}');"></div>
	  <div style="padding-bottom:2px; font-size:9px;">
	    <input type="button" value="X" onclick="removeitem('{$item_name}');">
		&nbsp;<input type="button" value="+" onclick="resize_obj('{$item_name}',60);
		  if (getObj('{$item_name}_0_box').size <= 4) showhide('{$item_name}_size_dec');
		  {foreach key=dkey item=ditem from=$item.DATA}getObj('{$item_name}_{$dkey}_box').size+=4;{/foreach}">
	  </div>
	  <div style="display:none;" id="{$item_name}_size_dec">
	    <input type="button" value="-" onclick="resize_obj('{$item_name}',-60);
		  {foreach key=dkey item=ditem from=$item.DATA}getObj('{$item_name}_{$dkey}_box').size-=4;{/foreach}
		  if (getObj('{$item_name}_0_box').size <= 4) showhide('{$item_name}_size_dec');">
	  </div>
	</td>
	<td style="width:45%; padding:0px;">
	  {if count($item.DATA)>1}
		<div>
	    {foreach key=dkey item=ditem from=$item.DATA}
		  <span id="tab{$item_name}{$dkey}" class="tab{$item_name} {if $dkey neq 0}tabstyle{else}tabstyle2{/if}" onclick="change_tab('tab{$item_name}','{$dkey}');">{$item.DATA_TITLE.$dkey|default:$dkey+1}</span>
		{/foreach}
		</div>
	  {/if}
	  {foreach key=dkey item=item_data from=$item.DATA}
		<div class="tab{$item_name}2 tab{$item_name}2{$dkey}" {if $dkey neq 0}style="display:none"{/if}>
		<select page="1" ondblclick="additem('{$item_name}',this)" id="{$item_name}_{$dkey}_box" class="{$item_name}_custom" multiple size="{$item.SIMPLE_SIZE|default:4}" style="width:100%;">
	  	{foreach key=key item=data_item_def from=$item_data}
		  {if $data_item_def[0] eq " "}<optgroup label="{$data_item_def}">{else}<option value="{$key|trim}">{$data_item_def}{/if}
		{/foreach}
		</select>
		{if $item.SIMPLE_LOOKUP.$dkey.overload}
		  <div style="margin-top:1px;">
			<input type="text" value="Search" id="{$item_name}_{$dkey}_custom" {if $item.ALLOW_CUSTOM}class="{$item_name}_custom"{/if} style="width:50%;" onkeypress="if (getmykey(event)==13) return search_data('{$item_name}_{$dkey}', 1);" onfocus="searchbox(this);">
			&nbsp;<input type="button" value="X" id="{$item_name}_{$dkey}_x" style="display:none; margin-right:4px;" onclick="return refresh_data_clear('{$item_name}_{$dkey}');">
			<input type="button" value="&lt;&lt;" id="{$item_name}_{$dkey}_prev" style="display:none; margin-right:4px;" onclick="return page_data('{$item_name}_{$dkey}',-1);">
			<input type="button" value="&gt;&gt;" id="{$item_name}_{$dkey}_next" onclick="return page_data('{$item_name}_{$dkey}',1);">
		  </div>
	    {elseif $item.ALLOW_CUSTOM}
		  <input type="text" class="{$item_name}_custom" value="Other" style="width:100%; margin-top:2px;" onkeypress="if (getmykey(event)==13) return additems('{$item_name}');" onfocus="searchbox(this);">
	    {/if}
		</div>
	  {/foreach}
	</td></tr></table>
  {elseif $item.SIMPLE_TYPE eq "dateselect"}
	<table cellpadding="0" cellspacing="0" border="0" style="width:100%;"><tr>
	<td style="padding:0px; margin:0px; border:0px; width:45%;">
	  <select name="{$item_name}[]" id="{$item_name}" multiple size="{$item.SIMPLE_SIZE|default:4}" style="width:100%;" {if $item.REQUIRED}required="true"{/if}>
	  {foreach key=key item=data_item_def from=$item_value|@modify::explode}
		<option value="{$key|trim}" {if $key eq $item_value}selected{/if}>{$data_item_def}
	  {/foreach}
	  </select>
	</td><td style="text-align:center;">
	  <div style="padding-bottom:2px;"><input type="button" value="&lt;&lt;" onclick="additems('{$item_name}');"></div>
	  <input type="button" value="X" onclick="removeitem('{$item_name}');">
	</td>
	<td style="width:45%; padding:0px;">
	  <input type="Text" class="{$item_name}_custom" id="{$item_name}_custom" value="" style="width:150px;" maxlength="11" onkeypress="if (getmykey(event)==13) return additems('{$item_name}');">
	  &nbsp;<input type="button" value="..." onclick="calendar('{$item_name}_custom',false);">
	</td></tr></table>
  {elseif $item.SIMPLE_TYPE eq "multitext"}
	<table cellpadding="0" cellspacing="0" border="0" style="width:100%;"><tr>
	<td style="padding:0px; margin:0px; border:0px; width:45%; height:100%;">
	  <textarea name="{$item_name}" id="{$item_name}" rel="no_resize" rows="4" style="width:100%; height:100%; margin:0px;" {if $item.REQUIRED}required="true"{/if}>{$item_value|replace:"|":", "}</textarea>
	</td><td style="text-align:center; white-space:nowrap;">
	  {foreach key=dkey item=lookup_data from=$item.SIMPLE_LOOKUP}
	    <input type="hidden" id="{$item_name}_{$dkey}_ticket" value="{$lookup_data.ticket}"/>
		<div class="tab{$item_name}2 tab{$item_name}2{$dkey}" style="{if $dkey neq 0}display:none;{/if} padding-bottom:2px;">
		  <input type="button" value="..." onclick="open_window('index.php?fschema={$lookup_data.schema}&lookup={$item_name}|{$lookup_data.ticket}&view=display&popup=1','popup',640,400);">
		  &nbsp;<input type="image" class="image" src="ext/icons/refresh.gif" onclick="return refresh_data('{$item_name}_{$dkey}');" />
		</div>
	  {/foreach}
	  <input type="button" value="&lt;&lt;" onclick="additems('{$item_name}');">
	  <div style="padding-top:2px;">
	    <input type="button" value="+" onclick="resize_obj('{$item_name}',60);
		  if (getObj('{$item_name}_0_box').size <= 4) showhide('{$item_name}_size_dec');
		  {foreach key=dkey item=ditem from=$item.DATA}getObj('{$item_name}_{$dkey}_box').size+=4;{/foreach}">
	  </div>
	  <div style="padding-top:2px; display:none;" id="{$item_name}_size_dec">
	    <input type="button" value="-" onclick="resize_obj('{$item_name}',-60);
		  {foreach key=dkey item=ditem from=$item.DATA}getObj('{$item_name}_{$dkey}_box').size-=4;{/foreach}
		  if (getObj('{$item_name}_0_box').size <= 4) showhide('{$item_name}_size_dec');">
	  </div>
	</td><td style="width:45%; padding:0px;" valign="top">
	  {if count($item.DATA)>1}
		<div>
	    {foreach key=dkey item=ditem from=$item.DATA}
		  <span id="tab{$item_name}{$dkey}" class="tab{$item_name} {if $dkey neq 0}tabstyle{else}tabstyle2{/if}" onclick="change_tab('tab{$item_name}','{$dkey}');">{$item.DATA_TITLE.$dkey|default:$dkey+1}</span>
		{/foreach}
		</div>
	  {/if}
	  {foreach key=dkey item=item_data from=$item.DATA}
		<div class="tab{$item_name}2 tab{$item_name}2{$dkey}" {if $dkey neq 0}style="display:none"{/if}>
		  <select page="1" ondblclick="additem('{$item_name}',this)" id="{$item_name}_{$dkey}_box" class="{$item_name}_custom" multiple size="4" style="width:100%;">
  		  {foreach key=key item=data_item_def from=$item_data}
			{if $data_item_def[0] eq " "}<optgroup label="{$data_item_def}">{else}<option value="{$key|trim}">{$data_item_def}{/if}
		  {/foreach}
		  </select>
		  {if $item.SIMPLE_LOOKUP.$dkey.overload}
		  <div style="margin-top:1px;">
			<input type="text" value="Search" id="{$item_name}_{$dkey}_custom" style="width:50%;" onkeypress="if (getmykey(event)==13) return search_data('{$item_name}_{$dkey}', 1);" onfocus="searchbox(this);">
			&nbsp;<input type="button" value="X" id="{$item_name}_{$dkey}_x" style="display:none; margin-right:4px;" onclick="return refresh_data_clear('{$item_name}_{$dkey}');">
			<input type="button" value="&lt;&lt;" id="{$item_name}_{$dkey}_prev" style="display:none; margin-right:4px;" onclick="return page_data('{$item_name}_{$dkey}',-1);">
			<input type="button" value="&gt;&gt;" id="{$item_name}_{$dkey}_next" onclick="return page_data('{$item_name}_{$dkey}',1);">
		  </div>
		  {/if}
		</div>
	  {/foreach}
	</td></tr></table>
  {elseif $item.SIMPLE_TYPE eq "checkbox"}
	<input type="hidden" name="{$item_name}" value="">
	<input type="Checkbox" name="{$item_name}" id="{$item_name}" value="1" {if $item_value}checked{/if} class="checkbox {if $sys.browser.is_mobile}checkbox3{/if}">
  {elseif $item.SIMPLE_TYPE eq "textarea"}
	{if !sys_contains($item.FORM, "no_template_bar")}
	  <input type="hidden" id="{$item_name}_ticket" value="templates"/>
	  <input type="button" value="..." onclick="open_window('index.php?fschema=templates&lookup={$item_name}|templates&view=display&popup=1','popup',640,400);">&nbsp;
	  <input type="text" id="{$item_name}_custom" value="" style="width:175px;" onkeypress="if (getmykey(event)==13) return search_data('{$item_name}', 1);">&nbsp;
	  <input type="button" value="Search" onclick="search_data('{$item_name}', 1);">&nbsp;
	  <input type="button" value="Add" onclick="additem_atcursor('{$item_name}', '{$item_name}_box');">&nbsp;
	  <select id="{$item_name}_box" size="1" style="width:175px;"></select>&nbsp;&nbsp;
	{/if}
	{if !sys_contains($item.FORM, "no_formatting_button")}
	  <input type="button" value="Text formatting rules" onclick="alert('
Syntax (at least 20 characters):\n
\n
underline: _word_\n
bold: *word*\n
Link: http://..., www...\n
\n
Collapse text:\n
&gt; some text\n
&gt; some text\n
\n
Short links:\n
Search by ID (Asset): [module/id/link text]\n
Search by ID (Asset): [module/id/link text/view]\n
Folder: [/folder-id]\n
Folder: [/folder-id/view]\n
Field is equal to value: [module/field=value/link text]\n
Field contains value: [module/field~value/link text]\n
Combination: [module/field=value,field2=value2/link text]\n
\n
Examples: Short links\n
[tasks/101/{"Task with ID %s"|sprintf:"101"}]\n
[tasks/101/{"Task with ID %s"|sprintf:"101"}/details]\n
[/2001] {"Folder with ID %s"|sprintf:"2001"}\n
[/2001/details] {"Folder with ID %s"|sprintf:"2001"}\n
[tasks/responsibles~jdoe/{"Tasks with responsible %s"|sprintf:"joe"}]\n
[tasks/closed=1/All finished tasks]\n
[tasks/closed=0/All pending tasks]
');">
	{/if}
	<textarea rel="cursor" name="{$item_name}" id="{$item_name}" style="width:100%; height:64px; margin-top:2px;" {if $item.REQUIRED}required="true"{/if}>{$item_value}</textarea>
  {elseif $item.SIMPLE_TYPE eq "files"}
  <div class="default10 file_upload" ondrop="css(this,'backgroundColor',''); drop_upload('{$item_name}','{$item.SIMPLE_FILE_SIZE}','{$item.SIMPLE_SIZE}',event);">
	<input type="File" {if $item.SIMPLE_SIZE neq "1"}multiple="true"{/if} name="{$item_name}[]" id="{$item_name}" value="" style="width:45%;"
	onchange="handle_upload('{$item_name}','{$item.SIMPLE_FILE_SIZE}','{$item.SIMPLE_SIZE}', this.files, '{$item_name}');">
	{if $item.SIMPLE_SIZE neq "1"}&nbsp;<input type="button" value="+" onclick="addupload('{$item_name}');">{/if}
	{if $item.SIMPLE_FILE_SIZE}&nbsp; ({$item.SIMPLE_FILE_SIZE}){/if}
	<span class="file_upload_text" style="display:none;">&nbsp; Drag and drop files or URLs here.</span>
	<span id="{$item_name}_progress"></span>
	
	<div id="{$item_name}_div1" style="border:0px; padding:0px; margin:0px;"></div>
	<input type="text" name="{$item_name}_cust[]" id="{$item_name}_cust" ondrop="this.value='';" value="http://" style="width:45%;"
	onkeypress="if (getmykey(event)==13) return handle_upload('{$item_name}','{$item.SIMPLE_FILE_SIZE}','{$item.SIMPLE_SIZE}', new Array({ldelim}size:0, url:true, name:this.value{rdelim}), '{$item_name}_cust');">
	{if $item.SIMPLE_SIZE neq "1"}&nbsp;<input type="button" value="+" onclick="addupload_url('{$item_name}');">{/if}
	<div id="{$item_name}_div2" style="border:0px; padding:0px; margin:0px;"></div>

	{if $item_value}
	  {assign var="subitem" value=-1}
  	  {foreach item=item_value from=$item_value|@modify::explode}
	  
	    {assign var="subitem" value=$subitem+1}
		{if file_exists($item_value)}
		  <input type="hidden" id="{$item_name}_{$subitem}" name="{$item_name}[]" value="{$item_value}">
		  <input type="input" id="{$item_name}_{$subitem}_label" readonly="true" style="width:38%; margin-top:2px;" value=" {$item_value|modify::basename}  ({$item_value|modify::filesize})  -  {$item_value|filemtime|modify::shortdatetimeformat}">
		  &nbsp;
		  {if !is_array($data_item) || !$data_item[$item.NAME].locked[$subitem] || $data_item[$item.NAME].can_unlock[$subitem]}
			<a href="#" onclick="set_attr('{$item_name}_{$subitem}', 'name', ''); css('{$item_name}_{$subitem}_label','textDecoration','line-through'); hide(this); return false;"><img src="ext/icons/empty.gif" title="Delete"></a>
			
			{if is_array($data_item) && $data_item[$item.NAME].locked[$subitem] && $data_item[$item.NAME].can_unlock[$subitem]}
			  &nbsp;<a href="#" onclick="file_func('file_unlock', '{$data_item._id}', '{$item.NAME}', '{$subitem}'); return false;">
			  <img src="ext/icons/lock.gif" title="Unlock"></a>
			{/if}
		  {else}
			Locked by {$item_value|sys_get_lock}
		  {/if}
		  <br/>
		{/if}
	  {/foreach}
	{/if}
	<div id="{$item_name}_div3" style="border:0px; padding:0px; margin:0px;"></div>
  </div>
  {elseif $item.SIMPLE_TYPE eq "date"}
    <input type="text" name="{$item_name}" id="{$item_name}" value="{$item_value}" style="width:150px;" {if $item.REQUIRED}required="true"{/if}>
	&nbsp;<input type="button" value="..." onclick="calendar('{$item_name}',false);">
  {elseif $item.SIMPLE_TYPE eq "datetime"}
    <input type="text" name="{$item_name}" id="{$item_name}" value="{$item_value}" style="width:150px;" {if $item.REQUIRED}required="true"{/if}>
	&nbsp;<input type="button" value="..." onclick="calendar('{$item_name}',true);">
  {elseif $item.SIMPLE_TYPE eq "time"}
    <input type="text" name="{$item_name}" id="{$item_name}" value="{$item_value}" style="width:150px;" {if $item.REQUIRED}required="true"{/if}>
  {elseif is_call_type($item.SIMPLE_TYPE)}
    {types type=$item.SIMPLE_TYPE func="form_render_value" name=$item_name value=$item_value}
  {else}
    &nbsp;
  {/if}
</td>
</tr>
{/if}
{/foreach}
</table></div>