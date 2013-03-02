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
<div style="margin:2px;" class="default10 baseline">

{foreach name=parents key=key item=item from=$folder.parents}
  /<a href="index.php?folder={$item.id}">&nbsp;{$item.ftitle}&nbsp;</a>
{/foreach}
/<a class="bold" href="index.php?folder={$folder.id}">&nbsp;{$folder.name}&nbsp;({$t.views[$t.view].modulename})&nbsp;</a>

{if $folder.children && !$print}
  /&nbsp;
  <select onchange="locate(this.value);" style="width:120px;">
    <option> - Subfolders -
	{foreach name=children key=key item=item from=$folder.children}
	  <option value="index.php?folder={$item.id}">{$item.ftitle} {if $item.fcount neq 0}({$item.fcount}){/if}</a>
	{/foreach}
  </select>
{/if}

&nbsp;&nbsp;
{if $popup}
  {if count($tree.tree)>1}
	<select onchange="locate('index.php?view={$t.view}&folder='+this.value);" style="width:170px;">
    {foreach key=key item=item from=$tree.tree}
	  <option value="{$item.id|escape:"url"}" {if $folder.id eq $item.id}selected{/if}> {$item.id|modify::getpath}
    {/foreach}
	</select>
  {/if}
{else}
  <select onchange="if (this.value) locate('index.php?'+this.value);" style="width:100px;">
	<option> - History -
	{foreach key=key item=item from=$sys.history}
	  <option value="folder={$item[1]|escape:"url"}&view={$item[2]|escape:"url"}"> {$item[0]} {if $item[2] neq "display"}({$item[2]}){/if}
	{/foreach}
  </select>
{/if}

  <span>
  &nbsp;&nbsp;
  <form action="index.php" method="get" style="display:inline;">
	<input type="hidden" name="view" value="search"/>
    <input name="search" type="text" accesskey="f" value="{$t.search.query}" style="width:110px;" maxlength="255">&nbsp;
    <input type="Submit" value="Search">
  </form>
  </span>
  
  {if !$popup && !$print}
    &nbsp;
	<span style="white-space:nowrap;" class="baseline">
    {if !$sys.browser.is_mobile}
	  &nbsp;
      <span class="cursor" onclick="locate('index.php?tree=maximize');">Maximize</span> |{" "}
      <span class="cursor" onclick="nWin('index.php?print=1');">Print</span> |{" "}
	  <span class="cursor" onclick="nWin('index.php?print=1&print_all=1');">Print all</span> |{" "}
	{/if}

	{if $sys.home}
	  &nbsp;<a href="{$sys.home}"><img src="ext/icons/home.gif" title="Home"/></a>{" "}
	{/if}

    {if $sys.username neq "anonymous" && !$sys.is_superadmin}
	  &nbsp;<a href="index.php?find=asset|simple_sys_users|1|username={$sys.username}&view=changepwd">
	  <img src="ext/icons/settings.gif" title="Change settings"/></a>{" "}
	  &nbsp;<a href="offline.php"><img src="ext/icons/offline.png" title="Offline folders"/></a>{" "}
    {/if}

    {if $sys.browser.is_mobile}
	   &nbsp;<a href="#" onclick="add_style('#content_def div,#content_def td', 'word-break:normal; white-space:nowrap;'); return false; "><img src="ext/icons/full_texts.gif" title="No line breaks"/></a>{" "}
	{/if}
    &nbsp;<a href="index.php?logout"><img src="ext/icons/logout.gif" title="Login/-out {$sys.username}" style="padding-top:2px;"/></a>
	</span>
  {/if}
  &nbsp;<span id="notification"></span>
  <script>notify(notification+warning);</script>
</div>

{if !$print && $t.views[$t.view].ENABLE_CALENDAR neq ""}
  <div style="margin:4px; border-top: {#border#};"></div>
  <span class="default10 baseline">
	&nbsp;<span class="cursor" onclick="locate('index.php?today=now')">Today</span>&nbsp;{" "}
    <input type="button" id="datebox_button" value="{$datebox.today|modify::localdateformat:"M j, Y"|default:"..."}" onclick="calendar('datebox',false,true);">
	<input type="hidden" id="datebox" name="datebox" value="{$datebox.today|modify::dateformat:"m/d/Y"}">&nbsp;{" "}

    <span class="cursor {if $datebox.mark eq "day"}bold{/if}" onclick="locate('index.php?markdate=day')">Day</span>&nbsp;
	<span class="cursor {if $datebox.mark eq "week"}bold{/if}" onclick="locate('index.php?markdate=week')">Week</span>{" "}
	<span class="cursor {if $datebox.mark eq "gantt"}bold{/if}" onclick="locate('index.php?markdate=gantt')">Gantt</span>&nbsp;
	<span class="cursor {if $datebox.mark eq "month"}bold{/if}" onclick="locate('index.php?markdate=month')">Month</span>&nbsp;
	<span class="cursor {if $datebox.mark eq "all"}bold{/if}" onclick="locate('index.php?markdate=all')">All</span>
  </span>
{/if}

<div style="margin:4px; border-top: {#border#};"></div>
{/strip}