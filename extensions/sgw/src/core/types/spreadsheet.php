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

class type_spreadsheet extends type_default {

static function form_render_value($name, $value, $smarty) {
  $item_size = "300";
  if (isset($smarty->item["INPUT_HEIGHT"])) $item_size = $smarty->item["INPUT_HEIGHT"];

  return <<<EOT
	<textarea name="{$name}" id="{$name}" class="spreadsheet" style="display:none;">{$value}</textarea>
	<div style="margin-bottom:2px; width:100%; height:{$item_size}px;">
	  <iframe id="{$name}_iframe" style="margin:0px; padding:0px; width:100%; height:100%;" src="ext/lib/simple_spreadsheet/spreadsheet.php?mode=editor&lang={t}en{/t}&data={$name}"></iframe>
	</div>
EOT;
}

static function render_value($value, $unused, $preview, $smarty) {
  $height = 350;
  if (isset($smarty->view["IMAGE_HEIGHT"])) $height = $smarty->view["IMAGE_HEIGHT"];
  
  $id = sha1($value);
  if ($preview) {
    return <<<EOT
	  <textarea id="{$id}" style="display:none;">{$value}</textarea>
	  <div style="margin:2px; width:100%; height:{$height}px;">
		<iframe style="margin:0px; padding:0px; width:99%; height:100%;" src="ext/lib/simple_spreadsheet/spreadsheet.php?mode=viewer&lang={t}en{/t}&data={$id}"></iframe>
	  </div>
EOT;
  }
  return modify::nl2br($value);
}

static function export_as_text() {
  return true;
}
}