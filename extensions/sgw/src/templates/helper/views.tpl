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
{if $t.sqlvars.item neq "" || count($t.views)>1 || (count($t.buttons)>0 && !$t.noviewbuttons)}
<div id="views">

  <div id="view_buttons" style="{if !$sys.browser.is_mobile}float:{if #direction#}left{else}right{/if};{else}display:none; margin:6px;{/if}" class="default10">
  {if !$popup && !$t.noviewbuttons}
    {foreach name=views key=key item=item from=$t.views}
	  {if $item.VISIBILITY eq "button"}
	    {if $item.ICON}
  	      <a href="index.php?view={$key}" onmousedown="this.href=asset_form_link('index.php?view={$key}');"><img src="ext/icons/{$item.ICON}" style="height:16px;" title="{$item.DISPLAYNAME|default:$key}{if $item.ACCESSKEY} [Alt-{$item.ACCESSKEY}]{/if}">&nbsp;
		  {if $sys.browser.is_mobile}{$item.DISPLAYNAME|default:$key}&nbsp;&nbsp;{/if}
		  </a>
	    {else}
  	      <a href="index.php?view={$key}" onmousedown="this.href=asset_form_link('index.php?view={$key}');">{$item.DISPLAYNAME|default:$key}&nbsp;</a>
	    {/if}
		{if $item.ACCESSKEY}<a onclick="asset_form_submit('index.php?view={$key}'); return false;" href="#" accesskey="{$item.ACCESSKEY}"></a>{/if}
	  {/if}
    {/foreach}
    {foreach key=key item=item from=$t.buttons}
	  {if !$item.RIGHT || $t.rights[$item.RIGHT]}
	    {if $item.ICON}
  	      <span class="cursor" onclick="{$item.ONCLICK}"><img src="ext/icons/{$item.ICON}" style="height:16px;" title="{$item.DISPLAYNAME|default:$item.NAME}{if $item.ACCESSKEY} [Alt-{$item.ACCESSKEY}]{/if}">&nbsp;
		  {if $sys.browser.is_mobile}{$item.DISPLAYNAME|default:$item.NAME}&nbsp;&nbsp;{/if}
		  </span>
	    {else}
  	      <span class="cursor" onclick="{$item.ONCLICK}">{$item.DISPLAYNAME|default:$item.NAME}&nbsp;</span>
	    {/if}
	    {if $item.ACCESSKEY}<a onclick="{$item.ONCLICK} return false;" href="#" accesskey="{$item.ACCESSKEY}"></a>{/if}
	  {/if}
    {/foreach}
  {/if}
  </div>
  
  <div style="float:{if #direction#}right{else}left{/if};" id="loading">
    <span id="tree_button" style="display:none;" onclick="tree_showhide();" class="tabstyle">{t}Tree{/t}</span>

    {if $t.datasets>0 && ($t.data_day || $t.data_month)}
	  <a href="index.php?hidedata" class="tabstyle">{if $t.hidedata}+{else}&ndash;{/if}</a>
	{/if}
    {if ($t.sqlvars.item && $t.maxdatasets>$t.datasets) || $t.sqlfilters}
	  <a href="index.php?session_remove_request&filters=" class="tabstyle" title="Alt-&lt;" accesskey="<">[{t}All{/t}]</a>
    {/if}
	
    {foreach name=views key=key item=item from=$t.views}
	  {if $item.VISIBILITY neq "hidden" && (!$item.VISIBILITY || $key eq $t.view)}
        {if $key neq $t.view}
		  <a onmousedown="this.href=asset_form_link('index.php?view={$key}');" class="tabstyle" title="Alt-{$item.ACCESSKEY|default:$smarty.foreach.views.iteration}">{$item.DISPLAYNAME|default:$key}</a>
        {else}
		  <a onmousedown="this.href=asset_form_link('index.php?view={$key}');" class="tabstyle2" title="Alt-{$item.ACCESSKEY|default:$smarty.foreach.views.iteration}">{$item.DISPLAYNAME|default:$key}</a>
        {/if}
		{if $item.ACCESSKEY || $smarty.foreach.views.iteration < 10}
		  <a onclick="asset_form_submit('index.php?view={$key}'); return false;" href="#" accesskey="{$item.ACCESSKEY|default:$smarty.foreach.views.iteration}"></a>
		{/if}
	  {/if}
    {/foreach}
	{if $t.lastpage neq 1}
	  {if $t.page neq $t.prevpage}
		<a class="tabstyle" href="index.php?page={$t.prevpage}" accesskey="-" title="Alt-'-'">&lt;</a>
	  {/if}
	  {if $t.page neq $t.nextpage}
		<a class="tabstyle" href="index.php?page={$t.nextpage}" accesskey="+" title="Alt-'+'">&gt;</a>
	  {/if}
	  <a onclick="locate('index.php?page=1'); return false;" href="#" accesskey="q"></a>
	  <a onclick="locate('index.php?page={$t.lastpage}'); return false;" href="#" accesskey="w"></a>
	{/if}
    <span id="notification2"></span>

	{if $t.views.new && !$t.att.DISABLE_QUICK_ADD && $t.att.ENABLE_NEW && $t.schema_mode eq ""}
	  {assign var="fields" value=$t.fields_all|@modify::get_required_fields:$t.att.QUICK_ADD}
	  {if $fields}
        <a title="{t}Quick add{/t} [Alt-k]" href="#" onclick="asset_insert_prompt('{$fields|@array_keys|@implode:"|"}','{$fields|@implode:"|"}'); return false;" accesskey="k">
	    <img src="ext/icons/add.gif"></a>
	  {/if}
	{/if}
  </div>
</div>
<div style="clear:both;"></div>
{/if}
{/strip}