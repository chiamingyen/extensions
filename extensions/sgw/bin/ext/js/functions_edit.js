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

function ______G_E_N_E_R_A_L______() {}

function array_contains(arr, elem) {
  for (j=0; j<arr.length; j++) {
	if (arr[j]==elem) return true;
  }
  return false;
}

function generate_password(field) {
  var keys = "abcdefghijklmnopqrstuvwxyz1234567890@";
  var temp = "";
  for (i=0;i<8;i++) {
    temp += keys.charAt(Math.floor(Math.random()*keys.length));
  }
  sys_alert("The new password is: " + temp);
  getObj(field).value = temp;
  getObj(field+"_confirm").value = temp;
}

function ______O_T_H_E_R_S______() {}

function resize_obj(id,size) {
  var obj = getObj(id);
  if (!obj) return;
  if (obj.offsetHeight + size > 0) obj.style.height = (obj.offsetHeight + size)+"px";
}

function addupload(obj) {
  var elem = document.createElement("div");
  elem.innerHTML += '<input type="File" name="'+obj+'[]" style="width:45%;" value="" multiple="true">';
  elem.style.paddingTop = "2px";
  getObj(obj+"_div1").appendChild(elem);
}

function get_uid() {
  return (new Date()).getTime();
}

// see: https://developer.mozilla.org/en/Using_files_from_web_applications
function drop_upload(item_name, max_file_size, max_file_count, event) {
  var files = event.dataTransfer.files;
  if (!files || files.length==0) {
    files = new Array({size:0, url:true, name:event.dataTransfer.getData("Text")}); // drop URL
	if (!files[0].name) return;
  }
  handle_upload(item_name, max_file_size, max_file_count, files, "");
}

function handle_upload(item_name, max_file_size, max_file_count, files, input_id) {
  if (!files || files.length==0) return false;
  var file_count = 0;
  var file_fields = document.getElementsByName(item_name+"[]");
  for (var i=0; i < file_fields.length; i++) {
	if (item_name == file_fields[i].id) continue;
    if (file_fields[i].files) file_count += file_fields[i].files.length;
	  else if (file_fields[i].value!="") file_count += 1;
  }
	  
  if (max_file_count!="" && file_count + files.length > max_file_count) {
	sys_alert("Upload failed: maximum number of files exceeded. ("+max_file_count+")");
	return false;
  }
  for (var i = 0; i < files.length; i++) {
    var file = files[i];
	max_file_size = max_file_size.replace("M", "000000");
	if (max_file_size > 0 && file.size > max_file_size) {
	  sys_alert("Upload failed: file is too big. Please upload a smaller one.");
	  return false;
	}
	var callback = function(result) {
	  var id = get_uid();
	  var elem = document.createElement('div');
	  elem.innerHTML+='<input type="hidden" name="'+item_name+'[]" style="width:45%;" value="'+result.tmp_path+'">';
	  elem.innerHTML+='<input type="input" readonly="true" style="width:38%;" value=" '+result.basename+' ('+result.filesize+')">';
	  elem.innerHTML+='&nbsp;<a href="#" onclick="set_html(\''+id+'\',\'\'); return false;"><img src="ext/icons/empty.gif" title="Delete"></a><br>';
	  elem.style.paddingTop="2px";
	  elem.id=id;
	  getObj(item_name+"_div3").appendChild(elem);
	  if (input_id!="") set_val(input_id, "");
	};
	if (file.url) {
	  ajax("upload_file", [file.name], callback);
	} else {
	  ajax_binary("upload_file", file, [file.name], callback, function(event) {
		if (!event.lengthComputable) return;
		var percentage = Math.round((event.loaded * 100) / event.total);
		set_html(item_name+"_progress", (percentage!=100) ? "<br>Uploading: "+file.name+": "+percentage+"%" : "");
	  });
	}
  }
  return false;
}

function addupload_url(obj) {
  var elem = document.createElement("div");
  elem.innerHTML='<input type="text" name="'+obj+'_cust[]" value="" style="width:45%; margin-top:1px;">';
  getObj(obj+"_div2").appendChild(elem);
}

