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

class type_wikiarea extends type_default {

static function form_render_value($name, $value) {
  static $init = false;
  if ($init === false) $init = <<<EOT
	<script>
		function wikiarea_preview(field) {
		  var obj = getObj("preview_"+field);
		  obj.style.display = "";
		  ajax("type_wikiarea::ajax_wikiarea_render_preview",[getObj(field).value],function(data){ set_html(obj,data); });
		}
	</script>
EOT;
  $output = $init.<<<EOT
	<div style="margin-bottom:1px;">
	  <input type="button" value="{t}Preview{/t}" onclick="wikiarea_preview('{$name}'); getObj('{$name}').focus();">&nbsp;
	  <input type="button" value="{t}Text formatting rules{/t}" onclick="nWin('../docs/ext/wiki/wiki.htm');">
	</div>
	<textarea name="{$name}" id="{$name}" style="width:100%; height:64px;">{$value}</textarea>
	<div class="wikibody">
	  <div id="preview_{$name}" style="display:none; padding:8px;"><img src="ext/images/loading.gif"/></div>
	</div>
EOT;
  $init = "";
  return $output;
}

static function render_value($value) {
  return modify::htmlfield(modify::htmlunquote($value));
}

static function render_page($str) {
  if ($str=="") return "";
  if (!class_exists("Text_Wiki",false)) require("lib/wiki/Wiki.php");
  $wiki = new Text_Wiki();
  $wiki->disableRule("Interwiki");
  $wiki->disableRule("Image");
  return str_replace("<p>","<p style='padding-bottom:16px;'>",$wiki->transform($str, "Xhtml"));
}

static function ajax_wikiarea_render_preview($data) {
  return sys_remove_trans("<b>{t}Preview{/t}:</b>")."<br/><br/>".modify::htmlfield(self::render_page($data));
}

static function export_as_html() {
  return true;
}

}