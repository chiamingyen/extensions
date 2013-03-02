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

function show_grid(folder, view, fields_hidden, filter, groupby, orderby, limit, title) {
  sgs_ajax("extjs::ajax_get_model", [folder, view], function(model) {

	model = _extjs_model_apply(model, groupby, orderby, limit);
	model.filter = filter;
	model.title = title;

	checksum = escape(folder+view+fields_hidden+filter+groupby+orderby+limit);
    extjs_grid(checksum, model, fields_hidden);
  });
}

function _extjs_model_apply(model, groupby, orderby, limit) {
  if (orderby!="") {
	orderby = orderby.split(" ");
	if (orderby[0] && model.fields[orderby[1]]) model.sort.field = orderby[0];
    if (orderby[1]) model.sort.direction = orderby[1];
  }
  if (typeof(limit)!="undefined" && limit!="") model.limit = limit;
  if (typeof(groupby)!="undefined" && model.fields[groupby]) model.groupby = groupby;
  
  return model;
}

function extjs_grid(checksum, model, fields_hidden) {
  
  Ext.BLANK_IMAGE_URL="images/default/s.gif";
  Ext.onReady(function(){

    Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
    model.filter = Ext.state.Manager.get("filter"+checksum, model.filter);
    model.limit = Ext.state.Manager.get("limit"+checksum, model.limit);
	
	var base = "../../../";
	var url = base+"index.php?folder="+escape(model.folder)+"&view="+escape(model.view);

	var fields = [];
	var columns = [];
	var fields_fetch_arr = [];
    var fields_hidden_arr = fields_hidden.split(",");

	for (var i in model.fields) fields_fetch_arr.push(i);

	var render_content = function(str, params, field, row, col, ghi) {
	  if (str.join) str = str.join("<br>");
	  return remove_trans(str);
	}

	var render_details = function(str, field) {
	  if (str.join) str = str.join(", ");
	  return remove_trans(str);
	}

	fields_fetch = [];
	fields_filter = [];
	for (var i=0; i < fields_fetch_arr.length; i++) {
	  var field = fields_fetch_arr[i];
	  if (!model.fields[field]) continue;
	  fields_fetch.push(field);
	  fields.push(model.fields[field]);
	  
      model.fields[field].dataIndex = model.fields[field].name;
	  model.fields[field].sortable = true;
	  model.fields[field].tooltip = field;
	  model.fields[field].renderer = render_content;
	  model.fields[field].is_hidden = model.fields[field].hidden;
	  
	  if (field == model.groupby || fields_hidden_arr.indexOf(field)!=-1 || model.fields[field].hidden) {
	    model.fields[field].hidden = true;
	  }
	  if (fields_hidden_arr.indexOf(field)==-1) {
	    fields_filter.push([field,model.fields[field].header]);
	  }
	  columns.push(model.fields[field]);
	}
	
	var filter_ops = [
	  ["like", remove_trans("contains")],
	  ["nlike", remove_trans("not contains")],
	  ["starts", remove_trans("starts with")],
	  ["eq", remove_trans("equal")],
	  ["neq", remove_trans("not equal")],
	  ["oneof", remove_trans("one of")],
	  ["lt", remove_trans("lesser than")],
	  ["gt", remove_trans("greater than")]
	];

	var show_filter = function(filters) {
	  var output = [];
	  filters = filters.split("||");
	  for (var i=0; i<filters.length; i++) {
	    var filter = filters[i].split("|");
		if (filter.length!=3 || !model.fields[filter[0]]) continue;

		var op = "";
		for (var j=0; j<filter_ops.length; j++) {
		  if (filter_ops[j][0] == filter[1]) op = filter_ops[j][1];
		}
		output.push(model.fields[filter[0]].header+" "+op+" '"+filter[2]+"'");
	  }
	  return output.join(", ");
	};

    var store = new Ext.data.GroupingStore({
	  reader: new Ext.data.JsonReader({
		root: "rows",
        totalProperty: "total",
		fields: fields
	  }),
	  autoLoad: true,
	  remoteSort: true,
	  remoteGroup: true,
      url: base+"ajax.php",
	  groupField: model.groupby,
	  sortInfo: model.sort,
	  baseParams: {
	    "function": "extjs::ajax_get_rows",
		params: Ext.encode([model.folder, model.view, fields_fetch.join(",")]),
		filter: model.filter,
		start: parseInt(model.start),
		limit: parseInt(model.limit)
	  },
	  listeners: {
		loadexception: function (proxy, options, response, e) {
		  if (e==null) return;
	 	  if (e) error = e.name+": "+e.message; else error = "";
		  var result = response.responseText;
		  sys_alert("[1] Error: "+error+"\n"+result+"\n"+Ext.encode(e));
		},
		load: function() {
		  Ext.state.Manager.set("filter"+checksum, this.baseParams.filter);
		  Ext.state.Manager.set("limit"+checksum, this.baseParams.limit);
		}
	  }
    });
	
	var fbar = new Ext.Toolbar({
	  hidden: !model.filter,
	  hideMode:"offsets",
	  items: [
        remove_trans("Filter: "),
		" ",
		new Ext.form.ComboBox({
		  id: id+"field",
		  mode: "local",
		  valueField: "key",
		  displayField: "value",
		  editable: false,
		  triggerAction: "all",
		  value: fields_filter[0][0],
		  width: 150,
		  store: new Ext.data.ArrayStore({
			fields: ["key", "value"],
			data: fields_filter
		  })
        }),
		" ",
		new Ext.form.ComboBox({
		  id: id+"type",
		  mode: "local",
		  valueField: "key",
		  displayField: "value",
		  editable: false,
		  triggerAction: "all",
		  value: "like",
		  width: 100,
		  store: new Ext.data.ArrayStore({
			fields: ["key","value"],
			data: filter_ops
		  })
        }),
		" ",
		new Ext.form.TextField({
		  id: id+"value",
		  width: 100,
		  subscribe: store.on("load", function(){
		    fbar.get(id+"value").setValue("");
		  }),
		  listeners: {
			specialkey: function(field, event) {
			  if (event.getKey() == event.ENTER) {
				if (store.baseParams.filter) store.baseParams.filter += "||";
				store.baseParams.filter += fbar.get(id+"field").getValue()+"|"+fbar.get(id+"type").getValue()+"|"+field.getValue()
				store.reload();
		  } } }
        }),
		"  ",
		new Ext.form.Label({
		  id: id+"label",
		  subscribe: store.on("load", function(){
			fbar.get(id+"label").setText(show_filter(store.baseParams.filter));
		  })
		}),
		"-",
		new Ext.Button({
		  id: id+"clear",
		  text: "X",
		  subscribe: store.on("load", function(){
		    var obj = fbar.get(id+"clear");
			if (store.baseParams.filter) obj.enable(); else obj.disable();
		  }),
		  handler: function(){
			store.setBaseParam("filter","");
			store.reload();
		  }
		})
    ]});

	var limit = new Ext.form.ComboBox({
		width: 50,
		listWidth: 50,
		mode: "local",
		value: parseInt(model.limit),
		triggerAction: "all",
		displayField: "id",
		valueField: "id",
		autoSelect: false,
		store: new Ext.data.ArrayStore({
		  fields: ["id"],
		  data: [["5"], ["10"], ["20"], ["25"], ["50"], ["75"], ["100"]]
		})
	});
	
	var bbar = new Ext.PagingToolbar({
	  store: store,
	  pageSize: parseInt(model.limit),
      displayInfo: true,
	  beforePageText: remove_trans("Page"),
	  afterPageText: remove_trans("of {0}"),
      displayMsg: remove_trans("{0} - {1} of {2} &nbsp;&nbsp;"),
      emptyMsg: remove_trans("No entries found."),
      items:[
	    "-",
		limit,
        "-", {
		  pressed: false,
		  enableToggle:true,
		  text: remove_trans("Show details"),
		  cls: "x-btn-text-icon details",
		  toggleHandler: function(btn, pressed){
			var view = grid.getView();
			view.showPreview = pressed;
			view.refresh();
		}},
        "-", {
		  text: remove_trans("Filter"),
		  cls: "x-btn-text-icon filter",
		  enableToggle: true,
		  pressed: model.filter,
		  handler: function(){
			fbar.setVisible(!fbar.isVisible());
			fbar.get(id+"value").focus();
		}},
        "-", {
		  text: remove_trans("Reset view"),
		  cls: "x-btn-text-icon resetview",
		  handler: function(){
			Ext.state.Manager.set(checksum,null);
			Ext.state.Manager.set("filter"+checksum,null);
			Ext.state.Manager.set("limit"+checksum,null);
			document.location = document.location;
		}},
        "-", {
		  text: remove_trans("PmWiki"),
		  cls: "x-btn-text-icon markup",
		  handler: function(){
		    var markup = " folder="+model.folder;
			markup += " view="+model.view;
			markup += " limit="+store.baseParams.limit;
			if (store.sortInfo.field) {
			  markup += " orderby=\""+store.sortInfo.field+" "+store.sortInfo.direction+"\"";
			}
			if (store.baseParams.filter) {
			  markup += " filter='"+store.baseParams.filter+"'";

			}
			var fields_hidden = [];
			var col_model = grid.getColumnModel();
			for (var i in model.fields) {
			  var number = col_model.findColumnIndex(i);
			  if (col_model.isHidden(number) && !model.fields[i].is_hidden) {
			    fields_hidden.push(i);
			  }
			}
			if (fields_hidden.length>0) markup += " fields_hidden="+fields_hidden.join(",");
			if (store.baseParams.groupBy) markup += " groupby="+store.baseParams.groupBy;
			
		    prompt("PmWiki markup:", "(:get_table"+markup+" :)");
		}},
        "-", {
		  text: remove_trans("URL"),
		  cls: "x-btn-text-icon url",
		  handler: function(){
		    prompt("URL:", get_url());
		}},
        "-", {
		  text: remove_trans("Clear grouping"),
		  id: id+"clear_group",
		  cls: "x-btn-text-icon grouping",
		  subscribe: store.on("load", function(){
		    var obj = bbar.get(id+"clear_group");
			if (store.groupField) obj.enable(); else obj.disable();
		  }),
		  handler: function(){
			store.clearGrouping();
		}},
        "-", {
		  cls: "x-btn-icon fullscreen",
		  tooltip: remove_trans("Full screen"),
		  hidden: (parent.window==window),
		  tooltipType: "title",
		  handler: function(){
			window.open(get_url());
		}}
	]});

	var get_url = function() {
	  var url = document.location.href.substr(0,document.location.href.indexOf("?"));
	  url += "?folder="+escape(model.folder);
	  url += "&view="+escape(model.view);
	  url += "&limit="+escape(store.baseParams.limit);
	  if (store.sortInfo.field) {
		url += "&orderby="+escape(store.sortInfo.field+" "+store.sortInfo.direction);
	  }
	  if (store.baseParams.filter) {
		url += "&filter="+escape(store.baseParams.filter);
	  }
	  var fields_hidden = [];
	  var col_model = grid.getColumnModel();
	  for (var i in model.fields) {
		var number = col_model.findColumnIndex(i);
		if (col_model.isHidden(number) && !model.fields[i].is_hidden) {
		  fields_hidden.push(i);
		}
	  }
	  if (fields_hidden.length>0) url += "&fields_hidden="+escape(fields_hidden.join(","));
	  if (store.baseParams.groupBy) url += "&groupby="+escape(store.baseParams.groupBy);
	  return url;
	}

    var grid = new Ext.grid.GridPanel({
	  renderTo: Ext.getBody(),
      store: store,
      columns: columns,
	  frame: true,
	  autoWidth: true,
	  autoHeight: parent.window!=window,
	  height: document.body.clientHeight,
	  stripeRows: true,
	  disableSelection: true,
	  stateful: true,
	  stateId: checksum,
	  title: html_escape(model.title),
      view: new Ext.grid.GroupingView({
        forceFit: true,
        enableRowBody: true,
        showPreview: false,
        groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})',
        getRowClass: function(record, rowIndex, p, store) {
	      if (this.showPreview) {
            p.body = '<p style="padding:5px; border-bottom:2px solid #D0D0D0; border-top:1px solid #D0D0D0;">';
			for (var i in record.data) {
			  if (record.data[i]=="") continue;
			  var value = render_details(record.data[i], model.fields[i]);
			  if (i == "id") {
			    value = "<a href='"+base+"index.php?folder="+model.folder+"&view="+model.view+"&item="+record.data[i]+"'>"+value+"</a>";
			  }
			  p.body += "<b>" + model.fields[i].header + ": </b> " + value + "<br>";
			}
			p.body += "</p>";
            return "x-grid3-row-expanded";
          }
          return "x-grid3-row-collapsed";
      }}),
	  tbar: fbar,
	  bbar: bbar
    });
	
    limit.on("specialkey", function(limit, event) {
	  if (event.getKey() != event.ENTER) return;
	  var value = parseInt(limit.getRawValue());
	  store.baseParams.limit = value;
	  bbar.pageSize = value;
	  bbar.doLoad(bbar.cursor);
	});
    limit.on("select", function(limit) {
	  var value = parseInt(limit.getValue());
	  store.baseParams.limit = value;
	  bbar.pageSize = value;
	  bbar.doLoad(bbar.cursor);
	});
	
	var resize = function() { resize_iframe(grid); };
	grid.getView().on("refresh", resize);
	fbar.on("show", resize);
	fbar.on("hide", resize);
	
	if (parent.window==window) window.onresize = function() {
	  grid.setSize(document.body.clientWidth, document.body.clientHeight);
	};
  });
}

