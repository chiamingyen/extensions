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

// Translations implemented by Sophie Lee.

var agent = navigator.userAgent.toLowerCase();
if (agent.indexOf("konqueror")!=-1) agent = "konqueror";
  else if (agent.indexOf("safari")!=-1) agent = "safari";
  else if (agent.indexOf("opera")!=-1) agent = "opera";
  else if (agent.indexOf("firefox")!=-1) agent = "firefox";
  else if (agent.indexOf("msie")!=-1) agent = "msie";

window.onerror=handleErr;
  
if (agent=="msie" || agent=="safari") { // cursor keys only in keydown
  document.onkeydown = keypress;
} else document.onkeypress=keypress;

sys = new function() {
  this.initData = "";
  this.autoRecalc = true;
  this.cols = 13;
  this.rows = 30;
  this.row0 = 0;
  this.col0 = 0;

  this.currRow = 0;
  this.currCol = 0;
  
  this.colMinWidth = "90px";
  this.showColumnGroups = false;
  this.allowPaging = true;
  this.isWriteable = true;
  this.isMouseDown = 0;
  this.isShiftDown = 0;
  
  this.saveMethod = function(){ save("js"); };
  this.closeMethod = null;
  
  this.multiRange = new Array();
  this.clipboardRow = 0;
  this.clipboardCol = 0;
  this.clipboardRange = new Array();
  this.clipboardMode = "";
  this.active = "";

  this.tab = String.fromCharCode(9);  
  this.marks = new Array();
  this.view = "values";
  this.strings = strings;
  
  /* format: cells[row][column][type]
  type:
  0 = formula
  1 = styling
  2 = cell description
  3 = current value
  */
  this.cells = new Array();
  this.registerFuncs = "";
  
  this.getObj = function(id) {
	return document.getElementById(id);
  };
  this.loadSheetFromUrl = function(location) {
	var script = document.createElement("script");
	script.type = "text/javascript";
	if (window.addEventListener) {
	  window.addEventListener("load",function() { load("// dbCells"); },false);
	} else {
      script.onreadystatechange = function() { if (this.readyState == 'loaded') load("// dbCells"); }
	}
	script.src = location;
	document.getElementsByTagName("head").item(0).appendChild(script);
  }
}

function trans(key) {
  if (sys.strings[key]) return sys.strings[key]; else return "["+key+"]";
}

function keypress(event) {
  if (!sys.getObj("value") || sys.active=="code") return true;
  var event = (event)?event:window.event;
  var keyCode = getmykey(event);
  var ret = true;
  if (sys.active=="position") {
    if (keyCode==13) {
	  gotoCell(sys.getObj("field").value);
	  sys.active="content";
	  ret = false;
	}
	return ret;
  }
  if (sys.active=="dimensions") {
    if (keyCode==13) {
	  sys.rows=parseInt(sys.getObj("rows").value);
	  sys.cols=parseInt(sys.getObj("cols").value);
	  sys.active="content";
	  display();
	  ret = false;
	}
	return ret;
  }
  if (sys.getObj("styling").disabled) {
    sys.getObj("focus").focus(); // avoid window scrolling
    sys.active = "content";
	var shift = event.shiftKey;
	var alt = event.altKey;
	var ctrl = event.ctrlKey;

	if ((ctrl && (keyCode==88 || keyCode==120)) || (shift && keyCode==46)) { // ctrl-x = cut || shift-del = cut
	  cutcopy("cut","#FFDDDD");
  	  ret=false;
	} else if ((ctrl && (keyCode==67 || keyCode==99)) || ctrl && keyCode==45) { // ctrl-c = copy || ctrl-ins = copy
	  cutcopy("copy","#DDDDFF");
  	  ret=false;
	} else if (((ctrl && (keyCode==86 || keyCode==118)) || (shift && keyCode==45)) && sys.clipboardMode!="") { // ctrl-v = paste || shift-ins = paste
	  paste();
  	  ret=false;
	}

	if (shift && !sys.isShiftDown) {
	  highlightRange(sys.multiRange,"cell");
	  sys.multiRange = new Array(sys.currRow,sys.currCol,sys.currRow,sys.currCol);
	  sys.isShiftDown = 1;
	} else if (!alt && !ctrl && !shift && sys.multiRange.length>0 && keyCode!=46) {
	  highlightRange(sys.multiRange,"cell");
	  sys.multiRange = new Array();
	  sys.isShiftDown=0;
	  sys.isMouseDown=0;
	}
	
	if (!alt && !ctrl && keyCode!=0) {
	  if (keyCode==33) { // page up
		if (sys.currRow-10 <= sys.row0 && sys.currRow > 10) {
		  sys.row0 -= sys.rows;
		  display();
	      mouseoverCell(sys.currRow-10,sys.currCol);
		  scrollDown();
		} else {
	      mouseoverCell(sys.currRow>=10?sys.currRow-10:0,sys.currCol);
		  scrollUp();
		}
  		ret=false;
	  } else if (keyCode==34) { // page down
	    if (sys.currRow+10 >= sys.row0+sys.rows) {
		  sys.row0 += sys.rows;
		  display();
	      mouseoverCell(sys.currRow+10,sys.currCol);
		  scrollUp();
		} else {
	      mouseoverCell(sys.currRow+10,sys.currCol);
		  scrollDown();
		}
  		ret=false;
	  } else if (keyCode==36) { // home
		if (sys.currCol!=sys.col0) {
		  mouseoverCell(sys.currRow,sys.col0);
		} else if (sys.currCol+1 >= sys.cols) {
		  sys.col0 -= sys.cols;
		  display();
	      mouseoverCell(sys.currRow,sys.currCol-sys.cols);
		} else if (sys.currRow > sys.row0) {
		  mouseoverCell(sys.row0,sys.currCol);
		} else if (sys.currRow+1 > sys.rows) {
		  sys.row0 -= sys.rows;
		  display();
	      mouseoverCell(sys.currRow-sys.rows,sys.currCol);
		}
		scrollLeft();
		ret=false;
	  } else if (keyCode==35) { // end
	    if (sys.currCol!=(sys.col0+sys.cols-1)) {
	      mouseoverCell(sys.currRow,sys.col0+sys.cols-1);
		} else {
		  sys.col0 += sys.cols;
		  display();
	   	  mouseoverCell(sys.currRow,sys.currCol+sys.cols);
		}
		scrollRight();
		ret=false;
	  } else if (keyCode==39 || keyCode==9) { // right
	    goRight();
		ret=false;
	  } else if (keyCode==40) { // down
	    goDown();
		ret=false;
	  } else if (keyCode==38 && sys.currRow>-2) { // up
	    goUp();
		ret=false;
	  } else if (keyCode==37 && sys.currCol>-1) { // left
	    goLeft();
		ret=false;
	  } else if (!shift && keyCode==46 && sys.isWriteable && confirm(trans("Really empty cell(s) ?"))) {
	    removeSelectedCell();
		ret=false;
	  } else if ((keyCode<33 || keyCode>40) && keyCode!=46 && keyCode!=45 &&
	    keyCode!=16 && keyCode!=17 && keyCode!=18)  {
	    editCell(sys.currRow,sys.currCol,keyCode);
		if (keyCode==13) ret=false;
	  }
	}
	if (sys.isShiftDown) {
	  highlightRange(sys.multiRange,"cell");
      sys.multiRange[2] = sys.currRow;
      sys.multiRange[3] = sys.currCol;
      highlightRange(sys.multiRange,"cell_highlight_over");
	}
  } else {
	if (keyCode==13) {
	  saveCell();
  	  ret=false;
	} else if (keyCode==27) {
	  cancelCell();
  	  ret=false;
	}
  }
  return ret;
}

function goLeft() {
  if (sys.currCol <= sys.col0 && sys.currCol > 0) {
	sys.col0 -= sys.cols;
	display();
	mouseoverCell(sys.currRow,sys.currCol-1);
	scrollRight();
  } else {
	mouseoverCell(sys.currRow,sys.currCol-1);
	scrollLeft();
  }
}

function goUp() {
  if (sys.currRow <= sys.row0 && sys.currRow > 0) {
	sys.row0 -= sys.rows;
	display();
	mouseoverCell(sys.currRow-1,sys.currCol);
	scrollDown();
  } else {
	mouseoverCell(sys.currRow-1,sys.currCol);
	scrollUp();
  }
}

function goRight() {
  if (sys.currCol+1 >= sys.col0+sys.cols) {
	sys.col0 += sys.cols;
	display();
	mouseoverCell(sys.currRow,sys.currCol+1);
	scrollLeft();
  } else {
	mouseoverCell(sys.currRow,sys.currCol+1);
	scrollRight();
  }
}

function goDown() {
  if (sys.currRow+1 >= sys.row0+sys.rows) {
	sys.row0 += sys.rows;
	display();
	mouseoverCell(sys.currRow+1,sys.currCol);
	scrollUp();
  } else {
	mouseoverCell(sys.currRow+1,sys.currCol);
	scrollDown();
  }
}

