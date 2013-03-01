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
{if $t.groupby neq "" && $data_item[$t.groupby].data[0] neq $last_groupitem && ($data_item[$t.groupby].filter[0] || $last_groupitem neq "_")}
  {counter assign="group_counter"}
  {if $table}<table border="0" cellspacing="0" cellpadding="0" class="data" style="border:0px; margin-top:6px; margin-bottom:8px;">{/if}
  <tr>
	<td class="item_groupby" colspan="20">
      {if $t.links[$t.groupby][1]}<a target="{$t.linkstext[$t.groupby][0]|modify::target}" href="{$t.links[$t.groupby][1]|modify::link:$data_item:0:$urladdon}">@</a>&nbsp;{/if}
      {if $t.linkstext[$t.groupby][1]}<a target="{$t.linkstext[$t.groupby][0]|modify::target}" id="linktext" href="{$t.linkstext[$t.groupby][1]|modify::link:$data_item:0:$urladdon}">{/if}
	  {$t.fields[$t.groupby].DISPLAYNAME|default:$t.groupby|replace:"_":" "}: {$data_item[$t.groupby].filter|@implode:$t.fields[$t.groupby].SEPARATOR|default:"{t}none{/t}"}
	  {if $t.linkstext[$t.groupby][1]}</a>{/if}
	</td>
  </tr>
  {if $table}</table>{/if}
  {assign var="last_groupitem" value=$data_item[$t.groupby].data[0]}
  {if $cycle_dataitem neq "items_even"}{cycle assign="cycle_dataitem" values="items_even,items_odd"}{/if}
{/if}
