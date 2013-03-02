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
{strip}
{if $t.datasets>0 && (!$t.hidedata || (!$t.data_day && !$t.data_month))}
{foreach key=tab_key item=tab_item from=$t.tabs}

{assign var="last_groupitem" value="_"}
{if $cycle_dataitem neq "items_even"}{cycle assign="cycle_dataitem" values="items_even,items_odd"}{/if}
<div class="tab2 tab2{$tab_key}" {if $tab_key neq "general" && !$t.disable_tabs}style="display:none;"{/if}>
<table cellspacing="0" class="data" style="margin-bottom:{if $iframe && !$t.data_day && !$t.data_month}0{else}2{/if}px;">
  <thead class="tfields" {if $iframe}style="display:none;"{/if}>
  <tr class="fields">
	{if !$iframe}<td style="width:1px;" title="Reset view" onclick="locate('index.php?reset_view=true');">{if $tab_key eq "general" && !$preview && $t.hidden_fields}+{/if}&nbsp;</td>{/if}
    {foreach key=curr_id item=item from=$t.fields}
      {if $curr_id neq $t.groupby && !$t.fields.$curr_id.HIDDENIN[$t.view] && !$t.fields.$curr_id.HIDDENIN.all}
        {if in_array($tab_key,$item.SIMPLE_TAB)}{include file="helper/fields.tpl"}{/if}
	  {/if}
    {/foreach}
	{if !$iframe}<td style="text-align:right; white-space:nowrap;" onclick="locate('index.php?group')"><img src="ext/icons/group.gif" title="(un)Group" class="noprint"></td>{else}<td></td>{/if}
  </tr>
  </thead>
  {foreach name=outer key=data_key item=data_item from=$t.data}
	{if !$iframe}{include file="helper/groups.tpl"}{/if}
	{cycle assign="cycle_dataitem" values="items_even,items_odd"}
	<tr title="{$data_item._id}" rel="{$data_item._id}" class="mover mdown asset_{$data_item._id} {$cycle_dataitem}" style="vertical-align:top; {$data_item._fgstyle}">
      {include file="helper/selitem.tpl"}
      {foreach key=curr_id item=item from=$data_item}
		{if !$t.fields.$curr_id.HIDDENIN[$t.view] && !$t.fields.$curr_id.HIDDENIN.all}{include file="helper/data.tpl"}{/if}
      {/foreach}
	  {assign var="style" value=$data_item._bgstyle}
      {include file="helper/buttons.tpl" style=$style}
    </tr>
  {/foreach}
</table>
</div>
<p style="page-break-after:always;"></p>
{/foreach}
{/if}

{assign var="subject" value=$t.field_1}
{if !$t.fields.$subject}{assign var="subject" value="createdby"}{/if}
{assign var="subject2" value=$t.field_2|default:"location"}

{if $t.data_day}
  {include file="helper/day.tpl"}
{elseif $t.data_month && $t.data_month.type eq "gantt"}
  {include file="helper/gantt.tpl"}
{elseif $t.data_month}
  {include file="helper/month.tpl"}
{/if}
{/strip}