function display() {
  sys.marks = new Array();
  sys.isMouseDown=0;
  var scrollX = 0; // keep scrolling states
  var scrollY = 0;
  if (sys.getObj("content")) {
    scrollX = sys.getObj("content").scrollLeft;
    scrollY = sys.getObj("content").scrollTop;
  }
  var out = "<div id='header' class='header'><table cellpadding='0' cellspacing='0' style='width:100%;'><tr><td nowrap>";
  out += "<textarea id='focus' onfocus='this.blur();'></textarea>";
  out += "&nbsp;";
  if (top.window!=window) {
    out += "<a href='#' onclick='window.frameElement.parentNode.style.height = window.frameElement.parentNode.offsetHeight+60+\"px\"; return false;'>(+)</a> - ";
    out += "<a href='#' onclick='window.frameElement.parentNode.style.height = window.frameElement.parentNode.offsetHeight-60+\"px\"; return false;'>(&ndash;)</a> - ";
	if (sys.closeMethod) out += "<a href='#' onclick='if (confirm(\"Really close ?\")) sys.closeMethod(); return false;' accesskey='q'>"+trans("Close")+"</a> - ";
  }
  if (sys.isWriteable) {
    out += "<a href='#' onclick='if (confirm(\""+trans("Really close without saving changes ?")+"\")) load(sys.initData); return false;' accesskey='n'>"+trans("New")+"</a> - ";
    out += "<a href='#' onclick='loadCode(); return false;' accesskey='l'>"+trans("Load")+"</a> - ";
    if (sys.saveMethod) out += "<a href='#' onclick='sys.saveMethod(); return false;' accesskey='s'>"+trans("Save")+"</a> - ";
  }
  out += "<a href='#' onclick='print(); return false;' accesskey='p'>"+trans("Print")+"</a> - ";
  if (sys.allowPaging) {
    if (sys.col0-sys.cols>=0 || sys.row0-sys.rows>=0) {
      out += "<a href='#' onclick='sys.row0=0; sys.col0=0; sys.currCol=0; sys.currRow=0; scroll(); display(); return false;'>"+trans("Home")+"</a> - ";
	}
    if (sys.col0-sys.cols>=0) {
      out += "<a href='#' onclick='sys.col0 -= sys.cols; sys.currCol -= sys.cols; display(); return false;'>"+trans("&lt;&lt;")+"</a> - ";
    } else out += "&lt;&lt; - ";
    out += "<a href='#' onclick='sys.col0 += sys.cols; sys.currCol += sys.cols; display(); return false;'>"+trans("&gt;&gt;")+"</a> - ";
    if (sys.row0-sys.rows>=0) {
      out += "<a href='#' onclick='sys.row0 -= sys.rows; sys.currRow -= sys.rows; display(); return false;'>"+trans("Up")+"</a> - ";
    } else out += trans("Up")+" - ";
    out += "<a href='#' onclick='sys.row0 += sys.rows; sys.currRow += sys.rows; display(); return false;'>"+trans("Down")+"</a> -&nbsp;";
    out += "</td><td nowrap>";
    out += "<input type='text' value='"+sys.cols+"' style='width:28px; text-align:center;' id='cols' onfocus='sys.active=\"dimensions\";' onblur='sys.active=\"content\";'> x ";
    out += "<input type='text' value='"+sys.rows+"' style='width:28px; text-align:center;' id='rows' onfocus='sys.active=\"dimensions\";' onblur='sys.active=\"content\";'> - ";
  }
  out += "<input type='text' value='' title='"+trans("Position")+"' id='field' style='width:30px; text-align:center;' onfocus='sys.active=\"position\";' onblur='sys.active=\"content\";' accesskey='g'> -&nbsp;";
  out += "</td><td nowrap style='width:100%;'>";
  out += "<iframe src='about:blank' id='multiline'></iframe>";
  out += "<input type='text' value='' title='"+trans("Formula")+"' id='value' style='width:67%;' disabled onmouseover='previewValue();' onkeyup='previewValue();'> ";
  out += "<input type='text' title='"+trans("Style")+"' value='' id='styling' style='width:33%;' disabled onmouseover='previewValue();' onkeyup='previewValue();'> ";
  out += "</td><td nowrap>";
  if (sys.isWriteable) {
    out += "&nbsp; <input type='button' value='"+trans("Save")+"' id='save' onclick='saveCell();' disabled>&nbsp;";
    out += "<input type='button' value='"+trans("X")+"' id='cancel' onclick='cancelCell();' disabled> - ";
  } else out += "&nbsp; ";
  if (sys.view=="values") {
    if (sys.isWriteable && agent=="msie") {
      out += "<a href='#' onclick='sys.autoRecalc=!sys.autoRecalc; display(); return false;' accesskey='m'>"+(sys.autoRecalc?trans("Auto")+"-"+trans("Refresh"):trans("Manual"))+"</a>";
      if (!sys.autoRecalc) out += " <a href='#' onclick='display(); return false;' accesskey='r'>"+trans("Refresh")+"</a>";
	  out += " - ";
	}
    out += "<a href='#' onclick='sys.view=\"formulas\"; display(); return false;'>"+trans("Values")+"</a> - ";
  } else if (sys.view=="formulas") {
    out += "<a href='#' onclick='sys.view=\"styles\"; display(); return false;'>"+trans("Formulas")+"</a> - ";
  } else {
    out += "<a href='#' onclick='sys.view=\"values\"; display(); return false;'>"+trans("Styles")+"</a> - ";
  }
  out += "<a href='#' onclick='sys.view=\"values\"; display(); return false;' accesskey='1'></a>";
  out += "<a href='#' onclick='sys.view=\"formulas\"; display(); return false;' accesskey='2'></a>";
  out += "<a href='#' onclick='sys.view=\"styles\"; display(); return false;' accesskey='3'></a>";
  out += "<a href='#' onclick='manual(); return false;' accesskey='h'>"+trans("Help")+"</a> - ";

  // You are not allowed to remove or alter the About button and/or the copyright.
  out += "<a href='#' onclick='alert(\"Simple Spreadsheet is an open source component created by Thomas Bley\\nand licensed under GNU GPL v2.\\n\\nSimple Spreadsheet is copyright 2006-2007 by Thomas Bley.\\nTranslations implemented by Sophie Lee.\\n\\nMore information and documentation at http://www.simple-groupware.de/\\n\"); return false;'>"+trans("About")+"</a>&nbsp;";
  out += "</td></tr></table></div>";
  var style = "";
  if (agent=="msie") style = "style='height:expression((document.body.clientHeight-40)+\"px\");'";
  out += "<div id='content' "+style+"><table id='table' cellspacing='0'>";
  out += "<tr>";
  if (sys.showColumnGroups) {
    out += "<th id='-2_-1' ondblclick='editCell(-2,-1);' onclick='mouseoverCell(-2,-1);'>"+htmlEscape(showCell(-2,-1,0),true)+"</th>";
    for (var i=sys.col0; i<sys.cols+sys.col0; i++) {
	  var colGroupTitle = showCell(-2,i,0);
	  if (colGroupTitle && (!sys.marks[-2] || !sys.marks[-2][i])) {
	    var colSpan = parseInt(getCellStyle(-2,i,"colspan"));
	    if (colSpan) {
		  if (!sys.marks[-2]) sys.marks[-2] = new Array();
		  for (var s=i+1; s<i+colSpan; s++) sys.marks[-2][s] = new Array(-2,i);
		}
	    out += "<th id='-2_"+i+"' ondblclick='editCell(-2,"+i+",0);' onclick='mouseoverCell(-2,"+i+");' colspan='"+colSpan+"'><div style='"+htmlEscape(formatStyle(getCells(-2,i,1),"_"),false)+"'>"+htmlEscape(colGroupTitle,true)+"</div></th>";
	  } else if ((!sys.marks[-2] || !sys.marks[-2][i])) {
	    out += "<th id='-2_"+i+"' ondblclick='editCell(-2,"+i+",0);' onclick='mouseoverCell(-2,"+i+");'>&nbsp;</th>";
	  }
	}
  }
  out += "</tr>";
  out += "<tr><th id='-1_-1' ondblclick='editCell(-1,-1,0);' onclick='mouseoverCell(-1,-1);'>"+htmlEscape(showCell(-1,-1,0),true)+"</th>";
  for (var i=sys.col0; i<sys.cols+sys.col0; i++) {
	var colTitle = showCell(-1,i,0);
    out += "<th id='-1_"+i+"' ondblclick='editCell(-1,"+i+",0);' onclick='mouseoverCell(-1,"+i+");'><div style='"+htmlEscape(formatStyle(getCells(-1,i,1),"_"),false)+"'>"+(colTitle?htmlEscape(colTitle,true)+" - ":"")+buildColName(i)+"</div></th>";
  }
  out += "</tr>";
  var lastIndex = -1;
  var noRowTtitle = false;
  for (var row=sys.row0; row<sys.rows+sys.row0; row++) {
    out += "<tr>";
	var rowTitle = showCell(row,-1,0);
	out += "<th id='"+row+"_-1' ondblclick='editCell("+row+",-1,0);' onclick='mouseoverCell("+row+",-1);'><div style='"+htmlEscape(formatStyle(getCells(row,-1,1),"_"),false)+"'>"+(rowTitle?htmlEscape(rowTitle,true)+"<br>":"")+(row+1)+"</div></th>";
    for (var col=sys.col0; col<sys.cols+sys.col0; col++) {
	  if (sys.view=="values") {
	    if (!sys.marks[row] || !sys.marks[row][col]) {
	      var style = getCells(row,col,1);
		  var value = showCell(row,col,0);
		  var colSpan = parseInt(getCellStyle(row,col,"colspan"));
		  if (colSpan) {
		    if (!sys.marks[row]) sys.marks[row] = new Array();
		    for (var s=col+1; s<col+colSpan; s++) sys.marks[row][s] = new Array(row,col);
	  	  }
		  var rowSpan = parseInt(getCellStyle(row,col,"rowspan"));
		  if (rowSpan) {
		    for (var s=row+1; s<row+rowSpan; s++) {
		      if (!sys.marks[s]) sys.marks[s] = new Array();
		      sys.marks[s][col] = new Array(row,col);
		    }
	  	  }
		  value = formatValue(value,style);
		  style = htmlEscape(formatStyle(style,value),false);
          out += "<td "+(rowSpan?"rowspan='"+rowSpan+"'":"")+" "+(colSpan?"colspan='"+colSpan+"'":"")+" id='"+row+"_"+col+"' onmousedown='mousedown("+row+","+col+");' onmouseup='mouseup();' onmouseover='buildStatus("+row+","+col+");' onclick='mouseoverCell("+row+","+col+");' onclick='mouseoverCell("+row+","+col+");' ondblclick='editCell("+row+","+col+",0);'><div style='"+style+"'>"+htmlEscape(value,true)+"</div></td>";
		}
	  } else if (sys.view=="formulas") {
        out += "<td id='"+row+"_"+col+"' onmouseover='buildStatus("+row+","+col+");' onclick='mouseoverCell("+row+","+col+");' onclick='mouseoverCell("+row+","+col+");' ondblclick='editCell("+row+","+col+",0);'>"+htmlEscape(getCells(row,col,0),true)+"</td>";
	  } else {
        out += "<td id='"+row+"_"+col+"' onmouseover='buildStatus("+row+","+col+");' onclick='mouseoverCell("+row+","+col+");' onclick='mouseoverCell("+row+","+col+");' ondblclick='editCell("+row+","+col+",0);'>"+htmlEscape(getCells(row,col,1),true)+"</td>";
	  }
    }
    out += "</tr>";
  }
  out += "<tr id='spacer'><th class='empty'></th>";
  for (var i=sys.col0; i<sys.cols+sys.col0; i++) out += "<th class='empty'><img src='' style='width:"+sys.colMinWidth+"; height:0px;'></th>";
  out += "</tr>";
  out += "</table></div>";
  out += "<div class='footer' id='footer' onmouseover='sys.getObj(\"status\").innerHTML=\"\";'>&nbsp;";
  if (sys.isWriteable) {
    out += trans("Insert")+": ";
    out += "<a href='#' onclick='insertRow(); return false;'>"+trans("Row")+"</a> - ";
    out += "<a href='#' onclick='insertColumn(); return false;'>"+trans("Column")+"</a> - ";
    out += trans("Delete")+": ";
    out += "<a href='#' onclick='if (confirm(\""+trans("Really delete entire row ?")+"\")) deleteRow(); return false;'>"+trans("Row")+"</a> - ";
    out += "<a href='#' onclick='if (confirm(\""+trans("Really delete entire column ?")+"\")) deleteColumn(); return false;'>"+trans("Column")+"</a> - ";
  }
  out += trans("Sort")+": ";
  out += "<a href='#' onclick='sort(1); return false;'>"+trans("asc.")+"</a> - ";
  out += "<a href='#' onclick='sort(0); return false;'>"+trans("desc.")+"</a> - ";

  if (sys.isWriteable) {
    out += "<a href='#' onclick='cutcopy(\"cut\",\"#FFDDDD\"); return false;' title='Alt-x' accesskey='x'>"+trans("Cut")+"</a> - ";
    out += "<a href='#' onclick='cutcopy(\"copy\",\"#DDDDFF\"); return false;' title='Alt-c' accesskey='c'>"+trans("Copy")+"</a> - ";
    out += "<a href='#' onclick='paste(); return false;' title='Alt-v' accesskey='v'>"+trans("Paste")+"</a> - ";
    out += "<a href='#' onclick='if (confirm(\""+trans("Really empty cell(s) ?")+"\")) removeSelectedCell(); return false;' title='Alt-e' accesskey='e'>"+trans("Empty")+"</a> - ";
  }
  out += trans("Export")+": ";
  out += "<a href='#' onclick='save(\"js\"); return false;'>"+trans("JS")+"</a> - ";
  out += "<a href='#' onclick='save(\"csv\"); return false;'>"+trans("CSV")+"</a> - ";
  out += "<a href='#' onclick='save(\"tsv\"); return false;'>"+trans("TSV")+"</a>";
  out += "</div>";
  out += "<div id='status' class='status'></div>";
  sys.getObj("data").innerHTML = out;
  sys.getObj("content").scrollLeft = scrollX;
  sys.getObj("content").scrollTop = scrollY;
  mouseoverCell(sys.currRow,sys.currCol);
  if (sys.clipboardMode!="") {
    var color = "#DDDDFF"
    if (sys.clipboardMode=="cut") color = "#FFDDDD";
    if (sys.clipboardRange.length>0) {
      highlightRange(sys.clipboardRange,"",color);
	} else {
      var obj = resolveCell(sys.clipboardRow,sys.clipboardCol);
	  if (obj) obj.style.backgroundColor = color;
	}
  }
  sys.getObj("focus").focus();
}