function form_submit(form) {
  var obj = form.getElementsByTagName("input");
  for (i=0; i<obj.length; i++) {
    if (obj[i].onsubmit) obj[i].onsubmit();
    if (obj[i].type=="password") {
	  var id = obj[i].id;
	  if (id.indexOf("_confirm")==-1 && getObj(id+"_confirm").value!=obj[i].value) {
		sys_alert(attr(id+"_confirm","rel")+": password not confirmed.");
		return false;
  } } }
  obj = form.getElementsByTagName("select");
  for (i=0; i<obj.length; i++) {
    if (obj[i].name.length>0 && obj[i].multiple) {
	  if (obj[i].options.length!=0) {
	    for (i2=0; i2<obj[i].options.length; i2++) obj[i].options[i2].selected = true;
	  } else {
	    var obj2 = document.createElement("input");
		obj2.name = obj[i].name.replace("[]","");
		obj2.style.display = "none";
		obj[i].parentNode.appendChild(obj2);
  } } }
  obj = form.getElementsByTagName("textarea");
  for (i=0; i<obj.length; i++) {
    if (obj[i].className=="spreadsheet") {
	  obj[i].value = getObj(obj[i].id+"_iframe").contentWindow.cellsToJS();
	}
  }
  return true;
}

function show_freebusy(prefix) {
  var today = val(prefix+"begin");
  var users = getObj(prefix+"participants");
  var cals = "";
  if (users.options.length>0) {
    for (var i=0;i<users.options.length;i++) {
	  if (cals.length!=0) cals += ",";
      cals += "calendar_"+users.options[i].value;
    }
	getObj("pane").src="index.php?folder="+escape(tfolder)+"&view=freebusy&markdate=day&iframe=2&find=folders|simple_sys_tree||anchor="+escape(cals)+"&today="+today;
	change_tab('tab','general');
  } else sys_alert("Please select a participant.");
}

function show_freebusy_location(prefix) {
  var today = val(prefix+"begin");
  var locs = val(prefix+"location");
  if (locs) {
	getObj("pane").src="index.php?folder=1&view=freebusy&markdate=day&iframe=2&find=assets|simple_calendar||location="+escape(locs)+"&today="+today;
  } else sys_alert("Please select a location.");
}

function ______G_U_I__L_O_O_K_U_P_S______() {}

var form_values = [];
function form_restore_values(prefix, data) {
  if (form_values[prefix]) {
	form_set_values(prefix, form_values[prefix]);
  }
  var values = form_get_values(prefix);
  for (key in values) {
	if (typeof(data[key])=="undefined") delete(values[key]);
  }
  form_values[prefix] = values;
}

function populate(prefix, fields, data) {
  fields = fields.split("|");
  for (var i=0; i<fields.length; i++) {
	var selectbox = prefix+fields[i]+"_0_box";
	if (!getObj(selectbox)) selectbox = prefix+fields[i];
	var value = val(selectbox);
	getObj(selectbox).options.length = 0;
	for (key in data) insert_into_selectbox(selectbox, data[key], key, 0);
	set_val(selectbox, value);
	css(selectbox, "border", css_conf.border_red, 1000);
  }
}

function form_get_prefix(node) {
  while (node.tagName && node.className!="prefix") node = node.parentNode;
  return node.id;
}

function form_get_values(prefix) {
  var values = {};
  var elems = getObj("asset_form").elements;
  for (var i=0; i<elems.length; i++) {
    if (!elems[i].name || elems[i].id.indexOf(prefix)!=0) continue;
	var key = elems[i].id.replace(prefix,"");
	values[key] = val(elems[i]);
  }
  return values;
}

function form_set_values(prefix, data, highlight) {
  for (key in data) {
	set_val(prefix+key, data[key]);

	if (typeof(highlight)!="undefined") {
	  var obj = getObj(prefix+key);
	  css(obj, "border", css_conf.border_red, 1000);
} } }

function commit_from_popup(lookup, values) {
  lookup = lookup.split("|");
  var selectbox = lookup[0];
  ajax("search_data", [lookup[1], "", 1, values], function(data) {
    for (key in data) {
	  if (getObj(selectbox).options) {
	    insert_into_selectbox(selectbox, data[key], key,1);
	  } else if (attr(selectbox, "rel")=="cursor") {
	    additem_atcursor(selectbox, key);
	  } else insert_into_textarea(selectbox, key);
	}
  });
}

