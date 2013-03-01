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

/**
 * default type class
 * e.g. simple_type="xy" => class type_xy extends type_default {}
 */
abstract class type_default {

/**
 * build content for history view
 *
 * @param string $old old content
 * @param string $new new content
 * @return string diff
 */
static function build_history($old, $new) {
  return asset::build_diff($old, $new);
}

/**
 * render input form in edit/new
 *
 * @param string $name field name (html quoted)
 * @param string $value value of the field (html quoted)
 * @param object $value smarty object, member "item" to read field attributes
 * @return string html input form
 */
static function form_render_value($name, $value, $smarty) {
  return <<<EOT
	<textarea name="{$name}" id="{$name}" style="width:100%;">{$value}</textarea>
EOT;
}

/**
 * render value on page (not used in export)
 *
 * @param string $value filtered value (filters defined in sgsML) (html quoted)
 * @param string $value_raw unfiltered value (html quoted)
 * @param boolean $preview whether a preview of the value is needed (e.g. thumbnail for images)
 * @param object $smarty smarty object, member "item" to read field attributes
 * @return string rendered value in html for page layout
 */
static function render_value($value, $value_raw, $preview, $smarty) {
  return $value;
}

/**
 * default output filter (also used in export)
 *
 * @param string $value filtered value (filters defined in sgsML)
 * @param string $params empty array, unused
 * @param boolean $row dataset array(data=>array(values exploded), filter=>array(values exploded, filtered))
 * @return string rendered value in html for page and export layout
 */
static function render_page($value, $params=array(), $row) {
  return $value;
}

/**
 * values are multiline texts?
 *
 * @return boolean true if multiline content
 */
static function export_as_text() {
  return false;
}

/**
 * values are HTML texts?
 *
 * @return boolean true if html content
 */
static function export_as_html() {
  return false;
}
}