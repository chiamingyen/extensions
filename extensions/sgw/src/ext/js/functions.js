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

var hpane = 0;
var vpane = 0;
var pane = 0.52;
var pane2 = 0.27;
var tree_width = 235;
var tree_min = 180;
var tree_scroll_pos = 0;
var pane_timer = null;
var refresh_timer = null;
var screen_width = return_width();
var smenu = 0;
var menu = 0;
var mbuffer = "";
var smenu_buffer = "";
var mtrigger = null;
var mtimer = null;
var mleft = (typeof(css_conf) != "undefined" && css_conf.direction) ? "right" : "left";
var keys_timer = null;
var keys_hl = null;
var pending_request = 0;

if (top != window && top.getObj("login_iframe")) {
  top.getObj("login_iframe").src = "about:blank";
  top.hide("login");
  top.setTimeout( function(){
	top.show("login_reminder");
	top.set_val("password", "");
	top.getObj("password").focus();
  }, sys.session_time*1000);
  if (window.sys.username!=top.sys.username) top.location.href = top.location.href;
} else if (!iframe && sys.session_time && sys.username != "anonymous") {
  setTimeout( function() { clearTimeout(refresh_timer); show("login_reminder"); getObj("password").focus(); },sys.session_time*1000);
}

if (iframe && !popup && window==top.window && document.location.href.indexOf("&iframe=1")!=-1) {
  document.location.href = document.location.href.replace("&iframe=1","");
}

var re = /tree_scroll_pos=(.*?)&tree_width=(.*?)&pane=(.*?)&pane2=(.*?)~/;
if ((m = re.exec(document.cookie))) {
  tree_scroll_pos = m[1];
  tree_width = m[2];
  if (tree_width <= tree_min) tree_width = tree_min + 6;
  pane = m[3];
  pane2 = m[4];
} else {
  if (screen_width>1024) tree_width = 250;
}
window.onbeforeunload = function(){
  if (form_changed(getObj("asset_form"))) return "";
};
if (sys.is_mobile) window.onload = start;
else if (document.addEventListener) document.addEventListener( "DOMContentLoaded", start, false );
else {
  document.attachEvent("onreadystatechange", function(){
    if ( document.readyState != "complete" ) return;
	start();
  });
}

function getElementsByClassName(name) {
  var a = [];
  var elems = document.getElementsByTagName("*");
  var pattern = new RegExp("(^|\\s)"+name+"(\\s|$)");
  for (var i=0,j=elems.length; i<j; i++) {
    if (pattern.test(elems[i].className)) a.push(elems[i]);
  }
  return a;
}

function hide_layout() {
  if (sys.menu_autohide) {
	top_menu(false);
	getObj("main").onmouseover = function(event) {
	  if (is_nested_target(event, this)) return;
	  top_menu(false);
	}
	getObj("main").onmouseout = function(event) {
	  if (!event) event = window.event;
	  if (is_nested_target(event, this)) return;
	  if (event.clientY < 10 && event.clientX < 600) top_menu(true);
	}
  }
  if (sys.tree_autohide) {
    if (document.location.href.indexOf("&folder=")==-1) tree(false);
	getObj("content").onmouseover = function(event) {
	  if (is_nested_target(event, this)) return;
	  tree(false);
	}
	getObj("content").onmouseout = function(event) {
	  if (!event) event = window.event;
	  if (is_nested_target(event, this)) return;
	  if (event.clientX < 10 && event.clientX!=-1) tree(true);
} } }

function is_nested_target(event, obj) {
  if (!event) event = window.event;
  var target = event.target || event.srcElement;
  if (in_array(target.tagName, ["INPUT", "TEXTAREA", "LABEL", "A", "IMG"])) return true;
  if (!event.relatedTarget && event.fromElement) {
	event.relatedTarget = (event.type=="mouseover") ? event.fromElement : event.toElement;
  }
  var parent = event.relatedTarget;
  while (parent && parent !== obj) parent = parent.parentNode;
  if (parent === obj) return true;
  return false;
}

function tree(show) {
  if (!getObj("tree")) return;
  if (show) {
    tree_width = 235;
	show2("tree");
  } else {
	tree_width = -2;
	hide("tree");
  }
  resizeit();
}

function top_menu(show) {
  if (!getObj("menu")) return;
  if (show) {
	show2("menu");
	show2("sgslogo");
  } else {
	hide("menu");
	hide("sgslogo");
  }
  resizeit();
}

function drag_handler(event, params, text_image) {
  if (!event) event = window.event;
  if (!event.dataTransfer) return;

  var dt = event.dataTransfer;
  dt.effectAllowed = "copyMove";
  if (event.ctrlKey) dt.effectAllowed = "copy";
  dt.setData("Text", JSON.stringify(params));

  if (!dt.setDragImage) return;
  var elem = document.createElement("div");
  elem.style.fontSize = "40px";
  elem.style.backgroundColor = "#FFFFFF";
  elem.innerHTML = text_image;
  document.body.appendChild(elem);
  dt.setDragImage(elem, -10, 0);
}

function drop_tree(event) {
  if (!event) event = window.event;
  var mode = "cut";
  var dt = event.dataTransfer;
  if (dt.dropEffect == "copy" || dt.effectAllowed == "copy") mode = "copy";
  
  var params = dt.getData("Text");
  if (params=="") return;
  params = JSON.parse(params);

  var type = params["type"];
  if (type=="asset") {
    if (!rights.write_folder) mode = "copy";
    ajax("asset_ccp",[params["folder"], params["view"], params["items"], attr(this, "rel"), mode], locate_folder);
  }
  if (type=="folder" && sys_confirm("{t}Really apply the changes ?{/t}")) {
    ajax("folder_ccp",[params["items"], attr(this, "rel"), mode], locate_folder);
  }
}

function cancel(e) {
  if (e) e.stopPropagation(); else window.event.cancelBubble = true;
}

function keys(ev) {
  if (!ev) ev = window.event;
  if (!ev.altKey || ev.shiftKey || ev.ctrlKey) return;
  if (ev.keyCode > 47 && ev.keyCode < 58) {
	change_tab("tab", attr("accesskey"+(ev.keyCode-48), "rel"));
	return;
  }
  if (keys_timer!=null) clearTimeout(keys_timer);
  if (keys_hl!=null) css(keys_hl,"backgroundColor", "");

  if (ev.keyCode=="38" || ev.keyCode=="40" || ev.keyCode=="33" || ev.keyCode=="34") {
	var objs = getObjs(".drop_tree");
	for (var i=0; i<objs.length; i++) {
	  var find = keys_hl ? attr(keys_hl, "rel") : tfolder;
	  if (attr(objs[i], "rel")!=find) continue;
	  if (ev.keyCode=="38") offset = i-1;
		else if (ev.keyCode=="40") offset = i+1;
		else if (ev.keyCode=="33") offset = i-10;
		else offset = i+10;
	  if (offset<0) offset=0;
	  if (offset>=objs.length) offset = objs.length-1;
	  keys_hl = objs[offset];
	  if (!keys_hl) return;
	  css(keys_hl,"backgroundColor", css_conf.bg_red_over);
	  auto_scroll_tree(keys_hl);
	  keys_timer = setTimeout(function(){ locate_folder(escape(attr(keys_hl, "rel"))); }, 600);
	  break;
} } }

