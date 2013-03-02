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
{if $iframe}
  <td title="{$curr_id}" style="{$fstyle}">{$t.fields.$curr_id.DISPLAYNAME|default:$curr_id|replace:"_":" "}</td>
{elseif $curr_id eq $t.orderby}
  <td title="{$curr_id}" class="cursor bold" style="white-space:nowrap; {$fstyle}" onclick="locate('index.php?orderby={$curr_id}&order={if $t.order eq "asc"}desc{else}asc{/if}')">{$t.fields.$curr_id.DISPLAYNAME|default:$curr_id}&nbsp;<img src="ext/icons/{$t.order}.gif" style="width:8px; height:6px; padding-bottom:2px;"></td>
{else}
  <td title="{$curr_id}" class="cursor hide_fields" style="{$fstyle}" onclick="locate('index.php?orderby={$curr_id}&order=asc')">{$t.fields.$curr_id.DISPLAYNAME|default:$curr_id}
  &nbsp;<a title="Hide" class="hide_field" href="index.php?hide_fields={$t.hidden_fields|@implode:","},{$curr_id}">&ndash;</a>
  </td>
{/if}