function showHeaderFooter(show) {
  if (sys.isWriteable) return;
  if (show) {
    sys.getObj("content").style.top="23px";
    sys.getObj("content").style.bottom="18px";
    sys.getObj("header").style.display="";
    sys.getObj("footer").style.display="";
    sys.getObj("status").style.display="";
  } else {
    sys.getObj("content").style.top="0px";
    sys.getObj("content").style.bottom="0px";
    sys.getObj("header").style.display="none";
    sys.getObj("footer").style.display="none";
    sys.getObj("status").style.display="none";
  }
}

function previewValue() {
  var value = sys.getObj("value").value;
  if (!sys.getObj("value").disabled && 
	 (value.length>25 || value.indexOf("\\n")!=-1 || value.indexOf("html:")==0)) {
    sys.getObj("multiline").style.display = "inline";
	//sys.getObj("content").style.overflow = "hidden"; // needed for invisible cursor
    if (value.indexOf("html:")==0 && sys.getObj("multiline").src.indexOf("tinymce/index.html")==-1) {
	  sys.getObj("multiline").src = "tinymce/index.html";
	} else if (value.indexOf("html:")!=0 && sys.getObj("multiline").src.indexOf("editor.htm")==-1) {
	  sys.getObj("multiline").src = "editor.htm";
	} else if (sys.getObj("multiline").contentWindow.update) {
	  sys.getObj("multiline").contentWindow.update();
	}
  }
  previewField();
}
function previewField() {
  var obj2 = resolveCell(sys.currRow,sys.currCol);
  value = previewCell(sys.getObj("value").value,0);
  value = formatValue(value,sys.getObj("styling").value);
  var style = htmlEscape(formatStyle(sys.getObj("styling").value,value),false);
  if (sys.currRow == -1) {
    if (value!="") value += " - ";
    value += buildColName(sys.currCol);
  } else if (sys.currCol == -1) {
    if (value!="") value += "\\n";
    value += sys.currRow+1;
  }
  value = htmlEscape(value,true);
  var val = "<div "+(style?"style='"+style+"'":"")+">"+value+"</div>";
  obj2.innerHTML = val;
  if (val.indexOf("<img")!=-1) obj2.style.height = obj2.offsetHeight+"px";
  if (sys.currRow == -1 || sys.currCol == -1) highlightCellHeader(sys.currRow,sys.currCol);
}
function previewMultiline(value) {
  sys.getObj("value").value = value.replace(/\n/g,"\\n").replace(/\r/g,"");
  previewField();
}

function log(val) {
  if (!sys.getObj("tconsole")) {
    var obj = document.createElement("textarea");
	obj.id = "tconsole";
	document.body.appendChild(obj);
  }
  sys.getObj("tconsole").value += (sys.getObj("tconsole").value.split("\n").length)+": "+val+"\n";
}

function manual() {
  window.open("manual.html","manual","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=800,height=600");
}

function isWritable(style) {
  if (style.indexOf("readonly:true")==-1 && sys.isWriteable) return true;
  return false; 
}

