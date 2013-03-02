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

$params = array(
  "folder", "view", "fields_hidden", "filter", "groupby", "orderby", "limit", "title"
);

if (empty($_REQUEST["folder"]) and !empty($_REQUEST["folder2"])) $_REQUEST["folder"] = $_REQUEST["folder2"];
if (empty($_REQUEST["view"]) and !empty($_REQUEST["view2"])) $_REQUEST["view"] = $_REQUEST["view2"];

if (!empty($_REQUEST["items"])) {
  $_REQUEST["filter"] = "id|oneof|".$_REQUEST["items"];
}

$values = array();
foreach ($params as $param) {
  $values[$param] = "";
  if (!empty($_REQUEST[$param])) {
    $values[$param] = htmlspecialchars($_REQUEST[$param], ENT_QUOTES);
  }
}
?>
<html>
<head>
  <base target="_top"/>
  <title><?php echo $values["title"]; ?> - Simple Groupware</title>
  <link rel="stylesheet" href="css/ext-all.css" type="text/css" />
  <link rel="stylesheet" href="css/xtheme-gray.css" type="text/css" />
  <script type="text/javascript" src="ext-all.js"></script>
  <script type="text/javascript" src="ext-sgs.js?0_743"></script>
  <style>
  body { overflow:hidden; }
  a,a:visited {color:#666666;}
  </style>
</head>
<body>
  <noscript>Please enable Javascript in your browser.</noscript>
  <script>show_grid(<?php echo "'".implode("', '",$values)."'"; ?>);</script>
</body>
</html>