function sgs_ajax(func, params, callback) {
  if (typeof(navigator.onLine)!="undefined" && !navigator.onLine) return;
  var xmlhttp = window.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
  xmlhttp.open("POST", "../../../ajax.php?function="+escape(func), true);
  xmlhttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
  xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
	  var result = xmlhttp.responseText;
	  try {
		if (xmlhttp.status==200 && result!="") {
		  var js = Ext.decode(result);
	      if (callback!=null) callback(js);
	    } else sys_alert("Ajax Error: "+func+" "+xmlhttp.status+" "+xmlhttp.statusText+"\n"+(result!=""?result:"no result"));
	  } catch (e) {
		if (result.length > 0) {
		  if (e == "SyntaxError: parseJSON") e = "";
		  if (e) error = e.name+": "+e.message; else error = "";
		  sys_alert("[2] Error: "+error+"\n"+result+"\n"+func+"\n"+Ext.encode(e));
  } } } }
  xmlhttp.send(Ext.encode(params));
}

function html_escape(str) {
  return str.replace(/</g,"&lt;").replace(/>/g,"&gt;");
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

function resize_iframe(grid) {
  if (grid == null) return;
  if (parent.window == window) {
	grid.syncSize();
	grid.setHeight(document.body.clientHeight);
	return;
  }
  var objs = parent.document.getElementsByTagName("iframe");
  for (var i=0; i<objs.length; i++) {
    if (objs[i].src != document.location) continue;
    objs[i].style.height = grid.getHeight()+"px";
  }
}