// convert e.g. B3 to (1,2)
function getCells(row,col,item) {
  if (sys.cells[row] && sys.cells[row][col] && sys.cells[row][col].length==4) return sys.cells[row][col][item];
  return "";
}
function getCellsR(row,col,item) {
  var arr = resolveCellArray(row,col);
  return getCells(arr[0],arr[1],item);
}
function getCells2(col_str,row,item,calls) {
  var col_num = colstrToColnum(col_str);
  var val_data = showCell(row-1,col_num,calls+1);
  var val = parseFloat(val_data);
  if (val == val_data) return val; else return val_data;
}
function getCells3(col_str,row,col_str2,row2,item,calls) {
  var result = new Array();
  var col_num = colstrToColnum(col_str);
  var col_num2 = colstrToColnum(col_str2);
  if (col_num > col_num2 || row > row2) return new Array("error");
  for (var i_row=row; i_row<=row2; i_row++) {
    for (var i_col=col_num; i_col<=col_num2; i_col++) {
	  var val_data = showCell(i_row-1,i_col,calls+1);
	  var val = parseFloat(val_data);
	  if (val == val_data) result[result.length] = val; else result[result.length] = val_data;
	}
  }
  return result;
}
function getCellStyle(row,col,style) {
  var span = getCells(row,col,1);
  var re = new RegExp(style+":(.*?);","i");
  if (p = re.exec(span)) span = parseInt(p[1]); else span = 0;
  return span;
}
function setCells(row,col,item,value) {
  if (!sys.cells[row]) sys.cells[row] = new Array();
  if (!sys.cells[row][col]) sys.cells[row][col] = new Array("","","","");
  if (sys.cells[row][col][item]!=value) {
    sys.cells[row][col][item] = value;
	return true;
  } else return false;
}
function setCellsR(row,col,item,value) {
  var arr = resolveCellArray(row,col);
  return setCells(arr[0],arr[1],item,value);
}

function load(code) {
  var cols = sys.cols;
  var rows = sys.rows;
  var registerFuncs = "";
  var dbCells = [];
  if (code.indexOf("dbCells")==-1) {
    if (code.indexOf(sys.tab)==-1) sys.cells = loadCSV(code); else sys.cells = loadTSV(code);
	if (!sys.cells) return;
  } else {
    try { eval(check_js(code)); }
    catch (err) {
      alert(trans("Error loading data:")+" "+err);
	  return;
    }
	sys.cols = parseInt(cols);
	sys.rows = parseInt(rows);

	if (registerFuncs) {
	  sys.registerFuncs = registerFuncs;
	  for (var i=0; i<sys.registerFuncs.length; i++) {
		window[sys.registerFuncs[i]] = eval(check_js(sys.registerFuncs[i]));
	} }
    // process 1 dimensional dbCells to 2 dimensional data
    sys.cells = new Array();
    try {
      for (var i=0; i<dbCells.length; i++) {
	    if (!dbCells[i]) continue;
        if (!sys.cells[dbCells[i][1]]) sys.cells[dbCells[i][1]] = new Array();
		// Row, Col, Value, Style, Tooltip, Evaluated Value
        sys.cells[dbCells[i][1]][dbCells[i][0]] = new Array(dbCells[i][2],dbCells[i][3],dbCells[i][4],"");
      }
    }
    catch (err) {
      alert(trans("Error parsing data:")+" "+err+" i="+i+" cells=\""+dbCells[i]+"\"");
	  return;
    }
  }
  if (typeof sys.cells[-2] != "undefined") sys.showColumnGroups = true; else sys.showColumnGroups = false;
  sys.active = "content";
  sys.getObj("source").style.display = "none";
  sys.getObj("data").style.display = "inline";
  display();
}
function cancelLoad() {
  sys.active = "content";
  sys.getObj("source").style.display = "none";
  sys.getObj("data").style.display = "inline";
  display();
}
function loadCSV(code) {
  code = code.replace(/([^,])""([^,])/g,"$1#quot#$2").replace(/\r/g,"");
  code = code.replace(/(".*?")/g, function(str){ return str.replace(/,/g,"#comm#"); });
  code = code.replace(/([^,])""([^,])/g,"$1#quot#$2");
  code = code.split("\n");
  for (var i=0; i<code.length; i++) {
    code[i] = "[\""+code[i].replace(/^"|"\s*$/g,"").replace(/"?,"?/g,"\",\"")+"\"]";
	if (i != code.length-1) code[i] += ",";
  }
  code = "dbCells = [\n"+code.join("\n")+"\n];\n";
  code = code.replace(/#comm#/g,",").replace(/#quot#/g,"\\\"");
  return loadDbCells(code);
}
function loadTSV(code) {
  code = code.replace(/^\/\/.*?\n/g,"").replace(/\r/g,"").split("\n");
  for (var i=0; i<code.length; i++) {
    code[i] = "[\""+code[i].replace(/"/g,"\\\"").replace(/	/g,"\",\"")+"\"]"; // tab
	if (i != code.length-1) code[i] += ",";
  }
  code = "dbCells = [\n"+code.join("\n")+"\n];\n";
  code = code.replace(/\[""\],\n/g,"");
  return loadDbCells(code);
}
function loadDbCells(code) {
  var cols = sys.cols;
  var rows = sys.rows;
  var dbCells = [];
  try { eval(check_js(code)); }
  catch (err) {
    alert(trans("Error loading data:")+" "+err);
	return false;
  }
  sys.cols = parseInt(cols);
  sys.rows = parseInt(rows);
  // process 1 dimensional dbCells to 2 dimensional data
  sys.cells = new Array();
  try {
    for (var i=0; i<dbCells.length; i++) {
      for (var i2=0; i2<dbCells[i].length; i2++) {
	    if (dbCells[i][i2]) {
          if (!sys.cells[i-1]) sys.cells[i-1] = new Array();
          sys.cells[i-1][i2] = new Array(dbCells[i][i2]+"","","","");
  } } } }
  catch (err) {
    alert(trans("Error parsing data:")+" "+err+" i="+i+" cells=\""+dbCells[i]+"\"");
	return false;
  }
  return sys.cells;
}

function save(format) {
  sys.active = "code";
  sys.getObj("status").innerHTML = "";
  var out = "";
  if (format == "csv") out = cellsToCSV();
    else if (format == "tsv") out = cellsToTSV();
	else out = cellsToJS();
  sys.getObj("data").style.display = "none";
  sys.getObj("source").style.display = "inline";
  sys.getObj("code").value = out;
}

function cellsToJS() {
  var out = "";
  out += "dbCells = [\n";
  for (var i=-2; i<sys.cells.length; i++) {
    if (sys.cells[i]) {
      for (var i2=-1; i2<sys.cells[i].length; i2++) {
	    if (!sys.cells[i][i2]) continue;
		if (sys.cells[i][i2][0]=="" && sys.cells[i][i2][1]=="") continue;
        out += "  ["+i2+","+i+",\""+strescape(sys.cells[i][i2][0])+"\",\""+strescape(sys.cells[i][i2][1])+"\"";
		if (sys.cells[i][i2][2] && isNaN(sys.cells[i][i2][2])) out += ",\""+strescape(sys.cells[i][i2][2])+"\"";
		  else if (sys.cells[i][i2][2]) out += ","+sys.cells[i][i2][2];
		out += "], // "+buildColName(i2)+(i+1)+"\n";
      }
	  out += "\n";
	}
  }
  if (sys.cells.length>0) out = out.substring(0,out.length-2)+"\n];\n"; else out += "];\n";
  if (sys.rows!=30) out += "rows = "+sys.rows+";\n";
  if (sys.cols!=13) out += "cols = "+sys.cols+";\n";
  if (sys.registerFuncs) {
    out += "\nvar registerFuncs = [";
    for (var i=0; i<sys.registerFuncs.length; i++) {
	  out += "\""+sys.registerFuncs[i]+"\",";
    }
    out = out.substring(0,out.length-1)+"];\n\n";
    for (var i=0; i<sys.registerFuncs.length; i++) {
	  out += eval(check_js(sys.registerFuncs[i]))+"\n\n";
    }
    out = out.substring(0,out.length-2);
  }
  return out;
}

function cellsToCSV() {
  var out = "";
  for (var i=-1; i<sys.cells.length; i++) {
    if (sys.cells[i]) {
      for (var i2=0; i2<sys.cells[i].length; i2++) {
	    if (sys.cells[i][i2] && sys.cells[i][i2][0]) {
		  if (i==-1 && sys.cells[i][i2][2]) {
		    out += "\""+sys.cells[i][i2][2].replace(/\\/g,"\\\\").replace(/"/g,"\"\"")+"\",";
		  } else {
		    var val = showCell(i,i2);
		    if (isNaN(val)) {
		      out += "\""+val.replace(/\\/g,"\\\\").replace(/"/g,"\"\"")+"\",";
		    } else out += val+",";
		  }
		} else out += ",";
      }
      out = out.substring(0,out.length-1)+"\n";
	} else out += ",\n";
  }
  return out;
}

function cellsToTSV() {
  var out = "// "+trans("Copy / paste this code to other spreadsheet applications (e.g. Excel)")+":\n\n";
  for (var i=-1; i<sys.cells.length; i++) {
    if (sys.cells[i]) {
      for (var i2=0; i2<sys.cells[i].length; i2++) {
	    if (sys.cells[i][i2] && sys.cells[i][i2][0]) {
	      if (i==-1 && sys.cells[i][i2][2]) {
		    out += sys.cells[i][i2][2]+sys.tab;
		  } else {
		    out += showCell(i,i2)+sys.tab;
		  }
		} else out += sys.tab;
      }
      out = out.substring(0,out.length-1)+"\n";
	} else if (i==-1) out += sys.tab+"\n";
  }
  return out;
}

function ajax(func, params, callback) {
  var xmlhttp = window.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
  xmlhttp.open("POST", "../../../ajax.php?function="+escape(func), true);
  xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
	  var result = xmlhttp.responseText;
	  try {
		if (xmlhttp.status==200 && result!="") {
		  callback(JSON.parse(result));
	    } else alert("Error: "+func+" "+xmlhttp.status+" "+xmlhttp.statusText+"\n"+(result!=""?result:"no result"));
	  } catch (e) {
		if (result.length > 0) {
		  if (e) error = e.name+": "+e.message; else error = "";
		  alert("Error: "+error+"\n"+result+"\n"+func);
  } } } }
  xmlhttp.send(JSON.stringify(params));
}
function ajax_sync(func, params) {
  var xmlhttp = window.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
  xmlhttp.open("POST", "../../../ajax.php?function="+escape(func), false);
  xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
  xmlhttp.send(JSON.stringify(params));

  var result = xmlhttp.responseText;
  try {
	if (xmlhttp.status==200 && result!="") return JSON.parse(result);
    alert("Error: "+func+" "+xmlhttp.status+" "+xmlhttp.statusText+"\n"+(result!=""?result:"no result"));
  } catch (e) {
	if (result.length > 0) {
	  if (e) error = e.name+": "+e.message; else error = "";
	  alert("Error: "+error+"\n"+result+"\n"+func);
  } }
  return null;
}