function start() {
  if (!sys.browser) return;
  if (!popup && !iframe && !preview && tree_visible) drawmenu();
  if (debug_js) eval(unescape(document.location.hash.substring(1)));
  if (sys.menu_autohide || sys.tree_autohide) hide_layout();
  var objs = document.getElementsByTagName("textarea");
  for (var i=0; i<objs.length; i++) {
    objs[i].onfocus = textarea_autosize;
	objs[i].onkeyup = textarea_autosize;
	objs[i].onfocus();
  }
  resizeit();
  setTimeout(refreshit, sys.folder_refresh*1000);

  objs = getObjs(".mover");
  for (var i=0; i<objs.length; i++) {
	if (!attr(objs[i],"rel")) continue;
	objs[i].onmouseover = function(event){
	  cancel(event);
	  css(".asset_"+attr(this,"rel"),"backgroundColor",css_conf.bg_green);
	};
	objs[i].onmouseout = function(){
	  var id = attr(this,"rel");
	  var obj = getObj("check_"+id);
	  if (obj && obj.checked) color = css_conf.bg_red; else color = "";
	  css(".asset_"+id,"backgroundColor",color);
	};
  }
  objs = getObjs(".mdown");
  for (var i=0; i<objs.length; i++) {
	if (!attr(objs[i],"rel")) continue;
	objs[i].onmousedown = function(event){
	  if (is_nested_target(event, this)) return;
	  var id = attr(this,"rel");
	  show2("view_buttons");
	  var obj = getObj("check_"+id);
	  if (obj) obj.checked = !obj.checked;
	  if (obj && obj.checked) color = css_conf.bg_red; else color = "";
	  css(".asset_"+id,"backgroundColor",color);
	};
	if (dblclick && !iframe) objs[i].ondblclick = function(){
	  locate("index.php?folder="+escape(tfolder)+"&view="+dblclick+"&item[]="+attr(this,"rel"));
	};
  }
  if (!preview && !iframe) {
	objs = getObjs(".hide_fields");
	for (var i=0; i<objs.length; i++) {
	  objs[i].onmouseover = function(event){
		css(this.getElementsByTagName("a"), "visibility", "visible");
	  };
	  objs[i].onmouseout = function(event){
		css(this.getElementsByTagName("a"), "visibility", "hidden");
	  };
	}
  }
  objs = getObjs(".drag_asset");
  for (var i=0; i<objs.length; i++) {
    objs[i].draggable = "true";
	objs[i].ondragstart = function(event){
	  var params = {"folder":tfolder, "view":tview, "type":"asset"};
	  params["items"] = assets_get_selected(true);
	  var item = attr(this,"rel");
	  if (params["items"].join(" ").indexOf(item)==-1) params["items"].push(item);
	  drag_handler(event, params, params["items"].join(", "));
	}
	objs[i].onmousedown = cancel;
  }
  document.onkeydown=keys;
  bind_drop_tree();
  bind_drop_files();
  auto_scroll_tree(tfolder);
  var objs = getObjs(".onload");
  for (var i=0; i<objs.length; i++) {
	objs[i].func = new Function("", attr(objs[i], "onload"));
	objs[i].func();
  }
}

function auto_scroll_tree(obj) {
  var tree_def = getObj("tree_def");
  if (!tree_def) return;

  var pos = findPosY(obj) - findPosY(tree_def) - 20;
  if (pos < tree_def.scrollTop) tree_def.scrollTop = pos;

  var pos = findPosY(obj) - return_height() + 40;
  if (pos > tree_def.scrollTop) tree_def.scrollTop = pos;
  save_cookie();  
}

function bind_drop_files() {
  // HTML5 drag and drop file upload
  if (!/Firefox|Chrome|Safari/.test(navigator.userAgent)) return;
  document.ondragover = document.ondrop = function(event){ event.preventDefault(); };
  show(".file_upload_text");
  var objs = getObjs(".file_upload");
  for (var i=0; i<objs.length; i++) {
	objs[i].ondragenter = function(){ css(this,"backgroundColor",css_conf.bg_red_over); };
	objs[i].ondragleave = function(event){
	  if (is_nested_target(event, this)) return;
	  css(this,"backgroundColor","");
	};
  }
}

function bind_drop_tree() {
  var objs = getObjs(".drop_tree");
  for (var i=0; i<objs.length; i++) {
    objs[i].draggable = "true";
	objs[i].ondragenter = function(){ auto_scroll_tree(this); css(this,"backgroundColor", css_conf.bg_red_over); return false; };
	objs[i].ondragleave = function(event){
	  if (is_nested_target(event, this)) return;
	  css(this,"backgroundColor", "");
	};
	objs[i].ondragover = function(){ return false; };
	objs[i].ondrop = drop_tree;
	objs[i].ondragstart = function(event){
	  drag_handler(event, {"type":"folder", "items":attr(this,"rel")}, this.innerHTML.replace(/<img.*?>/g,""));
	};
  }
}

function ______G_E_N_E_R_A_L______() {}

function print_r(value) {
  alert(JSON.stringify(value));
}

function getObj(id) {
  return document.getElementById(id);
}

function getObjs(val) {
  if (!val) return [];
  if (typeof(val)=="string") {
	if (val.charAt(0)==".") {
	  // IE
	  if (!document.getElementsByClassName) document.getElementsByClassName = getElementsByClassName;
	  objs = document.getElementsByClassName(val.substring(1));
	} else {
	  objs = [getObj(val)];
	}
  } else if (val.length) {
    objs = val;
  } else {
	objs = [val];
  }
  return objs;
}

function form_values(class_name) {
  var params = {};
  var objs = getObjs(class_name);
  if (objs.length==0 || !objs[0]) return params;
  for (var i=0; i<objs.length; i++) {
    if (!objs[i].id) continue;
	var id = objs[i].id;
	if (!params[id]) params[id] = [];
	params[id].push(val(objs[i]));
  }
  return params;
}

function form_changed(form) {
  if (!form) return false;
  for (var i=0; i < form.elements.length; i++) {
    var elem = form.elements[i];
    var type = elem.type;
	if (!elem.name) continue;
    if (type=="checkbox" || type=="radio") {
      if (elem.checked != elem.defaultChecked) return true;
    } else if (type=="hidden" || type=="password" || type=="text" || type=="textarea") {
      if (elem.value != elem.defaultValue) return true;
    } else if (type=="select-one") {
	  if (!elem.options[elem.selectedIndex].defaultSelected) return true;
    } else if (type=="select-multiple") {
      for (var j=0; j < elem.options.length; j++) {
        if (elem.options[j].selected != elem.options[j].defaultSelected) return true;
  } } }
  return false;
}

function val(obj) {
  if (typeof(obj)=="string") obj = getObj(obj);
  if (!obj) return "";
  if (obj.type=="checkbox" || obj.type=="radio") {
    if (obj.checked) return obj.value; return "";
  }
  if (obj.options) {
	if (!obj.multiple) {
	  if (obj.selectedIndex == -1) return "";
	  return obj.options[obj.selectedIndex].value;
	} else {
	  var arr = [];
	  for (var i=0; i<obj.options.length; i++) {
		arr.push(obj.options[i].value);
	  }
	  return arr.join("|");
	}
  }
  return obj.value;
}

function _selectbox_find(obj, key) {
  if (!obj.options || obj.options.length==0) return -1;
  for (var i=0; i<obj.options.length; i++) {
	if (obj.options[i].value == key) return i;
  }
  return -1;
}
  
function set_val(obj, val) {
  if (typeof(obj)=="string") obj = getObj(obj);
  if (!obj) return;
  if (obj.type=="checkbox" || obj.type=="radio") {
	obj.checked = val ? true : false;
  } else if (obj.options) {
	if (val=="") {
	  if (obj.multiple) obj.options.length = 0; else obj.selectedIndex = -1;
	  return;
	}
	if (obj.multiple && val.indexOf("|")!=-1) {
	  val = val.split("|");
	  for (var i=0; i<val.length; i++) set_val(obj, val[i]);
	} else {
	  var index = _selectbox_find(obj, val);
	  if (index == -1) {
		index = obj.options.length;
		obj.options[index] = new Option(val,val);
	  }
	  if (!obj.multiple) obj.options[index].selected = true;
	}
  } else obj.value = val;
}

