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

// TODO 2 export as text

class export {

static function html() {
  echo self::_html();
}

static function html_vertical() {
  echo self::_html_vertical();
}

static function sss() {
  header("Content-Type: text/plain; charset=utf-8");
  echo self::_sss();
}

static function sss_editor() {
  echo self::_sss_editor(self::_sss());
}

static function xml() {
  header("Content-Type: text/xml; charset=utf-8");
  header("Content-Disposition: attachment; filename=\"".self::_build_filename().".xml\"");
  echo "\xEF\xBB\xBF"; // BOM header
  echo self::_xml(false);
}

static function rss() {
  header("Content-Type: application/atom+xml; charset=utf-8");
  echo self::_xml(true);
}

static function icalendar() {
  header("Content-Type: text/calendar; charset=utf-8");
  header("Content-Disposition: attachment; filename=\"".self::_build_filename().".ics\"");
  echo self::icalendar_data();
}

static function vcard() {
  header("Content-Type: text/x-vcard; charset=utf-8");
  header("Content-Disposition: attachment; filename=\"".self::_build_filename().".vcf\"");
  echo "\xEF\xBB\xBF"; // BOM header
  echo self::_vcard_data();
}

static function ldif() {
  header("Content-Type: text/plain; charset=utf-8");
  header("Content-Disposition: attachment; filename=\"".self::_build_filename().".ldif\"");
  echo "\xEF\xBB\xBF"; // BOM header
  echo self::_ldif();
}

static function csv() {
  header("Content-Type: text/csv; charset=utf-8");
  header("Content-Disposition: attachment; filename=\"".self::_build_filename().".csv\"");
  echo "\xEF\xBB\xBF"; // BOM header
  echo self::_csv();
}

static function calc() {
  header("Content-Type: application/vnd.ms-excel; charset=utf-8");
  header("Content-Disposition: attachment; filename=\"".self::_build_filename().".xls\"");
  self::_xls();
}

static function writer() {
  header("Content-Type: application/msword; charset=utf-8");
  header("Content-Disposition: attachment; filename=\"".self::_build_filename().".doc\"");
  echo self::_html_vertical();
}

static function icalendar_data($data=array()) {
  $output = "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//Simple Groupware and CMS//iCalendar 2.0//EN\nMETHOD:REQUEST\n";
  $data = self::_build_data(false,$data,false,false,true);
  if (count($data)==0) {
	$output .= "BEGIN:VEVENT\nDTSTART:".sys_date("Ymd\THis")."\n";
	$output .= sys_remove_trans("DURATION:PT1H\nSUMMARY:{t}No entries found.{/t}\nEND:VEVENT\n");
  } else {
    $url = self::_url_folder();
    foreach ($data as $asset) {
      $output .= "BEGIN:VEVENT\n";
	  if (!empty($asset["begin"]["data"])) $begin = $asset["begin"]["data"]; else $begin = $asset["created"]["data"];
	  if (!empty($asset["ending"]["data"])) $end = $asset["ending"]["data"]; else $end = $begin+3600;

	  if (!isset($asset["subject"]["filter"])) {
	    $subject = "";
	    foreach ($asset as $aval) {
	      if ($aval["type"]=="text" and $aval["filter"]!="") $subject .= $aval["filter"]." ";
	    }
 	    $subject .= "[".$asset["_id"]["data"]."]";
	  } else $subject = $asset["subject"]["filter"];

	  if (isset($asset["description"]["filter"])) {
	    $description = $asset["description"]["filter"];
	  } else $description = "";

	  if (!empty($asset["recurrence"]["data"])) $recurrence = $asset["recurrence"]["data"]; else $recurrence = "";
	  if (!empty($asset["repeatinterval"]["data"])) $repeatinterval = $asset["repeatinterval"]["data"]; else $repeatinterval = 1;
	  if (!empty($asset["repeatcount"]["data"])) $repeatcount = $asset["repeatcount"]["data"]; else $repeatcount = 0;
	  if (!empty($asset["repeatuntil"]["data"])) $repeatuntil = $asset["repeatuntil"]["data"]; else $repeatuntil = 0;
	  if (!empty($asset["repeatexcludes"]["data"])) $excludes = (array)$asset["repeatexcludes"]["data"]; else $excludes = array();

	  $filter = array("LOCATION"=>"location","CATEGORIES"=>"category");
	  foreach ($filter as $key=>$field) {
		if (!empty($asset[$field]["filter"])) $filter[$key] = $asset[$field]["filter"]; else $filter[$key] = "";
	  }
	  if (!is_array($asset["created"]["data"])) $output .= "DTSTAMP:".sys_date("Ymd\THis",$asset["created"]["data"])."\n";
	  if (!is_array($asset["lastmodified"]["data"])) $output .= "LAST-MODIFIED:".sys_date("Ymd\THis",$asset["lastmodified"]["data"])."\n";
	  $uid = is_numeric($asset["_id"]["data"])?$asset["_id"]["data"]:md5($asset["_id"]["data"]);
	  $output .= "UID:".$uid."@".$_SERVER["SERVER_NAME"]."\n";
	  $output .= "URL;VALUE=URI:".$url."&item%5B%5D=".$asset["_id"]["data"]."\n";
	  
	  if (!empty($asset["allday"]["data"]) and $asset["allday"]["data"]==1) {
  	    $output .= "DTSTART;VALUE=DATE:".sys_date("Ymd",$begin)."\n";
	    $output .= "DTEND;VALUE=DATE:".sys_date("Ymd",$end+60)."\n";
	  } else {
	    $output .= "DTSTART:".sys_date("Ymd\THis",$begin)."\n";
	    $output .= "DTEND:".sys_date("Ymd\THis",$end)."\n";
	  }
	  $output .= "SUMMARY:".self::_icalendar_quote($subject)."\n";
	  if ($description) $output .= "DESCRIPTION:".self::_icalendar_quote($description)."\n";

	  $attendees = array();	  
	  if (!empty($asset["organizer"]["data"])) {
    	$row = db_select_first("simple_sys_users",array("firstname","lastname","email"),"username=@username@","",array("username"=>$asset["organizer"]["data"]));
		if (!empty($row["email"])) {
		  $attendees[] = array("ORGANIZER",$row["firstname"]." ".$row["lastname"],$row["email"]);
		}
	  }
	  if (!empty($asset["participants"]["data"])) {
    	$rows = db_select("simple_sys_users",array("firstname","lastname","email"),"username in (@username@)","","",array("username"=>$asset["participants"]["data"]));
		if (is_array($rows) and count($rows)>0) {
		  foreach ($rows as $row) {
		    $attendees[] = array("ATTENDEE",$row["firstname"]." ".$row["lastname"],$row["email"]);
	  } } }
	  if (!empty($asset["participants_ext"]["data"])) {
    	$rows = db_select("simple_contacts",array("firstname","lastname","email","company"),"contactid in (@ids@)","","",array("ids"=>$asset["participants_ext"]["data"]));
		if (is_array($rows) and count($rows)>0) {
		  foreach ($rows as $row) {
		    $attendees[] = array("ATTENDEE",$row["firstname"]." ".$row["lastname"]." ".$row["company"],$row["email"]);
	  } } }
	  foreach ($attendees as $data) {
		$output .= $data[0].";ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;CN=".self::_icalendar_quote($data[1]).":MAILTO:".self::_icalendar_quote($data[2])."\n";
	  }

	  foreach ($filter as $key=>$field) {
		if ($field) $output .= $key.":".self::_icalendar_quote($field)."\n";
	  }

	  if ($recurrence) {
	    switch ($recurrence) {
		  case "years": $recurrence = "YEARLY"; break;
		  case "months": $recurrence = "MONTHLY"; break;
		  case "weeks": $recurrence = "WEEKLY"; break;
		  case "days": $recurrence = "DAILY"; break;
		}
	    $output .= "RRULE:FREQ=".$recurrence.";INTERVAL=".$repeatinterval;
		if ($repeatcount != 0) $output .= ";COUNT=".$repeatcount;
		if ($repeatuntil != 0) $output .= ";UNTIL=".sys_date("Ymd",$repeatuntil);
		$output .= "\n";
	    if (count($excludes)>0) {
		  foreach ($excludes as $key=>$val) $excludes[$key] = sys_date("Ymd",$val);
	      $output .= "EXDATE;VALUE=DATE:".implode(",",$excludes)."\n";
	    }
	  }
      $output .= "END:VEVENT\n";

// TODO2 code / test
/*
  PRIORITY:[0..9]
  <field name="priority" displayname="{t}Priority{/t}" simple_type="select" simple_size="1" simple_default="3">
  ATTACH;FMTTYPE=application/binary:http://host.com/templates/agenda.doc
  <field name="image" displayname="{t}Image{/t}" simple_type="files" simple_file_size="1M" simple_size="1">
  <field name="attachment" displayname="{t}Attachment{/t}" simple_type="files" simple_file_size="2M" simple_size="1">
*/
    }
  }
  return $output."END:VCALENDAR\n";
}

private static function _html_link($data,$row) {
  if ($data=="") return "";
  if ($row["field"]=="id") {
    $link = self::_url_folder()."&item[]=".rawurlencode($data);
	$data = "<a href='".$link."' target='_blank'>".$data."</a>";
  }
  if (!empty($row["linktext"])) $data = "<a href='".$row["linktext"]."' target='_blank'>".$data."</a>";
  if (!empty($row["link"])) $data = "<a href='".$row["link"]."' target='_blank'>@</a>&nbsp;".$data;
  return $data;
}

private static function _vcard_data() {
  $output = "";
  $data = self::_build_data(false);
  if (count($data)==0) {
	$output .= "BEGIN:VCARD\nVERSION:3.0\n";
	$output .= sys_remove_trans("FN:{t}No entries found.{/t}\nN:{t}No entries found.{/t};---\nEND:VCARD\n");
  } else {
    $url = self::_url_folder();
    foreach ($data as $asset) {
      $output .= "BEGIN:VCARD\nVERSION:3.0\n";
	  $uid = is_numeric($asset["_id"]["data"])?$asset["_id"]["data"]:md5($asset["_id"]["data"]);
	  $output .= "UID:".$uid."@".$_SERVER["SERVER_NAME"]."\n";

	  if (isset($asset["firstname"]["filter"]) and isset($asset["lastname"]["filter"])) {
	    $output .= "FN:".$asset["firstname"]["filter"]." ".$asset["lastname"]["filter"]."\n";
	    $output .= "N:".$asset["lastname"]["filter"].";".$asset["firstname"]["filter"]."\n";
	  }
	  if (!empty($asset["nickname"]["filter"])) $output .= "NICKNAME:".$asset["nickname"]["filter"]."\n";
	  if (!empty($asset["title"]["filter"])) $output .= "TITLE:".$asset["title"]["filter"]."\n";
	  if (!empty($asset["position"]["filter"])) $output .= "ROLE:".$asset["position"]["filter"]."\n";

	  if (!empty($asset["birthday"]["data"]) and $asset["birthday"]["data"]!=0) $output .= "BDAY:".sys_date("Y-m-d",$asset["birthday"]["data"])."\n";

	  if (!empty($asset["company"]["filter"])) $output .= "ORG:".$asset["company"]["filter"]."\n";
	  if (!empty($asset["email"]["filter"])) $output .= "EMAIL;TYPE=WORK:".$asset["email"]["filter"]."\n";
	  if (!empty($asset["emailprivate"]["filter"])) $output .= "EMAIL;TYPE=HOME:".$asset["emailprivate"]["filter"]."\n";

  	  if (!empty($asset["phone"]["filter"])) $output .= "TEL;TYPE=VOICE,MSG,WORK:".$asset["phone"]["filter"]."\n";
	  if (!empty($asset["fax"]["filter"])) $output .= "TEL;TYPE=FAX,WORK:".$asset["fax"]["filter"]."\n";
	  if (!empty($asset["mobile"]["filter"])) $output .= "TEL;TYPE=CELL:".$asset["mobile"]["filter"]."\n";
	  if (!empty($asset["pager"]["filter"])) $output .= "TEL;TYPE=PAGER:".$asset["pager"]["filter"]."\n";

	  if (isset($asset["street"]["filter"]) and isset($asset["zipcode"]["filter"]) and isset($asset["city"]["filter"]) and isset($asset["state"]["filter"]) and isset($asset["country"]["filter"])) {
	    $output .= "ADR;TYPE=WORK:;;".$asset["street"]["filter"].";".$asset["city"]["filter"].";".$asset["state"]["filter"].";".$asset["zipcode"]["filter"].";".$asset["country"]["filter"]."\n";
	  }

	  if (!empty($asset["description"]["filter"])) $output .= "NOTE:".self::_icalendar_quote($asset["description"]["filter"])."\n";
	  if (!empty($asset["category"]["filter"])) $output .= "CATEGORIES:".$asset["category"]["filter"]."\n";
	  
	  $output .= "SOURCE:".$url."&item[]=".rawurlencode($asset["_id"]["data"])."\n";
      $output .= "END:VCARD\n";
    }
  }
  return $output;
}

private static function _ldif() {
  $output = "";
  $data = self::_build_data(false);
  if (count($data)==0) {
	$output .= "";
  } else {
    foreach ($data as $asset) {
	  $output .= "dn: cn=".(isset($asset["firstname"]["filter"])?$asset["firstname"]["filter"]:"")." ".(isset($asset["lastname"]["filter"])?$asset["lastname"]["filter"]:"").",mail=".(isset($asset["email"]["filter"])?$asset["email"]["filter"]:"")."\n";
	  $output .= "objectclass: person\n";
	  $output .= "objectclass: organizationalPerson\n";
	  $output .= "objectclass: inetOrgPerson\n";
	  if (!empty($asset["firstname"]["filter"])) $output .= "givenName: ".$asset["firstname"]["filter"]."\n";
	  if (!empty($asset["lastname"]["filter"])) $output .= "sn: ".$asset["lastname"]["filter"]."\n";
	  if (!empty($asset["contactid"]["filter"])) $output .= "uid: ".$asset["contactid"]["filter"]."\n";
	  
	  if (isset($asset["firstname"]["filter"]) and isset($asset["lastname"]["filter"])) {
	    $output .= "cn: ".$asset["firstname"]["filter"]." ".$asset["lastname"]["filter"]."\n";
	  }
	  if (!empty($asset["nickname"]["filter"])) $output .= "mozillaNickname: ".$asset["nickname"]["filter"]."\n";
	  if (!empty($asset["email"]["filter"])) $output .= "mail: ".$asset["email"]["filter"]."\n";

	  if (!empty($asset["emailprivate"]["filter"])) $output .= "mozillaSecondEmail: ".$asset["emailprivate"]["filter"]."\n";
  	  if (!empty($asset["phone"]["filter"])) $output .= "telephoneNumber: ".$asset["phone"]["filter"]."\n";
  	  if (!empty($asset["phoneprivate"]["filter"])) $output .= "homePhone: ".$asset["phoneprivate"]["filter"]."\n";
	  
	  if (!empty($asset["fax"]["filter"])) $output .= "fax: ".$asset["fax"]["filter"]."\n";
	  if (!empty($asset["pager"]["filter"])) $output .= "pager: ".$asset["pager"]["filter"]."\n";
	  if (!empty($asset["mobile"]["filter"])) $output .= "mobile: ".$asset["mobile"]["filter"]."\n";

	  if (!empty($asset["street"]["filter"])) $output .= "street: ".$asset["street"]["filter"]."\n";
	  if (!empty($asset["city"]["filter"])) $output .= "l: ".$asset["city"]["filter"]."\n";
	  if (!empty($asset["state"]["filter"])) $output .= "st: ".$asset["state"]["filter"]."\n";
	  if (!empty($asset["zipcode"]["filter"])) $output .= "postalCode: ".$asset["zipcode"]["filter"]."\n";
	  if (!empty($asset["country"]["filter"])) $output .= "c: ".$asset["country"]["filter"]."\n";

	  if (!empty($asset["title"]["filter"])) $output .= "title: ".$asset["title"]["filter"]."\n";
	  if (!empty($asset["department"]["filter"])) $output .= "department: ".$asset["department"]["filter"]."\n";

	  if (!empty($asset["company"]["filter"])) $output .= "company: ".$asset["company"]["filter"]."\n";

	  if (!empty($asset["description"]["filter"])) $output .= "description:: ".base64_encode($asset["description"]["filter"])."==\n";
	  
      $output .= "\n";
    }
  }
  return $output;
}

private static function _xml($isfeed) {
  $t = $GLOBALS["t"];
  $tview = $t["view"];
  $output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
  if ($isfeed) {
    $output = "";
    if (isset($t["views"][$tview]["DISPLAYNAME"])) {
      $view = $t["views"][$tview]["DISPLAYNAME"];
    } else $view = ucfirst($tview);

    $url = modify::htmlquote(self::_url()."folder=".rawurlencode($t["folder"])."&view=".$tview);
	$output .= "<rss version=\"2.0\">\n<channel>\n";
	$output .= "<title>".modify::htmlquote($GLOBALS["sel_folder"]["ftitle"]." - ".$view." - ".APP_TITLE)."</title>\n";
 	$output .= "<link>".$url."</link>\n";
  	$output .= "<generator>Simple Groupware &amp; CMS ".CORE_VERSION_STRING."</generator>\n";
	$output .= "<pubDate>".sys_date("r")."</pubDate>\n";

    $output .= "<image>\n";
    $output .= "<url>".self::_url_base()."ext/images/logo.gif</url>\n";
 	$output .= "<link>".self::_url()."</link>\n";
    $output .= "<title>".modify::htmlquote(APP_TITLE)."</title>\n";
	$output .= "<width>-15</width>\n";
    $output .= "</image>\n";
	
    $output .= "<item>\n";
    $output .= "<title>".modify::htmlquote($GLOBALS["sel_folder"]["ftitle"]." - ".$view)."</title>\n";
    $output .= "<link>".$url."</link>\n";
    $output .= "<pubDate>".sys_date("r")."</pubDate>\n";
    $output .= "<description></description>\n";
    $output .= "</item>\n";

	$data = self::_build_data(false,array(),true,$isfeed);
	if (count($data)>0) {
      foreach ($data as $asset) {
	    $title = "";
	    foreach ($asset as $akey=>$aval) {
	      if ($aval["type"]=="text" and $aval["filter"]!="") $title .= $aval["filter"]." ";
	    }
	    $title .= "[".$asset["_id"]["data"]."]";
	    $summary = "<table>";
	    foreach ($asset as $akey=>$aval) {
          if (isset($aval["filter"]) and $aval["name"]!="") {
			$aval["filter"] = self::_html_quote($aval["filter"], $aval["type"], false);
			$summary .= "<tr><td valign='top' nowrap>".$aval["name"].": </td><td>".$aval["filter"]."<br></td></tr>\n";
		  }
	    }
		$summary .= "</table>";
	    $item_url = $url."&amp;item[]=".rawurlencode($asset["_id"]["data"]);
	
		if ($t["att"]["NAME"]=="simple_bookmarks") {
		  if (!empty($asset["url"]["data"]) and !empty($asset["bookmarkname"]["data"])) {
		    $item_url = $asset["url"]["data"];
			$title = $asset["bookmarkname"]["data"];
		  } else continue;
		  if (!empty($asset["description"]["data"])) $summary = $asset["description"]["data"];
		}
	    $output .= "<item>\n";
	    $output .= "<title>".modify::htmlquote($title)."</title>\n";
	    $output .= "<link>".$item_url."</link>\n";
	    $output .= "<pubDate>".sys_date("r",$asset["lastmodified"]["data"])."</pubDate>\n";
	    $output .= "<description>".modify::htmlquote($summary)."</description>\n";
	    $output .= "</item>\n";
      }
	}
	$output .= "</channel></rss>";  
  } else {
    $output .= "<".$t["att"]["NAME"].">\n";
    foreach (self::_build_data(false,array(),true) as $asset) {
	  $output .= "<asset id=\"".$asset["_id"]["data"]."\">\n";
	  foreach ($asset as $akey=>$aval) {
	    if ($aval["name"]) $output .= "<".$akey.">".modify::htmlquote($aval["filter"])."</".$akey.">\n";
	  }
	  $output .= "</asset>\n";
    }
    $output .= "</".$t["att"]["NAME"].">\n";
  }
  return $output;
}

private static function _xls() {
  $t = $GLOBALS["t"];
  require("lib/spreadsheet/Writer.php");
  $xls = new Spreadsheet_Excel_Writer();
  $xls->setTempDir(SIMPLE_CACHE."/output/");
  $xls->setVersion(8);
  $sheet = $xls->addWorksheet($t["title"]);
  $sheet->setInputEncoding('utf-8');
  $sheet->freezePanes(array(1, 0, 1, 0));
  $bold = $xls->addFormat();
  $bold->setBold();
  $normal = $xls->addFormat();
  $normal->setVAlign("top");
  $normal->setTextWrap();

  $data = self::_build_data(true);
  $row = 0;
  $col = 0;
  if (count($data)>0) {
    $col = 0;
	$sheet->setColumn(0, count($data[0])-1, 15);
    foreach ($data[0] as $field) {
	  if (empty($field["name"])) continue;
	  $sheet->write($row,$col++,$field["displayname"], $bold);
    }
	$row++;
    foreach ($data as $asset) {
      $col = 0;
	  foreach ($asset as $aval) {
	    if (!isset($aval["filter"])) continue;
	    $sheet->write($row,$col++,trim(strip_tags($aval["filter"])),$normal);
      }
	  $row++;
    }
  } else {
	$header = self::_build_fields();
    $col = 0;
	$sheet->setColumn(0, count($header)-1, 15);
    foreach ($header as $field) {
	  $sheet->write($row,$col++,$field, $bold);
    }
  }
  $xls->close();
}

private static function _html() {
  $url = self::_url_folder();
  $path = modify::getpath($GLOBALS["sel_folder"]["id"]);
  $head = "<html><head>
  <title>Simple Groupware &amp; CMS - ".$path."</title>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
  <link rel='alternate' type='application/atom+xml' title='Atom-Feed' href='".$url."&export=rss'>
  <meta name='generator' content='Simple Groupware 0.743' />
  <style>
	h2, pre, img, p, div {margin:0px; padding:0px; border:0px; border-spacing:0px;}
	body,table {font-family: Arial; font-size: 12px; width:99%;}
	table {border-top:1px solid #666; border-left:1px solid #666;}
	td {border-right:1px solid #666; border-bottom:1px solid #666; vertical-align:top;}
	a {text-decoration:none;}
	a:hover {text-decoration:underline;}
	.show .hide_field {visibility:visible; text-decoration:none;}
	.hide .hide_field {visibility:hidden;}
  </style>
  <script>
	function hide_column(id) {
	  var objs = document.getElementsByClassName('col'+id);
	  for (var i=0; i<objs.length; i++) objs[i].style.display='none';
	}
  </script>
  </head>
  <body class='hide'>
";
  $output = "<a href='".$url."' target='_blank'>".$path."</a><br/><br/>";
  $output .= "<table cellpadding='4' cellspacing='0' class='sgs_table'>";
  $data = self::_build_data(true);
  if (count($data)>0) {
    $output .= "<tr>";
	$i = 0;
    foreach ($data[0] as $field) {
	  if (empty($field["name"])) continue;
	  $style = $field["width"] ? "width:".$field["width"] : "";
	  $output .= "<td class='col".$i."' onmouseover='document.body.className=\"show\";' onmouseout='document.body.className=\"hide\";' style='".$style."' nowrap>";
	  $output .= "<b>".modify::htmlquote($field["name"])."</b>";
	  $output .= "&nbsp;<a title='{t}Hide{/t}' class='hide_field' href='javascript:hide_column(".$i.");'>&ndash;</a></td>";
	  $i++;
    }
    $output .= "</tr>";
    foreach ($data as $asset) {
	  $output .= "<tr>";
	  $i = 0;
	  foreach ($asset as $aval) {
	    if (!isset($aval["filter"])) continue;
		$data = self::_html_quote($aval["filter"], $aval["type"]);
		if (trim($data)=="") $data = "&nbsp;"; else $data = self::_html_link($data,$aval);
		$output .= "<td class='col".$i."'>".$data."</td>";
		$i++;
      }
	  $output .= "</tr>";
    }
  }
  return $head.$output."</table></body></html>";
}

private static function _html_vertical() {
  $url = self::_url_folder();
  $path = modify::getpath($GLOBALS["sel_folder"]["id"]);
  $output = "<html><head>
  <title>Simple Groupware &amp; CMS - ".$path."</title>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
  <link rel='alternate' type='application/atom+xml' title='Atom-Feed' href='".$url."&export=rss'>
  <meta name='generator' content='Simple Groupware 0.743' />
  <style>
	h2, pre, img, p, div {margin:0px; padding:0px; border:0px; border-spacing:0px;}
    body,table {font-family: Arial; font-size: 12px; width:99%;}
    table {border-top:1px solid #666; border-left:1px solid #666;}
    td {border-right:1px solid #666; border-bottom:1px solid #666; vertical-align:top;}
	a {text-decoration:none;}
	a:hover {text-decoration:underline;}
	.show .hide_field {visibility:visible; text-decoration:none;}
	.hide .hide_field {visibility:hidden;}
  </style>
  <script>
	function hide_column(id) {
	  var objs = document.getElementsByClassName('col'+id);
	  for (var i=0; i<objs.length; i++) objs[i].style.display='none';
	}
  </script>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
  </head>
  <body class='hide'>
  ";
  $data = self::_build_data(true);
  $output .= "<a href='".$url."' target='_blank'>".$path."</a><br/><br/>";
  if (count($data)>0) {
    foreach ($data as $asset) {
 	  $output .= "<table cellpadding='4' cellspacing='0' class='sgs_table'>";
	  $i=0;
	  foreach ($asset as $aval) {
	    if (!isset($aval["filter"]) or $aval["filter"]=="") continue;
  	    $output .= "<tr class='col".$i."'>";
        $output .= "<td style='width:20%;' onmouseover='document.body.className=\"show\";' onmouseout='document.body.className=\"hide\";'>";
		$output .= "<b>".modify::htmlquote($aval["name"])."</b>";
		$output .= "&nbsp;<a title='{t}Hide{/t}' class='hide_field' href='javascript:hide_column(".$i.");'>&ndash;</a></td>";
		$data = self::_html_quote($aval["filter"], $aval["type"]);
		if (trim($data)=="") $data = "&nbsp;"; else $data = self::_html_link($data,$aval);
	    $output .= "<td>".$data."</td>";
	    $output .= "</tr>";
		$i++;
      }
	  $output .= "</table><br>";
	}
  }
  return $output."</body></html>";
}

private static function _csv() {
  $output = "";
  $data = self::_build_data(true);
  if (count($data)>0) {
    foreach ($data[0] as $field) {
	  if (empty($field["name"])) continue;
      $output .= "\"".self::_csv_quote($field["field"])."\",";
    }
    $output = rtrim($output,",")."\r\n";
    foreach ($data as $asset) {
	  foreach ($asset as $aval) {
	    if (!isset($aval["filter"])) continue;
	    $output .= "\"".trim(self::_csv_quote($aval["filter"]))."\",";
	  }
	  $output = rtrim($output,",")."\r\n";
	}
  }
  return $output;
}

private static function _sss() {
  $data = self::_build_data(true);
  $output = "dbCells = [\n";
  if (count($data)>0) {
    $row = -1;
    $col = 0;
    foreach ($data[0] as $field) {
	  if (empty($field["name"])) continue;
	  $output .= "  [".$col.",".$row.",\"".trim(self::_sss_quote($field["name"]))."\",\"white-space:nowrap;\",\"".trim(self::_sss_quote($field["field"]))."\"], // ".chr($row+65).($col+1)."\n";
	  $col++;
    }
    $output .= "\n";
	$row = 0;
    foreach ($data as $asset) {
	  $col = -1;
	  foreach ($asset as $aval) {
	    if (empty($aval["name"])) continue;
		$col++;
	    if (!isset($aval["filter"]) or trim($aval["filter"])=="") continue;
	    $output .= "  [".$col.",".$row.",\"".trim(self::_sss_quote($aval["filter"]))."\",\"\"], // ".chr($row+65).($col+1)."\n";
	  }
	  $output .= "\n";
	  $row++;
	}
  }
  return $output."];";
}  

private static function _sss_editor($output) {
  echo '  
	<html>
	<head>
	<title>Simple Spreadsheet</title>
	<link media="all" href="ext/lib/simple_spreadsheet/styles.css" rel="stylesheet" type="text/css" />
    <script src="ext/lib/simple_spreadsheet/translations/{t}en{/t}.js" type="text/javascript"></script>
	<script src="ext/lib/simple_spreadsheet/spreadsheet.js" type="text/javascript"></script>
	</head>
	<body>
	<div id="data" class="data"></div>
	<div id="source">
	<textarea id="code" wrap="off">'.modify::htmlquote($output).'</textarea>
	<input type="button" value="Load" onclick="load();">
	<script>var init_data=getObj("code").value; load();</script>
	</div>
	</body>
	</html>  
  ';
}

private static function _build_filename() {
  $t = $GLOBALS["t"];
  return "data-".$t["title"]."-".$t["folder"]."-".$t["view"]."-".$_SESSION["username"]."-".str_replace("/","_",sys_date("{t}m/d/Y{/t}"));
}

private static function _csv_quote($str) {
  return str_replace(array("\"","\r\n","\n"),array("\"\""," "," "),$str);
}

private static function _html_quote($value, $type, $images=true) {
  if ($type=="textarea" or (is_call_type($type) and call_type($type, "export_as_text"))) {
	return modify::nl2br(modify::htmlquote($value));
  }
  if (is_call_type($type) and call_type($type, "export_as_html")) {
	if (!$images) return preg_replace("|<img[^>]*?>|si","",modify::htmlfield($value));
	return modify::htmlfield($value);
  }
  return modify::htmlquote($value);
}

private static function _sss_quote($str) {
  return str_replace(array("\"","\r\n","\n","\r"),array("\\\""," "," ",""),$str);
}

private static function _url() {
  return "http".(sys_https()?"s":"")."://".$_SERVER["HTTP_HOST"].dirname($_SERVER["SCRIPT_NAME"])."/index.php?";
}

private static function _url_folder() {
  $t = $GLOBALS["t"];
  return self::_url()."folder=".rawurlencode($t["folder"])."&view=".$t["view"];
}

private static function _url_base() {
  return "http".(sys_https()?"s":"")."://".$_SERVER["HTTP_HOST"].dirname($_SERVER["SCRIPT_NAME"])."/";
}

private static function _build_fields() {
  $t = $GLOBALS["t"];
  $fields = array();
  foreach ($t["fields"] as $value) {
	if (isset($value["HIDDENIN"][$t["view"]]) or isset($value["HIDDENIN"]["all"])) continue;
	if ($value["SIMPLE_TYPE"]=="password") continue;
	$fields[] = !empty($value["DISPLAYNAME"])?$value["DISPLAYNAME"]:$value["NAME"];
  }
  return $fields;
}

private static function _build_data($html,$data_arr=array(),$xml=false,$rss=false,$hidden=false) {
  $t = $GLOBALS["t"];
  $data = array();
  $i = 0;
  if (count($data_arr)==0) $data_arr = $t["data"]; else $data_arr = array($data_arr);
  
  foreach ($data_arr as $asset) {
    if (!isset($asset["_id"])) continue;
	if (!in_array("id",$t["hidden_fields"]) and is_numeric($asset["_id"])) {
	  $data[$i]["id"] = array("name"=>"Id", "displayname"=>sys_remove_trans("{t}Id{/t}"),"type"=>"","field"=>"id","filter"=>$asset["_id"],"width"=>"");
	}
	foreach ($t["fields"] as $akey=>$value) {
	  if ((isset($value["HIDDENIN"][$t["view"]]) or isset($value["HIDDENIN"]["all"])) and !$hidden) continue;
	  if ($value["SIMPLE_TYPE"]=="password") continue;
 	  $aval = $asset[$akey];
	  if (!is_array($aval)) {
	    $aval_data = explode("|",trim($aval,"|"));
		$aval = array("name"=>"","type"=>"","data"=>$aval_data,"filter"=>$aval_data);
	  }
	  if ($value["SIMPLE_TYPE"]=="checkbox") {
		if ($aval["data"][0]=="1") $aval["filter"][0] = "{t}yes{/t}"; else $aval["filter"][0] = "";
	  }
	  if ($value["SIMPLE_TYPE"]=="date") $aval["filter"][0] = modify::dateformat($aval["data"][0],"{t}m/d/Y{/t}");
	  if ($value["SIMPLE_TYPE"]=="datetime") $aval["filter"][0] = modify::dateformat($aval["data"][0],"{t}m/d/Y g:i a{/t}");
	  $filter = "";
	  if (isset($aval["filter"]) and is_array($aval["filter"]) and isset($aval["filter"][0])) {
	    if ($xml and !$rss and sgsml::type_is_multiple($value["SIMPLE_TYPE"]) and (empty($value["SIMPLE_SIZE"]) or $value["SIMPLE_SIZE"]!="1"))  {
	      $filter = "|".implode("|",$aval["data"])."|";
	    } else if ($xml and !$rss) {
	      $filter = implode("|",$aval["data"]);
		} else {
	      $filter = implode(" ",$aval["filter"]);
		}
	  } else if (!is_array($aval["filter"]) and $aval!="") {
		$filter = $aval;
	  } else {
		if ($html) $filter = " ";
	  }
	  if (!empty($value["DISPLAYNAME"])) {
	    $name = $value["DISPLAYNAME"];
		$displayname = $value["DISPLAYNAME"];
	  } else {
	    $name = $value["NAME"];
	    $displayname = $value["NAME"];
	  }
	  if (!isset($value["WIDTH"])) $value["WIDTH"] = "";
	  if (count($aval["data"])<2) $aval["data"] = implode(" ",$aval["data"]);

	  $linktext = "";
	  if (!empty($value["LINKTEXT"][0]["VALUE"][1]) and strpos($value["LINKTEXT"][0]["VALUE"][1],"ext/norefer.php")!==false) {
		$linktext = modify::link($value["LINKTEXT"][0]["VALUE"][1],$asset,0,"folder2=".rawurlencode($t["folder"])."&view2=".rawurlencode($t["view"]));
		$linktext = str_replace("&iframe=1","",$linktext);
	  }
	  $link = "";
	  if (!empty($value["LINK"][0]["VALUE"][1]) and strpos($value["LINK"][0]["VALUE"][1],"ext/norefer.php")!==false) {
		$link = modify::link($value["LINK"][0]["VALUE"][1],$asset,0,"folder2=".rawurlencode($t["folder"])."&view2=".rawurlencode($t["view"]));
		$link = str_replace("&iframe=1","",$link);
	  }
	  $data[$i][$akey] = array("name"=>$name,"displayname"=>$displayname,"field"=>$value["NAME"],"data"=>$aval["data"],
		"filter"=>$filter,"type"=>$value["SIMPLE_TYPE"],"width"=>$value["WIDTH"],"linktext"=>$linktext,"link"=>$link);
	}
	if (!isset($asset["created"])) $asset["created"] = 0;
	if (!isset($asset["lastmodified"])) $asset["lastmodified"] = 0;
	if (is_array($asset["created"])) $asset["lastmodified"] = $asset["created"]["data"][0];
	if (is_array($asset["lastmodified"])) $asset["lastmodified"] = $asset["lastmodified"]["data"][0];
	if (empty($data[$i]["created"])) $data[$i]["created"] = array("name"=>"","type"=>"","data"=>$asset["created"]);
	if (empty($data[$i]["lastmodified"])) $data[$i]["lastmodified"] = array("name"=>"","type"=>"","data"=>$asset["lastmodified"]);
	if (!empty($asset["lastmodifiedby"]) and !isset($data[$i]["lastmodifiedby"]) and $html) {
	  if (!in_array("lastmodified",$t["hidden_fields"])) {
	    $data[$i]["lastmodified"] = array("name"=>"lastmodified", "displayname"=>sys_remove_trans("{t}Modified{/t}"),"type"=>"","field"=>"lastmodified","filter"=>sys_date("{t}m/d/Y{/t}", $asset["lastmodified"]),"width"=>"");
	  }
	  if (!in_array("lastmodifiedby",$t["hidden_fields"])) {
	    $data[$i]["lastmodifiedby"] = array("name"=>"lastmodifiedby", "displayname"=>sys_remove_trans("{t}Modified by{/t}"),"type"=>"","field"=>"lastmodifiedby","filter"=>$asset["lastmodifiedby"],"width"=>"");
	  }
	}
	$data[$i]["_id"] = array("name"=>"","type"=>"","data"=>$asset["_id"]);
	$i++;
  }
  return $data;
}

private static function _date($time) {
  return sys_date("Y-m-d\\TH:i:s",$time)."+00:00";
}

private static function _icalendar_quote($str) {
  return wordwrap(str_replace(array("\r","\n"),array("","\\n"),trim($str)), 75, " \n ",true);
}

}