function print_r(value) {
  alert(JSON.stringify(value));
}

function loadCode() {
  sys.active = "code";
  sys.getObj("status").innerHTML = "";
  sys.getObj("data").style.display = "none";
  sys.getObj("source").style.display = "inline";
  sys.getObj("code").value = "";
}
function print() {
  var out = document.getElementById("content").innerHTML;
  out = out.replace(/(<\/tr>|<\/table>)/g,"$1\n");
  var obj = window.open("","print","toolbar=no,location=no,directories=no,status=no,menubar=yes,scrollbars=yes,resizable=yes,copyhistory=no,width=800,height=600");
  obj.document.write("<html><head><link media='all' href='print.css' rel='stylesheet' type='text/css'/></head>\n");
  obj.document.write("<body>\n"+out+"\n</body></html>");
  obj.document.close();
  obj.window.print();
}

function insertRow() {
  for (var i=sys.cells.length-1; i>=sys.currRow; i--) {
    if (sys.cells[i]) {
      for (var i2=0; i2<sys.cells[i].length; i2++) {
	    if (!sys.cells[i][i2]) continue;
		// TODO2 change formulas ?
		if (!sys.cells[i+1]) sys.cells[i+1] = new Array();
		sys.cells[i+1][i2] = sys.cells[i][i2];
		sys.cells[i][i2] = "";
  } } }
  display();
}
function deleteRow() {
  var row0 = sys.currRow;
  var row1 = sys.currRow;
  if (sys.multiRange.length>0) {
    var cRange = getMultiRange(sys.multiRange);
	row0 = cRange[0];
	row1 = cRange[2];
  }
  for (var row=row0; row<=row1; row++) {
    var rowCount = sys.cells.length;
    for (var i=row0; i<rowCount; i++) {
      if (sys.cells[i]) {
		if (!sys.cells[i+1]) sys.cells[i+1] = new Array();
		var colCount=Math.max(sys.cells[i].length,sys.cells[i+1].length);
        for (var i2=0; i2<colCount; i2++) {
	      if (!sys.cells[i+1][i2] && sys.cells[i][i2]) sys.cells[i][i2] = "";
	      if (!sys.cells[i+1][i2] && !sys.cells[i][i2]) continue;
		  // TODO2 change formulas ?
		  sys.cells[i][i2] = sys.cells[i+1][i2];
		  sys.cells[i+1][i2] = "";
  } } } }
  display();
}
function insertColumn() {
  for (var i=0; i<sys.cells.length; i++) {
    if (sys.cells[i]) {
      for (var i2=sys.cells[i].length-1; i2>=sys.currCol; i2--) {
	    if (!sys.cells[i][i2]) continue;
		// TODO2 change formulas ?
		sys.cells[i][i2+1] = sys.cells[i][i2];
		sys.cells[i][i2] = "";
  } } }
  display();
}
function deleteColumn() {
  var col0 = sys.currCol;
  var col1 = sys.currCol;
  if (sys.multiRange.length>0) {
    var cRange = getMultiRange(sys.multiRange);
	col0 = cRange[1];
	col1 = cRange[3];
  }
  for (var col=col0; col<=col1; col++) {
    for (var i=0; i<sys.cells.length; i++) {
      if (sys.cells[i]) {
        for (var i2=col0; i2<sys.cells[i].length; i2++) {
	      if (!sys.cells[i][i2+1] && sys.cells[i][i2]) sys.cells[i][i2] = "";
	      if (!sys.cells[i][i2+1] && !sys.cells[i][i2]) continue;
		  // TODO2 change formulas ?
		  sys.cells[i][i2] = sys.cells[i][i2+1];
		  sys.cells[i][i2+1] = "";
  } } } }
  display();
}

function scroll() {
  scrollDown();
  scrollUp();
  scrollRight();
  scrollLeft();
}
function scrollUp() {
  var obj = resolveCell(sys.currRow,sys.currCol);
  var posY = findPosY(obj);
  if (posY < (sys.getObj("content").scrollTop+100) && sys.getObj("content").scrollTop > (posY-100)) {
    sys.getObj("content").scrollTop = posY-100;
  }

  var posX = findPosX(obj);
  if (posX < sys.getObj("content").scrollLeft+200 && sys.getObj("content").scrollLeft > (posX-150)) {
    sys.getObj("content").scrollLeft = posX-150;
  }
}
function scrollDown() {
  var obj = resolveCell(sys.currRow,sys.currCol);
  var posY = findPosY(obj);
  var height = return_height();
  if (obj && (posY+obj.offsetHeight+100) > height) {
    var newHeight = posY+obj.offsetHeight+100-height;
    if (newHeight > sys.getObj("content").scrollTop) sys.getObj("content").scrollTop = newHeight;
  }

  var posX = findPosX(obj);
  var width = return_width();
  if (obj && (posX+obj.offsetWidth+100) > width) {
    var newWidth = posX+obj.offsetWidth+100-width;
    if (newWidth > sys.getObj("content").scrollLeft) sys.getObj("content").scrollLeft = newWidth;
  }
}
function scrollLeft() {
  scrollUp();
}
function scrollRight() {
  scrollDown();
}

