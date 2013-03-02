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

class type_pmwikiarea extends type_default {

static function form_render_value($name, $value, $smarty) {
  static $init = false;
  if ($init === false) $init = <<<EOT
	<script>
		function pmwikiarea_preview(field,pagename,title) {
		  var obj = getObj("preview_"+field);
		  obj.style.display = "";
		  ajax("type_pmwikiarea::ajax_render_preview",[getObj(field).value,getObj(pagename).value,getObj(title).value,tname],function(data){ set_html(obj,data); });
		}
		
		// Copyright (C) 2001-2007 Patrick R. Michaud (pmichaud § pobox.com)
		function insMarkup(mopen, mclose, mtext, id) {
		  var tarea = document.getElementById(id);
		  if (tarea.setSelectionRange > "") {
			var p0 = tarea.selectionStart;
			var p1 = tarea.selectionEnd;
			var top = tarea.scrollTop;
			var str = mtext;
			var cur0 = p0 + mopen.length;
			var cur1 = p0 + mopen.length + str.length;
			while (p1 > p0 && tarea.value.substring(p1-1, p1) == ' ') p1--; 
			if (p1 > p0) {
			  str = tarea.value.substring(p0, p1);
			  cur0 = p0 + mopen.length + str.length + mclose.length;
			  cur1 = cur0;
			}
			tarea.value = tarea.value.substring(0,p0)
			  + mopen + str + mclose
			  + tarea.value.substring(p1);
			tarea.focus();
			tarea.selectionStart = cur0;
			tarea.selectionEnd = cur1;
			tarea.scrollTop = top;
		  } else if (document.selection) {
			var str = document.selection.createRange().text;
			tarea.focus();
			range = document.selection.createRange()
			if (str == "") {
			  range.text = mopen + mtext + mclose;
			  range.moveStart('character', -mclose.length - mtext.length );
			  range.moveEnd('character', -mclose.length );
			} else {
			  if (str.charAt(str.length - 1) == " ") {
				mclose = mclose + " ";
				str = str.substr(0, str.length - 1);
			  }
			  range.text = mopen + str + mclose;
			}
			range.select();
		  } else { tarea.value += mopen + mtext + mclose; }
		  return;
		}
	</script>
EOT;

  $prefix = $smarty->prefix;
  $output = $init.<<<EOT
	<div style="max-width:710px;"> 
	<div style="float:right; padding-top:2px;">
	  <input type="button" value="Preview" accesskey="p" onclick="pmwikiarea_preview('{$name}','{$prefix}pagename','{$prefix}title'); getObj('{$name}').focus();">
	</div>
	<div id="wikiedit">
	  <a href="javascript:insMarkup('\'\'\'','\'\'\'','Strong','{$name}');"><img src="ext/cms/icons/strong.gif" title="bold"></a>
	  <a href="javascript:insMarkup('\'\'','\'\'','Emphasized','{$name}');"><img src="ext/cms/icons/em.gif" title="italic"></a>
	  <a href="javascript:insMarkup('[[',']]','Page link','{$name}');"><img src="ext/cms/icons/pagelink.gif" title="link to internal page"></a>
	  <a href="javascript:insMarkup('[[',']]','http:// | link text','{$name}');"><img src="ext/cms/icons/extlink.gif" title="link to external page"></a>
	  <a href="javascript:insMarkup('Attach:','','file.ext','{$name}');"><img src="ext/cms/icons/attach.gif" title="attach file"></a>
	  <a href="javascript:insMarkup('----','\\n','','{$name}');"><img src="ext/cms/icons/hr.gif" title="horizontal line"></a>
	  <a href="javascript:insMarkup('\'+','+\'','Big text','{$name}');"><img src="ext/cms/icons/big.gif" title="big text"></a>
	  <a href="javascript:insMarkup('\'-','-\'','Small text','{$name}');"><img src="ext/cms/icons/small.gif" title="small text"></a>
	  <a href="javascript:insMarkup('\'^','^\'','Superscript','{$name}');"><img src="ext/cms/icons/sup.gif" title="superscript"></a>
	  <a href="javascript:insMarkup('\'_','_\'','Subscript','{$name}');"><img src="ext/cms/icons/sub.gif" title="subscript"></a>
	  <a href="javascript:insMarkup('\\n!! ','\\n','Heading','{$name}');"><img src="ext/cms/icons/h.gif" title="heading"></a>
	  <a href="javascript:insMarkup('\\n!!! ','\\n','Subheading','{$name}');"><img src="ext/cms/icons/h3.gif" title="subheading"></a>
	  <a href="javascript:insMarkup('%25center%25 ','','','{$name}');"><img src="ext/cms/icons/center.gif" title="centered"></a>
	  <a href="javascript:insMarkup('%25right%25 ','','','{$name}');"><img src="ext/cms/icons/right.gif" title="right-aligned"></a>
	  <a href="javascript:insMarkup('\\n->','\\n','Indented text','{$name}');"><img src="ext/cms/icons/indent.gif" title="indent text"></a>
	  <a href="javascript:insMarkup('\\n# ','\\n','Ordered list','{$name}');"><img src="ext/cms/icons/ol.gif" title="numbered"></a>
	  <a href="javascript:insMarkup('\\n* ','\\n','Unordered list','{$name}');"><img src="ext/cms/icons/ul.gif" title="bulleted"></a>
	  <a href="javascript:insMarkup('(:table border=1 width=80%:)\\n(:cell style=\'padding:5px\;\':)\\n1a\\n(:cell style=\'padding:5px\;\':)\\n1b\\n(:cellnr style=\'padding:5px\;\':)\\n2a\\n(:cell style=\'padding:5px\;\':)\\n2b\\n(:tableend:)\\n\\n','','','{$name}');">
	  <img src="ext/cms/icons/table.gif" title="Table"></a>
	  <a href="javascript:insMarkup('(:graphviz [= digraph {\\n\\n} =]:)\\n\\n','','','{$name}');"><img src="ext/cms/icons/graphviz.gif" title="Graphviz"></a>
	  <a href="javascript:insMarkup('(:get_table folder=&quot;Id / Path&quot; view=View fields_hidden=&quot;Comma separated values&quot; limit=&quot;Maximum number of assets per page&quot; filter=&quot;Filter (URL)&quot; groupby=Field:)\\n\\n','','','{$name}');"><img src="ext/cms/icons/extjs.gif" title="ExtJS Table"></a>
	  <a href="javascript:insMarkup('(:include_page url=&quot;&quot; height=&quot;&quot;:)\\n\\n','','','{$name}');"><img src="ext/cms/icons/iframe.gif" title="Include page"></a>
	</div>
	<link rel="stylesheet" href="ext/cms/styles.css" type="text/css"/>
	<textarea name="{$name}" id="{$name}" style="font-size:10pt; font-family:Courier; width:100%; height:96px; color:#555; background-color:#FFF;">{$value}</textarea>
    <div class="wikibody">
	  <div id="preview_{$name}" style="display:none; padding:8px;"><img src="ext/images/loading.gif"/></div>
	</div>
	<div style="font-size:90%; margin-top:8px; margin-bottom:8px; padding-left:2px;">
	  <b>PmWiki:</b> &nbsp;<a onclick="nWin('http://www.pmwiki.org/PmWiki/BasicEditing');">Basic editing</a> -
	  <a onclick="nWin('http://www.pmwiki.org/PmWiki/TextFormattingRules');">Text formatting rules</a> -
	  <a onclick="nWin('http://www.pmwiki.org/PmWiki/Images');">Images</a> -
	  <a onclick="nWin('http://www.pmwiki.org/PmWiki/Tables');">Simple tables</a> -
	  <a onclick="nWin('http://www.pmwiki.org/PmWiki/TableDirectives');">Advanced tables</a> -
	  <a onclick="nWin('http://www.pmwiki.org/PmWiki/WikiStyles');">WikiStyles</a> --
	  <a onclick="nWin('http://www.pmwiki.org/PmWiki/DocumentationIndex');">Documentation index</a>
	  <br/><br/>
	  <b>Paragraphs:</b> for a new paragraph, use a blank line, <b>Line break:</b> <b>\\</b> or [[&lt;&lt;]], Join lines: <b>\</b><br/>
	  <div style="padding-left:70px;"><b>-&gt;</b> indent text, <b>-&lt;</b> hanging text</div>
	  <br/>
	  <b>Lists:</b> <b>*</b> bulleted, <b>#</b> numbered, <b>:</b>term<b>:</b>definition for definition lists<br/>
	  <b>Emphasis:</b> <b>''</b><i>italic</i><b>''</b>, <b>'''bold'''</b>, <b>'''''<i>bold italic</i>'''''</b>, <b>@@</b>monospace<b>@@</b><br/>
	  <br/>
	  <b>Links:</b> <b>[[</b>another page<b>]]</b>, <b>[[</b>http://www.example.com<b>]]</b>, <b>[[</b>another page <b>|</b> link text<b>]]</b>, <b>[[#</b>Anchor<b>]]</b><br/>
	  <b>Groups:</b> [[Group/Page]] displays Page,
							[[Group.Page]] displays Group.Page,
							[[Group(.Page)]] displays Group
	  <br/><br/>
	  <b>Separators:</b> <b>!!</b>, <b>!!!</b> headings, <b>----</b> horizontal line<br/>
	  <b>Other:</b> <b>[+</b><span style="font-size: 120%;">big</span><b>+]</b>,
						   <b>[++</b><span style="font-size: 144%;">bigger</span><b>++]</b>,
						   <b>[-</b><span style="font-size: 83%;">small</span><b>-]</b>,
						   <b>[--</b><span style="font-size: 69%;">smaller</span><b>--]</b>,
						   <b>'^</b><sup>superscript</sup><b>^'</b>,
						   <b>'_</b><sub>subscript</sub><b>_'</b>,
						   <b>{ldelim}+</b><ins>inserted</ins><b>+{rdelim}</b>,
						   <b>{ldelim}-</b><del>deleted</del><b>-{rdelim}</b>
	  <br/><br/>
	  <b>Prevent formatting:</b> <b>[=</b>...<b>=]</b>, <b>Preformatted block:</b> <b>[@</b>...<b>@]</b>, 
	  <b>Graphviz:</b> (:graphviz [= digraph { ... } =]:)
	  <br/>
	  <b>ExtJS Table:</b> (:get_table folder=... view=... :)
	</div>
	</div>
EOT;
  $init = "";
  return $output;
}

static function render_value($value, $value_raw, $preview, $unused) {
  if ($preview) {
	$id = uniqid();
	$value = modify::htmlfield(modify::htmlunquote($value),true,true);
    $value_raw = modify::nl2br($value_raw);
	
    return <<<EOT
	  <div class="wikibody">
		<div id="html_{$id}" style="padding:8px;">{$value}</div>
		<div id="data_{$id}" style="display:none; padding:8px;">{$value_raw}</div>
	  </div>
	  <input type="button" value="Source code / HTML" onclick="showhide('data_{$id}'); showhide('html_{$id}');" style="margin-bottom:1px;">
EOT;
  }
  return modify::nl2br($value_raw);
}

static function render_page($str,$args,$row) {
  $row = array(
    "pagename" => $row["pagename"]["data"][0],
	"title" => $row["title"]["data"][0],
	"data" => $str,
    "staticcache" => @$row["staticcache"]["data"][0],
	"lastmodified" => $row["lastmodified"],
	"table"=>$row["_table"],
  );
  if (empty($row["data"])) return "";
  $title = $row["title"] ? $row["title"] : $row["pagename"];
  if (($pos = strpos($title,"."))) $title = substr($title,$pos+1);

  return "<h1 class='pagetitle'>".modify::htmlquote($title)."</h1><div id='wikitext'>".
 	pmwiki_render($row["pagename"],"(:groupheader:)".$row["data"]."(:groupfooter:)",$row["table"],$row["staticcache"],$row["lastmodified"])."</div>";
}

static function ajax_render_preview($text, $pagename, $title, $table) {
  if (empty($text)) return "";
  if ($title=="") $title = $pagename;
  if (($pos = strpos($title,"."))) $title = substr($title,$pos+1);
  return sys_remove_trans("Preview")."<br/><br/><h1 class='pagetitle'>".modify::htmlquote($title)."</h1>".
  		 "<div id='wikitext'>".modify::htmlfield(pmwiki_render($pagename,"(:groupheader:)".$text."(:groupfooter:)",$table))."</div>";
}

static function export_as_html() {
  return true;
}

}