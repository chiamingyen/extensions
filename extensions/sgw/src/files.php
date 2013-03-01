<?php
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

define("NOCONTENT",true);
define("NOSESSION",true);
require("index.php");

if (empty($_REQUEST["item"]) or empty($_REQUEST["action"]) or empty($_REQUEST["folder"]) or !isset($_REQUEST["subitem"])) {
  sys_error("Missing parameters.","403 Forbidden");
}

sys_check_auth();

if (empty($_REQUEST["field"])) $field = "filedata"; else $field = ltrim($_REQUEST["field"],"_");
if (empty($_REQUEST["output"])) $output = ""; else $output = $_REQUEST["output"];

$folder = folder_from_path($_REQUEST["folder"]);

if (strtolower($_REQUEST["action"])=="lock") {
  ajax::file_lock($folder, $_REQUEST["item"], $field, $_REQUEST["subitem"]);
} else {
  ajax::file_unlock($folder, $_REQUEST["item"], $field, $_REQUEST["subitem"]);
}

if (strtolower($_REQUEST["action"])=="lock") {
  _files_confirm_lock($_SESSION["username"], $output);
}
if (strtolower($_REQUEST["action"])=="unlock") {
  _files_confirm_unlock($output);
}

function _files_confirm_lock($username,$output) {
  header("Cache-Control: private, max-age=1, must-revalidate");
  header("Expires: ".gmdate("D, d M Y H:i:s", NOW)." GMT");
  if ($output=="sh") {
    echo "#!/bin/sh\necho '{t}File locked.{/t}'\nsleep 2";
  } else if ($output=="vbs") {
    echo 'CreateObject("Wscript.Shell").Popup "{t}File locked.{/t}",2,"Simple Groupware",64';
  } else {
    header("Content-Type: text/xml; charset=utf-8");
	header("Lock-Token: <opaquelocktoken:1>");
	echo '<?xml version="1.0" encoding="utf-8"?>
<D:prop xmlns:D="DAV:">
<D:lockdiscovery><D:activelock>
<D:lockscope><D:exclusive/></D:lockscope><D:locktype><D:write/></D:locktype>
<D:depth>0</D:depth><D:timeout>Second-7200</D:timeout>
<ns0:owner xmlns:ns0="DAV:">'.$username.'</ns0:owner>
<D:locktoken><D:href>opaquelocktoken:1</D:href></D:locktoken>
</D:activelock></D:lockdiscovery>
</D:prop>';
  }
}

function _files_confirm_unlock($output) {
  header("Cache-Control: private, max-age=1, must-revalidate");
  header("Expires: ".gmdate("D, d M Y H:i:s", NOW)." GMT");
  if ($output=="sh") {
    echo "#!/bin/sh\necho '{t}File unlocked.{/t}'\nsleep 2";
  } else if ($output=="vbs") {
    echo 'CreateObject("Wscript.Shell").Popup "{t}File unlocked.{/t}",2,"Simple Groupware",64';
  } else {
    header("HTTP/1.1 204 No Content");
  }
}