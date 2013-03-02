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

class lib_ldap extends lib_default {

static function get_dirs($path, $parent, $recursive) {
  $tree = array();
  self::_get_dirs($path, 1, 0, $parent, $recursive, $tree);
  return $tree;
}

static function count($path,$where,$vars,$mfolder) {
  $ldap_path = substr($path,strpos($path,"/")+1,-1);
  if ($ldap_path!="") $paths = array_reverse(explode("/",$ldap_path)); else $paths = array();
  $paths[] = self::_base_dn($mfolder);
  $path = implode(",",$paths);
  $cid = "ldap_".md5("count_".$mfolder."/".$path);
  if (($count = sys_cache_get($cid))) return $count;
  if (!$ds = self::_connect($mfolder)) return 0;
  if (!$sr = @ldap_list($ds,$path,"objectClass=*",array("dn"))) return 0;
  $count = ldap_count_entries($ds,$sr);
  sys_cache_set($cid,$count,LDAP_LIST_CACHE);
  return $count;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $ldap_path = substr($path,strpos($path,"/")+1,-1);
  if ($ldap_path!="") $paths = array_reverse(explode("/",$ldap_path)); else $paths = array();
  $paths[] = self::_base_dn($mfolder);
  $new_path = implode(",",$paths);

  $mapping = array(
    "lastname"=>"sn",
    "firstname"=>"givenname",
	"email"=>"mail",
	"phone"=>"telephonenumber",
	"street"=>"postaladdress"
  );
  $cid = "ldap_".md5($mfolder."/".$path);
  $datas = sys_cache_get($cid);
  if (!is_array($datas)) {
    $datas = array();
    if (!$ds = self::_connect($mfolder)) return array();
    if (!$sr = @ldap_list($ds,$new_path,"objectClass=*",array("dn","objectClass"))) {
	  sys_warning(ldap_error($ds));
	  return array();
	}
    $info = ldap_get_entries($ds, $sr);
    foreach ($info as $item) {
	  if (!is_array($item)) continue;
	  $sr = ldap_read($ds,$item["dn"],"objectClass=*",array("*","modifyTimestamp","modifiersName","createTimestamp","creatorsName"));
      $data = ldap_get_entries($ds, $sr);
	  if (empty($data[0])) continue;
	  $data = $data[0];
	  $datas[] = $data;
    }
	sys_cache_set($cid,$datas,LDAP_LIST_CACHE);
  }
  
  $rows = array();
  foreach ($datas as $data) {
    $row = array();
    foreach ($fields as $field) {
	  switch ($field) {
	    case "id": $row[$field] = $path."/?".md5($data["dn"]); break;
	    case "folder": $row[$field] = $path; break;
		case "contactid":
      	  $data_2 = ldap_explode_dn($data["dn"],0);
		  $row[$field] = str_replace(array("cn="," "),"",$data_2[0]);
		  break;
		case "lastmodified":
		  $row[$field] = 0; // TODO2 parse $data["modifytimestamp"][0] 20060312043657Z
		  break;
		case "created":
		  $row[$field] = 0; // TODO2 parse $data["createtimestamp"][0]
		  break;
		case "lastmodifiedby":
      	  $data_2 = ldap_explode_dn($data["modifiersname"][0],0);
		  $row[$field] = $data_2[0];
		  break;
		case "createdby":
      	  $data_2 = ldap_explode_dn($data["creatorsname"][0],0);
		  $row[$field] = $data_2[0];
		  break;
		default: 
		  $row[$field] = "";
		  if (isset($mapping[$field])) $ld_field = $mapping[$field]; else $ld_field = $field;
		  if (isset($data[$ld_field][0])) $row[$field] = $data[$ld_field][0];
		  break;
	  }
	}
    if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  $rows = sys_select($rows,$order,$limit,$fields);
  return $rows;
}

private static function _connect($mfolder) {
  static $cache = array();
  if (empty($cache[$mfolder])) {
    $creds = sys_credentials($mfolder);
    if ($creds["server"]=="") return false;
	
    $basedn = $creds["options"];
    if (!$creds["port"]) $creds["port"] = 389;
    if ($creds["ssl"] and !extension_loaded("openssl")) {
      sys_warning(sprintf("[0] %s is not compiled / loaded into PHP.","OpenSSL"));
      return false;
    }
    if (!function_exists("ldap_connect")) {
	  sys_warning(sprintf("%s is not compiled / loaded into PHP.","LDAP"));
	  return false;
    }
    if (!$ds=ldap_connect($creds["server"])) sys_die(sprintf("LDAP connection to host %s failed.",$creds["server"]));
    ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) ;
	ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
	
    if (!@ldap_bind($ds, $creds["username"], $creds["password"])) {
      if (!@ldap_bind($ds)) {
	    sys_warning("LDAP anonymous connection failed.");
	    return false;
	  }
	  if ($basedn=="") {
        $result_id = @ldap_read($ds,"","(objectclass=*)",array("namingContexts"));
  	    $attrs = ldap_get_attributes($ds, ldap_first_entry($ds,$result_id));
		if (isset($attrs["namingContexts"]) and is_array($attrs["namingContexts"])) {
		  $basedn = $attrs["namingContexts"][0];
		}
	  }
	  $creds["username"] = preg_replace("/[\\\\*()#!|&=<>~ ]/", "", $creds["username"]);
  	  $res = ldap_search($ds,$basedn,"uid=".$creds["username"]);
  	  if (ldap_count_entries($ds,$res)==1) {
    	$dn = ldap_get_dn($ds, ldap_first_entry($ds,$res));
    	if (@ldap_bind($ds, $dn, $creds["password"])) {
		  sys_warning(sprintf("Login failed from %s. (ldap) (%s)\n(for active directory username must be: username@domain)",_login_get_remoteaddr(),ldap_error($ds)));
		  return false;
	} } }
	$cache[$mfolder] = $ds;
  }
  return $cache[$mfolder];
}

private static function _get_dirs($path, $left, $level, $parent, $recursive, &$tree) {
  $right = $left+1;
  $ldap_path = substr($path,strpos($path,"/")+1,-1);
  if ($recursive and sys_is_folderstate_open($path,"ldap",$parent)) {
	if ($ldap_path!="") $paths = array_reverse(explode("/",$ldap_path)); else $paths = array();
	$paths[] = self::_base_dn($parent);
	$new_path = implode(",",$paths);

    $cid = "ldap_".md5("boxes_".$parent."/".$path);
	$info = sys_cache_get($cid);
    if (!is_array($info)) {
	  if (!$ds = self::_connect($parent)) return $right+1;
  	  if (!$sr = @ldap_list($ds,$new_path,"objectClass=*",array("dn","objectClass"))) return $right+1;
      $info = ldap_get_entries($ds, $sr);
	  sys_cache_set($cid,$info,LDAP_LIST_CACHE);
	}
	foreach ($info as $item) {
	  if (!is_array($item)) continue;
	  if (isset($item["objectclass"]) and 
	  	(in_array("organizationalPerson",$item["objectclass"]) or in_array("organizationalRole",$item["objectclass"]) or 
		 in_array("posixAccount",$item["objectclass"]) or in_array("inetOrgPerson",$item["objectclass"]) or 
	  	 in_array("group",$item["objectclass"]))) continue;
      $data = ldap_explode_dn($item["dn"],0);
	  $right = self::_get_dirs($path.$data[0]."/", $right, $level+1, $parent, true, $tree);
	}
  }
  $icon = "";
  if ($level==0) $icon = "sys_nodb_ldap.png";
  $tree[$left] = array("id"=>$path,"lft"=>$left,"rgt"=>$right,"flevel"=>$level,"ftitle"=>utf8_encode(basename($path)),"ftype"=>"sys_nodb_ldap","icon"=>$icon);
  return $right+1;
}

private static function _base_dn($mfolder) {
  $cid = "ldap_".md5("basedn_".$mfolder);
  if (($basedn = sys_cache_get($cid))) return $basedn;

  $creds = sys_credentials($mfolder);
  $basedn = $creds["options"];
  if ($basedn=="") {
    if (!$ds = self::_connect($mfolder)) return "";
    $result_id = ldap_read($ds,"","(objectclass=*)",array("namingContexts"));
    $attrs = ldap_get_attributes($ds, ldap_first_entry($ds,$result_id));
	if (isset($attrs["namingContexts"]) and is_array($attrs["namingContexts"])) {
      $basedn = $attrs["namingContexts"][0];
	}
  }
  sys_cache_set($cid,$basedn,LDAP_LIST_CACHE);
  return $basedn;
}
}