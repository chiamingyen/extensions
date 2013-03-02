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
    <tr class="fields" style="padding:0px;">
      <td onclick="locate('index.php?today={$datebox.prev_date_year}');">&laquo;&laquo;</td>
      <td onclick="locate('index.php?today={$datebox.next_date_year}');">&raquo;&raquo;&nbsp;</td>
      <td colspan="5" class="datebox_headline" style="text-align:center;" onclick="locate('index.php?markdate=month&today={$datebox.fstinmonths[$month_key]}');">
	    {$datebox.months[$month_key]} {if $month_key<$datebox.month}{$datebox.year+1}{else}{$datebox.year}{/if}
	  </td>
	  <td></td>
    </tr>
    <tr class="default">
	  <td class="datebox_headline_day">&nbsp;</td>
      {foreach key=day_key item=day from=$datebox.dow}
	    <td class="datebox_headline_day" style="width:14%; text-align:center;" onclick="locate('index.php?weekstart={$day.date_w}');">{$day.date_d}</td>
	  {/foreach}
    </tr>
    {foreach key=week_key item=week from=$datebox_item}
      <tr rel="mo_{$week_key}_{$month_key}" class="mover asset_mo_{$week_key}_{$month_key}">
	    <td onclick="locate('index.php?markdate=week&today={$week[0].timestamp}');" class="item_week">{$week_key}</td>
	    {foreach key=days_key item=days from=$week}
	      <td class="item_data_spacer" valign="top">
			{if $month_key eq $days.n}
			  <div class="{if $days.timestamp eq $datebox.realtoday}datebox_head_div2{else}datebox_head_div{/if}" onmousedown="locate('index.php?markdate=day&today={$days.timestamp}');">
				{$days.j}
			  </div>
			{else}&nbsp;{/if}
	        {foreach item=id from=$t.data_month.table[$days.timestamp]}
	          <table cellspacing="0" class="data" style="width:99%; margin:1px; margin-left:0px; border:0px;">
	            {assign var="item" value=$t.data.$id}
		        <tr><td title="{$id}" rel="{$id}" class="asset_{$id} mover mdown item_data" style="border-left:5px solid {#bg_light_blue#}; {$item._fgstyle} {$item._bgstyle}">
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
			    </td></tr>
		      </table>
	        {/foreach}
	      </td>
	    {/foreach}
	  </tr>
    {/foreach}
  {/foreach}
  <tr class="datebox_footerline_b">
	<td>&nbsp;</td>
	<td onclick="locate('index.php?today={$smarty.now}');" colspan="7" style="text-align:center;" class="cursor">
	Today: {$smarty.now|modify::localdateformat:"F j, g:i a"}
	</td>
  </tr>
</table>
{/strip}