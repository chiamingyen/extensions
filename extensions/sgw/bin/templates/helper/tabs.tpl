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
{if count($t.tabs)>1 && ($t.datasets>0 || $t.schema_mode eq "new")}
{if !$sys.browser.is_mobile}
  <table border="0" cellpadding="0" cellspacing="0" style="width:100%; margin-bottom:2px;" class="noprint"><tr><td>
	<table border="0" cellpadding="0" cellspacing="0"><tr>
	  <td class="tabstyle_empty" id="pane_close" style="padding:0px 4px; cursor:pointer; display:none;" onclick="top.hidepane(window.name);">x</td>
	  <td class="tabstyle_empty" id="tab_spacer" style="padding:0px; padding-left:4px;">&nbsp;</td>
	  <td><img src="about:blank" style="width:2px; height:1px;"></td>
	  {foreach key=tab_key item=tab_item name=tabs from=$t.tabs}
		<td title="Alt-{$smarty.foreach.tabs.iteration}" id="tab{$tab_key}" ondragover="onclick();" onclick="change_tab('tab','{$tab_key}');" class="tab {if $tab_key neq "general"}tabstyle{else}tabstyle2{/if}">
		<span rel="{$tab_key}" id="accesskey{$smarty.foreach.tabs.iteration}">{$tab_item.DISPLAYNAME|default:$tab_item.NAME}</span></td>
		<td><img src="about:blank" style="width:2px; height:1px;"></td>
	  {/foreach}
	  <td id="tab" onclick="show2('.tfields'); change_tab('tab','');" class="tab tabstyle" title="Alt-{$smarty.foreach.tabs.total+1}">+</td>
	  <td><img src="about:blank" style="width:2px; height:1px;"></td>
	</tr></table>
  </td>
  <td class="tabstyle_empty" style="width:100%;">&nbsp;</td>
  </tr></table>
{else}
  <div style="margin:2px; border-top: {#border#};"></div>
  <div class="default10" style="margin:0 4px; margin-bottom:2px; float:{if #direction#}right{else}left{/if};">
	{foreach name=tabs key=tab_key item=tab_item from=$t.tabs}
	  <a class="tab_ {if $tab_key eq "general"}bold{/if}" href="#" onclick="css('.tab_','fontWeight','normal'); css(this,'fontWeight','bold'); change_tab('tab','{$tab_key}'); return false;">{$tab_item.DISPLAYNAME|default:$tab_item.NAME}</a>
	  {" "}|{" "}
	{/foreach}
	<a class="tab_" href="#" onclick="css('.tab_','fontWeight','normal'); css(this,'fontWeight','bold'); show2('.tfields'); change_tab('tab',''); return false;">+</a>
  </div>
  <div style="clear:both;"></div>
{/if}
{/if}
{/strip}