function removeSelectedCell() {
  if (sys.multiRange.length>0) {
    var cRange = getMultiRange(sys.multiRange);
	for (var row=cRange[0]; row<=cRange[2]; row++) {
	  for (var col=cRange[1]; col<=cRange[3]; col++) {
	    if (isWritable(getCellsR(row,col,1))) {
	      setCellsR(row,col,0,"");
	      setCellsR(row,col,1,"");
    	  if (!sys.autoRecalc) {
		    var obj2 = resolveCell(row,col);
		    obj2.innerHTML = "<div>&nbsp;</div>";
  }	} } } } else {
    removeCell(sys.currRow,sys.currCol);
    if (!sys.autoRecalc && isWritable(getCellsR(sys.currRow,sys.currCol,1))) {
      var obj2 = resolveCell(sys.currRow,sys.currCol);
      obj2.innerHTML = "<div>&nbsp;</div>";
	}
  }
  if (sys.autoRecalc) display();
}
function cutcopy(mode,color) {
  if (!sys.isWriteable) return;
  if (sys.clipboardRange.length>0) {
    highlightRange(sys.clipboardRange,"","");
  } else {
    if (sys.clipboardMode) {
	  var obj = resolveCell(sys.clipboardRow,sys.clipboardCol);
	  if (obj) obj.style.backgroundColor = "";
	}
  }
  sys.clipboardMode = mode;
  sys.clipboardRow = sys.currRow;
  sys.clipboardCol = sys.currCol;
  sys.clipboardRange = getMultiRange(sys.multiRange);
  if (sys.multiRange.length>0) {
    highlightRange(sys.multiRange,"",color);
  } else {
    var obj = resolveCell(sys.currRow,sys.currCol);
	if (obj) obj.style.backgroundColor = color;
  }
}
function paste() {
  if (sys.clipboardRange.length>0) {
    var sRange = getMultiRange(sys.clipboardRange);
    var cRange = new Array(sys.currRow,sys.currCol,sys.currRow+sRange[2]-sRange[0],sys.currCol+sRange[3]-sRange[1]);
    if (sys.multiRange.length>0 && (sys.multiRange[0]!=sys.multiRange[2] || sys.multiRange[1]!=sys.multiRange[3])) {
	  cRange = getMultiRange(sys.multiRange);
	}
    var rOffset = 0;
	var cOffset = 0;
	if ((cRange[0] >= sRange[0] && cRange[1] >= sRange[1]) || (cRange[0] > sRange[0] && cRange[1] < sRange[1])) {
	  for (var row=cRange[2]; row>=cRange[0]; row--) {
	    rOffset = (row - cRange[0]) % (sRange[2]-sRange[0]+1);
	    for (var col=cRange[3]; col>=cRange[1]; col--) {
	      cOffset = (col - cRange[1]) % (sRange[3]-sRange[1]+1);
          copyCell(sRange[0] + rOffset, sRange[1] + cOffset, row, col);
		  var obj = resolveCell(sRange[0] + rOffset, sRange[1] + cOffset);
		  if (obj) obj.style.backgroundColor = "";
	    }
	  }
	} else {
	  for (var row=cRange[0]; row<=cRange[2]; row++) {
	    rOffset = (row - cRange[0]) % (sRange[2]-sRange[0]+1);
	    for (var col=cRange[1]; col<=cRange[3]; col++) {
	      cOffset = (col - cRange[1]) % (sRange[3]-sRange[1]+1);
          copyCell(sRange[0] + rOffset, sRange[1] + cOffset, row, col);
	      var obj = resolveCell(sRange[0] + rOffset, sRange[1] + cOffset);
		  if (obj) obj.style.backgroundColor = "";
	    }
	  }
	}
	if (sys.clipboardMode=="cut") {
	  for (var row=sRange[0]; row<=sRange[2]; row++) {
	    for (var col=sRange[1]; col<=sRange[3]; col++) {
		  if ((row < cRange[0] || row > cRange[2]) || 
		      (col < cRange[1] || col > cRange[3])) removeCell(row,col);
	    }
	  }
	  sys.clipboardMode = "";
	}
  } else {
 	if (sys.multiRange.length>0 && (sys.multiRange[0]!=sys.multiRange[2] || sys.multiRange[1]!=sys.multiRange[3])) {
	  cRange = getMultiRange(sys.multiRange);
	  for (var row=cRange[2]; row>=cRange[0]; row--) {
	    for (var col=cRange[3]; col>=cRange[1]; col--) {
	      copyCell(sys.clipboardRow,sys.clipboardCol,row,col);
		}
	  }
      if (sys.clipboardMode=="cut" && (sys.clipboardRow < cRange[0] || sys.clipboardRow > cRange[2] || 
		  sys.clipboardCol < cRange[1] || sys.clipboardCol > cRange[3])
	  ) {
	    removeCell(sys.clipboardRow,sys.clipboardCol);
      }
	} else {
      copyCell(sys.clipboardRow,sys.clipboardCol,sys.currRow,sys.currCol);
      if (sys.clipboardMode=="cut" && (sys.clipboardRow!=sys.currRow || sys.clipboardCol!=sys.currCol)) {
	    removeCell(sys.clipboardRow,sys.clipboardCol);
      }
	}
	if (sys.clipboardMode=="cut") sys.clipboardMode = "";
  }
  display();
}
function sortNum(val) {
  if (isNaN(val)) return val.charAt(0).toLowerCase().charCodeAt(0);
  return parseFloat(val);
}
function sortCells(a,b) {
  if (a[sys.currCol] && b[sys.currCol]) {
    if (a[sys.currCol][3]=="") return 1;
    if (b[sys.currCol][3]=="") return -1;
    a = sortNum(a[sys.currCol][3]);
	b = sortNum(b[sys.currCol][3]);
	return a-b;
  }
  return (a[sys.currCol]?1:-1);
}
function sort(asc) {
  if (sys.multiRange.length>0) {
    var cRange = getMultiRange(sys.multiRange);
	var cCells = new Array();
	for (var row=cRange[0]; row<=cRange[2]; row++) {
	  for (var col=cRange[1]; col<=cRange[3]; col++) {
		if (!cCells[row]) cCells[row] = new Array();
		if (sys.cells[row] && sys.cells[row][col]) cCells[row][col] = sys.cells[row][col];
	  }
	}
    if (asc) cCells = cCells.sort(sortCells); else cCells = cCells.sort(sortCells).reverse();
	for (var row=cRange[0]; row<=cRange[2]; row++) {
	  for (var col=cRange[1]; col<=cRange[3]; col++) {
	    if (!sys.cells[row]) sys.cells[row] = new Array();
		sys.cells[row][col] = cCells[row][col];
	  }
	}
  } else {
    if (asc) sys.cells = sys.cells.sort(sortCells); else sys.cells = sys.cells.sort(sortCells).reverse();
  }
  display();
}

function buildStatus(row,col) {
  var status = "";
  var colTitle = getCellsR(-1,col,0);
  var colGroupTitle = getCellsR(-2,col,0);
  var cellTitle = getCellsR(row,col,2);
  if (cellTitle && row != -1) status += cellTitle+" - ";
  if (colTitle) {
	var colTitleLong = getCellsR(-1,col,2);
	if (colGroupTitle) status += colGroupTitle+": ";
	status += (colTitleLong?colTitleLong:colTitle)+" - ";
  } else if (colGroupTitle) {
	status += colGroupTitle+" - ";
  }
  var rowTitle = getCellsR(row,-1,0);
  if (rowTitle) {
	var rowTitleLong = getCellsR(row,-1,2);
	status += (rowTitleLong?rowTitleLong:rowTitle)+" - ";
  }
  sys.getObj("status").innerHTML = htmlEscape(status+buildColName(col)+(row+1),false);

  if (sys.isMouseDown) {
    markCell(row,col);
    highlightRange(sys.multiRange,"cell");
    sys.multiRange[2] = row;
    sys.multiRange[3] = col;
    highlightRange(sys.multiRange,"cell_highlight_over");
  }
}
function highlightRange(multiRange,classname,att) {
  if (multiRange.length==0) return false;
  var cRange = getMultiRange(multiRange);
  for (var row=cRange[0]; row<=cRange[2]; row++) {
	for (var col=cRange[1]; col<=cRange[3]; col++) {
	  obj = resolveCell(row,col);
	  if (obj && classname) obj.className = classname;
	    else if (!classname && obj) obj.style.backgroundColor = att;
	}
  }
}
function getMultiRange(multiRange) {
  if (multiRange.length==0) return new Array();
  var row1 = multiRange[0]>multiRange[2]?multiRange[2]:multiRange[0];
  var row2 = multiRange[0]>multiRange[2]?multiRange[0]:multiRange[2];
  var col1 = multiRange[1]>multiRange[3]?multiRange[3]:multiRange[1];
  var col2 = multiRange[1]>multiRange[3]?multiRange[1]:multiRange[3];
  return new Array(row1,col1,row2,col2);
}
function mousedown(row,col) {
  if (sys.getObj("styling").disabled) {
	document.onmousedown=new Function("return false;");
	document.onselectstart=new Function("return false;");
	sys.isMouseDown=1;
	highlightRange(sys.multiRange,"cell");
    sys.multiRange = new Array(row,col,row,col);
	highlightRange(sys.multiRange,"cell_highlight_over");
  }
}
function mouseup() {
  if (sys.getObj("styling").disabled) {
	document.onmousedown="";
    document.onselectstart="";
	sys.isMouseDown=0;
  }
}
function markCell(row,col) {
  highlightCell(row,col,"cell_highlight_over");
  highlightCellHeader(row,col);
  sys.currRow = row;
  sys.currCol = col;
  sys.getObj("value").value = getCellsR(row,col,0);
  sys.getObj("styling").value = getCellsR(row,col,1);
  sys.getObj("field").value = buildColName(col)+(row+1);
}
function mouseoverCell(row,col) {
  var obj = sys.getObj("value");
  if (!sys.getObj("value").disabled && obj.value.charAt(0)=="=") {
	var ins = buildColName(col)+(row+1);
	if (obj.selectionStart) {
	  var tmp = obj.selectionStart;
	  obj.value = obj.value.substring(0,obj.selectionStart)+ins+obj.value.substring(obj.selectionStart);
	  obj.selectionStart = tmp+ins.length;
	  obj.selectionEnd = tmp+ins.length;
	  obj.focus();
	} else if (document.selection) {
	  obj.value += ins;
	}
	previewValue();
  } else {
	if (!sys.getObj("value").disabled) saveCell();
	if (row==sys.currRow && col==sys.currCol && sys.getObj("field").value) {
	  editCell(row,col,0);
	} else {
	  markCell(row,col);
	  buildStatus(row,col);
} } }