function attr(obj,name) {
  if (typeof(obj)=="string") obj = getObj(obj);
  if (!obj) return "";
  return obj.getAttribute(name);
}
function css(obj, name, value, delay) {
  var objs = getObjs(obj);
  if (objs.length==0 || !objs[0]) return;
  for (var i=0; i<objs.length; i++) objs[i].style[name]=value;
  if (typeof(delay)!="undefined") setTimeout(function(){ css(obj, name, ''); },delay);
}
function set_attr(obj, name, value) {
  var objs = getObjs(obj);
  if (objs.length==0 || !objs[0]) return;
  for (var i=0; i<objs.length; i++) {
	if (name.indexOf("on")==0 || name=="value") {
	  objs[i][name] = value;
	} else {
	  objs[i].setAttribute(name, value);
} } }

function add_style(selector, style) {
  var objs = getObj("content").getElementsByTagName("iframe");
  if (objs.length > 0) {
    for (var i=0; i<objs.length; i++) {
	  if (!objs[i].contentWindow) continue;
	  var css = objs[i].contentWindow.document.styleSheets[0];
	  if (!css) continue;
	  css_conf.insertRule(selector + "{"+style+"}", 0);
	  objs[i].contentWindow.resizeit();
  } }
  document.styleSheets[0].insertRule(selector + "{"+style+"}", 0);
}

function _getSize(width) {
  var myWidth = 0, myHeight = 0;
  if (typeof(window.innerWidth) == "number") {
    myWidth = window.innerWidth;
    myHeight = window.innerHeight;
  } else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
    myWidth = document.body.clientWidth;
    myHeight = document.body.clientHeight;
  } else if (self.screen.height && self.screen.width) {
    myWidth = self.screen.width;
    myHeight = self.screen.height;
  }
  if (width) return myWidth; else return myHeight;
}

function return_height() {
  return _getSize(0);
}

function return_width() {
  return _getSize(1);
}

function getFirstParentByName(node,tag) {
  while (node.tagName && node.tagName.toLowerCase()!=tag) node = node.parentNode;
  return node;
}

function getmykey(ev) {
  if (!ev) return null;
  if (typeof(ev.which)=="undefined") return window.event.keyCode;
  return ev.which;
}

function findPosY(obj) {
  if (typeof(obj)=="string") obj = getObj(obj);
  var curtop = 0;
  if (obj.offsetParent) {
	while (obj.offsetParent) {
	  curtop += obj.offsetTop;
	  obj = obj.offsetParent;
	}
  } else if (obj.y) curtop += obj.y;
  return curtop;
}

function findPosX(obj) {
  if (typeof(obj)=="string") obj = getObj(obj);
  var curtop = 0;
  if (obj.offsetParent) {
	while (obj.offsetParent) {
	  curtop += obj.offsetLeft;
	  obj = obj.offsetParent;
	}
  } else if (obj.x) curtop += obj.x;
  return curtop;
}

function getRequestObject() {
  return (window.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest());
}

function show(obj) {
  css(obj,"display","inline");
}
function show2(obj) {
  css(obj,"display","");
}
function hide(obj) {
  css(obj,"display","none");
}
function showhide(obj) {
  if (typeof(obj)=="string") obj = getObj(obj);
  if (!obj) return false;
  if (obj.style.display=="none") {
    show2(obj);
	return true;
  } else hide(obj);
  return false;
}
function showhide_inline(obj) {
  if (typeof(obj)=="string") obj = getObj(obj);
  if (!obj) return false;
  if (obj.style.display=="none") {
    show(obj);
	return true;
  } else hide(obj);
  return false;
}
function check_bold(obj) {
  setTimeout(function(){ getFirstParentByName(obj,"tr").style.fontWeight="normal";},1000);
}

function doclick(field) {
  field = getObj(field);
  if (field.dispatchEvent) {
    var e = document.createEvent("MouseEvents");
    e.initEvent("click", true, true);
    field.dispatchEvent(e);
  } else field.click();
}

function showhide_tree(obj) {
  if (showhide(obj)) tree_scroll(-1); else tree_scroll(0);
  focus_form(obj);
}

function focus_form(obj) {
  if (typeof(obj)=="string") obj = getObj(obj);
  if (!obj) return;
  var objs = obj.getElementsByTagName("form");
  if (objs.length > 0) {
    var elems = objs[0].elements;
    for (var i=0; i<elems.length; i++) {
	  if (elems[i].type != "hidden" && elems[i].type != "checkbox" && elems[i].name != "") {
	    elems[i].focus();
		break;
} } } }

function set_html(obj,txt) {
  if (!obj) return;
  if (typeof(obj)=="string") obj = getObj(obj);
  obj.innerHTML = txt;
}
function append_html(obj,txt) {
  if (typeof(obj)=="string") obj = getObj(obj);
  obj.innerHTML += txt;
}

function remove_trans(str) {
  return str.replace(new RegExp("{t"+"}|{/t"+"}","g"), "");
}

function sys_alert(str) {
  str = remove_trans(str);
  var result = "";
  while (str.length > 0) {
    var pos = str.indexOf("\n");
	if (pos == -1 || pos > 120) pos = 120;
    result += str.substring(0, pos+1)+"\n";
	str = str.substring(pos+1);
  }
  alert(result);
}

function sys_confirm(str) {
  return confirm(remove_trans(str));
}

function html_escape(str) {
  return str.replace(/</g,"&lt;").replace(/>/g,"&gt;");
}

function open_window(address,name,width,height) {
  var _top = Math.floor((screen.height - height) / 2);
  var _left = Math.floor((screen.width - width) / 2);
  var loc = "no";
  var menu = "no";
  var scrollbars = "no";
  if (width>640) {
    loc = "yes";
	scrollbars = "yes";
  }
  var wname = name;
  if (window.name == wname) wname += Math.random();
  var my_win = window.open(address,wname.replace(".",""),"location="+loc+",scrollbars="+scrollbars+",resizable=yes,width="+width+",height="+height);
  try {
    my_win.moveTo(_left,_top);
	my_win.resizeTo(width,height);
  } catch (e) {} // ie7
  my_win.name = name;
  return my_win;
}

function locate(str) {
  if (str.indexOf("norefer.php")==-1 && iframe) str += "&iframe=1";
  if (popup) str += "&popup=1";
  document.location = str;
}
function locate_folder(folder) {
  if (pending_request>0) return;
  locate("index.php?folder="+folder); // unicode: no escape
}
function alert_ok(data) {
  sys_alert("{t}Item successfully created.{/t}");
}
function search() {
  var url = "index.php?folder="+escape(tfolder)+"&view=search&module="+val("search_module")+"&search="+val("search_query");
  var filters = [];
  var user = val("search_user");
  if (user) filters.push("lastmodifiedby|eq|"+user);
  var modified = val("search_modified_value");
  if (modified) filters.push("lastmodified|"+val("search_modified")+"|"+modified);
  if (filters.length>0) url += "&filters="+filters.join("||");
  var similar = getObj("search_similar");
  if (similar && similar.checked) url += "&similar=1";
  var subfolders = getObj("search_subfolders");
  if (subfolders && !subfolders.checked) url += "&subfolders=0";
  locate(url);
}

function nWin(address) {
  open_window(address,"custom_"+Math.random(),920,600);
}

function neWin(address) {
  open_window(address+"&folder2="+escape(tfolder)+"&view2="+tview,"custom_"+Math.random(),840,600);
}

function sWin(address) {
  locate(address+"&folder2="+escape(tfolder)+"&view2="+tview);
}

function log(val) {
  var obj = top.window.getObj("tconsole");
  if (!obj) return;
  obj.value = (obj.value.split("\n").length)+": "+(window.name)+": "+val+"\n"+obj.value;
  top.window.getObj("console").style.display = "inline";
}

function in_array(val,arr) {
  for (var i=0; i<arr.length; i++) if (arr[i]==val) return true;
  return false;
}

function has_flash() {
  if (navigator.plugins["Shockwave Flash"]) return;
  try {
	if (new ActiveXObject("ShockwaveFlash.ShockwaveFlash")) return;
  } catch (e) {}
  document.write(remove_trans("{t}Flash plugin not installed.{/t}"));
}

function ______I_N_I_T______() {}

