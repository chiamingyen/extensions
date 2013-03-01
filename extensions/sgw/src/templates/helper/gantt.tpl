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
<table cellspacing="0" class="data" style="margin-bottom:2px;">
  {foreach key=month_key item=datebox_item from=$datebox.dates}
    <thead>
    <tr class="fields" style="padding:0px;">
      <td>
		<span onclick="locate('index.php?today={$datebox.prev_date}');">&laquo;&laquo;&nbsp;</span>
		<span onclick="locate('index.php?today={$datebox.next_date}');">&nbsp;&raquo;&raquo;&nbsp;</span>
	  </td>
	  {assign var="count" value=$datebox_item|@count}
      <td colspan="{$count*7}" class="datebox_headline" style="text-align:center;" onclick="locate('index.php?markdate=month&today={$datebox.fstinmonths[$month_key]}');">
	    {$datebox.months[$month_key]} {if $month_key<$datebox.month}{$datebox.year+1}{else}{$datebox.year}{/if}
	  </td>
    </tr>
    <tr class="default">
	  <td class="datebox_headline_day" rowspan="2" style="width:14%;">&nbsp;</td>
      {foreach key=week_key item=week from=$datebox_item}
		{if $month_key eq $week[6].n}
	    <td colspan="7" onclick="locate('index.php?markdate=week&today={$week[0].timestamp}');" class="datebox_headline_day">{$week_key}</td>
		{/if}
	  {/foreach}
    </tr>
    <tr class="default">
    {foreach item=week from=$datebox_item}
	  {foreach key=days_key item=days from=$week}
		{if $month_key eq $days.n}
	    <td class="{if $days.timestamp eq $datebox.realtoday}datebox_headline_day2{else}datebox_headline_day{/if}" style="padding-left:2px; padding-right:2px; text-align:center;" onclick="locate('index.php?markdate=day&today={$days.timestamp}');">
		  {$days.j}<br>{$datebox.dow.$days_key.date_d|truncate:4:"":true}
		</td>
		{/if}
	  {/foreach}
	{/foreach}
    </tr>
	</thead>
    {foreach item=data_item from=$t.data}
    {if !$data_item.issum}
	{cycle assign="cycle_cdataitem" values="items_even,items_odd"}
    <tr rel="{$data_item._id}" class="mover mdown {$cycle_cdataitem} asset_{$data_item._id}" title="{$data_item._id}">
	  <td class="datebox_headline_text">
	  {if count($t.folders)>1}
		<img src="ext/images/empty.gif" class="folder_block_image" style="height:9px; background-color: {$t.folders[$data_item._folder][1]};"/>&nbsp;
	  {/if}
	  {if $t.linkstext.$subject[1]}
		{assign var="link_data" value=$t.linkstext.$subject[1]|modify::link:$data_item:0:$urladdon}
		{if $link_data neq ""}<a target="{$t.linkstext.$subject[0]|modify::target}" id="linktext" href="{$link_data}">{/if}
	  {/if}
	  {$data_item.$subject.filter[0]|modify::field}
	  {if $data_item.$subject2.filter[0]} ({$data_item.$subject2.filter[0]}){/if}
	  {if $t.linkstext.$subject && $link_data neq ""}</a>{/if}
	  </td>
	  {assign var="break" value="0"}
      {foreach item=week from=$datebox_item}
	    {foreach item=days from=$week}
		  {if in_array($data_item._id,$t.data_month.table[$days.timestamp]) && $month_key eq $days.n}
		      <td class="gantt_bar item_data_spacer" style="{$data_item._fgstyle} {$data_item._bgstyle}">
				&nbsp;
				{if $data_item.milestone.data[0]}<img src="ext/icons/milestone.gif" title="{t}Milestone{/t}" style="margin-top:1px;"/>&nbsp;{/if}
				<div style="border-bottom:2px solid red; width:100%; height:2px;">
				  <div id="box_{$data_item._id}" style="height:2px; border-bottom:2px solid green; width:{$data_item.progress.data[0]*100}%">&nbsp;</div>
				</div>
			  </td>
		  {elseif $month_key eq $days.n}
		    <td class="item_data_spacer">&nbsp;</td>
		  {/if}
	    {/foreach}
      {/foreach}
	</tr>
	{/if}
    {/foreach}
  {/foreach}
  <tr class="datebox_footerline_b">
	<td>&nbsp;</td>
	<td onclick="locate('index.php?today={$smarty.now}');" colspan="{$count*7}" style="text-align:center;" class="cursor">
	{t}Today{/t}: {$smarty.now|modify::localdateformat:"{t}F j{/t}, {t}g:i a{/t}"}
	</td>
  </tr>  
</table>
{/strip}