function highlightCell(row,col,className) {
  var obj = resolveCell(sys.currRow,sys.currCol);
  if (obj) obj.className = "cell";
  obj = resolveCell(row,col);
  if (obj) obj.className = className;
}
function highlightCellHeader(row,col) {
  var obj = resolveCell(-1,sys.currCol);
  if (obj) obj.className = "border";
  obj = resolveCell(sys.currRow,-1);
  if (obj) obj.className = "border";
  var sRow = -1;
  if (row<-1) sRow = -2;
  obj = resolveCell(sRow,col);
  if (obj) obj.className = "border_highlight";
  if (row>=-1) {	
    obj = resolveCell(row,-1);
    if (obj) obj.className = "border_highlight";
  }
}
function showCell(row,col,calls) {
  if (typeof calls == "undefined") calls = 0;
  value = getCells(row,col,0);
  if (calls>25) { // avoid endless recursion
    value = "undefined";
  } else if (value!="" && value.charAt(0)=="=") {
    var cmd = "";
    var token = "";
    var openToken = "";
    var sequence = "";
    for (var i=0; i<value.length; i++) {
      token = value.charAt(i);
	  sequence += token;
      if (((token=="'" || token=="\"") && openToken=="") || i==value.length-1) {
	    if (openToken=="") {
		  sequence = sequence.replace(/([A-Z]+)([0-9]+):([A-Z]+)([0-9]+)/g,"getCells3('$1',$2,'$3',$4,0,"+(calls+1)+")");
		  sequence = sequence.replace(/([A-Z]+)([0-9]+)/g,"getCells2('$1',$2,0,"+(calls+1)+")");
	    }
	    openToken = token;
	    cmd += sequence;
	    sequence = "";
	  } else if (token==openToken && value.charAt(i-1)!="\\") {
	    openToken = "";
	    cmd += sequence;
	    sequence = "";
	  }
    }
    try { eval("value"+check_js(cmd)); }
    catch (err) {
      alert(trans("Error evaluating")+" "+buildColName(col)+(row+1)+" \""+value+"\"\n\n"+err+"\n\n"+trans("value")+cmd);
    }
  }
  if (sys.cells[row] && sys.cells[row][col]) sys.cells[row][col][3] = value;
  return value;
}
function previewCell(value,calls) {
  if (value=="" || value.charAt(0)!="=") return value;
  if (calls>25) return "undefined"; // avoid endless recursion
  var cmd = "";
  var token = "";
  var openToken = "";
  var sequence = "";
  for (var i=0; i<value.length; i++) {
    token = value.charAt(i);
	sequence += token;
    if (((token=="'" || token=="\"") && openToken=="") || i==value.length-1) {
	  if (openToken=="") {
		sequence = sequence.replace(/([A-Z]+)([0-9]+):([A-Z]+)([0-9]+)/g,"getCells3('$1',$2,'$3',$4,0,"+(calls+1)+")");
		sequence = sequence.replace(/([A-Z]+)([0-9]+)/g,"getCells2('$1',$2,0,"+(calls+1)+")");
	  }
	  openToken = token;
	  cmd += sequence;
	  sequence = "";
	} else if (token==openToken && value.charAt(i-1)!="\\") {
	  openToken = "";
	  cmd += sequence;
	  sequence = "";
	}
  }
  var nvalue = "";
  try { eval("nvalue"+check_js(cmd)); }
  catch (err) {}
  if (nvalue!=null && (nvalue+"").length>0) return nvalue; else return value;
}
function gotoCell(pos) {
  var re = new RegExp("([@A-Z]+)([0-9]+)","g");
  if (p = re.exec(pos)) {
    var col = colstrToColnum(p[1]);
	var row = p[2]-1;
    if (col>=-1 && row>=0) {
	  sys.getObj("focus").focus();
	  if (col!=sys.currCol || row!=sys.currRow) {
		if (col >= sys.cols || row <= sys.rows) {
		  sys.col0 = col - (col % sys.cols);
		  sys.row0 = row - (row % sys.rows);
		  display();
		}
	    mouseoverCell(row,col);
		scroll();
	  }
	  return;
	}
  }
  alert(trans("Invalid cell."));
}
function editCell(row,col,keyCode) {
  sys.active = "content";
  if (!sys.isWriteable) return;
  if (!sys.getObj("styling").disabled) cancelCell();
  
  highlightCell(row,col,"cell_highlight");
  highlightCellHeader(row,col);
  sys.currRow = row;
  sys.currCol = col;
  
  if (isWritable(getCellsR(row,col,1))) {
    sys.getObj("value").disabled = false;
  }
  sys.getObj("styling").disabled = false;
  sys.getObj("save").disabled = false;
  sys.getObj("cancel").disabled = false;
  if (sys.getObj("cols")) sys.getObj("cols").disabled = true;
  if (sys.getObj("rows")) sys.getObj("rows").disabled = true;
  sys.getObj("field").disabled = true;
  sys.getObj("styling").value = getCellsR(row,col,1);
  if (keyCode > 32 && agent=="firefox" && !sys.getObj("value").disabled) {
  	sys.getObj("value").value = String.fromCharCode(keyCode);
  } else {
    sys.getObj("value").value = getCellsR(row,col,0);
  }
  if (!sys.getObj("value").disabled) {
    sys.getObj("value").focus(); 
  } else {
    sys.getObj("styling").focus(); 
  }
}
function copyCell(row,col,cRow,cCol) {
  if (!isWritable(getCellsR(cRow,cCol,1))) {
    alert(trans("Cannot edit: cell is marked as readonly."));
	return;
  }
  if (row!=cRow || col!=cCol) {
    setCells(cRow,cCol,0,getCellsR(row,col,0));
    setCells(cRow,cCol,1,getCellsR(row,col,1));
  }
}
function saveCell() {
  var changed = setCellsR(sys.currRow,sys.currCol,0,sys.getObj("value").value);
  var changed2 = setCellsR(sys.currRow,sys.currCol,1,sys.getObj("styling").value);
  if (changed || changed2) {
    if (!sys.autoRecalc) {
	  disableEdit();
  	  previewValue();
    } else display();
  } else cancelCell();
}
function disableEdit() {
  sys.getObj("value").blur();
  highlightCell(sys.currRow,sys.currCol,"cell_highlight_over");
  sys.getObj("value").disabled = true;
  sys.getObj("styling").disabled = true;
  sys.getObj("save").disabled = true;
  sys.getObj("cancel").disabled = true;

  if (sys.getObj("cols")) sys.getObj("cols").disabled = false;
  if (sys.getObj("rows")) sys.getObj("rows").disabled = false;
  sys.getObj("field").disabled = false;

  sys.getObj("multiline").style.display = "none";
  sys.getObj("content").style.overflow = "auto"; // needed for invisible cursor
}
function cancelCell() {
  disableEdit();
  sys.getObj("value").value = getCellsR(sys.currRow,sys.currCol,0);
  sys.getObj("styling").value = getCellsR(sys.currRow,sys.currCol,1);
  previewValue();
}
function removeCell(row,col) {
  if (!isWritable(getCellsR(row,col,1))) {
    alert(trans("Cannot edit: cell is marked as readonly."));
	return;
  }
  setCellsR(row,col,0,"");
  setCellsR(row,col,1,"");
}
function resolveCell(row,col) {
  var obj = sys.getObj(row+"_"+col);
  if (!obj && sys.marks[row] && sys.marks[row][col]) {
	obj = sys.getObj(sys.marks[row][col][0]+"_"+sys.marks[row][col][1]);
  }
  return obj;
}
function resolveCellArray(row,col) {
  var obj = sys.getObj(row+"_"+col);
  if (!obj && sys.marks[row] && sys.marks[row][col]) {
    var arr = sys.marks[row][col];
    row = arr[0];
	col = arr[1];
  }
  return new Array(row,col);
}