function display_images() {
  var objs = getObj("content").getElementsByTagName("img");
  if (objs.length==0) return;
  for (var i=0; i<objs.length; i++) {
    if (objs[i].src=="" && objs[i].title!="" && objs[i].title.indexOf("ext/norefer.php?")==0) objs[i].src = objs[i].title;
  }
}

function save_cookie() {
  tree_scroll_pos = getObj("tree_def").scrollTop;
  document.cookie = "tree_scroll_pos="+tree_scroll_pos+"&tree_width="+tree_width+"&pane="+pane+"&pane2="+pane2+"~";
}

function notify(str, warn) {
  if (str=="") return;
  if (sys.menu_autohide) {
	set_attr("notification2", "class", "tabstyle"+(warn?" red":""));
	set_html("notification2", remove_trans("{t}Notification{/t}: "+str));
  } else {
	set_attr("notification", "class", "menu_notification"+(warn?" red":""));
	set_html("notification", remove_trans("{t}Notification{/t}: "+str));
  }
}

function refreshit() {
  if (iframe || popup || schema_mode) return;
  ajax("folder_has_changed", [tfolder, sys_time], function(result) {
	if (result) {
	  notify('{t}Folder has changed.{/t} <a href="index.php?folder='+escape(tfolder)+'&view='+tview+'">({t}Refresh{/t})</a>');
	  document.title = remove_trans("({t}Changed{/t}) ") + document.title;
	} else {
	  refresh_timer = setTimeout(refreshit, sys.folder_refresh*1000);
	}
  });
}

function resizeit() {
  var height = return_height();
  var width = return_width();

  if (sys.is_mobile) css(".vertical","display",width<=320 ? "none" : "inline");

  if ((iframe || preview) && typeof(parent.window)!="unknown" && window.name!="pane" && window.name!="pane2") {
	var obj = parent.window.getObj(window.name);
	if (obj != null) {
	  var height_main = getObj("main").clientHeight;
	  var obj_iframe = obj;
	  if (obj_iframe.tagName.toLowerCase()!="iframe") obj_iframe = obj.getElementsByTagName("iframe")[0];
	  if (height_main > 10) {
		obj_iframe.style.height = height_main+"px";
		return;
  } } }
  if (iframe && (window.name=="pane" || window.name=="pane2")) {
	show2("pane_close");
	hide("tab_spacer");
  }
  if (sys.browser.no_scrollbar) return;
  
  var content_def = getObj("content_def");
  var tree_frame = getObj("tree_frame");
  
  if (tree_frame) {
    var height_obj = findPosY(tree_frame);
    var tree_def = getObj("tree_def");
	tree_frame.style.height = (height-height_obj-1)+"px";
    tree_def.style.height = (height-height_obj-1)+"px";

	var pane_width = 0;
	if (vpane > 1) {
	  pane_width = Math.floor(width*pane2);
	  getObj("pane2").style.width = (pane_width-4)+"px";
	}
	content_def.style.width = (width-tree_width-pane_width-4)+"px";
	if (getObj("tree").style.display!="none") tree_def.style.width = tree_width+"px";
	if (vpane > 1 && hpane > 1) getObj("pane").style.width = content_def.style.width;
  }

  var fixed_footer = getObj("fixed_footer");
  if (content_def) {
    var height_obj = findPosY(content_def);
    var content_def_table = getObj("content_def_table");
	var height2 = height;
	if (fixed_footer) height2 -= fixed_footer.offsetHeight;
	if (hpane > 1) height2 = Math.floor(height*pane);
	if (!sys.is_mobile) content_def.style.height = (height2-height_obj-1)+"px";
    content_def_table.style.height = (height2-height_obj-1)+"px";
  }
  
  var height2 = fixed_footer ? fixed_footer.offsetHeight+1 : 0;
  if (hpane > 1) {
	var obj_pane = getObj("pane");
 	obj_pane.style.height = (height-findPosY(obj_pane)-height2)+"px";
  }
  if (vpane > 1) {
	var obj_pane = getObj("pane2");
	obj_pane.style.height = (height-findPosY(obj_pane)-height2)+"px";
  }
  if (tree_frame) {
	tree_scroll(tree_scroll_pos);
  }
}

function ______O_T_H_E_R_S______() {}

function switch_wrap(field_w, event) {
  if (is_nested_target(event, this)) return;
  var field = field_w.getElementsByTagName("div");
  if (field.length==0) return;
  field = field[0];
  if (field.style.overflow=="hidden") {
    field.style.overflow="visible";
	var len = field.innerHTML.replace(/<img.*?>/g,"  ").replace(/&.*?;/g," ").replace(/<script>.*?<\/script>|<.*?>/g,"").length;
	if (field.parentNode.offsetWidth < (len*7)) field.parentNode.style.width=(len*7)+"px";
  }
}

function change_tab(classname,id) {
  set_attr("."+classname,"class",classname+" tabstyle");
  set_attr(classname+id,"class",classname+" tabstyle2");
  css("."+classname+"2","display","none");
  css("."+classname+"2"+id,"display","");
  css(getObjs("."+classname+"2"),"marginBottom",id?"0px":"2px");
  
  if (/MSIE/.test(navigator.userAgent)) {
	set_attr("."+classname,"className",classname+" tabstyle");
	set_attr(classname+id,"className",classname+" tabstyle2");
  }
  if (iframe) resizeit();
}

function portal_change(id,size) {
  var obj = getFirstParentByName(getObj(id),"tr");
  var objs = obj.getElementsByTagName("iframe");
  if (objs.length==0) return;
  for (var i=0; i<objs.length; i++) {
  	if (objs[i].offsetHeight+size > 0) objs[i].style.height = (objs[i].offsetHeight+size)+"px";
	if (!objs[i].contentWindow) continue;
	try {
	  if (size<0) {
		objs[i].contentWindow.hide(".tfields");
	  } else {
		objs[i].contentWindow.show2(".tfields");
	  }
	} catch (e) {}
  }
}

function portal_refresh(id,time,init) {
  var obj = getObj(id);
  if (obj && obj.src!="about:blank" && time!=0) {
	if (!init && (sys.username=="anonymous" || getObj("login_reminder").style.display!="inline")) {
	  obj.src = obj.src;
	}
	if (time < 10) time = 10;
	setTimeout(function(){ portal_refresh(id,time,0); },time*1000);
  }
}

function file_preview_change(id,size) {
  var obj = getObj(id);
  if (obj.offsetHeight+size > 0) obj.style.height = (obj.offsetHeight+size)+"px";
}

function file_func(operation, id, field, subitem) {
  ajax(operation, [tfolder, id, field, subitem], locate_folder);
}

function searchbox(obj) {
  if (obj.value==remove_trans("{t}Search{/t}")) obj.value = "";
  if (obj.value==remove_trans("{t}Other{/t}")) obj.value = "";
}

function searchbox2(obj) {
  if (obj.value=="") obj.value = remove_trans("{t}Search{/t}");
}

function ______G_U_I__G_R_I_D______() {}

function asset_filter_submit() {
  var result = "";
  var fields = getObjs(".filter_field");
  var values = getObjs(".filter_value");
  var types = getObjs(".filter_type");
  for (var i=0; i<values.length; i++) {
    if (values[i].value=="") values[i].value = " ";
    result += "||"+fields[i].value+"|"+types[i].value+"|"+values[i].value;
  }
  locate("index.php?folder="+escape(tfolder)+"&view="+tview+"&filters="+result.substring(2));
}

function assets_get_selected(array) {
  var items=[];
  var objs = getObjs(".asset_check");
  for (var i=0; i<objs.length; i++) {
    var id = objs[i].value;
	if (getObj("check_"+id).checked) items.push(id);
  }
  if (array) return items;
  var result = "";
  for (var i=0; i<items.length; i++) result += "&item[]="+escape(items[i]);
  return result;
}

function asset_form_submit(action) {
  locate(action + assets_get_selected(false));
}

