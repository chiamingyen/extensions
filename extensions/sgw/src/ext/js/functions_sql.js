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

var token_id = 0;
var last_token = null;
var last_value = null;
var cache = [];

function start() {
  if (!obj("selectbox")) return;
  obj("codebox").onkeyup = obj("codebox").onclick = function() {
	set_sel_start(this);
	return keyup(this,obj("selectbox"),val("database"));
  }
  obj("codebox").onkeypress = function(event) {
	if (typeof(event)=="undefined") event = window.event;
	if (obj("selectbox").selectedIndex != -1 && event.keyCode==13 && !event.shiftKey) {
	  return false;
    }
  }
  obj("codebox").onkeydown = function(event) {
    set_sel_start(this);
    return keydown(event,this,obj("selectbox"));
  }
}

function set_sel_start(obj) {
  if (/MSIE/.test(navigator.userAgent)) { // IE
    var range = document.selection.createRange();
    var stored_range = range.duplicate();
    stored_range.moveToElementText(obj);
	stored_range.setEndPoint("EndToEnd", range);
	obj.selectionStart = stored_range.text.length - range.text.length;
  }
}

function cache_get(func, cache_id) {
  if (cache[func]==null) cache[func] = [];
  if (cache[func][cache_id]!=null) {
    return cache[func][cache_id];
  }
  return false;
}
function call(func, params, callback, params_callback) {
  var cache_id = params.join(",");
  if ((cached = cache_get(func, cache_id))) {
    callback(cached, params_callback);
    return;
  }
  var token = token_id;
  var xmlhttp = window.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
  xmlhttp.open("GET", "console.php?console=sql&func="+escape(func)+"&params="+params.join(","), true);
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
	  if (token != token_id) return; // request outdated
	  var result = xmlhttp.responseText;
	  try {
    	if (xmlhttp.status == 200 && result != "") {
		  result = JSON.parse(result);
		  cache[func][cache_id] = result;
		  callback(result, params_callback);
	    } else alert("{t}Error{/t}: "+func+" "+xmlhttp.status+" "+xmlhttp.statusText+" "+result);
	  } catch (e) {
	    if (result.length > 0) {
	      alert("{t}Error{/t} : "+e+" "+result+" "+func);
  } } } }
  xmlhttp.send(null);
}

function get_last_keyword(obj) {
  var keywords = {"select":"","from":"","where":"","order":"","update":"","into":"","set":""};
  var tokens = obj.value.substr(0,obj.selectionStart).split(" ");
  for (var i=tokens.length-1; i>=0; i--) {
    if (tokens[i].substr(-1)==";") return "";
    if (keywords[tokens[i]] != null) return tokens[i];
  }
  return "";
}

function get_last_token(obj) {
  var token = obj.value.substr(0,obj.selectionStart);
  var pos = token.lastIndexOfArr([","," ",";"]);
  if (pos!=-1) token = token.substr(pos+1);
  return token.trim();
}

function select_show(options, params) {
  if (options.length==0) return;
  var box = params[0];
  for (var i=0; i<options.length; i++) {
	box.options[box.options.length] = new Option(options[i][1],options[i][0]);
  }
  box.options[box.options.length] = new Option("","");
}
function select_hide(box) {
  box.innerHTML = "";
}

function select_insert(input,box) {
  if (box.selectedIndex==-1) return;
  var text = box.options[box.selectedIndex].value;
  var token = get_last_token(input);
  if (get_last_keyword(input) != token) {
    pos = text.indexOf(token);
    if (pos == 0) text = text.substr(token.length);
  }
  input.focus();
  if (!input.selectionEnd) {
    document.selection.createRange().text = text;
  } else {
    input.value = input.value.substr(0,input.selectionStart) + text + input.value.substr(input.selectionEnd);
    input.selectionStart += text.length;
    input.selectionEnd += text.length;
  }
}

function find_tables(obj,database) {
  var tables = null;
  var re = /(?:from|into|update)(.*?)(?:where|order|set|$)/;
  if ((m = re.exec(obj.value))) {
    tables = [];
    var items = m[1].split(",");
	for (var i=0; i<items.length; i++) {
	  items[i] = items[i].trim().split(" ");
	  if (items[i][1]==null) items[i][1] = items[i][0];
	  if (items[i][0].indexOf(".")==-1 && database!="") items[i][0] = database+"."+items[i][0];
	  tables[items[i][1]] = items[i][0];
	}
  }
  return tables;
}

