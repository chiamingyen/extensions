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
  

  $obj = htmlspecialchars($_REQUEST["obj"], ENT_QUOTES);
  echo "
    <html>
  	<head>
	  <title>Simple Groupware Icons</title>
	  <script>
	  	function select(value) {
		  opener.set_val('{$obj}',value);
		  window.close();
		}
	  </script>
	  <style>
	  	a { text-decoration: none; }
		img { border:0px; }
		td { padding-right:20px; }
	  </style>
	</head>
  	<body>
	  <h3>Simple Groupware Icons</h3>
	  <table><tr>
  ";
  $i=0;
  $path = "./";
  $dir = opendir($path);
  while (($file=readdir($dir))) {
    if ($file[0]=="." or is_dir($path.$file) or strpos($file,".php")) continue;
	$i++;
	echo "<td><a href='#' onclick='select(\"{$file}\");'><img src='{$path}{$file}'><br/>{$file}</a></td>";
	if ($i%5==0) echo "</tr><tr><td colspan='5'><hr></td></tr><tr>";
  }
  closedir($dir);
  echo "
  	  </tr></table>
	  </body></html>
  ";