function page_data(id, page_increment) {
  var page = parseInt(attr(id+"_box", "page"));
  return search_data(id, page + page_increment);
}

function refresh_data_clear(id) {
  set_val(id + "_custom", "");
  return refresh_data(id);
}

function refresh_data(id) {
  var page = attr(id + "_box", "page");
  return search_data(id, page);
}

function search_data(id, page) {
  var selectbox = id + "_box";
  if (page<1) page = 1;
  set_attr(selectbox, "page", page);

  if (!getObj(selectbox)) {
    selectbox = selectbox.replace("_0_box", "");
  } else {
	getObj(selectbox).options.length = 0;
  }

  var value = val(id + "_custom"); // inputbox
  if (value==remove_trans("Search")) value = "";

  ajax("search_data", [val(id + "_ticket"), value, page], function(data) {
	if (page==1) hide(id + "_prev"); else show(id + "_prev");
    if (data["_overload_"]) show(id + "_next"); else hide(id + "_next");
	if (value!="") show(id + "_x"); else hide(id + "_x");
	for (key in data) if (key!="_overload_") insert_into_selectbox(selectbox, data[key], key, 0);
  });
  return false;
}

function insert_into_textarea(id,value) {
  var left = val(id);
  left = left.replace(/, /g,",");
  var left_arr = left.split(",");
  if (!array_contains(left_arr, value)) left_arr.push(value);
  left = left_arr.sort().join(", ");
  if (left.indexOf(", ")==0) left = left.substr(2);
  if (left.lastIndexOf(", ")==left.length-2) left = left.substr(0,left.length-2);
  getObj(id).value = left;
}

function insert_into_selectbox(selectbox,text,value,selected) {
  if (text=="" || value=="") return;
  var obj = getObj(selectbox);
  var index = _selectbox_find(obj, value);
  if (index == -1) {
	index = obj.options.length;
	obj.options[index] = new Option(text,value);
  }
  if (!obj.multiple && selected) obj.options[index].selected = true;
}

function additems(field) {
  var objs = document.getElementsByClassName(field+"_custom");
  for (var i=0; i<objs.length; i++) additem(field,objs[i]);
  return false;
}

function additem(id,right) {
  // left: textarea
  if (right==null) return;
  if (typeof(getObj(id).options) == "undefined") {
    var options_right = right.options;
	if (options_right==null) {
	  insert_into_textarea(id,right.value);
	  right.value = "";
	} else {
      for (var i=0; i<options_right.length; i++) {
        if (options_right[i].selected) {
		  options_right[i].selected = false;
		  insert_into_textarea(id,options_right[i].value);
	} } }
  } else {
    // right: input
    if (typeof(right.options)=="undefined" && right.value!="") {
	  insert_into_selectbox(id,right.value,right.value,1);
  	  right.value = "";
	  return;
    }
    // right: select
    if (right.options==null) return;
    options_right = right.options;
    for (var i=0; i<options_right.length; i++) {
      if (!options_right[i].selected) continue;
	  options_right[i].selected = false;
	  if (options_right[i].value=="---") continue;
	  insert_into_selectbox(id,options_right[i].text,options_right[i].value,1);
} } }

function additem_atcursor(left, right) {
  left = getObj(left);
  right = getObj(right);
  var options_right = right.options;
  if (options_right!=null) {
    for (var i=0; i<options_right.length; i++) {
      if (!options_right[i].selected) continue;
	  options_right[i].selected = false;
	  left.value = left.value.substring(0,left.selectionStart)+options_right[i].value+"\n"+left.value.substring(left.selectionStart);
	}		
  } else {
	left.value = left.value.substring(0,left.selectionStart)+right+"\n"+left.value.substring(left.selectionStart);
  }
  left.focus();
}

function removeitem(id) {
  var left = getObj(id);
  for (i=left.options.length-1; i>=0; i--) {
    if (left.options[i].selected) left.removeChild(left.options[i]);
  }
}