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

class type_rating extends type_default {

static function form_render_value($name, $value, $smarty) {
  $max = $smarty->item["SIMPLE_SIZE"];
  if (!is_numeric($max)) $max = 5;
  if (!is_numeric($value)) $value = 0;

  static $init = false;
  if ($init === false) $init = <<<EOT
	<script>
	  function rating_set(name, value) {
		set_val(name, value);
		css("."+name, "display", "none");
		var max = getObjs("."+name).length/2;
		for (var i=0; i<=max; i++) show(name+i+ (i<=value ? "1" : "2"));
	  }
	</script>
EOT;

  $result = $init.<<<EOT
	<input type="hidden" name="{$name}" id="{$name}" value="{$value}">
EOT;
  $init = "";
  for ($i=1; $i<=$max; $i++) {
	$result .= "<a href='#' onclick='rating_set(\"{$name}\", {$i}); return false;'>".
			   "<img id='{$name}{$i}1' class='{$name}' src='ext/icons/star.png' />".
			   "<img id='{$name}{$i}2' class='{$name}' src='ext/icons/star2.png' /></a> ";
  }
  return $result . "<script>rating_set('{$name}', {$value});</script>";
;
}

static function render_value($value, $unused, $unused2, $smarty) {
  if (!is_numeric($value) and !empty($value)) return $value;
  
  $id = $smarty->id;
  $name = $smarty->field["NAME"];
  $max = $smarty->field["SIMPLE_SIZE"];
  if (!is_numeric($max)) $max = 5;
  
  if (!isset($smarty->t["views"]["edit"]) or $id=="") {
	return str_repeat("<img src='ext/icons/star.png'/>", $value);
  }
  $result = "<div onmouseover='show(\".r{$id}\");' onmouseout='hide(\".r{$id}\");' style='width:".($max*16)."px;'>";
  for ($i=1; $i<=$max; $i++) {
	$result .= "<a href='#' onclick='asset_update({{$name}:{$i}},\"{$id}\");'>";
	if ($i>$value) {
	  $result .= "<img src='ext/icons/star2.png' class='r{$id}' style='display:none;'/></a>";
	} else {
	  $result .= "<img src='ext/icons/star.png'/></a>";
	}
  }
  return $result . "</div>";
}
}