function asset_form_link(action) {
  return action + assets_get_selected(false);
}

function asset_action(mode, timeout) {
  if (mode=="cut" || mode=="copy") {
    func = "asset_cutcopy";
    callback = function() { mselectall(true); };
  } else if (mode=="paste") {
    func = "asset_paste";
    callback = locate_folder;
  } else {
    func = "asset_delete";
    callback = locate_folder;
  }
  ajax(func, [tfolder, tview, assets_get_selected(true), mode], callback, timeout);
}

function asset_form_selected() {
  if (assets_get_selected(false)=="") {
    sys_alert("{t}No dataset selected.{/t}");
	return false;
  }
  return true;
}

function mselectall(checked) {
  var objs = getObjs(".asset_check");
  for (var i=0; i<objs.length; i++) {
    var id = objs[i].value;
	getObj("check_"+id).checked = !checked;
    if (getObj("check_"+id).checked) color = css_conf.bg_red; else color = "";
    css(".asset_"+id,"backgroundColor",color);
  }
}

function mselectallkey() {
  var obj = getObj("itemall");
  mselectall(obj.checked);
  obj.checked = !obj.checked;
}

function customize_field() {
  var params={};
  params["module"] = ftype;
  params["field"] = val("cust_field");

  var action = val("cust_field_action");
  if (action=="hidden") params["hidden"] = 1;
  else if (action=="hiddenin") params["hiddenin"] = tview;
  else if (action=="notinall") params["notinall"] = 1;
  else if (action=="rename") params["displayname"] = val("cust_field_name");
  else {
	  params["displayname"] = val("cust_field_name");
	  params["fbefore"] = params["field"];
	  params["field"] = val("cust_field_name").replace(/[^a-z0-9_]/ig, "");
	  params["simple_type"] = action;
  }
  if (getObj("cust_field_folder").checked) params["ffolder"] = tfolder;

  if (params["field"]=="" || action=="") return;
  if (action=="rename" && params["displayname"]=="") return;

  ajax("sgsml_customizer::ajax_get_field", [params["module"], params["field"]], function(data) {
	if (data.length==0) data = {};
    for (var key in params) data[key] = params[key];
	ajax("asset_insert", ["~sys_custom_fields", "new", data], ajax_locate_folder);
  });
}

function ______G_U_I__T_R_E_E______() {}

function tree_drag_resize(event) {
  if (getObj("tree").style.display=="none") return;
  cancel(event);
  if (!event) event = window.event;
  var screen_width = return_width();
  tree_width = event.clientX+6;
  if (css_conf.direction) tree_width = screen_width-tree_width+12;
  if (tree_width > screen_width*0.5) tree_width = screen_width*0.5;
  save_cookie();
  if (tree_width < tree_min) tree_showhide(); else resizeit();
}

function tree_selectall(checked) {
  var objs = getObj("tcategories").getElementsByTagName("input");
  if (objs.length>0) {
    for (var i1=0; i1<objs.length; i1++) {
	  if (typeof(objs[i1].checked) != "undefined") objs[i1].checked = checked;
} } }

function tree_refresh(id) {
  var obj = getObj(id);
  obj.style.display = "none";
  obj.innerHTML = "";
  ajax("tree_close", [id], function(data) { tree_open(id); });
}

