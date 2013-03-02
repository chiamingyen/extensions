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
<table cellspacing="0" class="data" style="margin-bottom:0px;">
<tr class="fields" style="padding:0px;">
  <td style="text-align:center;">
	<span onclick="locate('index.php?today={if $t.data_day.type eq "day"}{$t.data_day.title[0]-86400}{else}{$t.data_day.title[0]-604800}{/if}');">&nbsp;&laquo;&laquo;&nbsp;</span>
	<span onclick="locate('index.php?today={if $t.data_day.type eq "day"}{$t.data_day.title[0]+86400}{else}{$t.data_day.title[0]+604800}{/if}');">&nbsp;&raquo;&raquo;&nbsp;</span>
  </td>
  <td colspan="{$t.data_day.cols*2}" class="datebox_headline" style="text-align:center; cursor:default;">
  {if $t.data_day.type eq "day"}
    {$t.data_day.title[0]|modify::localdateformat:"l, F j, Y"}
  {else}
    {$t.data_day.title[0]|modify::localdateformat:"F j"} - {$t.data_day.title[1]|modify::localdateformat:"F j, Y"}
  {/if}
  </td>
</tr>
{if $t.data_day.type eq "week"}
<tr style="padding:0px;">
  <td class="datebox_headline_day" style="text-align:center;">{$t.data_day.weeknum}</td>
  {foreach name=labels item=day from=$t.data_day.daylabels}
    <td colspan="{$day.span}" class="datebox_headline_day" style="text-align:center; width:14%; {if $day.timestamp eq $datebox.realtoday}font-weight:bold;{/if}" onclick="locate('index.php?markdate=day&today={$day.timestamp}');">
      {$day.day} {$day.daynum}
    </td>
  {/foreach}
</tr>
{/if}
{assign var="cols" value=$t.data_day.cols}
{assign var="hun" value=100}
{section name=row loop=$t.data_day.rows}
  {assign var="row" value=$smarty.section.row.index}
  {assign var="rowdiv" value=$row%4}
  {assign var="row_base" value=$row-$rowdiv}
  {assign var="hour" value=$row_base/4}
  {if $row eq 0}
    {assign var="hour_str" value="All day"}
  {else}
    {assign var="hour_str" value=$t.data_day.times[$row_base]|modify::dateformat:"g:i a"}
  {/if}
  <tr rel="da_{$hour}" class="mover asset_da_{$hour}">
    {if $rowdiv eq 0}
	  <td rowspan="4" class="item_time" onclick="locate('index.php?view=new&{if $row eq 0}allday=1&{/if}begin={$t.data_day.times[$row]}&ending={$t.data_day.times[$row]+3600}');">{$hour_str}</td>
	{/if}
    {section name=col loop=$t.data_day.cols}
  	  {assign var="col" value=$smarty.section.col.index}
      {if $rowdiv eq 0}<td rowspan="4" class="item_data_spacer">&nbsp;</td>{/if}
      {assign var="this" value=$t.data_day.table[$row][$col]}
      {assign var="id" value=$this[2]}
	  {if $this}
	    {if $id}
          {assign var="item" value=$t.data.$id}
          <td title="{$id}" rel="{$id}" class="asset_{$id} mover mdown item_data {if $rowdiv eq 0}cal_item{/if}" style="width:{$hun/$cols|string_format:"%d"}%; border-left:5px solid {#bg_light_blue#}; {$item._fgstyle} {$item._bgstyle}" rowspan="{$this[1]}">
			<div style="padding:0 5px; {if !$t.fields.$curr_id.NOWRAP}overflow:hidden;{/if}">
			{if count($t.folders)>1}
			  <img src="ext/images/empty.gif" class="folder_block_image" style="background-color: {$t.folders[$item._folder][1]};"/>&nbsp;
			{/if}
			{if $t.linkstext.$subject[1]}
			  {assign var="link_data" value=$t.linkstext.$subject[1]|modify::link:$item:0:$urladdon}
			  {if $link_data neq ""}<a target="{$t.linkstext.$subject[0]|modify::target}" id="linktext" href="{$link_data}">{/if}
			{/if}
			{$item.$subject.filter|@modify::field}
			{if $item.$subject2.filter[0]} ({$item.$subject2.filter[0]}){/if}
			{if $t.linkstext.$subject && $link_data neq ""}</a>{/if}
			{if $t.hidedata}<input type="checkbox" name="item[]" value="{$id}" style="display:none;">{/if}
			</div>
			<div style="width:40px; font-size:0px;"></div>
	      </td>
  		{else}
          <td title="{$hour_str} / {$t.data_day.daylabels.$col.day} {$t.data_day.daylabels.$col.daynum}" rowspan="{$this[1]}" class="cursor {if $rowdiv eq 0}item_data_spacer{/if}" style="width:{$hun/$cols|string_format:"%d"}%;"
			onclick="locate('index.php?view=new&{if $row eq 0}allday=1&{/if}begin={$t.data_day.times[$row_base]+$col*86400}&ending={$t.data_day.times[$row_base]+$col*86400+3600}');">&nbsp;</td>
		{/if}
	  {/if}
    {/section}
  </tr>
{/section}
</table>
<table cellspacing="0" class="data" style="margin-bottom:2px; border-top:0px;">
<tr class="datebox_footerline"><td onclick="locate('index.php?today={$smarty.now}');" style="text-align:center;">
  Today: {$smarty.now|modify::localdateformat:"F j, g:i a"}
</td></tr>
</table>
{/strip}

