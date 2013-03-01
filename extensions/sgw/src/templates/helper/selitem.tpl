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
{if ($tab_key eq "general" || $t.template eq "asset_display.tpl" || $t.disable_tabs) && !$iframe}
  {if $t.template neq "asset_display.tpl"}
    {if $data_item.issum}
	  <td style="width:10px; white-space:nowrap;">{t}Total{/t}</td>
    {elseif !$data_item.issum}
      <td style="white-space:nowrap;">
		{if !$iframe}<input type="checkbox" name="item[]" id="check_{$data_item._id}" value="{$data_item._id}" class="asset_check checkbox">{/if}
		{if $sys.browser.name neq "opera"}
		  <span rel="{$data_item._id}" class="drag_asset"><img src="ext/icons/drag2.gif"></span>
		{/if}
	  </td>
	  {if count($t.folders)>1}
   	    <td style="padding:0px; padding-left:4px;">
		  <div class="folder_block2" style="background-color: {$t.folders[$data_item._folder][1]};">&nbsp;</div>
	    </td>
	  {/if}
    {/if}
  {else}
    {if !$data_item.issum && $tab_key eq "general"}
      <td style="width:10px; {if count($t.folders)>1}background-color: {$t.folders[$data_item._folder][1]};{/if}" >
	    <div style="white-space:nowrap;">
		{if !$iframe}<input type="checkbox" name="item[]" id="check_{$data_item._id}" value="{$data_item._id}" class="asset_check checkbox checkbox2">{/if}
		{if $sys.browser.name neq "opera"}
		  <span rel="{$data_item._id}" class="drag_asset"><img src="ext/icons/drag.gif" style="padding-top:1px;"></span>
		{/if}
	    {if isset($data_item.tlevel)}
		  &nbsp;
		  <img src="ext/images/empty.gif" style="width:1px; height:19px;"/>
		  {repeat count=$data_item.tlevel}<img src="ext/icons/line.gif">{/repeat}
		  <img src="ext/icons/folder1.gif">
		{/if}
		</div>
	  </td>
	{elseif $data_item.issum && $tab_key eq "general"}
	  <td style="width:10px;">&nbsp;&sum;</td>
    {else}
	  <td style="width:1px;"></td>
    {/if}
  {/if}
{/if}