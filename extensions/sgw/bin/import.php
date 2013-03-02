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

require("index.php");
require("lib/spreadsheet/Reader.php");
@set_time_limit(1800);

if (empty($_REQUEST["folder"])) sys_error("Missing parameters.","403 Forbidden");
$folder = $_REQUEST["folder"];

sys_check_auth();

setup::out('
	<html>
	<head>
	<title>Simple Groupware Import</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<style>
	  body, h2, img, div, table.data, a {
		background-color: #FFFFFF; color: #666666; font-size: 13px; font-family: Arial, Helvetica, Verdana, sans-serif;
	  }
	  a,input { color: #0000FF; }
	  input {
		font-size: 11px; background-color: #F5F5F5; border: 1px solid #AAAAAA; height: 18px;
		vertical-align: middle; padding-left: 5px; padding-right: 5px; border-radius: 10px;
	  }
	  .checkbox, .radio { border: 0px; background-color: transparent; }
	  .submit { color: #0000FF; background-color: #FFFFFF; width: 125px; font-weight: bold; }
	  
	  .border {
		border-bottom: 1px solid black;
	  }
	  .headline {
		letter-spacing: 2px;
		font-size: 18px;
		font-weight: bold;
	  }
	</style>
	</head>
	<body>
	<div class="border headline">Simple Groupware Import</div>
	<br>
	<a href="index.php">Back</a><br>
');

$infos = array();
$errors = array();
if (isset($_FILES["file"]) and is_array($_FILES["file"])) {
  $files = array();
  $data = $_FILES["file"];
  foreach (array_keys($data["name"]) as $filenum) {
	if ($data["error"][$filenum]=="0" and $data["size"][$filenum]!=0) {
	  if ($data["name"][$filenum]=="") $data["name"][$filenum] = "default";
	  list($target,$filename) = sys_build_filename($data["name"][$filenum]);
	  dirs_checkdir($target);
	  $target .= $_SESSION["username"]."__".$filename;
	  if (move_uploaded_file($data["tmp_name"][$filenum], $target)) {
		$files[] = $target;
	  } else {
		@unlink($data["tmp_name"][$filenum]);
	  }
	} else if ($data["error"][$filenum]!=UPLOAD_ERR_NO_FILE) {
	  $filename = $data["name"][$filenum];
	  switch ($data["error"][$filenum]) {
		case UPLOAD_ERR_FORM_SIZE: $message = "file is too big. Please upload a smaller one. (".$filename.")"; break;
		case UPLOAD_ERR_INI_SIZE: $message = "file is too big. Please change upload_max_filesize, post_max_size in your php.ini (".$filename.") (upload_max_filesize=".@ini_get("upload_max_filesize").", post_max_size=".@ini_get("post_max_size").")"; break;
		case UPLOAD_ERR_PARTIAL: $message = "file was uploaded partially. Please upload again. (".$filename.")"; break;
		case UPLOAD_ERR_NO_FILE: $message = "No file was uploaded Please upload again. (".$filename.")"; break;
		case UPLOAD_ERR_NO_TMP_DIR: $message = "missing a temporary folder. Please upload again. (".$filename.")"; break;
		case UPLOAD_ERR_CANT_WRITE: $message = "Failed to write file to disk. Please upload again. (".$filename.")"; break;
        default: $message = "Please upload again. (".$filename.")"; break;
	  }
	  setup::out("Upload failed: ".modify::htmlquote($message));
	}
  }
  if (!empty($files)) {
	if (!sys_validate_token()) sys_die("Invalid security token");
	$folder = folder_from_path($folder);
	$validate_only = isset($_REQUEST["validate_only"]);
	foreach ($files as $file) {
	  $message = "<b>Processing %s ...</b>";
	  if ($validate_only) $message = "<b>Validating %s ...</b>";
	  setup::out(sprintf($message, modify::htmlquote(modify::basename($file))));
	  ajax::file_import($folder, $file, array("setup", "out"), $validate_only);
	  setup::out("<hr>");
} } }

$sgsml = new sgsml($folder, "new");
$view = $sgsml->view;
$required_fields = array();
foreach ($sgsml->current_fields as $name=>$field) {
  if (empty($field["REQUIRED"])) continue;
  $required_fields[$name] = !empty($field["DISPLAYNAME"])?$field["DISPLAYNAME"]:$name;
}
setup::out_exit('
	Folder: '.modify::htmlquote(modify::getpathfull($folder)).'<br>
	<br>
	<a href="index.php?export=calc&limit=1&hide_fields=id&folder='.modify::htmlquote($folder).'&view=details">Download example file (.xls)</a>
	<br>
	Required fields: '.modify::htmlquote(implode(", ", $required_fields)).'
	<br><br>
	File (.xls):<br>
	<form method="post" action="import.php?" enctype="multipart/form-data">
	<input type="hidden" name="token" value="'.modify::get_form_token().'">
	<input type="hidden" name="folder" value="'.modify::htmlquote($folder).'">
	<input type="File" name="file[]" value="" multiple="true" required="true">
	<input type="submit" value="I m p o r t" class="submit">
	<input type="submit" name="validate_only" value="V a l i d a t e" class="submit">
	</form>
	<br>
	<b>Note:</b> Assets can be imported into multiple folders by adding the "Folder" column.<br>
	<b>Note:</b> Assets can be overwritten by adding the "Id" column.<br>
	<br>
	<div style="border-top: 1px solid black;">Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.</div></div>
	</body>
	</html>
');

// TODO use URL for upload