function tree_open(id) {
  var obj = getObj(id);
  var obj2 = getObj(id+"_img");
  if (obj.innerHTML.length > 0) {
    obj2.src = "ext/icons/plus.gif";
    obj.style.display = "none";
    obj.innerHTML = "";
    ajax("tree_close", [id], null);
  } else {
    ajax("tree_open", [id], function(data) {
	  var out = "";
	  for (i in data.children) {
		var img = "plus";
	    var item = data.children[i];
		if (!item.id) continue;
	    item.id = html_escape(item.id);
		item.fdescription = item.id.replace(/^.+:\d+\//, "") + " " + item.fdescription;
	    out += "<div class='drop_tree' rel='"+html_escape(item.id)+"' title='"+html_escape(item.fdescription)+"' style='white-space:nowrap;'>";
	    for (j=0; j <= data.level; j++) out += "<img src='ext/icons/line.gif'>";
		if (item.ffcount + item.mp == 0) img = "line";
	    out += "<a onclick='tree_open(\""+item.id+"\");'><img id='"+item.id+"_img' src='ext/icons/"+img+".gif'></a>&nbsp;";
		out += "<a href='index.php?folder="+item.id+"' "+(item.id==tfolder?"style='font-weight:bold;'":"")+">";

		if (item.icon) {
		  out += "<a href='index.php?folder="+item.id+"' "+(item.id==tfolder?"style='font-weight:bold;'":"")+"><img src='ext/modules/"+item.icon+"'> ";
		} else if (css_conf.tree_icons) {
		  if (item.anchor && item.anchor.indexOf("_")==-1) icon = "anchor_"+item.anchor; else icon = item.ftype;
		  out += "<a href='index.php?folder="+item.id+"' "+(item.id==tfolder?"style='font-weight:bold;'":"")+"><img src='ext/modules/"+icon+".png'> ";
		} else {
		  if (css_conf.bg_light_blue=="#B6BDD2")
		    out += "<img src='ext/icons/folder1.gif'> ";
		  else
		    out += "<img src='images.php?image=folder1&color="+css_conf.bg_light_blue.replace("#","")+"'> ";
		}
	    out += html_escape(item.ftitle)+"&nbsp;";
	    if (item.fcount > 0) out += "("+item.fcount+")";
	    out += "</a></div><div id='"+item.id+"' style='display:none;'></div>";
	  }
	  if (out!="") obj2.src = "ext/icons/minus.gif"; else obj2.src = "ext/icons/line.gif";
      show(obj);
	  obj.innerHTML = out;
	  bind_drop_tree();
	});
  }
}

function tree_showhide() {
  showhide("tree");
  showhide("tree_button");
  if (getObj("tree").style.display!="none") {
    tree_width = 235;
	save_cookie();
  } else tree_width = -2;
  resizeit();
}

function tree_folder_info() {
  tree_scroll(0);
  var obj = getObj("tree_info");
  ajax("folder_info", [tfolder], function(data) {
	obj.innerHTML = remove_trans(data);
	show2("tree_info");
  });
}

function tree_folder_mountpoint() {
  tree_scroll(0);
  var obj = getObj("tree_info");
  ajax("folder_mountpoint", [tfolder], function(data) {
	obj.innerHTML = remove_trans(data);
	show2("tree_info");
	mountpoint_parse(attr("tree_mountpoint_form","rel"));
  });
}

function tree_folder_options() {
  tree_scroll(0);
  var obj = getObj("tree_info");
  ajax("folder_options", [tfolder], function(data) {
	obj.innerHTML = remove_trans(data);
	show2("tree_info");
	getObj("frenametitle").focus();
  });
}

function tree_scroll(pos) {
  if (pos==-1) getObj("tree_def").scrollTop = getObj("tree_def").scrollHeight;
    else getObj("tree_def").scrollTop = pos;
}

function tree_categories_save() {
  var folders = [];
  var objs = getObj("tcategories").getElementsByTagName("input");
  for (var i=0; i<objs.length; i++) {
    if (objs[i].name=="folders[]" && objs[i].checked) folders.push(objs[i].value);
  }
  ajax("tree_set_folders", [tfolder,folders],locate_folder);
  return false;
}

function tree_categories() {
  tree_scroll(0);
  var obj = getObj("tree_info");
  ajax("tree_get_category", [ftype,tfolder,tfolders], function(data) {
	obj.innerHTML = remove_trans(data);
	show2("tree_info");
  });
}

function ______G_U_I__C_A_L_E_N_D_A_R______() {}

var calendars = [];
function calendar(obj_target,time,callback) {
  obj_target = getObj(obj_target);
  this.popup_open = calendar_popup;
  if (!obj_target || !obj_target.id) {
	alert("Error calling the calendar: no target control specified");
	return;
  }
  this.target = obj_target;
  this.time_comp = time;
  this.year_scroll = true;
  this.id = calendars.length;
  this.NUM_WEEKSTART = cal_firstday;
  calendars[this.id] = this;
  this.popup_open();
  if (callback) this.callback=true; else this.callback=false;
}

function calendar_popup(str_datetime) {
  if (!str_datetime) str_datetime = this.target.value; else this.dt_selected = this.dt_current;
  getObj("calendar").style.left = ((return_width()-200)/2)+"px";
  getObj("calendar").style.display = "inline";
  getObj("calendar_iframe").src="ext/lib/calendar/calendar.php?datetime="+str_datetime+"&id="+this.id;
}

function calendar_changed() {
  var url = "../../../index.php?today="+val("datebox");
  locate(url);
}

function ______G_U_I__M_O_U_N_T_P_O_I_N_T______() {}

function mountpoint_build() {
  var proto = getObj("mount_proto").value+":";
  var user = _mountpoint_escape(getObj("mount_user").value);
  var pass = _mountpoint_escape(getObj("mount_pass").value);
  var port = getObj("mount_port").value;
  var enc = getObj("mount_enc").value;
  var host = getObj("mount_host").value;
  var path = getObj("mount_path").value;

  var re = /^(imap|fs|pmwiki|ldap|cifs|gdocs)/;
  if (m = re.exec(proto) && path!="" && path.charAt(path.length-1)!="/") path += "/";
  var options = _mountpoint_escape(getObj("mount_options").value);
  var vals = user+(pass?":"+pass:":")+(port?":"+port:":")+(enc?":"+enc:":")+(options?":"+options:":")+"@";
  if (proto.length<2 || (!host && !path)) {
    return "";
  } else {
    if (host.length==0) vals = "";
    if (host.length>0 && (path.length==0 || path.charAt(0)!="/")) path = "/"+path;
    return proto+vals+host+path;
  }
}

function _mountpoint_escape(str) {
  return str.replace(/:/g,"==").replace(/@/g,"%%");
}

function _mountpoint_unescape(str) {
  return str.replace(/%%/g,"@").replace(/==/g,":");
}

function mountpoint_parse(mountpoint) {
  var re = /^(imap|smtp|pop3|ldap|cifs|gdocs):([^:@]*):?([^:@]*):?([^:@]*):?([^:@]*):?([^:@]*)(@[^\/]*)?\/(.*?)$/;
  if ((m = re.exec(mountpoint))) {
    getObj("mount_proto").value = m[1];
	var user = "";
	var pass = "";
	var options = "";
	if (m[2] && m[3]) {
	  user = _mountpoint_unescape(m[2]);
	  pass = _mountpoint_unescape(m[3]);
	}
	if (m[6]) options = _mountpoint_unescape(m[6]);
    getObj("mount_user").value = user;
    getObj("mount_pass").value = pass;
    getObj("mount_port").value = m[4];
    getObj("mount_enc").value = m[5];
    getObj("mount_options").value = options;
	var host = m[2];
	if (m[7]) host = m[7].substr(1);
	getObj("mount_host").value = host;
    getObj("mount_path").value = m[8];
    mountpoint_show(m[1]);
  } else {
    re = /([^:]*):(.*)/;
    if ((m = re.exec(mountpoint))) {
      getObj("mount_proto").value = m[1];
      getObj("mount_path").value = m[2];
  	  mountpoint_show(m[1]);
} } }

function mountpoint_show(proto) {
  var re = /^(imap|smtp|pop3|ldap|cifs|gdocs)$/;
  if (re.exec(proto)) {
	show2("mount_auth");
	show2("mount_details");
	if (proto=="gdocs") {
	  set_val("mount_host", "docs.google.com");
	  set_attr(".mp2", "value", "");  
	  hide("mount_details");
	}
  } else {
	hide("mount_auth");
	hide("mount_details");
	set_attr(".mp", "value", "");  
  }
}

function ______G_U_I__P_R_E_V_I_E_W__P_A_N_E______() {}

function show_pane() {
  hpane++;
  if (hpane>1) {
   show2("content_pane");
   resizeit();
  }
}

function hidepane(name) {
  if (name=="pane") {
	hpane=1;
	hide("content_pane");
  } else {
	vpane=1;
	hide("content_pane2");
  }
  resizeit();
}

function show_pane2() {
  vpane++;
  if (vpane>1) {
   show2("content_pane2");
   resizeit();
  }
}

function stop_drag() {
  var obj = getObj("pane");
  if (obj) obj.style.visibility="";
  document.onmousemove=null;
  document.onmouseup=null;
  document.onmousedown=null;
  document.onselectstart=null;
}

function start_drag(func) {
  getObj("tree").style.width="auto"; // IE
  document.onmousemove=func;
  document.onmouseup=stop_drag;
  document.onmousedown=function(){return false;}
  document.onselectstart=function(){return false;}
}

function resize_pane2(event) {
  cancel(event);
  if (!event) event = window.event;
  getObj("pane2").style.visibility="hidden";
  pane2 = 1-event.clientX/return_width();
  if (pane2 > 0.5 || pane2 < 0.15) {
	pane2=0.27;
    hidepane("pane2");
  } else resizeit();
  if (pane_timer!=null) clearTimeout(pane_timer);
  pane_timer = setTimeout(function(){ getObj("pane2").style.visibility=""; },250);
  save_cookie();
  return false;
}

function resize_pane(event) {
  cancel(event);
  if (!event) event = window.event;
  getObj("pane").style.visibility="hidden";
  pane = event.clientY/return_height()-0.005;
  if (pane > 0.9 || pane < 0.25) {
	pane=0.53;
    hidepane("pane");
  } else resizeit();
  if (pane_timer!=null) clearTimeout(pane_timer);
  pane_timer = setTimeout(function(){ getObj("pane").style.visibility=""; },250);
  save_cookie();
  return false;
}

function ______A_J_A_X______() {}

function asset_update(data, id) {
  if (typeof(id)=="object") {
	for (var i=0; i<id.length; i++) asset_update(data, id[i]);
	return;
  }
  ajax("asset_update", [tfolder, "edit", data, id], ajax_locate_folder);
}

function asset_update_confirm(data, id, message) {
  if (typeof(message)=="undefined") message = "{t}Really apply the changes ?{/t}";
  if (sys_confirm(message)) asset_update(data, id);
}

function asset_insert(data) {
  ajax("asset_insert", [tfolder, "new", data], ajax_locate_folder);
}

var prompt_value = "";
function asset_insert_prompt(fields, field_names) {
  fields = fields.split("|");
  field_names = field_names.split("|");
  var info = "\n\n{t}Line break{/t}: \\n\n{t}Comma{/t}: \\k\n{t}Separate fields{/t}: ,\n{t}Separate values{/t}: |\n\n";
  prompt_value = prompt(remove_trans("{t}Quick add{/t}: "+field_names.join(", ")+info), prompt_value);
  var data = {};
  var values = prompt_value.split(",");
  for (var i=0; i<values.length; i++) {
    if (!fields[i]) {
	  fields[i] = values[i].split(":")[0].replace(/^\s+|\s+$/g,""); // trim
	  values[i] = values[i].split(":")[1];
	}
	if (values[i] != null) data[fields[i]] = values[i].replace(/\\k/g,",").replace(/\\n/g,"\n").replace(/^\s+|\s+$/g,""); // trim
  }
  asset_insert(data);
}

function get_loading(func) {
  var elem = document.createElement("div");
  elem.innerHTML = '<img src="ext/images/loading.gif" title="'+func+'"/>';
  elem.style.display = "none";
  if (getObj("loading")) getObj("loading").appendChild(elem); else return null;
  return elem;
}

function ajax(func, params, callback, timeout) {
  if (typeof(navigator.onLine)!="undefined" && !navigator.onLine) return;
  if (typeof(timeout)=="undefined") timeout = 10000;
  var xmlhttp = window.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
  xmlhttp.open("POST", "ajax.php?function="+escape(func), true);
  xmlhttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
  xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
  pending_request++;
  var timer = setTimeout(function() {
	timer = null;
	xmlhttp.abort();
	sys_alert("{t}Error doing request to xmlhttp{/t}: {t}timeout{/t} "+func);
  }, timeout);
  var image = get_loading(func);
  var loading = setTimeout(function() { show(image); }, 750);
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
	  pending_request--;
	  if (loading != null) clearTimeout(loading);
	  hide(image);
	  if (timer != null) clearTimeout(timer); else return;
	  var result = xmlhttp.responseText;
	  try {
	    if (xmlhttp.status==0 && result=="") return;
		if (xmlhttp.status==200 && result!="") {
	      if (callback!=null) callback(JSON.parse(result));
	    } else sys_alert("{t}Ajax Error{/t}: "+func+" "+xmlhttp.status+" "+xmlhttp.statusText+"\n"+(result!=""?result:"{t}no result{/t}"));
	  } catch (e) {
		if (result.length > 0) {
		  if (e == "SyntaxError: parseJSON") e = "";
		  if (e) error = e.name+": "+e.message; else error = "";
		  if (!debug) trace = "\n"+JSON.stringify(e); else trace = "";
		  sys_alert("{t}Error{/t}: "+error+"\n"+result+"\n"+func+trace);
  } } } }
  xmlhttp.send(JSON.stringify(params));
}

