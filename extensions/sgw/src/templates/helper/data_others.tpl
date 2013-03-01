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
{if $t.links[$curr_id][1] && !$t.links[$curr_id][3]}
  {assign var="item_data" value=$t.links[$curr_id][1]|modify::link:$data_item:$key_filter:$urladdon}
  <a target="{$t.links[$curr_id][0]|modify::target}" href="{$item_data}">
  {if $t.links[$curr_id][0] neq "_top"}
    <img src="ext/icons/{$t.links[$curr_id][2]|default:"link_ext.gif"}" style="vertical-align:top;">
  {else}
    <img src="ext/icons/{$t.links[$curr_id][2]|default:"link.gif"}" style="vertical-align:top;">
  {/if}
  </a>&nbsp;
{/if}
		
{if $t.linkstext[$curr_id][1]}
  {assign var="link_data" value=$t.linkstext[$curr_id][1]|modify::link:$data_item:$key_filter:$urladdon}
  {if $link_data neq ""}<a target="{$t.linkstext[$curr_id][0]|modify::target}" id="linktext" href="{$link_data}" onmousedown="check_bold(this);">{/if}
{/if}

{if $t.fields.$curr_id.SIMPLE_TYPE eq "textarea"}
  {$item.filter[$key_filter]|modify::nl2br}
{elseif is_call_type($t.fields.$curr_id.SIMPLE_TYPE)}
  {assign var="field" value=$t.fields.$curr_id}
  {assign var="view" value=$t.views[$t.view]}
  {assign var="id" value=$data_item._id}
  {types type=$t.fields.$curr_id.SIMPLE_TYPE func="render_value" value=$item.filter[$key_filter] value_raw=$item.data[$key_filter] preview=$t.views[$t.view].SHOW_PREVIEW}
{elseif $t.fields.$curr_id.NO_CHECKS neq ""}
  {$item.filter[$key_filter]|no_check}
{else}
  {$item.filter[$key_filter]|modify::field}
{/if}

{if $t.linkstext[$curr_id] && $link_data neq ""}</a>{/if}

{if $t.links[$curr_id][1] && $t.links[$curr_id][3]}
  &nbsp;<a target="{$t.links[$curr_id][0]|modify::target}" href="{$t.links[$curr_id][1]|modify::link:$data_item:$key_filter:$urladdon}">
  {if $t.links[$curr_id][0] neq "_top"}
    <img src="ext/icons/{$t.links[$curr_id][2]|default:"link_ext.gif"}" style="vertical-align:top;">
  {else}
    <img src="ext/icons/{$t.links[$curr_id][2]|default:"link.gif"}" style="vertical-align:top;">
  {/if}
  </a>
{/if}