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
{if ($curr_id neq $t.groupby)}
  {if !$tab_key || in_array($tab_key,$t.fields.$curr_id.SIMPLE_TAB)}
 	<td onmousedown="switch_wrap(this, event);" style="{if $item.filter[0] neq "" || !$iframe}min-width:40px;{/if} {if $t.fields.$curr_id.WIDTH}width:{$t.fields.$curr_id.WIDTH};{/if} {if $t.fields.$curr_id.HEIGHT}height:{$t.fields.$curr_id.HEIGHT};{/if} {if $t.fields.$curr_id.NOWRAP && !$sys.browser.is_mobile}white-space:nowrap;{/if}">
    {if $item.filter[0] eq ""}&nbsp;{else}
	  <div class="type_{$t.fields.$curr_id.SIMPLE_TYPE}" style="width:100%; {$item._fgstyle} {$item._bgstyle} {if !$t.fields.$curr_id.NOWRAP}overflow:hidden;{/if}">
	  {foreach name=item_filter key=key_filter item=item_filter from=$item.filter}
		{if $t.fields.$curr_id.SIMPLE_TYPE eq "files"}
		  {if $item_filter neq "original.eml.txt" || $smarty.const.DEBUG}
			{include file="helper/data_files.tpl"}
		  {/if}
		{else}
		  {include file="helper/data_others.tpl"}
		{/if}
		{if count($item.filter) > $t.subitem+5 && $key_filter eq $t.subitem+4}
      	  <span>&nbsp;<a href="#" onclick="showhide_inline(this.parentNode.lastChild); return false;">&gt;&gt;</a><div style="display:none;">
		{/if}
		{if !$smarty.foreach.item_filter.last}{$t.fields.$curr_id.SEPARATOR|default:"\n"|modify::nl2br}{/if}
	  {/foreach}
	  {if $key_filter ge 4 && count($item.filter) > $t.subitem+5}</div></span>{/if}
  	  </div>
	{/if}
	</td>
  {/if}
{/if}
