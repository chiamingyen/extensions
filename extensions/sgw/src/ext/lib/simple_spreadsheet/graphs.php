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
  if (is_dir("artichow")) {
    define("INCLUDE_PATH","artichow");
  } else define("INCLUDE_PATH","../../../lib/artichow");
  
  $cid = sha1(serialize($_GET).filemtime(__FILE__)).".png";
  
  $cache_dir = "cache/";
  if (@is_dir("../../../../simple_cache/artichow/")) $cache_dir = "../../../../simple_cache/artichow/";
  
  if (file_exists($cache_dir.$cid) and filesize($cache_dir.$cid)>0) {
    if ($cache_dir=="cache/") {
	  header("Location: ".$cache_dir.$cid);
	} else {
      header("Content-Type: image/png; charset=utf-8");
      readfile($cache_dir.$cid);
	}
	exit;
  }
  
  if (ini_get("magic_quotes_gpc")!==false and get_magic_quotes_gpc()) modify::stripslashes($_REQUEST);
  
  $data = array(0);
  if (!empty($_REQUEST["data"])) $data = explode(",",$_REQUEST["data"]);

  $data2 = array();
  if (!empty($_REQUEST["data2"])) $data2 = explode(",",$_REQUEST["data2"]);

  $data3 = array();
  if (!empty($_REQUEST["data3"])) $data3 = explode(",",$_REQUEST["data3"]);

  $keys = array(0);
  if (!empty($_REQUEST["keys"])) $keys = explode(",",$_REQUEST["keys"]);
  foreach ($keys as $key=>$val) $keys[$key] = strip_tags(str_replace("<br>","\n",$val));

  $height = 125;
  if (!empty($_REQUEST["height"]) and is_numeric($_REQUEST["height"])) $height = $_REQUEST["height"];
  
  $width = 300;
  if (!empty($_REQUEST["width"]) and is_numeric($_REQUEST["width"])) $width = $_REQUEST["width"];
  
  $title = "";
  if (isset($_REQUEST["title"])) $title = $_REQUEST["title"];
  
  $type = "bar";
  if (!empty($_REQUEST["type"])) $type = $_REQUEST["type"];

  if ($type=="bar" or $type=="baraccumulate") {
	require(INCLUDE_PATH."/BarPlot.class.php");
  
	$graph = new Graph($width, $height);
	$graph->title->set($title);
	$graph->title->setFont(new Tuffy(11));

	$group = new PlotGroup;
	$group->setSpace(2, 2);
	$group->setPadding(25, 15, 27, 20);
	$group->grid->setType(LINE_DASHED);
	$group->grid->hideVertical(TRUE);

	if (count($data2)>0) {
	  if ($type=="baraccumulate") {
	    $data3 = array();
	    foreach ($data2 as $key=>$val) $data3[$key] = $data2[$key] + (isset($data[$key])?$data[$key]:0);
	    $plot = new BarPlot($data3, 1, 1);
		$plot->setBarColor(new Color(255,187,0,20));
		$plot->setBarPadding(0.15, 0.15);
		$plot->label->set($data2);
		$plot->label->move(0, -5);
		$group->add($plot);
	  }
	}
	if (count($data2)>0) {
	  if ($type=="baraccumulate") {
	    $plot = new BarPlot($data, 1, 1);
		$plot->setBarColor(new Color(173,216,230));
	  } else {
	    $plot = new BarPlot($data, 1, 2);
		$plot->setBarColor(new Color(173,216,230,30));
	  }
	} else {
	  $plot = new BarPlot($data, 1, 1,0);
	  $plot->setBarColor(new Color(173,216,230,30));
	}
	$plot->setBarPadding(0.15, 0.15);
	$plot->label->set($data);
	$plot->label->move(0, -5);
	if (!empty($_REQUEST["xtitle"]) or !empty($_REQUEST["ytitle"])) {
	  $group->setPadding(35, 15, 27, 27);	
	  if (!empty($_REQUEST["xtitle"])) $group->axis->bottom->title->set($_REQUEST["xtitle"]);
	  if (!empty($_REQUEST["ytitle"])) $group->axis->left->title->set($_REQUEST["ytitle"]);
	  $group->axis->bottom->title->move(6, -6);
	  $group->axis->bottom->setTitleAlignment(LABEL_RIGHT);
	  $group->axis->left->title->move(-2, -4);
	  $group->axis->left->setTitleAlignment(LABEL_TOP);
	}
	$group->add($plot);
	$group->axis->bottom->setLabelText($keys);
	$group->axis->bottom->hideTicks(TRUE);

	if (count($data2)>0 and $type!="baraccumulate") {
	  $plot = new BarPlot($data2, 2, 2);
	  $plot->setBarColor(new Color(255,187,0,20));
	  $plot->setBarPadding(0.15, 0.15);
	  $plot->label->set($data2);
	  $plot->label->move(0, -5);
	  $group->add($plot);
	}
	$graph->add($group);
  } else if ($type=="pie") {
	require(INCLUDE_PATH."/Pie.class.php");

	$graph = new Graph($width, $height);
	$graph->title->set($title);
	$graph->title->setFont(new Tuffy(11));

	$colors = array(new Color(102,205,0), new Color(122,197,205), new Color(238,197,145), new Color(238,180,34), new LightOrange, new LightPurple, new LightBlue, new LightRed, new LightPink);
	$plot = new Pie($data,$colors);
	$plot->setCenter(0.40, 0.55);
	$plot->setSize(0.65, 0.7);
	$plot->set3D(10);
	$plot->setLabelPosition(10);
	$plot->setLegend($keys);

	$plot->legend->setPosition(1.37);
	$plot->legend->shadow->setSize(0);
	$plot->legend->setBackgroundColor(new Color(235,235,235));
	$graph->add($plot);
  } else if ($type=="line" or $type=="linesteps") {
	require(INCLUDE_PATH."/LinePlot.class.php");

	$graph = new Graph($width,$height);
	$graph->title->set($title);
	$graph->title->setFont(new Tuffy(11));

	$group = new PlotGroup;
	$group->setSpace(1, 0);
	$group->setPadding(25, 15, 27, 20);
	$group->grid->setType(LINE_DASHED);
	$group->grid->hideVertical(TRUE);

	if ($type=="linesteps") {
	  list($data_new,$data_label,$keys_new) = build_line_steps($width,$data,$keys);
	} else {
 	  $data_new = $data;
	  $data_label = $data;
	  $keys_new = $keys;
	}
	$plot = new LinePlot($data_new);
	$plot->setColor(new Color(0,0,255));
	$plot->setFillColor(new LightBlue(40));
	$plot->label->set($data_label);
	$plot->label->move(5,-7);

	if (!empty($_REQUEST["xtitle"]) or !empty($_REQUEST["ytitle"])) {
	  $group->setPadding(35, 15, 27, 27);	
	  if (!empty($_REQUEST["xtitle"])) $group->axis->bottom->title->set($_REQUEST["xtitle"]);
	  if (!empty($_REQUEST["ytitle"])) $group->axis->left->title->set($_REQUEST["ytitle"]);
	  $group->axis->bottom->title->move(4, -6);
	  $group->axis->bottom->setTitleAlignment(LABEL_RIGHT);
	  $group->axis->left->title->move(-2, -4);
	  $group->axis->left->setTitleAlignment(LABEL_TOP);
	}
	$plot->setSpace(4, 4, 10, 0);
	$plot->setPadding(40, 15, 10, 40);
	$plot->yAxis->title->move(-4, 0);
	$plot->yAxis->setTitleAlignment(LABEL_TOP);

	$group->add($plot);
	$group->axis->bottom->setLabelText($keys_new);
	$group->axis->bottom->hideTicks(TRUE);

	if (count($data2)>0) {
	  if ($type=="linesteps") {
	    list($data_new,$data_label,$keys_new) = build_line_steps($width,$data2,$keys);
	  } else {
 	    $data_new = $data2;
	    $data_label = $data2;
	  }
	  $plot = new LinePlot($data_new);
	  $plot->label->set($data_label);
	  $plot->label->move(5,-7);
	  $plot->setColor(new Color(255,165,0));
	  $plot->setFillColor(new LightOrange(80));
	  $group->add($plot);
	}
	$graph->add($group);
  } else if ($type=="scatter") {
	require(INCLUDE_PATH."/ScatterPlot.class.php");
	$graph = new Graph($width,$height);
	$graph->title->set($title);
	$graph->title->setFont(new Tuffy(11));
	
	$plot = new ScatterPlot($data, $keys);
	$plot->grid->setType(LINE_DASHED);
	$plot->grid->hideVertical(TRUE);
	$plot->setSpace(6, 6, 6, 0);
	$plot->setPadding(25, 15, 27, 20);
	$graph->add($plot);
  }

  $graph->draw(str_replace("\\","/",realpath($cache_dir))."/".$cid);
  if ($cache_dir=="cache/") {
	header("Location: ".$cache_dir.$cid);
  } else {
    header("Content-Type: image/png; charset=utf-8");
    readfile($cache_dir.$cid);
  }

  
function build_line_steps($width,$data,$keys) {
  $width /= 2;
  $data_new = $data;
  $data_label = $data;
  $keys_new = $keys;
  $last = -1;
  for ($i=0; $i < $width; $i++) {
    $curr = floor((count($data)-1)*(($i+1)/$width));
	$data_label[$i] = "";
	$keys_new[$i] = "";
	if ($curr != $last) {
	  $keys_new[$i] = $keys[$curr];
	  $data_label[$i] = $data[$curr];
	}
    $data_new[$i] = $data[$curr];
	$last = $curr;
  }
  return array($data_new,$data_label,$keys_new);
}

function modify_stripslashes(&$val) {
  if (is_array($val)) array_walk($val,array("modify","stripslashes")); else $val = stripslashes($val);
} 
?>
