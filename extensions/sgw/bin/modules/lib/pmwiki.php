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

class lib_pmwiki extends lib_default {

static function count($path,$where,$vars,$mfolder) {
  if (sys_allowedpath($path)!="") return 0;
  $count = 0;
  if (is_dir($path) and $dh = opendir($path)) {
    while (($file = readdir($dh)) !== false) {
      if (is_dir($path.$file) or $file[0]=="." or strpos($file,".GroupAttributes")) continue;
	  $count++;
	}
  } else {
    sys_warning("Access denied.");
  }
  return $count;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  if (sys_allowedpath($path)!="") return array();
  $file_array = array();
  if (!$handle = @opendir($path)) return array();
  while (false !== ($file = readdir($handle))) {
    if (is_dir($path.$file) or $file[0]=='.' or strpos($file,".GroupAttributes")) continue;
	$file_array[]=$file;
  }
  closedir($handle);

  $GLOBALS["WikiLibDirs"] = array(new PageStore());
  $rows = array();
  foreach ($file_array as $filename) {
    $data = stat($path.$filename);
	$row = array();
	foreach ($fields as $field) {
	  switch ($field) {
	    case "filedata":
	    case "id": $row[$field] = $path.$filename; break;
	    case "folder": $row[$field] = $path; break;
	    case "pagename":
		case "searchcontent": $row[$field] = $filename; break;
		case "created": $row[$field] = $data["ctime"]; break;
		case "lastmodified": $row[$field] = $data["mtime"]; break;
		case "lastmodifiedby": $row[$field] = ""; break;
		default: $row[$field] = ""; break;
	  }
	}
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  $rows = sys_select($rows,$order,$limit,$fields);
  if (count($rows)>0) {
    foreach ($rows as $key=>$row) {
	  $meta = self::_parse_pmwiki_file($row["id"]);
	  $meta["history"] = "";
	  foreach ($meta as $mkey=>$mval) {
	    if (strpos($mkey,":")) {
		  $mkey_arr = explode(":",$mkey);
		  if ($mkey_arr[0]=="author") {
	    	$meta["history"] .= "\n".sprintf("Item edited (%s) by %s at %s\n","Content",$mval,sys_date("m/d/y g:i:s a",$mkey_arr[1]))."\n";
		  }
		  if ($mkey_arr[0]=="diff") $meta["history"] .= $mval."\n";
		  if ($mkey_arr[0]=="csum") $meta["history"] .= "Change summary: ".$mval."\n";
		  unset($meta[$mkey]);
		}
	  }
	  $meta["history"] = preg_replace(array("/(^< )/ms","/(^> )/ms"),array("- ","+ "),$meta["history"]);
	  foreach ($meta as $mkey=>$mval) if (in_array($mkey,$fields)) $rows[$key][$mkey] = $mval;
	  $rows[$key]["title"] = modify::htmlunquote($rows[$key]["title"]);
	}
  }
  return $rows;
}

// Copyright (C) 2001-2007 Patrick R. Michaud (pmichaud § pobox.com)
function _parse_pmwiki_file($pagefile) {
  if (sys_allowedpath(dirname($pagefile))!="") return array();
  $page = array();
  if (($fp=@fopen($pagefile, "r"))) {
    $newline = '';
    $urlencoded = false;
    while (!feof($fp)) {
      $line = fgets($fp, 8192);
      while (substr($line, -1, 1) != "\n" && !feof($fp)) $line .= fgets($fp, 8192);
	  $line = rtrim($line);
	  if ($urlencoded) $line = urldecode(str_replace('+', '%2b', $line));
	  @list($k,$v) = explode('=', $line, 2);
	  if (!$k) continue;
	  if ($k == 'version') { 
		$urlencoded = (strpos($v, 'urlencoded=1') !== false); 
		if (strpos($v, 'pmwiki-0.')!==false) $newline="\262";
	  }
	  if ($k == 'newline') { $newline = $v; continue; }
	  if ($newline) $v = str_replace($newline, "\n", $v);
	  if ($k=="text") $k = "data";
	  if ($k=="summary") $k = "description";
	  $page[$k] = $v;
	}
	fclose($fp);
  } else {
	sys_warning(sprintf("Cannot read the file %s.",$pagefile));
  }
  return $page;
}
}