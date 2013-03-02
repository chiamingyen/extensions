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

class sync4j {

public static function delete($id, $data, $unused, $table) {
  if (!defined("SYNC4J") or !SYNC4J) return "";
  
  $module = str_replace("simple_","",$table);
  $anchor = db_select_value("simple_sys_tree","anchor","id=@id@",array("id"=>$data["folder"]));
  if (empty($data["id"]) or empty($anchor) or !sys_strbegins($anchor,$module."_")) return "";

  switch ($table) {
	case "simple_calendar":
	case "simple_tasks":
	  if (empty($data["syncid"])) return "";
	  db_update("fnbl_pim_calendar",array("status"=>"D","last_update"=>NOW*1000),array("id=@id@"),array("id"=>$data["syncid"]),array("no_defaults"=>1));
	  break;
	case "simple_contacts":
	  if (empty($data["syncid"])) return "";
	  db_update("fnbl_pim_contact",array("status"=>"D","last_update"=>NOW*1000),array("id=@id@"),array("id"=>$data["syncid"]),array("no_defaults"=>1));
	  break;
	case "simple_notes":
	  if (empty($data["syncid"])) return "";
	  db_update("fnbl_pim_note",array("status"=>"D","last_update"=>NOW*1000),array("id=@id@"),array("id"=>$data["syncid"]),array("no_defaults"=>1));
	  break;
  }
  return "";
}

public static function createedit($id, $data, $unused, $table) {
  if (!defined("SYNC4J") or !SYNC4J) return "";

  $module = str_replace("simple_","",$table);
  $anchor = db_select_value("simple_sys_tree","anchor","id=@id@",array("id"=>$data["folder"]));
  if (empty($id) or empty($anchor) or !sys_strbegins($anchor,$module."_")) return "";
  $username = substr($anchor,strlen($module)+1);

  $error = "";
  switch ($table) {
	case "simple_notes":
	  $row = db_select_first("fnbl_simple_notes_exp","*","id=@id@","",array("id"=>$id));
	  if (!empty($row["id"])) {
	    self::_create_item("fnbl_pim_note",$row,"simple_notes",$id,$username);
	  }
	  break;
	case "simple_tasks":
	  $row = db_select_first("fnbl_simple_tasks_exp","*","id=@id@","",array("id"=>$id));
	  if (!empty($row["id"])) {
	    self::_create_item("fnbl_pim_calendar",$row,"simple_tasks",$id,$username);
	  }
	  break;
	case "simple_calendar":
	  $row = db_select_first("fnbl_simple_calendar_exp","*","id=@id@","",array("id"=>$id));
	  if (!empty($row["id"])) {
		db_delete("fnbl_pim_calendar_exception",array("calendar=@id@"),array("id"=>$row["syncid"]));
	    self::_create_item("fnbl_pim_calendar",$row,"simple_calendar",$id,$username);

		if (!empty($row["rec_exceptions"])) {
		  $exceptions = explode("|",trim($row["rec_exceptions"],"|"));
		  if (count($exceptions)>0) {
		    foreach ($exceptions as $exception) {
			  $data = array("calendar"=>$row["syncid"],"occurrence_date"=>date("Y-m-d H:i:s",$exception));
			  db_insert("fnbl_pim_calendar_exception",$data,array("no_defaults"=>1));
	  } } } }
	  break;
	case "simple_contacts":
	  $row = db_select_first("simple_contacts","*","id=@id@","",array("id"=>$id));
	  $company = db_select_first("simple_companies","*","companyname=@company@","",array("company"=>$row["company"]));
	  if (!empty($row["id"])) {
		
		$row2 = db_select_first("fnbl_simple_contacts_exp","*","id=@id@","",array("id"=>$id));
	    self::_create_item("fnbl_pim_contact",$row2,"simple_contacts",$id,$username);

		db_delete("fnbl_pim_address",array("contact=@syncid@"),array("syncid"=>$row["syncid"]));
		if (!empty($row["street"])) {
		  $data = array("contact"=>$row["syncid"], "type"=>1, "street"=>$row["street"], "city"=>$row["city"],
				  "state"=>$row["state"], "postal_code"=>$row["zipcode"], "country"=>$row["country"]);
		  db_insert("fnbl_pim_address",$data,array("no_defaults"=>1));
		}
		if (!empty($company["street"])) {
		  $data = array("contact"=>$row["syncid"], "type"=>2, "street"=>$company["street"], "city"=>$company["city"],
				  "state"=>$company["state"], "postal_code"=>$company["zipcode"], "country"=>$company["country"]);
		  db_insert("fnbl_pim_address",$data,array("no_defaults"=>1));
		}

		/**
		 * @see https://core.forge.funambol.org/source/browse/core/branches/v10/modules/foundation/foundation-core/src/main/java/com/funambol/foundation/items/dao/PIMContactDAO.java?view=markup
		 * @see http://code.google.com/p/syncby/source/browse/trunk/SyncSources/funambol/fnbl_pim_contact_item.txt
		 */
		$items = array(
		  1 => $row["phoneprivate"],
		  2 => $row["faxprivate"],
		  3 => $row["mobile"],
		  4 => $row["email"],
		  6 => $row["homepage"],
		  7 => @$company["homepage"],
		  8 => $row["skype"],
		  10 => $row["phone"],
		  11 => $row["fax"],
		  12 => @$company["phone"],
		  14 => $row["pager"],
		  16 => $row["emailprivate"],
		  // 21 PrimaryTelephoneNumber
		  // 23 Email3Address
		);
		foreach ($items as $key=>$item) {
		  db_delete("fnbl_pim_contact_item",array("contact=@syncid@", "type=@type@"),array("syncid"=>$row["syncid"],"type"=>$key));
		  if (empty($item)) continue;
		  db_insert("fnbl_pim_contact_item",array("contact"=>$row["syncid"],"type"=>$key,"value"=>$item),array("no_defaults"=>1));
		}
	  }
	  break;
  }  
  return $error;
}

public static function import_createedit($tfolder, $module, $username, $lastsync, $fields) {
  $table_source = "fnbl_simple_".$module."_imp";
  $table_dest = "simple_".$module;

  $sys_date = date("Y-m-d H:i:s");
  $db_date = sgsml_parser::sql_date();
  if (abs(strtotime($sys_date) - strtotime($db_date)) > 60) {
	sys_warning("Error: current time System: ".$sys_date." Database: ".$db_date);
  }
  if (DEBUG) echo "Sync4j: ".$table_source." lastmodified > ".$lastsync." ".date("c", $lastsync);
  
  $count_insert = 0;
  $count_update = 0;
  $rows = db_select($table_source,"*",array("userid=@username@","lastmodified > @lastmodified@"),"","",array("username"=>$username,"lastmodified"=>$lastsync-600));
  if (is_array($rows) and count($rows)>0) {
	foreach ($rows as $row) {
	  if ($row["status"]=="D") { // delete
		self::_import_delete($tfolder, $row["syncid"], $table_dest, $module);
		continue;
	  }
	  unset($row["userid"]);
	  unset($row["status"]);
	  if ($table_dest=="simple_contacts" and empty($row["contactid"])) continue;
	  if ($table_dest=="simple_tasks") {
	    if (empty($row["begin"]) and empty($row["ending"])) continue;
		if (empty($row["begin"])) $row["begin"] = $row["ending"];
	  }
	  $exists = db_select_value($table_dest,"id","syncid=@id@",array("id"=>$row["syncid"]));
	  if (!empty($exists)) $id = $exists; else $id = 0;
	  if ($id!=0) { // update
		$row["history"] = sprintf("Item edited (%s) by %s at %s (sync)\n","@fields@",$_SESSION["username"],sys_date("m/d/y g:i:s a"));
	    $cdata = "";
		$data = $row;
		$cfields = array();
		$data_old = db_select_first($table_dest,"*","id=@id@","",array("id"=>$id));
		if (!empty($data_old["id"])) {
	      if ($row["lastmodified"]==$data_old["lastmodified"]) continue;
		  foreach ($data as $key=>$val) {
			if (isset($data_old[$key]) and $key!="history") {
			  if ($data_old[$key]!=$val) {
			    if (trim($val)!="") $cdata .= $key.": ".$val."\n";
			    $cfields[] = $key;
			  } else unset($data[$key]);
		} } }
	    if (count($data)<3) continue;
		$data["history"] = str_replace("@fields@",implode(", ",$cfields),$data["history"]).$cdata."\n";
		
		if (DEBUG) print_r($data);
		$error_sql = db_update($table_dest,$data,array("id=@id@"),array("id"=>$id));
		$count_update++;
	  } else { // new
	    $id = sql_genID($table_dest)*100+$_SESSION["serverid"];
		$row["id"] = $id;
		$row["folder"] = $tfolder;
		$row["dsize"] = 0;
		$row["history"] = sprintf("Item created by %s at %s (sync)\n",$_SESSION["username"],sys_date("m/d/y g:i:s a"));
		if (DEBUG) print_r($row);
		$error_sql = db_insert($table_dest,$row);
		$count_insert++;
      }
	  if ($error_sql=="") {
	    if ($module=="calendar") trigger::calcappointment($id,$row,false,"simple_calendar");
	    if ($module=="tasks") trigger::duration($id,$row,false,"simple_tasks");
		trigger::notify($id,$row,array(),"simple_".$module);
		
		db_search_update($table_dest,$id,$fields);
		if ($count_insert>0) sys_log_stat("new_records",$count_insert);
		if ($count_update>0) sys_log_stat("changed_records",$count_update);
  } } }
  db_update_treesize($table_dest,$tfolder);
  return "";
}

private static function _import_delete($folder, $id, $tname, $module) {
  $where = array("folder=@folder@");
  if ($id[0]=="_") $where[] = "id=@id@"; else $where[] = "syncid=@id@";
  $row_id = db_select_value($tname,"id",$where,array("id"=>trim($id,"_"),"folder"=>$folder));
  if (!empty($row_id)) {
	$trash = db_select_value("simple_sys_tree","id","anchor=@anchor@",array("anchor"=>"trash"));
	if (empty($trash)) {
	  sys_warning("Error: Trash folder not found.");
	  return;
	}
	$id = folders::create(sys_date("m/d/Y"),"blank","",$trash,true);
	$id2 = folders::create($module,str_replace("simple_","",$tname),"",$id,true);
	$data = array("folder"=>$id2,"history"=>sprintf("Item deleted by %s at %s\n",$_SESSION["username"],sys_date("m/d/y g:i:s a")));
	db_update($tname,$data,array("id=@id@"),array("id"=>$row_id));
	db_update_treesize($tname,$folder);
	db_search_delete($tname,$row_id,$folder);
    sys_log_stat("deleted_records",1);
  }
}

private static function _create_item($table, $row, $source_table, $id, $userid) {
  $row["userid"] = $userid;
  $row["id"] = $row["syncid"];
  $unset = array("syncid","rec_exceptions");
  foreach ($unset as $var) if (isset($row[$var])) unset($row[$var]);
  if (DEBUG) print_r($row);
  foreach ($row as $key=>$val) if ($val=="") $row[$key] = null;
  if ($row["status"]=="U") {
	return db_update($table,$row,array("id=@syncid@","status!='D'"),array("syncid"=>$row["id"]),array("no_defaults"=>1));
  } else {
	$error = db_insert($table,$row,array("no_defaults"=>1));
	if ($error=="") {
	  $error .= db_update($source_table,array("syncid"=>$row["id"]),array("id=@id@"),array("id"=>$id));
	}
	return $error;
  }
}

}
?>