function getSize(width) {
  var myWidth = 0, myHeight = 0;
  if (typeof(window.innerWidth) == 'number') {
    myWidth = window.innerWidth;
    myHeight = window.innerHeight;
  } else if( document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
    //IE 6+ in 'standards compliant mode'
    myWidth = window.document.documentElement.clientWidth;
    myHeight = window.document.documentElement.clientHeight;
  } else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
    // IE 4 compatible
    myWidth = document.body.clientWidth;
    myHeight = document.body.clientHeight;
  } else if (self.screen.height && self.screen.width) {
    myWidth = self.screen.width;
    myHeight = self.screen.height;
  }
  if (width) return myWidth; else return myHeight;
}
function return_height() {
  return getSize(0);
}
function return_width() {
  return getSize(1);
}
function getmykey(event) {
  if (typeof(event)=="undefined") return window.event.keyCode;
  if (event.keyCode==0 && event.charCode!=0) return event.charCode;
  if (event.keyCode==0) return event.which;
  return event.keyCode;
}
function findPosY(obj) {
  if (!obj) return 0;
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
  if (!obj) return 0;
  var curtop = 0;
  if (obj.offsetParent) {
	while (obj.offsetParent) {
	  curtop += obj.offsetLeft;
	  obj = obj.offsetParent;
	}
  } else if (obj.x) curtop += obj.x;
  return curtop;
}
function strescape(str) {
  return str.replace(/\\/g,"\\\\").replace(/"/g,"\\\"");
}
function htmlEscape(str,fill) {
  if (str==null || str.length == 0) return (fill?"&nbsp;":"");
  str = new String(str);
  if (str.indexOf("html:")==0) {
    str = check_html(str.substring(5));
  } else {
    str = str.replace(/</g,"&lt;").replace(/>/g,"&gt;"); // escape special characters
    str = str.replace(/'/g,"&#039;").replace(/"/g,"&quot;"); // quotes
    str = str.replace(/([^\s$]+@[^\s$]+)/g,"<a href='mailto:$1'>$1</a>"); // make emails appear as a link
    str = str.replace(/img:((http[s]?:\/\/[^\s$]+\.(gif|jpg|png))|(graphs\.php\?.*?$))/g,"<img src='$1'>"); // display images
    str = str.replace(/(^|[^'])(http[s]?:\/\/[^\s$]+)/g,"$1<a target='_blank' href='$2'>$2</a>"); // make http(s)://... appear as link
    str = str.replace(/\\n/g,"<br>");
  }
  return str;
}
function check_js(code) {
  var code_a = code.replace(/\\"|\\'|'.*?'|".*?"/g,"");
  pattern = /top|parent|window|document|this|eval|alert|settimeout|xmlhttp|ajax|check|sys\./i;
  if (pattern.test(code_a)) return "";
  return code;
}
function check_html(code) {
  var tags = /<\/?(applet|area|base|body|button|embed|form|frame|head|html|input|link|map|meta|object|script|select|style|textarea|title|!)[^>]*>/gi;
  var attributes = /<[^>]+( on|behavior|expression\(|javascript:)[^>]+>/gi;
  if (tags.test(code) || attributes.test(code)) return htmlEscape(code,false);
  return code;
}

function handleErr(msg,url,l) {
  alert(trans("There was an error on this page.")+"\n\n"+trans("Error:")+" "+msg+"\n"+trans("Url")+": "+url+"\n"+trans("Line:")+" "+l);
  return true;
}

// convert 26 to AA, 0 to A
function buildColName(num) {
  var val = "";
  var result = "";
  if (num>=702) {
    val = (Math.floor(num/676)-1)%26;
	result += String.fromCharCode(val+65);
  }
  if (num>=26) {
    val = (Math.floor(num/26)-1)%26;
	result += String.fromCharCode(val+65);
  }
  result += String.fromCharCode(num%26+65);
  return result;
}
//convert AA to 26
function colstrToColnum(col_str) {
  var col_num = 0;
  for (var i=0; i<col_str.length; i++) {
    col_num += 26*(col_str.length-i-1) + (col_str.charCodeAt(i)-65);
  }
  return col_num;
}

function formatStyle(style,value) {
  if (style.indexOf("text-align:")==-1 && value.length>0 && !isNaN((value+"").replace(/[$%,]/g,"").replace("&euro;",""))) {
    style += "; text-align:right; white-space:nowrap;";
	if (value<0 && style.indexOf("color:")==-1) style += "color:#FF0000;";
  }
  return style.replace(/(format|readonly|colspan|rowspan):.*?;|expression|behavior/ig,"");
}
function formatValue(value,style) {
  if (style.indexOf("format:")!=-1) {
    if (style.indexOf("format:euro")!=-1) value = formatNumber(value)+" &euro;";
      else if (style.indexOf("format:dollar")!=-1) value = "$"+formatNumber(value);
      else if (style.indexOf("format:percent")!=-1) value = (value*100).toFixed(2)+"%";
      else if (style.indexOf("format:number")!=-1) value = formatNumber(value);
      else if (style.indexOf("format:datefulltime")!=-1) value = formatDateFullTime(value);
      else if (style.indexOf("format:datetime")!=-1) value = formatDateTime(value);
      else if (style.indexOf("format:datefull")!=-1) value = formatDateFull(value);
      else if (style.indexOf("format:date")!=-1) value = formatDate(value);
      else if (style.indexOf("format:time")!=-1) value = formatTime(value);
  } else if (!isNaN(value) && value!=0) {
    value = formatNumber(value).replace(/\.00$/,"");
  }
  return value;
}
function formatDate(value) {
  if (isNaN(new Date(value).getHours())) {
    value = value.replace(/(\d{1,2})\.(\d{1,2})\.(\d{2,4})/,"$2/$1/$3");
  }
  var dateObj = new Date(value);
  return (dateObj.getMonth()+1)+"/"+dateObj.getDate()+"/"+dateObj.getFullYear();
}
function formatDateFull(value) {
  if (isNaN(new Date(value).getHours())) {
    value = value.replace(/(\d{1,2})\.(\d{1,2})\.(\d{2,4})/,"$2/$1/$3");
  }
  var dateObj = new Date(value);
  var months = new Array(trans("January"),trans("February"),trans("March"),trans("April"),trans("May"),trans("June"),trans("July"),trans("August"),trans("September"),trans("October"),trans("November"),trans("December"));
  var days = new Array(trans("Sunday"),trans("Monday"),trans("Tuesday"),trans("Wednesday"),trans("Thursday"),trans("Friday"),trans("Sunday"));
  return days[dateObj.getDay()]+", "+months[dateObj.getMonth()]+" "+dateObj.getDate()+" "+dateObj.getFullYear();
}
function formatTime(value) {
  if (isNaN(new Date(value).getHours())) {
    value = value.replace(/\d+\.\d+\.\d+/,"$2/$1/$3");
	if (value.length<12) value = "01/01/01 "+value;
  }
  var dateObj = new Date(value);
  hour = dateObj.getHours();
  var a = "am";
  if (hour > 11) a = "pm";
  if (hour > 12) hour -= 12;
  if (hour == 0) hour = 12;
  return hour+":"+dateObj.getMinutes()+":"+dateObj.getSeconds()+" "+a;
}
function formatDateTime(value) {
  return formatDate(value)+" "+formatTime(value);
}
function formatDateFullTime(value) {
  return formatDateFull(value)+", "+formatTime(value);
}
function formatNumber(val) {
  var output = "";
  var sign = "";
  if (val < 0) {
	sign = "-";
	val *= -1;
  }
  number = Math.floor((val-0).toFixed(2))+"";
  if (number.length > 3) {
    var mod = number.length%3;
    var output = (mod==0?"":(number.substring(0,mod)));
    for (i=0; i<Math.floor(number.length/3); i++) {
      if (mod==0 && i==0) {
        output += number.substring(mod+3*i,mod+3*i+3);
      } else {
        output += ","+number.substring(mod+3*i,mod+3*i+3);
      }
    }
  } else output += number;
  if (val-number != 0) {
    output += Math.abs(val-number).toFixed(2).replace("0.",".");
  }   
  return sign+output;
}
function param(str) {
  if (str.join) str = str.join(",");
  if (str.replace) return escape(str);
  return str;
}
function graph(type,title,data,keys,xtitle,ytitle,width,height) {
  var url = "img:graphs.php?type="+param(type)+"&title="+param(title);
  url += "&data="+param(data)+"&keys="+param(keys);
  if (typeof xtitle != "undefined") {
    url += "&xtitle="+param(xtitle);
  }
  if (typeof ytitle != "undefined") {
    url += "&ytitle="+param(ytitle);
  }
  if (typeof width != "undefined") {
    url += "&width="+width;
  }
  if (typeof height != "undefined") {
    url += "&height="+height;
  }
  return url;
}
function graph2(type,title,data,data2,keys,xtitle,ytitle,width,height) {
  var url = "img:graphs.php?type="+param(type)+"&title="+param(title);
  url += "&data="+param(data)+"&data2="+param(data2)+"&keys="+param(keys);
  if (typeof xtitle != "undefined") {
    url += "&xtitle="+param(xtitle);
  }
  if (typeof ytitle != "undefined") {
    url += "&ytitle="+param(ytitle);
  }
  if (typeof width != "undefined") {
    url += "&width="+width;
  }
  if (typeof height != "undefined") {
    url += "&height="+height;
  }
  return url;
}
/*
function param2(str) {
  if (str.join) str = str.join(",");
  if (str.replace) return escape(str.replace(/,/g,"|"));
  return str;
}
function graph(type,title,data,keys,xtitle,ytitle,width,height) {
  if (type=="line") type = "lc";
    else if (type=="pie") type = "p3";
    else if (type=="bar") type = "bvg";
    else if (type=="scatter") type = "s";
// Check: linesteps, 

  var url = "img:http://chart.apis.google.com/chart?cht="+param(type)+"&chtt="+param(title);
  if (type!="s") {
	url += "&chd=t:"+param(data)+"&chl="+param2(keys);
    url += "&chds="+min(data)+","+max(data);
    url += "&chm=N*f0*,0000FF,0,-1,11";
  } else {
    url += "&chxt=x,y&chd=t:"+param(keys)+"|"+param(data);
  }
  /* Check: map
  if (typeof xtitle != "undefined") {
    url += "&xtitle="+param(xtitle);
  }
  if (typeof ytitle != "undefined") {
    url += "&ytitle="+param(ytitle);
  }
  if (typeof(width)!= "undefined" && typeof(height)!= "undefined") {
    url += "&chs="+width+"x"+height;
  } else url += "&chs=300x125";
  return url+"&.png";
}

function graph2(type,title,data,data2,keys,xtitle,ytitle,width,height) {
  if (type=="bar") type = "bvg";
    else if (type=="baraccumulate") type = "bvs";
    else if (type=="bar") type = "bvg";
    else if (type=="scatter") type = "s";
// Check linesteps, line

  var url = "img:http://chart.apis.google.com/chart?cht="+param(type)+"&chtt="+param(title);
  url += "&chd=t:"+param(data)+"|"+param(data2)+"&chl="+param2(keys);
  if (typeof xtitle != "undefined") {
    url += "&xtitle="+param(xtitle);
  }
  if (typeof ytitle != "undefined") {
    url += "&ytitle="+param(ytitle);
  }
  if (typeof(width)!= "undefined" && typeof(height)!= "undefined") {
    url += "&chs="+width+"x"+height;
  } else url += "&chs=300x125";
  return url;
}
  */
function sum(arr) {
  var result = 0;
  for (var i=0; i<arr.length; i++) result += arr[i]*1;
  return result;
}
function min(arr) {
  var result = 0;
  if (arr.length>0) result = arr[0];
  for (var i=0; i<arr.length; i++) if (arr[i]<result) result = arr[i];
  return result;
}
function max(arr) {
  var result = 0;
  if (arr.length>0) result = arr[0];
  for (var i=0; i<arr.length; i++) if (arr[i]>result) result = arr[i];
  return result;
}
function avg(arr) {
  var result = 0;
  if (arr.length==0) return 0;
  for (var i=0; i<arr.length; i++) result += arr[i];
  return Math.round(1000*(result/arr.length))/1000; // round to 0.000
}
function count(arr) {
  var result = 0;
  return arr.length;
}
