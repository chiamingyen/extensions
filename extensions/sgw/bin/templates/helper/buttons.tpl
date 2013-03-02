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
<td valign="top" style="padding-right:0px; text-align:right; {$style}">
<div style="padding-right:4px; {if !$sys.browser.is_mobile}white-space:nowrap;{/if}">
  {if $t.nosinglebuttons || $popup || $data_item.issum || $iframe || $print eq 1}
    &nbsp;
  {else}
    {if count($t.singlebuttons) neq 0 && !$t.nosinglebuttons}
      {foreach key=key item=item from=$t.singlebuttons}
	    {if (!$item.RIGHT || $t.rights[$item.RIGHT]) && (!$item.CONDITION || modify("match", $data_item, $item.CONDITION, $t.fields))}
		  &nbsp;<a href="#" onclick="{$item.ONCLICK|modify::link:$data_item} return false;" style="white-space:nowrap;">
	      {if $item.ICON}<img src="ext/icons/{$item.ICON}" title="{$item.DISPLAYNAME|default:$item.NAME}">{/if}
		  {if !$item.ICON || $sys.browser.is_mobile}&nbsp;{$item.DISPLAYNAME|default:$item.NAME}{/if}
		  </a>
		{/if}
      {/foreach}
    {/if}

    {foreach key=key item=item from=$t.views}
      {if $item.SHOWINSINGLEVIEW eq "true" && $item.VISIBILITY neq "hidden" && $item.VISIBILITY neq "active" && $key neq $t.view && (!$item.RIGHT || $t.rights[$item.RIGHT])}
		&nbsp;<a href="index.php?item[]={$data_item._id|escape:"url"}&view={$key}" style="white-space:nowrap;">
	    {if $item.ICON}<img src="ext/icons/{$item.ICON}" title="{$item.DISPLAYNAME|default:$item.NAME}">{/if}
		{if !$item.ICON || $sys.browser.is_mobile}&nbsp;{$item.DISPLAYNAME|default:$item.NAME}{/if}
		</a>
      {/if}
    {/foreach}
  {/if}
</div>
</td>