// https://developer.mozilla.org/en/Using_files_from_web_applications
function ajax_binary(func, file, params, callback, callback_progress) {
  if (typeof(navigator.onLine)!="undefined" && !navigator.onLine) return;
  var xmlhttp = window.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
  if (callback_progress!=null) {
    xmlhttp.upload.onprogress = callback_progress;
    xmlhttp.upload.onload = callback_progress;
  }
  xmlhttp.open("PUT", "ajax.php?function="+escape(func)+"&params="+JSON.stringify(params), true);
  xmlhttp.setRequestHeader("Content-Type", "application/octet-stream; charset=utf-8");
  xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
	  var result = xmlhttp.responseText;
	  try {
		if (xmlhttp.status==200 && result!="") {
		  var js = JSON.parse(result);
	      if (callback!=null) callback(js);
	    } else sys_alert("{t}Ajax Error{/t}: "+func+" "+xmlhttp.status+" "+xmlhttp.statusText+"\n"+(result!=""?result:"{t}no result{/t}"));
	  } catch (e) {
		if (result.length > 0) {
		  if (e == "SyntaxError: parseJSON") e = "";
		  if (e) error = e.name+": "+e.message; else error = "";
		  sys_alert("{t}Error{/t}: "+error+"\n"+result+"\n"+func);
  } } } }
  xmlhttp.send(file);
}

function ajax_locate_folder(data) {
  if (typeof(data) != "object") {
	locate_folder(tfolder);
	return;
  }
  var output = "";
  for (i in data) {
    for (j in data[i]) {
	  output += data[i][j][0] + ": " + data[i][j][1] + "\n";
	}
  }
  sys_alert(output);
}

var call_autosize=[];
function textarea_autosize(event) {
  if ((call_autosize[this.id]>2 && getmykey(event)!=13) || attr(this,"rel")=="no_resize") return;
  if (typeof(this.clone)=="undefined") {
	this.clone = document.createElement("textarea");
	this.clone.style.width = this.clientWidth+"px";
	this.clone.style.position = "absolute";
	this.clone.style.left = -9999;
	this.clone.disabled = true;
	this.parentNode.insertBefore(this.clone,this);
  }
  this.clone.style.width = this.clientWidth+"px";
  this.clone.value = this.value;
  this.style.height = Math.max(64,Math.min(return_height()-160, this.clone.scrollHeight+44))+"px";
  call_autosize[this.id]++;
  setTimeout(function(){ call_autosize[this.id] = 0; }, 2500);
}

function ______M_E_N_U______() {}

function menuover(obj) {
  if (mtrigger!=null) menuopen(obj);
  obj.className = "menu_item2";
}
function menuout(obj) {
  obj.className = "menu_item";
}
function menuopen(obj) {
  if (mtimer!=null) clearTimeout(mtimer);
  if (obj.id=="menu") return;
  var menu = getObj(obj.id.replace("item",""));
  if (menu && (mtrigger==null || mtrigger.id!=menu.id)) {
    if (mtrigger!=null) hide(mtrigger);
    mtrigger = menu;
    show2(menu);
  } else if (!menu && mtrigger!=null) {
	mtriggerclose();
  }
}
function menuclose() {
  mtimer = setTimeout("mtriggerclose()",250);
}
function mtriggerclose() {
  if (mtrigger!=null) {
    hide(mtrigger);
	mtrigger = null;
  }
}

