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

class lib_csv_contacts extends lib_default {

static function count($path,$where,$vars,$mfolder) {
  $count = count(sys_parse_csv($path));
  if ($count>0) $count--;
  return $count;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $path = rtrim($path,"/");
  $csv_data = sys_parse_csv($path);
  $rows = array();
  $index = array_shift($csv_data);
  $i = 0;
  $ids = array();
  
  $datas = array();
  foreach ($csv_data as $row) {
    $n_row = array();
    foreach ($row as $key=>$val) {
	  if (!empty($index[$key])) $key = strtolower($index[$key]);
	  $n_row[$key] = $val;
	}
	$datas[] = $n_row;
  }

/*  
"Middle Name","Suffix","Business Street 2","Business Street 3",
"Home Street","Home Street 2","Home Street 3","Home City","Home State","Home Postal Code",
"Home Country/Region","Other Street","Other Street 2","Other Street 3","Other City",
"Other State","Other Postal Code","Other Country/Region",
"Assistant's Phone","Business Phone 2","Callback","Car Phone",
"Company Main Phone","Home Fax","Home Phone 2","ISDN",
"Other Fax","Other Phone","Primary Phone","Radio Phone","TTY/TDD Phone","Telex",
"Account","Anniversary","Assistant's Name","Billing Information",
"Business Address PO Box","Categories","Children","Directory Server"
"E-mail Type","E-mail Display Name","E-mail 2 Address","E-mail 2 Type",
"E-mail 2 Display Name","E-mail 3 Address","E-mail 3 Type","E-mail 3 Display Name",
"Gender","Government ID Number","Hobby","Home Address PO Box","Initials",
"Internet Free Busy","Keywords","Language","Location","Manager's Name","Mileage",
"Office Location","Organizational ID Number","Other Address PO Box","Priority",
"Private","Profession","Referred By","Sensitivity","Spouse","Web Page"  
  */
  
  $mapping = array(
    "e-mail address" => "email",
	"company" => "company",
	"first name" => "firstname",
	"last name" => "lastname",
	"title" => "title",
	"mobile phone" => "mobile",
	"pager" => "pager",
	"business phone" => "phone",
	"business fax" => "fax",
	"notes" => "description",
	"job title" => "position",
	"e-mail 2" => "emailprivate",
	"home phone" => "phoneprivate",
	"business address" => "street",
	"business street" => "street",
	"business city" => "city",
	"business postal code" => "zipcode",
	"business state" => "state",
	"business country/region" => "country",
	"department" => "department",
	"birthday" => "birthday",
  );

  foreach ($datas as $data) {
	$i++;
    $row = array();
	foreach ($fields as $field) {
	  $row[$field] = "";
	  switch ($field) {
	    case "id": $row[$field] = $path."/?".$i; break;
	    case "folder": $row[$field] = $path; break;
		case "created": $row[$field] = 0; break;
		case "lastmodified": $row[$field] = 0; break;
		case "lastmodifiedby": $row[$field] = ""; break;
		case "searchcontent": $row[$field] = implode(" ",$data); break;
		case "contactid": 
		  if (empty($data["name"])) {
			if (!empty($data["last name"])) $row[$field] = $data["last name"];
			if (!empty($data["first name"])) $row[$field] .= " ".$data["first name"];
			if ($row[$field]=="" and !empty($data["e-mail address"])) $row[$field] = $data["e-mail address"];
		  } else $row[$field] = $data["name"];
			
		  $row[$field] = str_replace(array(" ",".",",","@","\"","'"),array("_","_","","_","",""),$row[$field]);
		  $row[$field] = substr(trim($row[$field]," _-."),0,15);
		  while (isset($ids[$row[$field]])) $row[$field] .= "_2";
		  $ids[$row[$field]] = "";
		  break;
		case "lastname":
		  if (!empty($data["last name"])) $row[$field] = $data["last name"];
		  if ($row[$field]=="" and !empty($data["name"])) {
		    if (($pos = strpos($data["name"]," "))) {
			  $row[$field] = substr($data["name"],$pos+1);
			} else $row[$field] = $data["name"];
		  }
		  if ($row[$field]=="" and !empty($data["e-mail address"])) {
		    preg_match("/[-._]?([^-._@]+)@/i",$data["e-mail address"],$match);
			if (!empty($match[1])) $row[$field] = ucfirst(strtolower($match[1]));
		  }
		  $row[$field] = trim($row[$field]," ,");
		  break;
		case "firstname":
		  if (!empty($data["first name"])) $row[$field] = $data["first name"];
		  if ($row[$field]=="" and !empty($data["name"]) and $pos = strpos($data["name"]," ")) {
		    $row[$field] = substr($data["name"],0,$pos);
		  }
		  if ($row[$field]=="" and !empty($data["e-mail address"])) {
		    preg_match("/([^-._@]+)[-._][^-._@]*@/i",$data["e-mail address"],$match);
			if (!empty($match[1])) $row[$field] = ucfirst(strtolower($match[1]));
		  }
		  $row[$field] = trim($row[$field]," ,");
		  break;
		default:
		  if ($field_key = array_search($field,$mapping) and !empty($data[$field_key])) {
		    $row[$field] = str_replace(array("\"","'"),"",$data[$field_key]);
		  }
		  if ($field=="birthday" and $row[$field]!="0/0/00") $row[$field] = modify::datetime_to_int($row[$field]);
		  break;
	  }
	}
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  $rows = sys_select($rows,$order,$limit,$fields);
  return $rows;
}
}