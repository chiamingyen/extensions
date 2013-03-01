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

class lib_modules extends lib_default {

static function get_filecontent($var,$args,$data) {
  $file = $data["filename"]["data"][0];
  if (!file_exists($file)) return "";
  return modify::displayfile("modules",$file,false,false);;
}

static function count($path,$where,$vars,$mfolder) {
  $files = array();
  foreach (array("modules/schema/","modules/schema_sys/",SIMPLE_CUSTOM."modules/schema/",SIMPLE_CUSTOM."modules/schema_sys/") as $path) {
    if (is_dir($path) and ($handle=@opendir($path))) {
      while (false !== ($file = readdir($handle))) {
        if($file=='.' or $file=='..' or is_dir($path.$file) or !strpos($file,".xml")) continue;
	    $files[$file] = 0;
      }
      closedir($handle);
    }
  }
  return count($files);
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $rows = array();
  $modules = select::modules();
  asort($modules);
  foreach ($modules as $module=>$name) {
	if ($name[0]==" ") continue;
    $file = sys_find_module($module);
	$row = array(
	  "id"=>$file,
	  "name"=>$name,
	  "modulename"=>$module,
	  "created"=>@filectime($file),
	  "lastmodified"=>@filemtime($file),
	  "lastmodifiedby"=>"",
	  "searchcontent"=>$file,
	  "filename"=>$file,
	  "filemtime"=>@filemtime($file),
	  "filectime"=>@filectime($file),
	  "filesize"=>@filesize($file),
	  "filecontent"=>""
	);
	if (sys_select_where($row,$where,$vars)) $rows[$file] = $row;
  }
  return sys_select($rows,$order,$limit,$fields);
}
}