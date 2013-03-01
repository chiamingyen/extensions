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
	  <input type="button" value="{t}Preview{/t}" accesskey="p" onclick="pmwikiarea_preview('{$name}','{$prefix}pagename','{$prefix}title'); getObj('{$name}').focus();">
	</div>
	<div id="wikiedit">
	  <a href="javascript:insMarkup('\'\'\'','\'\'\'','Strong','{$name}');"><img src="ext/cms/icons/strong.gif" title="{t}bold{/t}"></a>
	  <a href="javascript:insMarkup('\'\'','\'\'','Emphasized','{$name}');"><img src="ext/cms/icons/em.gif" title="{t}italic{/t}"></a>
	  <a href="javascript:insMarkup('[[',']]','Page link','{$name}');"><img src="ext/cms/icons/pagelink.gif" title="{t}link to internal page{/t}"></a>
	  <a href="javascript:insMarkup('[[',']]','http:// | link text','{$name}');"><img src="ext/cms/icons/extlink.gif" title="{t}link to external page{/t}"></a>
	  <a href="javascript:insMarkup('Attach:','','file.ext','{$name}');"><img src="ext/cms/icons/attach.gif" title="{t}attach file{/t}"></a>
	  <a href="javascript:insMarkup('----','\\n','','{$name}');"><img src="ext/cms/icons/hr.gif" title="{t}horizontal line{/t}"></a>
	  <a href="javascript:insMarkup('\'+','+\'','Big text','{$name}');"><img src="ext/cms/icons/big.gif" title="{t}big text{/t}"></a>
	  <a href="javascript:insMarkup('\'-','-\'','Small text','{$name}');"><img src="ext/cms/icons/small.gif" title="{t}small text{/t}"></a>
	  <a href="javascript:insMarkup('\'^','^\'','Superscript','{$name}');"><img src="ext/cms/icons/sup.gif" title="{t}superscript{/t}"></a>
	  <a href="javascript:insMarkup('\'_','_\'','Subscript','{$name}');"><img src="ext/cms/icons/sub.gif" title="{t}subscript{/t}"></a>
	  <a href="javascript:insMarkup('\\n!! ','\\n','Heading','{$name}');"><img src="ext/cms/icons/h.gif" title="{t}heading{/t}"></a>
	  <a href="javascript:insMarkup('\\n!!! ','\\n','Subheading','{$name}');"><img src="ext/cms/icons/h3.gif" title="{t}subheading{/t}"></a>
	  <a href="javascript:insMarkup('%25center%25 ','','','{$name}');"><img src="ext/cms/icons/center.gif" title="{t}centered{/t}"></a>
	  <a href="javascript:insMarkup('%25right%25 ','','','{$name}');"><img src="ext/cms/icons/right.gif" title="{t}right-aligned{/t}"></a>
	  <a href="javascript:insMarkup('\\n->','\\n','Indented text','{$name}');"><img src="ext/cms/icons/indent.gif" title="{t}indent text{/t}"></a>
	  <a href="javascript:insMarkup('\\n# ','\\n','Ordered list','{$name}');"><img src="ext/cms/icons/ol.gif" title="{t}numbered{/t}"></a>
	  <a href="javascript:insMarkup('\\n* ','\\n','Unordered list','{$name}');"><img src="ext/cms/icons/ul.gif" title="{t}bulleted{/t}"></a>
	  <a href="javascript:insMarkup('(:table border=1 width=80%:)\\n(:cell style=\'padding:5px\;\':)\\n1a\\n(:cell style=\'padding:5px\;\':)\\n1b\\n(:cellnr style=\'padding:5px\;\':)\\n2a\\n(:cell style=\'padding:5px\;\':)\\n2b\\n(:tableend:)\\n\\n','','','{$name}');">
	  <img src="ext/cms/icons/table.gif" title="{t}Table{/t}"></a>
	  <a href="javascript:insMarkup('(:graphviz [= digraph {\\n\\n} =]:)\\n\\n','','','{$name}');"><img src="ext/cms/icons/graphviz.gif" title="Graphviz"></a>
	  <a href="javascript:insMarkup('(:get_table folder=&quot;{t}Id{/t} / {t}Path{/t}&quot; view={t}View{/t} fields_hidden=&quot;{t}Comma separated values{/t}&quot; limit=&quot;{t}Maximum number of assets per page{/t}&quot; filter=&quot;{t}Filter{/t} (URL)&quot; groupby={t}Field{/t}:)\\n\\n','','','{$name}');"><img src="ext/cms/icons/extjs.gif" title="ExtJS {t}Table{/t}"></a>
	  <a href="javascript:insMarkup('(:include_page url=&quot;&quot; height=&quot;&quot;:)\\n\\n','','','{$name}');"><img src="ext/cms/icons/iframe.gif" title="{t}Include page{/t}"></a>
	</div>
	<link rel="stylesheet" href="ext/cms/styles.css" type="text/css"/>
	<textarea name="{$name}" id="{$name}" style="font-size:10pt; font-family:Courier; width:100%; height:96px; color:#555; background-color:#FFF;">{$value}</textarea>
    <div class="wikibody">
	  <div id="preview_{$name}" style="display:none; padding:8px;"><img src="ext/images/loading.gif"/></div>
	</div>
	<div style="font-size:90%; margin-top:8px; margin-bottom:8px; padding-left:2px;">
	  <b>PmWiki:</b> &nbsp;<a onclick="nWin('{t}http://www.pmwiki.org/PmWiki/{/t}BasicEditing');">{t}Basic editing{/t}</a> -
	  <a onclick="nWin('{t}http://www.pmwiki.org/PmWiki/{/t}TextFormattingRules');">{t}Text formatting rules{/t}</a> -
	  <a onclick="nWin('{t}http://www.pmwiki.org/PmWiki/{/t}Images');">{t}Images{/t}</a> -
	  <a onclick="nWin('{t}http://www.pmwiki.org/PmWiki/{/t}Tables');">{t}Simple tables{/t}</a> -
	  <a onclick="nWin('{t}http://www.pmwiki.org/PmWiki/{/t}TableDirectives');">{t}Advanced tables{/t}</a> -
	  <a onclick="nWin('{t}http://www.pmwiki.org/PmWiki/{/t}WikiStyles');">{t}WikiStyles{/t}</a> --
	  <a onclick="nWin('{t}http://www.pmwiki.org/PmWiki/{/t}DocumentationIndex');">{t}Documentation index{/t}</a>
	  <br/><br/>
	  <b>{t}Paragraphs{/t}:</b> {t}for a new paragraph, use a blank line{/t}, <b>{t}Line break{/t}:</b> <b>\\</b> {t}or{/t} [[&lt;&lt;]], {t}Join lines{/t}: <b>\</b><br/>
	  <div style="padding-left:70px;"><b>-&gt;</b> {t}indent text{/t}, <b>-&lt;</b> {t}hanging text{/t}</div>
	  <br/>
	  <b>{t}Lists{/t}:</b> <b>*</b> {t}bulleted{/t}, <b>#</b> {t}numbered{/t}, <b>:</b>{t}term{/t}<b>:</b>{t}definition for definition lists{/t}<br/>
	  <b>{t}Emphasis{/t}:</b> <b>''</b><i>{t}italic{/t}</i><b>''</b>, <b>'''bold'''</b>, <b>'''''<i>{t}bold italic{/t}</i>'''''</b>, <b>@@</b>{t}monospace{/t}<b>@@</b><br/>
	  <br/>
	  <b>{t}Links{/t}:</b> <b>[[</b>{t}another page{/t}<b>]]</b>, <b>[[</b>http://www.example.com<b>]]</b>, <b>[[</b>{t}another page{/t} <b>|</b> {t}link text{/t}<b>]]</b>, <b>[[#</b>{t}Anchor{/t}<b>]]</b><br/>
	  <b>{t}Groups{/t}:</b> [[{t}Group{/t}/{t}Page{/t}]] {t}displays{/t} {t}Page{/t},
							[[{t}Group{/t}.{t}Page{/t}]] {t}displays{/t} {t}Group{/t}.{t}Page{/t},
							[[{t}Group{/t}(.{t}Page{/t})]] {t}displays{/t} {t}Group{/t}
	  <br/><br/>
	  <b>{t}Separators{/t}:</b> <b>!!</b>, <b>!!!</b> {t}headings{/t}, <b>----</b> {t}horizontal line{/t}<br/>
	  <b>{t}Other{/t}:</b> <b>[+</b><span style="font-size: 120%;">{t}big{/t}</span><b>+]</b>,
						   <b>[++</b><span style="font-size: 144%;">{t}bigger{/t}</span><b>++]</b>,
						   <b>[-</b><span style="font-size: 83%;">{t}small{/t}</span><b>-]</b>,
						   <b>[--</b><span style="font-size: 69%;">{t}smaller{/t}</span><b>--]</b>,
						   <b>'^</b><sup>{t}superscript{/t}</sup><b>^'</b>,
						   <b>'_</b><sub>{t}subscript{/t}</sub><b>_'</b>,
						   <b>{ldelim}+</b><ins>{t}inserted{/t}</ins><b>+{rdelim}</b>,
						   <b>{ldelim}-</b><del>{t}deleted{/t}</del><b>-{rdelim}</b>
	  <br/><br/>
	  <b>{t}Prevent formatting{/t}:</b> <b>[=</b>...<b>=]</b>, <b>{t}Preformatted block{/t}:</b> <b>[@</b>...<b>@]</b>, 
	  <b>Graphviz:</b> (:graphviz [= digraph { ... } =]:)
	  <br/>
	  <b>ExtJS {t}Table{/t}:</b> (:get_table folder=... view=... :)
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
	  <input type="button" value="{t}Source code{/t} / HTML" onclick="showhide('data_{$id}'); showhide('html_{$id}');" style="margin-bottom:1px;">
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
  return sys_remove_trans("{t}Preview{/t}")."<br/><br/><h1 class='pagetitle'>".modify::htmlquote($title)."</h1>".
  		 "<div id='wikitext'>".modify::htmlfield(pmwiki_render($pagename,"(:groupheader:)".$text."(:groupfooter:)",$table))."</div>";
}

static function export_as_html() {
  return true;
}

}