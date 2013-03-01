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

// TODO2 list:
/*
- store data on server and protect sheets with password
- bug: compose html, select text, mouseover value
- fix konqueror ?
- read-only / viewer example
- sgs-import-submit with post instead of get ?
- onunload ask for save ?
- import/export xls, opendocument ods ??
- row/column locking ? row/column types ? row/column styles ?
- disable editing headers ? editable area ?
- functions: lookups ?
- use unicode characters in charts ??
- cross workbook reference
- name/save spreadsheet in db
- concatenate sheets ?
- multiple sheets ?
- select boxes / checkboxes format:checkbox, format:selectbox
- undo, redo ?
*/
?>

<html>
<head>
<title>Simple Spreadsheet</title>
<style>
body, img, div, td {
  margin:0px;
  padding:0px;
  border:0px;
  border-spacing:0px;
  font-family: Arial, Helvetica, Verdana, sans-serif;
  color:#000000;
  font-size:25px;
  overflow:hidden;
}
a, a:visited {
  font-family: Arial, Helvetica, Verdana, sans-serif;
  color:#0000FF;
  font-size:25px;
}
#bg_full {
  position:absolute;
  left:0px;
  top:0px;
  width:100%;
  height:100%;
}
#table_obj {
  position:absolute;
  width:100%;
  left:0px;
  bottom:1%;
  opacity:0.8;
  filter:alpha(opacity=80);
}
.table {
  border:1px solid #AAAAAA;
  background-color:#FFFFFF;
  border-radius:10px;
  white-space:nowrap;
}
.table td {
  text-align:center;
  padding:75px;
  padding-top:0px;
  padding-bottom:0px;
}
</style>
</head>
<body>

<div class="bg_full"><img src="nature.jpg" style="width:100%; height:100%;"></div>

<div id="table_obj" align="center">
<table class="table"><tr><td>
Simple Spreadsheet<br>
<a href="spreadsheet.php?lang=en">English</a> - 
<a href="spreadsheet.php?lang=de">Deutsch</a> - 
<a href="spreadsheet.php?lang=it">Italiano</a> - 
<a href="spreadsheet.php?lang=es">Espa&ntilde;ol</a> - 
<a href="spreadsheet.php?lang=nl">Nederlands</a> - 
<a href="spreadsheet.php?lang=fr">Fran&cecedil;ais</a> - 
<a href="spreadsheet.php?lang=pl">Polski</a> - 
<a href="spreadsheet.php?lang=ptBR">Portuguese</a>&nbsp;
</td></tr></table>
</div>
</body>
</html>