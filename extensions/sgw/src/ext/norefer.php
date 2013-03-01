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
  
  error_reporting(E_ALL);

  if (ini_get("magic_quotes_gpc")!==false and get_magic_quotes_gpc()) stripslashes($_SERVER["QUERY_STRING"]);
  $url = trim(str_replace(array("\n","\r","'","\"","<wbr/>"),"",urldecode(trim(substr($_SERVER["QUERY_STRING"],strpos($_SERVER["QUERY_STRING"],"=")+1)))));
  if (preg_match("/<([^>]+)>/",$url,$match)) $url = $match[1];
  $url = str_replace("ext/norefer.php?url=","",$url);

  if (preg_match("!^(https?|ftp)://!i",$url)) {
    header("Location: ".$url);
  } else if (strpos("@".$url,"index.php?")==1) {
    header("Location: ../".$url);
  } else if (strpos("@".$url,"www.")==1) {
    header("Location: http://".$url);
  } else if (preg_match("/([\S]*?@[\S]+|mailto:[\S]+)/",$url,$match)) {
    $url = str_replace(array("mailto:","(",")"),"",$match[1]);
	if (strpos(file_get_contents("../../simple_store/config.php"),"'ENABLE_EXT_MAILCLIENT',true")) {
      echo "<script>document.location='mailto:".$url."';window.close();</script>";
	} else {
	  $url = "../index.php?onecategory=1&find=folder|simple_sys_tree|1|ftype=emails&view=new&eto=".$url;
      header("Location: ".$url);
	}
  } else die("{t}Link restricted{/t}");