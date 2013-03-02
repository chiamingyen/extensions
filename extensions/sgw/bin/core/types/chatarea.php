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

class type_chatarea extends type_default {

static function render_value() {
  $id = uniqid();
  $smarty = func_get_arg(3);
  $roomname = modify::htmlquote($smarty->data_item["roomname"]["data"][0]);

  static $init = false;
  if ($init === false) $init = <<<EOT
	<script>
		function chat_archive(id,room) {
		  set_html(getObj("room_"+id), "");
		  if (getObj("archive_"+id).checked) getObj("last_"+id).value = 0;
		  chat_load(id,room,false);
		}
		function chat_load(id,room,reload) {
		  var obj = getObj("room_"+id);
		  var last = getObj("last_"+id).value;
		  var archive = (getObj("archive_"+id).checked?1:0);
		  ajax("chat_load", [archive, tfolder, last, room], function(data) {
			if (data.length > 0) {
			  getObj("last_"+id).value = data[data.length-1].id;
			  var result = "";
			  for (var i=0; i < data.length; i++) {
				var message = html_escape(data[i].message);
				var createdby = html_escape(data[i].createdby);
				result += "<div><font style='color:#"+data[i].bgcolor+";'>"+createdby+":</font> "+message+"</div>";
			  }
			  var elem = document.createElement("div");
			  elem.innerHTML = result;
			  obj.appendChild(elem);
			  obj.scrollTop = obj.scrollHeight + obj.offsetHeight + elem.offsetHeight;
			} else if (obj.innerHTML == "") {
			  obj.innerHTML = remove_trans("Ok...");
			}
			if (reload) setTimeout(function(){ chat_load(id,room,true); },2500);
		  });
		}
		function chat_add(id,room) {
		  var obj = getObj("room_"+id);
		  var message = getObj("input_"+id).value;
		  getObj("input_"+id).value = "";
		  if (message == "") return;
		  ajax("chat_add", [tfolder, room, message], function(data) {
			if (data=="") chat_load(id,room,false);
		  });
		}
	</script>
	<input type="hidden" id="last_{$id}" value="0">
EOT;

  $output = $init.<<<EOT
	  <input type="button" onclick="set_html('room_{$id}','');" value="Clear">&nbsp;
	  <input type="Checkbox" class="checkbox" id="archive_{$id}" onclick="chat_archive('{$id}','{$roomname}');" style="margin:0px;"> Show archive<br>
      <div class="chat" id="room_{$id}"></div>
	  <script>chat_load('{$id}','{$roomname}',true);</script>
EOT;
  if ($smarty->t["rights"]["write"]) {
	$output .= <<<EOT
	  <input type="text" style="width:100%;" id="input_{$id}" onkeypress="if (getmykey(event)==13) chat_add('{$id}','{$roomname}');">
EOT;
  }  
  $init = "";
  return $output;
}

}