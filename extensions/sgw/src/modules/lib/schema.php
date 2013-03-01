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

class lib_schema extends lib_default {

static function count($path,$where,$vars,$mfolder) {
  return 1;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $type = $path;
  $filename = sys_find_module($type);
  $custom_schema = "";
  if (!file_exists($filename)) {
	if (!is_numeric($path)) {
	  $type = "sys_nodb_".$vars["handler"];
	} else {
	  $row = db_select_first("simple_sys_tree",array("ftype","folders"),"id=@id@","",array("id"=>$path));
	  if (empty($row["ftype"])) throw new Exception("{t}Folder not found.{/t}");
	  $type = $row["ftype"];
	}
	$filename = sys_find_module($type);
	$custom_schema = db_select_value("simple_sys_tree","custom_schema","id=@id@",array("id"=>$path));
  }

  // TODO optimize
  $rows = db_select("simple_sys_custom_fields",array("custom_schema"),array("module=@schema@", "(ffolder='' or ffolder like @folder@)", "activated=1"),"","",array("schema"=>$type, "folder"=>"%|".$path."|%"));
  if (is_array($rows) and count($rows)>0) {
	$custom_schema = str_replace("</table>", "", $custom_schema);
	if (!strpos($custom_schema, "<table")) $custom_schema = "<table>";
	foreach ($rows as $row) $custom_schema .= $row["custom_schema"];
	$custom_schema .= "</table>";
  }
  return array(array(
	"id"=>$filename,
	"filename"=>$filename,
	"filemtime"=>filemtime($filename),
	"filecontent"=>sgsml_parser::file_get_contents($filename,$type,$custom_schema)
  ));
}
}