function menu_begin() {
  mbuffer += '<table id="menu" class="menu" cellspacing="2" cellpadding="0" onmouseout="menuclose();" onmouseover="menuopen(this);"><tr>';
}
function menu_end() {
  mbuffer += "</tr></table>"+smenu_buffer;
}
function menuitem(name) {
  mbuffer += '<td id="menuitem'+menu+'" class="menu_item" style="width:85px;" onmouseover="menuover(this);" onmouseout="menuout(this);" onclick="menuopen(this);">'+remove_trans(name)+'</td>';
  menu++;
}
function menubutton(name,ref,accesskey,style) {
  var html = "";
  var hint = "";
  if (typeof(style)=="undefined") style="width:85px;";
  if (typeof(accesskey)!="undefined") {
	html = "<a onclick='"+ref.replace(/'/g, "&quot;")+"' href='#' accesskey='"+accesskey+"'></a>";
	hint = "[Alt-"+accesskey+"]";
  }
  mbuffer += '<td id="menuitem'+menu+'" class="menu_item" style="'+style+'" onmouseover="menuover(this);" onmouseout="menuout(this);" onclick="'+ref+'" title="'+hint+'">'+remove_trans(name)+html+'</td>';
  menu++;
  smenu++
}
function smenu_begin() {
  smenu_buffer += '<table onmouseover="menuopen(this);" onmouseout="menuclose();" id="menu'+smenu+'" class="submenu" cellspacing="0" cellpadding="0" style="display:none;'+mleft+':'+(smenu*90)+'px;">';
  smenu++
}
function smenu_end() {
  smenu_buffer += '</table>';
}
function smenuitem(name,ref,accesskey) {
  var html = "";
  var hint = "";
  if (typeof(accesskey)!="undefined") {
  	html = "<a onclick='"+ref.replace(/'/g, "&quot;")+"' href='#' accesskey='"+accesskey+"'></a>";
	hint = "[Alt-"+accesskey+"]";
  }
  smenu_buffer += '<tr><td id="menuitem0" class="menu_item" style="padding-'+mleft+': 6px;" onmouseover="menuover(this);" onmouseout="menuout(this);" onclick="menuclose(); '+(ref!=""?ref:"")+'" title="'+hint+'">'+remove_trans(name)+html+'</td></tr>';
}
function smenu_home() {
  if (sys.username!="anonymous" && !sys.is_superadmin) {
    smenuitem("{t}Change settings{/t}","locate('index.php?find=asset|simple_sys_users|1|username="+sys.username+"&view=changepwd')");
    smenu_hr();
    smenuitem("{t}Home{/t}","locate('"+sys.home+"')", "h");
  } else if (sys.is_superadmin) {
    smenuitem("{t}Change settings{/t}","locate('index.php?action_sys=edit_setup')");
    smenu_hr();
    smenuitem("{t}Administration{/t}","locate('"+sys.home+"')", "h");
    smenuitem("{t}Backup current folder{/t}","sWin('index.php?action_sys=backup');");
  } else {
    smenuitem("{t}Home{/t}","locate('index.php?folder=1')","h");
  }
}
function smenu_history() {
  if (hist!="") {
    for (var i=0; i<hist.length; i++) {
      if (hist[i].length!=3) continue;
	  var title = hist[i][0];
	  if (hist[i][2]!="" && hist[i][2]!="display") title += " &nbsp;("+hist[i][2]+")";
      smenuitem(title,"locate('index.php?folder="+escape(hist[i][1])+"&view="+hist[i][2]+"')");
    }
  } else smenuitem("{t}Empty{/t}","");
}
function smenu_hr() {
  smenu_buffer += '<tr><td><div style="margin-top:3px; margin-bottom:3px; border-top: 1px solid '+css_conf.color_light_grey+';"><none></div></td></tr>';
}
function folder_delete() {
  if (sys_confirm('{t}Really delete ?{/t}') && sys_confirm('{t}Really delete ALL subfolders ?{/t}')) {
    ajax("folder_delete", [tfolder], locate_folder);
  }
}
function tree_applyrights() {
  if (sys_confirm("{t}Really apply rights to subfolders ?{/t}")) {
    ajax("folder_applyrights", [tfolder], function(data) {
      if (data) sys_alert("{t}Item successfully updated.{/t}");
	    else sys_alert("{t}Item not found.{/t}");
    });
  }
}

function drawmenu() {
  menu_begin();

  menuitem("{t}Main menu{/t}");
  smenu_begin();
  smenu_home();
  smenu_hr();
  smenuitem("{t}Print{/t}","neWin('index.php?print=1')");
  smenuitem("{t}Print all{/t}","neWin('index.php?print=1&print_all=1')");
  smenu_hr();
  if (tree_visible) smenuitem("{t}Minimize{/t}","sWin('index.php?tree=minimize')");
	else smenuitem("{t}Maximize{/t}","sWin('index.php?tree=maximize')");
  smenu_hr();
  smenuitem("{t}Collapse tree{/t}","sWin('index.php?tree=closeall')");
  smenuitem("{t}Reset view{/t}","sWin('index.php?reset_view=true')");
  smenuitem("{t}Close session{/t}","sWin('index.php?logout&session_clean')");
  smenu_hr();
  smenuitem("{t}Offline sync{/t}","sWin('offline.php?')");
  smenu_end();

  if (sys.username!="anonymous" && !sys.is_superadmin) {
	menuitem("{t}Create new{/t}");
    smenu_begin();
	var quick_create = {
	  emails:"{t}E-mail{/t}", calendar:"{t}Appointment{/t}", tasks:"{t}Task{/t}",
	  contactactivities:"{t}Contact activity{/t}", contacts:"{t}Contact{/t}",
	  passwords:"{t}Password{/t}", bookmarks:"{t}Bookmark{/t}", notes:"{t}Note{/t}",
	  files:"{t}File{/t}", gallery:"{t}Photo{/t}", spreadsheets:"{t}Spreadsheet{/t}",
	  graphviz:"{t}Diagram{/t}", htmldocs:"{t}HTML document{/t}", cms:"{t}Web page{/t}"
	}
	for (var type in quick_create) {
	  if (sys.disabled_modules[type]) continue;
	  var icon = "";
	  if (sys_style=="core_tree_icons") icon += "<img src='ext/modules/"+type+".png'>&nbsp; &nbsp;";
	  smenuitem(icon+quick_create[type],"sWin('index.php?folder=^home_"+sys.username+"/!"+type+"&view=new')");
	}
    smenu_end();
  }
  
  menuitem("{t}Folder{/t}");
  smenu_begin();
  if (sys.username!="anonymous") {
	smenuitem("{t}Add to offline folders{/t}","ajax('folder_add_offline',[tfolder,tview,tfolder_name],alert_ok);");
  }
  if (rights.write_folder && isdbfolder) {
	smenu_hr();
	smenuitem("{t}Move{/t}: {t}Up{/t}","ajax('folder_moveup',[tfolder],locate_folder)");
	smenuitem("{t}Move{/t}: {t}Down{/t}","ajax('folder_movedown',[tfolder],locate_folder)");
  }
  if (!no_folder_operations && rights.write_folder) {
	if (isdbfolder) {
	  smenu_hr();
	  smenuitem("{t}Cut{/t}","ajax('folder_cut',[tfolder],null)");
	  smenuitem("{t}Copy{/t}","ajax('folder_copy',[tfolder],null)");
	  smenuitem("{t}Paste{/t}","ajax('folder_paste',[tfolder],locate_folder,30000)");
	}
	smenu_hr();
	smenuitem("{t}Delete incl subfolders{/t}","folder_delete()");
  }
  if (rights.admin && isdbfolder) {
	smenu_hr();
	smenuitem("{t}Rights{/t}: {t}Show{/t}","sWin('index.php?view=rights')");
	smenuitem("{t}Rights{/t}: {t}Edit{/t}","sWin('index.php?view=rights_edit')","0");
	smenuitem("{t}Apply rights to subfolders{/t}","tree_applyrights()");
  }
  if (rights.write_folder && isdbfolder) {
	smenu_hr();
	smenuitem("{t}Merge folders permanently{/t}","tree_categories()");
  }
  if (!no_folder_operations && rights.write_folder) {
	smenu_hr();
	smenuitem("{t}Options{/t}","tree_folder_options()");
  }
  if (!no_folder_operations && isdbfolder && ((rights.write_folder && !sys.mountpoint_admin) || rights.admin_folder)) {
	smenuitem("{t}Mountpoint{/t}","tree_folder_mountpoint()");
  }
  smenuitem("{t}Info{/t}","tree_folder_info()");
  smenu_end();

  menuitem("{t}History{/t}");
  smenu_begin();
  smenu_history();
  smenu_end();

  menuitem("{t}Im-/Export{/t}");
  smenu_begin();
  if (rights.write) {
	smenuitem("Simple Groupware {t}Import{/t}","sWin('import.php?folder="+escape(tfolder)+"')");
	smenu_hr();
  }
  smenuitem("Simple Spreadsheet","neWin('index.php?export=sss')");
  smenuitem("ExtJS {t}Table{/t}","neWin('ext/lib/extjs/index.php?title='+escape(tfolder_name)+'&items='+assets_get_selected(true).join(','))");
  smenuitem("HTML","neWin('index.php?export=html')");
  smenuitem("HTML ({t}vertical{/t})","neWin('index.php?export=html_vertical')");
  smenuitem("CSV","sWin('index.php?export=csv')");
  smenuitem("{t}Spreadsheet{/t}","sWin('index.php?export=calc')");
  smenuitem("{t}Text Document{/t}","sWin('index.php?export=writer')");
  smenuitem("XML","sWin('index.php?export=xml')");
  smenuitem("RSS","sWin('index.php?export=rss')");
  smenuitem("iCalendar","sWin('index.php?export=icalendar')");
  smenuitem("vCard","sWin('index.php?export=vcard')");
  smenuitem("LDIF","sWin('index.php?export=ldif')");
  smenu_end();

  menuitem("{t}Theme{/t}");
  smenu_begin();
  var styles = {
	core:"core", core_tree_icons:"core tree icons",	contrast:"contrast", water:"water", lake:"lake",
	beach:"beach", paradise:"paradise", earth:"earth", sunset:"sunset", nature:"nature", desert:"desert",
	blackwhite:"black / white", rtl:"right-to-left"
  }
  for (var style in styles) {
	var checked = "";
	if (sys_style==style) checked = "&gt; ";
	smenuitem(checked+"simple "+styles[style],"sWin('index.php?style="+style+"')");
  }
  smenu_end();

  menubutton("{t}Help{/t}","nWin('cms.php?page=Help')");
  menubutton("&nbsp;{t}Login/-out{/t} <img src='ext/icons/logout.gif' title='{t}Login/-out{/t}'/>&nbsp;","sWin('index.php?logout')", "l", "");
  mbuffer += '<td><span id="notification"></span></td>';
  menu_end();
  getObj("menu").innerHTML = mbuffer;
  if (warning) notify(warning, true); else notify(notification);
}