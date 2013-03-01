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
{assign var="tab_key" value="general"}
{assign var="col_counter" value="0"}
{assign var="last_groupitem" value="_"}
{assign var="cols" value=$t.views[$t.view].COLS}
{if $sys.browser.is_mobile}{assign var="cols" value="1"}{/if}

{if $t.datasets>0}
<table border="0" cellpadding="0" cellspacing="0" style="width:100%;"><tr>
{foreach name=fouter key=data_key item=data_item from=$t.data}
{if $data_item.fullwidth.data[0] && $col_counter neq 0}</tr><tr>{/if}
{assign var="col_counter" value=$col_counter+1}
<td id="{$data_item._id}_frame" {if $data_item.fullwidth.data[0]}colspan="{$cols}"{/if} style="{if $col_counter < $cols && !$data_item.fullwidth.data[0]}padding-right:2px;{/if} padding-bottom:4px; height:100%;" valign="top">
{if !$iframe}{include file="helper/groups.tpl" table="1"}{/if}
  
<table cellspacing="0" class="data" style="margin:0px;">
  <tr rel="{$data_item._id}" class="mdown {if $data_item._bgstyle}id_header_bg{else}id_header{/if} asset_{$data_item._id}" style="{$data_item._bgstyle}">
    {include file="helper/selitem.tpl"}
	<td style="width:100%;">
	<div style="height:16px; overflow:hidden;">
	<a href="{$data_item.url.filter[0]}">{$data_item.bookmarkname.filter[0]|modify::field}</a>	
	&nbsp;
	<a href="#" onclick="portal_change('{$data_item._id}_frame',60); return false;"> + </a>/
	<a href="#" onclick="portal_change('{$data_item._id}_frame',-60); return false;"> &ndash;&nbsp;</a>
	</div>
	</td>
	{include file="helper/buttons.tpl" style=""}
  </tr>
</table>
{if $data_item.url.data[0] neq "about:blank"}
<table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
  <tr>
    <td valign="top" style="padding-top:2px;">
	  <iframe name="{$data_item._id}_frame" style="border:0px; width:100%; height:{$data_item.height.filter[0]|default:"200"}px;" src="{$data_item.url.data[0]|default:"about:blank"}{if sys_is_internal_url($data_item.url.data[0])}&iframe=1{/if}"></iframe>
    </td>
  </tr>
</table>
{/if}
<script>setTimeout("portal_refresh('{$data_item._id}_iframe',{$data_item.refresh.data[0]},1)",{$data_item.refresh.data[0]});</script>
</td>
{if $col_counter >= $cols || $data_item.fullwidth.data[0]}
  </tr><tr>
  {assign var="col_counter" value="0"}
{/if}
{/foreach}
</tr></table>
{/if}
{/strip}
