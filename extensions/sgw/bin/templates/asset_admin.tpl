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
<a href="http://www.simple-groupware.de/cms/AdministrationMenu" target="_blank">Help</a>
</div>
<div class="bold">Administration</div>
<hr>
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_users">Users</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_groups">Groups</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_identities">Mail identities</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_tree&view=permissions">Permissions</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_events">Events</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_stats">Statistics</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_nodb_backups">Backups</a> |
<a href="index.php?folder=^trash">Trash</a>
<br/><br/>
<a href="browser.php" target="_blank">Web File Browser</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_console">Console scripts</a> |
<a href="console.php?console=sys" target="_blank">SYS Console</a> |
<a href="console.php?console=sql" target="_blank">SQL Console</a> |
<a href="console.php?console=php" target="_blank">PHP Console</a> |
<a href="cron.php?debug" target="_blank">Cron</a> |
<a href="index.php?action_sys=phpinfo" target="_blank">Phpinfo</a>
</div>

<div style="margin-bottom:30px;">
<div class="bold">Database</div>
<hr>
<a href="index.php?action_sys=clean_tables">Optimize Tables</a> |
<a href="index.php?action_sys=rebuild_search&token={""|modify::get_form_token}">Rebuild search index</a> |
<a href="console.php?console=sql&name=show+processlist&token={""|modify::get_form_token}">Processes</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_nodb_status">Status</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_nodb_tablesizes">Table sizes</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_nodb_tables">Table status</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_nodb_variables">Variables</a><br>
<br>
Clear Data:
<a href="index.php?action_sys=clean_events&token={""|modify::get_form_token}" onclick="return confirm('REALLY delete ALL datasets ?');">Events</a> |
<a href="index.php?action_sys=clean_statistics&token={""|modify::get_form_token}" onclick="return confirm('REALLY delete ALL datasets ?');">Statistics</a> |
<a href="index.php?action_sys=clean_trash&token={""|modify::get_form_token}" onclick="return confirm('REALLY delete ALL datasets ?');">Trash</a> |
<a href="index.php?action_sys=clean_notifications&token={""|modify::get_form_token}" onclick="return confirm('REALLY delete ALL datasets ?');">Notifications</a>
</div>

<div style="margin-bottom:30px;">
<div class="bold">Caches</div>
<hr>
<a href="index.php?action_sys=clean_cache">Clean Cache</a> ||
Clear Cache:
<a href="index.php?action_sys=clear_output">Output</a> |
<a href="index.php?action_sys=clear_schema">Schema</a> |
<a href="index.php?action_sys=clear_schemadata">Schema data</a> |
<a href="index.php?action_sys=clear_debug">Debug-dir</a> |
<a href="index.php?action_sys=clear_cms">CMS</a> |
<a href="index.php?action_sys=clear_ip">IP</a> |
<a href="index.php?action_sys=clear_upload">Uploaded files</a> |
<a href="index.php?action_sys=clear_email">E-mail</a> |
<a href="index.php?action_sys=clear_locking">Locking</a> |
<a href="index.php?action_sys=clear_session&token={""|modify::get_form_token}">Sessions</a><br>
</div>

<div style="margin-bottom:30px;">
<div class="bold">Setup</div>
<hr>
<a href="index.php?action_sys=edit_setup">Change Setup settings</a> |
<a href="index.php?action_sys=clear_setup&token={""|modify::get_form_token}" onclick="return confirm('Really run setup again ?');">Run Setup again</a> |
<a href="index.php?action_sys=maintenance&token={""|modify::get_form_token}" onclick="return confirm('Really apply the changes ?');">Switch maintenance mode</a> |
<a href="index.php?find=folder|simple_sys_tree|1|ftype=sys_custom_fields">Field customization</a><br>
<br>
<b><a href="updater.php">Update Simple Groupware</a> | <a href="extensions.php">Simple Groupware Extensions</a></b>
</div>

<div style="margin-bottom:30px;">
<div class="bold">Support</div>
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
<a href="http://groups.google.com/group/simple-groupware" target="_blank">Forum</a> (Give Feedback<i>!</i>) |
<a href="http://sourceforge.net/tracker/?func=add&group_id=96330&atid=614418" target="_blank">Support request</a> |
<a href="http://sourceforge.net/tracker/?func=add&group_id=96330&atid=614420" target="_blank">Feature request</a> |
<a href="http://sourceforge.net/tracker/?func=add&group_id=96330&atid=614419" target="_blank">Submit a patch</a>
</div>

<div style="margin-bottom:30px;">
<div class="bold">Documentation</div>
<hr>
<a href="http://www.simple-groupware.de/cms/Main/Documentation" target="_blank">Documentation</a> |
<a href="http://www.simple-groupware.de/cms/Main/Administration" target="_blank">Administration</a> |
<a href="http://www.simple-groupware.de/cms/Main/FAQ" target="_blank">FAQ</a>
</div>

<div style="margin-bottom:10px;">
<div class="bold">Simple Groupware {$sys.version_str}</div>
<hr>
<a href="../docs/about.html" target="_blank">About</a> |
<a href="LICENSE.txt" target="_blank">License</a> |
<a href="../docs/Changelog.txt" target="_blank">Changelog</a>
<br><br>
<a href="http://www.simple-groupware.de" target="_blank">Simple Groupware Homepage</a> |
<a href="http://sourceforge.net/projects/simplgroup/" target="_blank">Sourceforge</a> |
<a href="http://freecode.com/projects/simplegroupware/" target="_blank">Freecode</a> |
<a href="http://www.facebook.com/SimpleGroupware" target="_blank">Facebook</a>
</div>

</td></tr></table>