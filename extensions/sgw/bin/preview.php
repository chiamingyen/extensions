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

define("NOCONTENT",true);
require("index.php");
if (empty($_SESSION["folder"])) exit;

if (!empty($_REQUEST["filename"])) {
  $filename = SIMPLE_CACHE."/thumbs/".basename($_REQUEST["filename"]);
  if (!file_exists($filename)) sys_error("file not found.");
  $modified = filemtime($filename);
  $etag = '"'.md5($filename.$modified).'"';
  header("Last-Modified: ".gmdate("D, d M Y H:i:s", $modified)." GMT");
  header("ETag: $etag");
  if (!empty($_SERVER["HTTP_IF_NONE_MATCH"]) and $etag == stripslashes($_SERVER["HTTP_IF_NONE_MATCH"]) and !DEBUG) {
    header("HTTP/1.0 304 Not Modified");
    exit;
  }
  header("Content-Type: image/png");
  header("Expires: ".gmdate("D, d M Y H:i:s", NOW)." GMT");
  header("Content-Length: ".(int)filesize($filename));
  readfile($filename);
}

if (!empty($_REQUEST["type"]) and $_REQUEST["type"]=="bar" and !empty($_REQUEST["stat"]) and !empty($_REQUEST["style"]) and !empty($_REQUEST["data"])) {
  $data = explode(",",$_REQUEST["data"]);
  $labels = explode(",",$_REQUEST["labels"]);
  if (!isset($_REQUEST["height"])) $_REQUEST["height"] = 200;
  if (!isset($_REQUEST["width"])) $_REQUEST["width"] = 600;

  require_once "lib/artichow/BarPlot.class.php";
  
  $smarty = new Smarty;
  $smarty->compile_dir = SIMPLE_CACHE."/smarty";
  $smarty->template_dir = "templates";
  $smarty->config_dir = "templates";
  $smarty->security = true;
  $smarty->config_load("core_css.conf",$_REQUEST["style"]);
  $vars_style = $smarty->_config[0]["vars"];

  $filename = SIMPLE_CACHE."/artichow/stats_".md5($_REQUEST["style"]."_".$_REQUEST["stat"]."_".$_REQUEST["data"]."_".$_REQUEST["labels"]."_".$_REQUEST["height"]."_".$_REQUEST["width"]).".png";
  if (!file_exists($filename)) {
    _stats_build_graph($data,$labels, $filename, $_REQUEST["stat"],$_REQUEST["width"],$_REQUEST["height"],$vars_style);
  }
  header("Content-Type: image/png");
  readfile($filename);
}

function _stats_color($col,$transp) {
  return new Color(hexdec(substr($col,1,2)),hexdec(substr($col,3,2)),hexdec(substr($col,5,2)),$transp);
}

function _stats_build_graph($data, $labels, $filename, $stat, $width, $height, $vars) {
  if (file_exists($filename)) unlink($filename);
  $data_orig = $data;
  foreach ($data as $key=>$val) if (!is_numeric($val)) $data[$key] = 0;

  // $vars["color_tab_black"]
  $bg_grey = _stats_color($vars["bg_grey"],0);
  $bg_light_blue = _stats_color($vars["bg_light_blue"],25);
  
  $graph = new Graph($width, $height);
  $group = new PlotGroup;
  $group->setSpace(2, 2);

  $group->grid->setType(LINE_DASHED);
  $group->grid->hideVertical(TRUE);

  $group->setPadding(30, 10, 25, 20);
  $graph->setBackgroundColor($bg_grey);

  $graph->title->set($stat);
  $graph->title->setFont(new Tuffy(10));

  $plot = new BarPlot($data, 1, 1, 0);
  $plot->setBarColor($bg_light_blue);
  $plot->label->set($data_orig);
  $plot->label->move(0, -5);
  
  $group->add($plot);
  $group->axis->bottom->setLabelText($labels);
  $group->axis->bottom->hideTicks(TRUE);
  $graph->add($group);
  $graph->draw($filename);
}