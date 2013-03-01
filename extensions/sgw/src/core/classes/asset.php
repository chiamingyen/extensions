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

class asset {

// modes = delete, empty (complete folder), purge (no trash), purgeall (complete folder + no trash)
static function delete_items($folder,$view,$items,$mode="delete") {
  if (empty($folder) or empty($view) or !is_array($items) or $mode=="") return;

  $sgsml = new sgsml($folder,$view,$items);
  $tname = $sgsml->tname;
  $handler = $sgsml->handler;
  if (!isset($sgsml->buttons[$mode]) or ($mode=="delete" and count($items)==0)) return;
  if ($mode=="empty") $sgsml->where = array("folder in (@folders@)");
  if ($mode=="purgeall") $sgsml->where = array();
  
  if  (in_array($mode,array("purge","purgeall"))) $delete = true; else $delete = false;
  if (folder_in_trash($folder)) $delete = true;
  if ($handler=="") $file_fields = $sgsml->get_fields_by_type("files"); else $file_fields = array();
  
  if (!empty($sgsml->att["TRIGGER_DELETE"])) {
    $fields = array("*");
  } else {
    if (isset($sgsml->fields["notification"])) {
	  $fields = array("id","folder","notification");
	  foreach ($sgsml->fields as $key=>$field) {
		if (isset($field["REQUIRED"]) and $field["SIMPLE_TYPE"]!="files" and !in_array($key,$fields)) $fields[] = $key;
	  }
	} else $fields = array("id");
  	$fields = array_unique(array_merge($fields,$file_fields));
  }

  $rows = $sgsml->get_rows($fields);
  if (!is_array($rows) or count($rows)==0 or count($rows) < count($items)) exit("{t}Item(s) not found or access denied.{/t}");
  
  if ($delete) {
    foreach ($rows as $row) {
	  foreach ($file_fields as $field) {
		$files = explode("|",$row[$field]);
		sys_unlink($files);
	  }
	  $data = array("id"=>$row["id"], "folder"=>$folder);
	  db_delete($tname,array("id=@id@"),$data,array("handler"=>$handler));
	}
  } else {
    $trash = db_select_value("simple_sys_tree","id","anchor=@anchor@",array("anchor"=>"trash"));
    if (empty($trash)) exit("{t}Error{/t}: {t}Trash folder not found.{/t}");

    foreach ($rows as $row) {
	  $id = folders::create(sys_date("{t}m/d/Y{/t}"),"blank","",$trash,true);
	  $tid = folders::create($sgsml->att["MODULENAME"],str_replace("simple_","",$tname),"",$id,true);
	  $data = array("folder"=>$tid,"history"=>sprintf("{t}Item deleted by %s at %s{/t}\n",$_SESSION["username"],sys_date("{t}m/d/y g:i:s a{/t}")));
	  db_update($tname,$data,array("id=@id@"),array("id"=>$row["id"]),array("handler"=>$handler));
	  db_update_treesize($tname,$tid);

	  if (!isset($row["notification"])) $row["notification"] = "";
	  $tree_notification = db_select_value("simple_sys_tree","notification","id=@id@",array("id"=>$folder));
	  if ($tree_notification) $row["notification"] .= ",".$tree_notification;

	  if (!empty($row["notification"])) {
		$smtp_data = self::build_notification($tname,$sgsml->fields,$row,$data,$id);
		asset_process_trigger("sendmail",$row["id"],$smtp_data);
	  }
	  if (!empty($sgsml->att["TRIGGER_DELETE"])) {
	    asset_process_trigger($sgsml->att["TRIGGER_DELETE"],$row["id"],$row,$tname);
	  }
	  db_update("simple_sys_tree",array("history"=>"[".$row["id"]."/details] ".$data["history"]),array("id=@id@"),array("id"=>$folder));
	  db_search_delete($tname,$row["id"],$folder);
	  db_notification_delete($tname,$row["id"]);
	}
  }
  db_update_treesize($tname,$folder);
  sys_log_stat("deleted_records",count($rows));
}

static function build_history($type, $value, $data_old) {
  if ($value=="0" and in_array($type, array("folder", "date", "datetime", "time"))) {
	return "";
  }
  switch($type) {
    case "folder": $value = modify::getpathfull($value,false,"/")." ([/".$value."])"; break;
    case "password": $value = ""; break;
	case "date": $value = sys_date("{t}m/d/Y{/t}",$value); break;
   	case "datetime": $value = sys_date("{t}m/d/Y g:i a{/t}",$value); break;
	case "time": $value = sys_date("{t}g:i a{/t}",$value); break;
	case "dateselect":
	  $data = array();
	  foreach (explode("|",trim($value,"|")) as $date) $data[] = sys_date("{t}m/d/Y{/t}", $date);
	  $value = implode(", ", $data);
	  break;
	case "files":
	  $new = explode("|",trim($value,"|"));
	  $old = explode("|",trim($data_old,"|"));
	  $value = array("");
	  foreach ($new as $file) {
	    if ($file!="" and !in_array($file,$old)) $value[] = "+ ".modify::basename($file);
	  }
	  foreach ($old as $file) {
	    if ($file!="" and !in_array($file,$new)) $value[] = "- ".modify::basename($file);
	  }
	  $value = rtrim(implode("\n  ",$value));
	  break;
	case "select": $value = str_replace("|",", ",trim($value,"|")); break;
	case "checkbox": $value = $value?"{t}yes{/t}":"{t}no{/t}"; break;
	case "textarea": $value = self::build_diff($data_old, $value); break;
	default:
	  if (is_call_type($type)) $value = call_type($type, "build_history", $data_old, $value);
	  break;
  }
  return $value;
}

static function build_diff($old, $new) {
  if (empty($old)) {
    return str_replace(array("\n","\r"),array("\n  ",""),"\n".$new);
  }
  return self::_PHPDiff( str_replace("\r","",$old)."\n", str_replace("\r","",$new)."\n", true);
}

static function build_notification($module,$fields,$data_full,$data,$id,$data_row=array()) {
  if (!is_numeric($data_full["folder"])) {
	$folder_title = basename($data_full["folder"]);
  } else {
    $folder_title = db_select_value("simple_sys_tree","ftitle",array("id=@id@"),array("id"=>$data_full["folder"]));
  }
  if (!empty($data_full["folder"])) {
    $details = "http".(sys_https()?"s":"")."://".$_SERVER['HTTP_HOST'].dirname($_SERVER["SCRIPT_NAME"]);
	$details .= "/index.php?view=details&folder=".$data_full["folder"]."&item%5B%5D=".$id;
  } else $details = "";

  $message = substr($data["history"],0,strpos($data["history"],"\n"))."\n\n";
  if (!empty($data_full["notification_summary"])) {
    $message .= "{t}Summary{/t}: ".trim($data_full["notification_summary"])."\n\n";
  }
  $title = "";
  foreach ($data_full as $key=>$value) {
    if (!isset($data[$key]) and !isset($fields[$key]["REQUIRED"])) continue;
	if (!isset($fields[$key]["DISPLAYNAME"]) or !empty($fields[$key]["NO_SEARCH_INDEX"])) continue;
	if (is_array($value)) $value = implode("|",$value);
    if ($key!="notification_summary" and strlen($value)>0) {
	  if (!isset($data_row[$key])) $data_row[$key] = "";
	  $value = trim(self::build_history($fields[$key]["SIMPLE_TYPE"],$value,$data_row[$key]));
	  if ($value!="") {
	    $message .= $fields[$key]["DISPLAYNAME"].": ".$value."\n";
	    if ($title=="") $title = $fields[$key]["DISPLAYNAME"].": ".$value;
  } } }
  if ($details) $message .= "\n{t}Details{/t}:\n".$details;
  if (DEBUG) $message = sys_remove_trans($message);

  $attachment = "";
  if ($module=="simple_calendar" and !empty($GLOBALS["t"])) {
    $attachment = implode("",sys_build_filename("invite.ics"));
	$ical_data = array();
	foreach ($data_full as $key=>$val) {
	  $ical_data[$key] = array("data"=>(array)$val, "filter"=>(array)$val);
	}
	$ical_data["_id"] = $id;
	file_put_contents($attachment, export::icalendar_data($ical_data), LOCK_EX);
  }
  $smtp_data = array(
    "efrom"=>"",
    "eto"=>$data_full["notification"],
    "subject"=>SMTP_NOTIFICATION." - ".$folder_title." - ".$title,
    "message"=>$message,
    "attachment"=>$attachment,
    "folder"=>$data_full["folder"],
  );
  return $smtp_data;
}

static function create_edit($tfolder, $tview, $mode) {
  $errors = array();
  $defaults = array();
  $form_ids = array();
  $saved_ids = array();

  $sgsml = new sgsml($tfolder,$tview,array_keys($_REQUEST["form_fields"]));
  
  $file_fields = $sgsml->get_fields_by_type("files");

  foreach ($_REQUEST["form_fields"] as $id) {
	$prefix = "form_".md5($id);

	$result = array();
    foreach ($file_fields as $field_name) {
	  if (($error = self::_processfiles($sgsml,$field_name,$id))) $result[$field_name] = $error;
	}
	if (!sys_validate_token()) $result['token'] = array(array("{t}validation failed{/t}", "{t}Invalid security token{/t} {t}Please activate cookies.{/t}"));

    $data = array();
    foreach ($sgsml->current_fields as $field_name => $field) {
	  $prefix_name = $prefix.$field_name;
	  if (isset($_REQUEST[$prefix_name])) $data[$field_name] = $_REQUEST[$prefix_name];
	}

	if (!$result) {
	  if ($mode=="create") $result = $sgsml->insert($data);
	    else $result = $sgsml->update($data,$id);
	}
	
    foreach ($sgsml->current_fields as $field_name => $field) {
	  if (!isset($data[$field_name])) continue;
	  $defaults[$prefix][$field_name] = is_array($data[$field_name])?implode("|",$data[$field_name]):$data[$field_name];
	}
	  
	if (!is_array($result)) {
	  $form_ids[] = $id;
	  $saved_ids[] = $result;
	  if ($mode=="create") unset($defaults[$prefix]);
	} else $errors[$prefix] = $result;
  }
  return array($errors, $defaults, $form_ids, $saved_ids);
}

private static function _processfiles($sgsml,$field_name,$id) {
  $error = array();
  $field = $sgsml->fields[$field_name];
  $fieldname = "form_".md5($id).$field_name;
  $displayname = isset($field["DISPLAYNAME"])?$field["DISPLAYNAME"]:$field["NAME"];
  
  if (!isset($_REQUEST[$fieldname])) $_REQUEST[$fieldname] = array();
  
  if (isset($_REQUEST[$fieldname."_cust"]) and is_array($_REQUEST[$fieldname."_cust"])) {
	foreach ($_REQUEST[$fieldname."_cust"] as $url) {
	  if ($url=="" or !preg_match("|^https?://.+|i",$url)) continue;
	  $_REQUEST[$fieldname][] = $url;
  } }

  if (isset($_FILES[$fieldname]) and is_array($_FILES[$fieldname])) {
    $data = $_FILES[$fieldname];
	foreach (array_keys($data["name"]) as $filenum) {
	  if ($data["error"][$filenum]=="0" and $data["size"][$filenum]!=0) {
		if ($data["name"][$filenum]=="") $data["name"][$filenum] = "default";
	    list($target,$filename) = sys_build_filename($data["name"][$filenum]);
		dirs_checkdir($target);
		$target .= $_SESSION["username"]."__".$filename;
		if (move_uploaded_file($data["tmp_name"][$filenum],$target)) {
		  $_REQUEST[$fieldname][] = $target;
		} else {
		  @unlink($data["tmp_name"][$filenum]);
		}
	  } else if ($data["error"][$filenum]!=UPLOAD_ERR_NO_FILE) {
		$filename = $data["name"][$filenum];
	    switch ($data["error"][$filenum]) {
		  case UPLOAD_ERR_FORM_SIZE: $message = "{t}file is too big. Please upload a smaller one.{/t} (".$filename.")"; break;
		  case UPLOAD_ERR_INI_SIZE: $message = "{t}file is too big. Please change upload_max_filesize, post_max_size in your php.ini{/t} (".$filename.") (upload_max_filesize=".@ini_get("upload_max_filesize").", post_max_size=".@ini_get("post_max_size").")"; break;
		  case UPLOAD_ERR_PARTIAL: $message = "{t}file was uploaded partially.{/t} {t}Please upload again.{/t} (".$filename.")"; break;
		  case UPLOAD_ERR_NO_FILE: $message = "{t}No file was uploaded{/t} {t}Please upload again.{/t} (".$filename.")"; break;
		  case UPLOAD_ERR_NO_TMP_DIR: $message = "{t}missing a temporary folder.{/t} {t}Please upload again.{/t} (".$filename.")"; break;
		  case UPLOAD_ERR_CANT_WRITE: $message = "{t}Failed to write file to disk.{/t} {t}Please upload again.{/t} (".$filename.")"; break;
          default: $message = "{t}Please upload again.{/t} (".$filename.")"; break;
		}
		$error[] = array($displayname,"{t}Upload failed{/t}: ".$message);
  } } }
  
  if (!empty($field["SIMPLE_SIZE"]) and count($_REQUEST[$fieldname])>$field["SIMPLE_SIZE"]) {
	$error[] = array($displayname,"{t}maximum number of files exceeded.{/t} (".$field["SIMPLE_SIZE"].")");
  }
  return $error;
}

// Copyright 2003,2004 Nils Knappmeier (nk@knappi.org)
// Copyright 2004-2005 Patrick R. Michaud (pmichaud@pobox.com)
private static function _PHPDiff($old,$new,$details) {
  $t1 = explode("\n",$old);
  $x=array_pop($t1);
  if ($x>'') $t1[]=$x."\n";
  $t2 = explode("\n",$new);
  $x=array_pop($t2);
  if ($x>'') $t2[]=$x."\n";
  # build a reverse-index array using the line as key and line number as value
  # don't store blank lines, so they won't be targets of the shortest distance search
  foreach($t1 as $i=>$x) if ($x>'') $r1[$x][]=$i;
  foreach($t2 as $i=>$x) if ($x>'') $r2[$x][]=$i;
  $a1=0; $a2=0;
  $actions=array();
  while ($a1<count($t1) && $a2<count($t2)) {
    if ($t1[$a1]==$t2[$a2]) { $actions[]=4; $a1++; $a2++; continue; }
    $best1=count($t1); $best2=count($t2);
    $s1=$a1; $s2=$a2;
    while(($s1+$s2-$a1-$a2) < ($best1+$best2-$a1-$a2)) {
      $d=-1;
      foreach((array)@$r1[$t2[$s2]] as $n) {
	    if ($n>=$s1) {
	      $d=$n; break;
	    }
	  }
      if ($d>=$s1 && ($d+$s2-$a1-$a2)<($best1+$best2-$a1-$a2)) {
	    $best1=$d;
		$best2=$s2;
	  }
      $d=-1;
      foreach((array)@$r2[$t1[$s1]] as $n) {
        if ($n>=$s2) {
		  $d=$n;
		  break;
		}
	  }
      if ($d>=$s2 && ($s1+$d-$a1-$a2)<($best1+$best2-$a1-$a2)) {
        $best1=$s1;
		$best2=$d;
	  }
      $s1++;
	  $s2++;
    }
    while ($a1<$best1) { $actions[]=1; $a1++; }  # deleted elements
    while ($a2<$best2) { $actions[]=2; $a2++; }  # added elements
  }
  while($a1<count($t1)) { $actions[]=1; $a1++; }  # deleted elements
  while($a2<count($t2)) { $actions[]=2; $a2++; }  # added elements
  $actions[]=8;
  $op = 0;
  $x0=$x1=0; $y0=$y1=0;
  $out = array("");
  foreach($actions as $act) {
    if ($act==1) { $op|=$act; $x1++; continue; }
    if ($act==2) { $op|=$act; $y1++; continue; }
    if ($op>0) {
      $xstr = ($x1==($x0+1)) ? $x1 : ($x0+1).",$x1";
      if ($op==1 and $details) $out[] = "{t}deleted line{/t} ({$xstr})";
        elseif ($op==3 and $details) $out[] = "{t}changed line{/t} ({$xstr})";
      while ($x0<$x1) { $out[] = '- '.trim($t1[$x0]); $x0++; } # deleted elems
      if ($op==2 and $details) $out[] = "{t}added line{/t} ({$x1})";
      while ($y0<$y1) { $out[] = '+ '.trim($t2[$y0]); $y0++; } # added elems
    }
    $x1++; $x0=$x1;
    $y1++; $y0=$y1;
    $op=0;
  }
  return join("\n  ",$out);
}

}