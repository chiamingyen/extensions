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
<table class="data data_page"><tr><td>

<div style="margin-bottom:30px; margin-top:5px;">
<div style="float:right;">
<a href="http://www.simple-groupware.de/cms/AdministrationMenu" target="_blank">{t}Help{/t}</a>
</div>
<div class="bold">{t}Administration{/t}</div>
<hr>
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_users">{t}Users{/t}</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_groups">{t}Groups{/t}</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_identities">{t}Mail identities{/t}</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_tree&view=permissions">{t}Permissions{/t}</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_events">{t}Events{/t}</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_stats">{t}Statistics{/t}</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_nodb_backups">{t}Backups{/t}</a> |
<a href="index.php?folder=^trash">{t}Trash{/t}</a>
<br/><br/>
<a href="browser.php" target="_blank">Web File Browser</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_console">{t}Console scripts{/t}</a> |
<a href="console.php?console=sys" target="_blank">SYS Console</a> |
<a href="console.php?console=sql" target="_blank">SQL Console</a> |
<a href="console.php?console=php" target="_blank">PHP Console</a> |
<a href="cron.php?debug" target="_blank">Cron</a> |
<a href="index.php?action_sys=phpinfo" target="_blank">Phpinfo</a>
</div>

<div style="margin-bottom:30px;">
<div class="bold">{t}Database{/t}</div>
<hr>
<a href="index.php?action_sys=clean_tables">{t}Optimize Tables{/t}</a> |
<a href="index.php?action_sys=rebuild_search&token={""|modify::get_form_token}">{t}Rebuild search index{/t}</a> |
<a href="console.php?console=sql&name=show+processlist&token={""|modify::get_form_token}">{t}Processes{/t}</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_nodb_status">{t}Status{/t}</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_nodb_tablesizes">{t}Table sizes{/t}</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_nodb_tables">{t}Table status{/t}</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_nodb_variables">{t}Variables{/t}</a><br>
<br>
{t}Clear Data{/t}:
<a href="index.php?action_sys=clean_events&token={""|modify::get_form_token}" onclick="return confirm('{t}REALLY delete ALL datasets ?{/t}');">{t}Events{/t}</a> |
<a href="index.php?action_sys=clean_statistics&token={""|modify::get_form_token}" onclick="return confirm('{t}REALLY delete ALL datasets ?{/t}');">{t}Statistics{/t}</a> |
<a href="index.php?action_sys=clean_trash&token={""|modify::get_form_token}" onclick="return confirm('{t}REALLY delete ALL datasets ?{/t}');">{t}Trash{/t}</a> |
<a href="index.php?action_sys=clean_notifications&token={""|modify::get_form_token}" onclick="return confirm('{t}REALLY delete ALL datasets ?{/t}');">Notifications</a>
</div>

<div style="margin-bottom:30px;">
<div class="bold">{t}Caches{/t}</div>
<hr>
<a href="index.php?action_sys=clean_cache">{t}Clean Cache{/t}</a> ||
{t}Clear Cache{/t}:
<a href="index.php?action_sys=clear_output">{t}Output{/t}</a> |
<a href="index.php?action_sys=clear_schema">{t}Schema{/t}</a> |
<a href="index.php?action_sys=clear_schemadata">{t}Schema data{/t}</a> |
<a href="index.php?action_sys=clear_debug">{t}Debug-dir{/t}</a> |
<a href="index.php?action_sys=clear_cms">{t}CMS{/t}</a> |
<a href="index.php?action_sys=clear_ip">IP</a> |
<a href="index.php?action_sys=clear_upload">{t}Uploaded files{/t}</a> |
<a href="index.php?action_sys=clear_email">{t}E-mail{/t}</a> |
<a href="index.php?action_sys=clear_locking">{t}Locking{/t}</a> |
<a href="index.php?action_sys=clear_session&token={""|modify::get_form_token}">{t}Sessions{/t}</a><br>
</div>

<div style="margin-bottom:30px;">
<div class="bold">{t}Setup{/t}</div>
<hr>
<a href="index.php?action_sys=edit_setup">{t}Change Setup settings{/t}</a> |
<a href="index.php?action_sys=clear_setup&token={""|modify::get_form_token}" onclick="return confirm('{t}Really run setup again ?{/t}');">{t}Run Setup again{/t}</a> |
<a href="index.php?action_sys=maintenance&token={""|modify::get_form_token}" onclick="return confirm('{t}Really apply the changes ?{/t}');">{t}Switch maintenance mode{/t}</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_custom_fields">{t}Field customization{/t}</a><br>
<br>
<b><a href="updater.php">{t}Update Simple Groupware{/t}</a> | <a href="extensions.php">Simple Groupware {t}Extensions{/t}</a></b>
</div>

<div style="margin-bottom:30px;">
<div class="bold">{t}Support{/t}</div>
<hr>
<pre>
Simple Groupware version: {$sys.version_str}
Simple Groupware language: {$smarty.const.SETUP_LANGUAGE}
PHP Version: {$smarty.const.PHP_VERSION}
Database + Version: {$smarty.const.SETUP_DB_TYPE} {""|sgsml_parser::sql_version}
Server OS: {""|php_uname}
Webserver: {$smarty.server.SERVER_SOFTWARE}
Webbrowser: <script>document.write(navigator.userAgent);</script>
APC cache usage: <b>{""|admin::apc_stats}</b>
Disk usage: <b>{""|admin::disk_stats}</b>
</pre>
<hr>
<a href="http://groups.google.com/group/simple-groupware" target="_blank">Forum</a> ({t}Give Feedback{/t}<i>!</i>) |
<a href="http://sourceforge.net/tracker/?func=add&group_id=96330&atid=614418" target="_blank">{t}Support request{/t}</a> |
<a href="http://sourceforge.net/tracker/?func=add&group_id=96330&atid=614420" target="_blank">{t}Feature request{/t}</a> |
<a href="http://sourceforge.net/tracker/?func=add&group_id=96330&atid=614419" target="_blank">{t}Submit a patch{/t}</a>
</div>

<div style="margin-bottom:30px;">
<div class="bold">{t}Documentation{/t}</div>
<hr>
<a href="http://www.simple-groupware.de/cms/Main/Documentation" target="_blank">{t}Documentation{/t}</a> |
<a href="http://www.simple-groupware.de/cms/Main/Administration" target="_blank">Administration</a> |
<a href="http://www.simple-groupware.de/cms/Main/FAQ" target="_blank">FAQ</a>
</div>

<div style="margin-bottom:10px;">
<div class="bold">Simple Groupware {$sys.version_str}</div>
<hr>
<a href="../docs/about.html" target="_blank">{t}About{/t}</a> |
<a href="LICENSE.txt" target="_blank">{t}License{/t}</a> |
<a href="../docs/Changelog.txt" target="_blank">{t}Changelog{/t}</a>
<br><br>
<a href="http://www.simple-groupware.de" target="_blank">Simple Groupware Homepage</a> |
<a href="http://sourceforge.net/projects/simplgroup/" target="_blank">Sourceforge</a> |
<a href="http://freecode.com/projects/simplegroupware/" target="_blank">Freecode</a> |
<a href="http://www.facebook.com/SimpleGroupware" target="_blank">Facebook</a>
</div>

</td></tr></table>