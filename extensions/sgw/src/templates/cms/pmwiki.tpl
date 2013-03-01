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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>{$config.cms_title|escape:"html"}: {$page.title|default:$page.name|escape:"html"}</title>
{* You are not allowed to remove or alter the copyright. *}
<!-- 
	This website is brought to you by Simple Groupware
	Simple Groupware is an open source Groupware and Web Application Framework created by Thomas Bley and licensed under GNU GPL v2.
	Simple Groupware is copyright 2002-2012 by Thomas Bley.	Extensions and translations are copyright of their respective owners.
	More information and documentation at http://www.simple-groupware.de/
-->
  <meta name="generator" content="Simple Groupware &amp; CMS" />
  <meta name="description" content="{$page.description|escape:"html"}" />
  <meta name="author" content="{$page.author|escape:"html"}" />
  <meta name="keywords" content="{$page.keywords|escape:"html"}" />
  <link rel="stylesheet" href="ext/cms/styles.css" type="text/css" media="all" />
  <link rel="alternate" type="application/atom+xml" title="Atom-Feed" href="?rss">
</head>
<body class="body">
  <div id="wikilogo">
    <a href="?"><img src="ext/cms/icons/pmwiki-32.gif"> &amp; Simple Groupware</a>
  </div>
  <div id="wikihead">
  <form action="?page=Site.Search" method="get">
    <input type="hidden" name="page" value="Site.Search"/>
    <input type="text" name="q" value="" class="inputbox searchbox" accesskey="s" />
    <input type="submit" class="inputbutton searchbutton" value="Search" />
  </form>
  </div>
  <table id="wikimid" width="100%" cellspacing="0" cellpadding="0"><tr>
    <td id="wikileft" valign="top">
	  {$cms->render("Site.SideBar")}
	  <p class="vspace" style="text-align: right;">
		<span style="font-size:83%;">- <a target="_blank" href="?page=Site.SideBar&edit">{t}Edit{/t}</a> -</span>
	  </p>
	</td>
    <td id="wikibody" valign="top">
	  <h1 class="pagetitle">{$page.title|default:$page.name|escape:"html"}</h1>
	  <div id="wikitext">{$cms->render($page.pagename)}</div>
    </td>
  </tr></table>
  <div id="wikifoot">
    <div class="footnav">
	  {if $smarty.session.username neq "anonymous"}<a href="?logout" accesskey="l">{t}Logout{/t}</a> - {/if}
	  <a href="?rss">{t}Recent changes{/t}</a> - 
	  <a target="_blank" href="?page={$page.pagename|escape:"html"}&edit" accesskey="e">{t}Edit{/t}</a> - 
	  <a href="?page={$page.pagename|escape:"html"}&source">{t}Source{/t}</a> - 
	  <a href="?page=Site.Search">{t}Search{/t}</a>
	</div>
    <div class="lastmod">{t}Page last modified on{/t} {$page.lastmodified|modify::localdateformat:"{t}F j, Y{/t}"}</div>
  </div>
</body>
</html>
