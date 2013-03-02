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

class type_codearea extends type_default {

static function form_render_value($name, $value, $smarty) {
  if (!$smarty->sys["browser"]["comp"]["codeedit"]) {
    return <<<EOT
	  <textarea name="{$name}" style="width:100%; height:64px;">{$value}</textarea>
EOT;
  } else {
	$item_size = "300";
	if (isset($smarty->item["INPUT_HEIGHT"])) $item_size = $smarty->item["INPUT_HEIGHT"];
	
    return <<<EOT
      <div style="padding-bottom:2px;">
		<input type="button" value="+" onclick="resize_obj('{$name}_iframe',60);">&nbsp;
		<input type="button" value="&ndash;" onclick="resize_obj('{$name}_iframe',-60);"><br>
	  </div>
	  <input type="hidden" name="{$name}" id="{$name}" value="{$value}" onsubmit="getObj('{$name}_iframe').contentWindow.change();">
	  <iframe name="{$name}_iframe" id="{$name}_iframe" src="ext/lib/codepress/index.html" style="margin:0px; padding:0px; border:0px; width:100%; height:{$item_size}px;"></iframe>
EOT;
  }
}

static function render_value($value) {
  return "<div style='text-align:left;'>".modify::nl2br($value)."</div>";
}

static function export_as_text() {
  return true;
}

}