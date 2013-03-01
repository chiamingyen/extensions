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

class import extends Spreadsheet_Excel_Reader {

  private $_sgsml = null;
  private $_folder = 0;
  private $_validate_only = false;
  private $_errors = array();
  
  private $_fields = array();
  private $_headers = array();

  private $_data = array();
  private $_last_row = -1;
  private $_output_func = false;

  function __construct() {
    parent::Spreadsheet_Excel_Reader();
	$this->setOutputEncoding("UTF-8");
	$this->setRowColOffset(0);
  }

  function file($file, $folder, $output_func=false, $validate_only=false) {
	if (!file_exists($file) or filesize($file)==0) return array();

	$this->_sgsml = new sgsml($folder, "new", array(), true);
	$this->_folder = $folder;
	$this->_fields = array_flip(self::get_fields($this->_sgsml));
	$this->_validate_only = $validate_only;

	$this->_output_func = $output_func;
	$this->read($file);
	return $this->_errors;
  }

  function process_row($row, $line) {
	$data = array("folder"=>$this->_folder);
	if (DEBUG) print_r(array($line, $row));
	foreach ($row as $key=>$val) {
	  if (isset($this->_fields[$this->_headers[$key]])) $data[$this->_fields[$this->_headers[$key]]] = $val;
	}
	if (DEBUG) print_r($data);
	
	$id = !empty($data["id"]) ? $data["id"] : -1;
	if ($this->_validate_only) {
	  $result = $this->_sgsml->validate($data, $id);
	} else {
	  $result = $this->_sgsml->update($data, $id);
	}
	sys::$db_queries = array(); // reduce memory usage
	if (empty($result)) { // validate
	  $this->out(".");
	} else if (is_array($result)) {
	  $message = sprintf("{t}line{/t} %s: %s", $line, self::err_to_str($result));
	  $this->_errors[] = $message;
	  $this->out("<span style='color:red; font-weight:bold;'>{t}Error{/t}:</span> ".modify::htmlquote($message).", ");
	} else {
	  $this->out("#".$line.": ".modify::htmlquote($result).", ");
	}
  }

  function out($message) {
	if (empty($this->_output_func)) return;
	call_user_func($this->_output_func, $message, false);
  }
  
  function addcell($row, $col, $string) {
	if ($row!=$this->_last_row and $this->_last_row!=-1) {
	  if (empty($this->_headers)) {
		$this->_headers = $this->_data;
	  } else {
		$this->process_row($this->_data, $this->_last_row+1);
	  }
	  $this->_data = array();
	}
	if ($row<0) return;
	$this->_data[$col] = $string;
	$this->_last_row = $row;
  }

  function _parsesheet($spos) {
    parent::_parsesheet($spos);
	$this->addcell(-1, 0, "");
	$this->_headers = array();
	$this->_data = array();
	$this->_last_row = -1;
  }

  static function err_to_str($error) {
	if (empty($error) or !is_array($error)) return "";
	$result = array();
	foreach ($error as $field) {
	  foreach ($field as $error) $result[] = "{t}Column{/t} \"".$error[0]."\": ".$error[1];
	}
	return implode(", ", $result);
  }
  
  static function get_fields($sgsml) {
	$fields = array("id"=>sys_remove_trans("{t}Id{/t}"), "folder"=>sys_remove_trans("{t}Folder{/t}"));
	$view = $sgsml->view;
	foreach ($sgsml->current_fields as $name=>$field) {
	  if (isset($field["READONLYIN"][$view]) or isset($field["READONLYIN"]["all"])) continue;
	  if (!isset($field["EDITABLE"]) and (isset($field["HIDDENIN"][$view]) or isset($field["HIDDENIN"]["all"]))) continue;
	  $fields[$name] = !empty($field["DISPLAYNAME"])?$field["DISPLAYNAME"]:$name;
	}
	return $fields;
  }
}