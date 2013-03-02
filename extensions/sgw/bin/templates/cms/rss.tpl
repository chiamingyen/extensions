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
<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">
<channel>
<title>PmWiki &amp; Simple Groupware</title>
<link>{$page.url}</link>
<description>{$page.description|escape:"html"}</description>
<generator>Simple Groupware &amp; CMS</generator>
<pubDate>{"r"|sys_date}</pubDate>

<image>
<url>ext/cms/icons/logo.gif</url>
<title>PmWiki &amp; Simple Groupware</title>
<link>{$page.url}</link>
<width>-15</width>
</image>
	
{foreach name=outer item=entry from=$rss_pages}
<item>
<title>{$entry.title|default:$entry.pagename|escape:"html"}</title>
<link>{$page.url}{$page.url_param}{$entry.pagename}</link>
<description>{$entry.change_summary|default:$entry.description|escape:"html"}</description>
<pubDate>{"r"|sys_date:$entry.lastmodified}</pubDate>
</item>
{/foreach}
</channel>
</rss>