function keydown(event,obj,box) {
  if (typeof(event)=="undefined") {
    event = window.event;
    keycode = window.event.keyCode;
  } else keycode = event.which;
	
  if (box.options.length > 0) {
    if (keycode==27) {
	  select_hide(box);
	  return false;
	}
	if (keycode==13 && !event.shiftKey) {
	  select_insert(obj,box);
	  return false;
	}
	if (keycode==40 && !event.shiftKey) { // cursor down
	  if (box.options.length-1 > box.selectedIndex) box.selectedIndex++;
	  // else select_hide(box);
	  return false;
	}
	if (keycode==38 && !event.shiftKey) { // cursor up
	  if (box.selectedIndex > -1) box.selectedIndex--; else select_hide(box);
	  return false;
	}
  }
}

function keyup(obj,box,database) {
  var keyword = get_last_keyword(obj);
  var token = get_last_token(obj);

  var prefix = "";
  if (token != keyword) prefix = token;
  if (obj.value != last_value) token_id++;

  if (token.indexOf("'")==-1 && last_token != keyword + token) {
    if (token=="") select_hide(box);
	if (keyword=="select" || keyword=="where" || keyword=="order" || keyword=="set") {
	  var tables = find_tables(obj,database);
	  var pos = token.indexOf(".");
	  if (pos != -1) {
	    var table = prefix.substr(0,pos);
		select_hide(box);
	    if (tables!=null && tables[table]!=null) {
		  prefix = tables[table] + prefix.substr(pos);
		  call("get_columns",[prefix,table+"."],select_show,[box]);
		} else {
		  call("get_columns",[database+"."+prefix,null],select_show,[box]);
		}
	  } else if (tables!=null) {
		var alias = "";
		var size = array_size(tables);
		select_hide(box);
		for (var i in tables) {
		  if (size>1) alias = i+".";
		  call("get_columns",[tables[i],alias],select_show,[box]);
		}
	  }
	}
	if (keyword=="from" || keyword=="into" || keyword=="update") {
	  select_hide(box);
	  if (prefix.indexOf(".")==-1) {
	    call("get_databases",[prefix],select_show,[box]);
	    if (database!="") call("get_tables",[database+"."+prefix,0],select_show,[box]);
	  } else {
	    call("get_tables",[prefix,1],select_show,[box]);
	  }
	}
  }
  last_value = obj.value;
  last_token = keyword + token;
}

function hide(id) {
  obj(id).style.display="none";
}
function show(id) {
  obj(id).style.display="";
}
function obj(id) {
  return document.getElementById(id);
}
function val(id) {
  return document.getElementById(id).value;
}
function array_size(arr) {
  var l = 0;
  for (var k in arr) l++;
  return l;
}
String.prototype.trim = function () {
  return this.replace(/\s*$/,"").replace(/^\s*/,"");
}
String.prototype.lastIndexOfArr = function (arr) {
  var pos = -1;
  for (var i=0; i<arr.length; i++) {
    var pos2 = this.lastIndexOf(arr[i]);
	if (pos2 > pos) pos = pos2;
  }
  return pos;
}
String.prototype.firstIndexOfArr = function (arr,offset) {
  var pos = -1;
  for (var i=0; i<arr.length; i++) {
    var pos2 = this.indexOf(arr[i],offset);
	if (pos2 != -1 && (pos == -1 || pos2 < pos)) pos = pos2;
  }
  return pos;
}

function resizeit() {
  var output = obj("output");
  if (output) {
	if (obj("code").style.display=="none") {
	  output.style.width = "100%";
	  return;
	}
	output.style.width = "60%";
  }
  var height = Math.min(500, document.body.clientHeight-obj("buttons").clientHeight-23);
  if (height<0) return;
  
  var codebox = obj("codebox");
  var selectbox = obj("selectbox");
  if (selectbox) {
	selectbox.style.height = Math.floor(height*0.4) + "px";
	codebox.style.height = (height*0.6) + "px";
  } else {
	codebox.style.height = height + "px";
  }
  codebox.focus();
}