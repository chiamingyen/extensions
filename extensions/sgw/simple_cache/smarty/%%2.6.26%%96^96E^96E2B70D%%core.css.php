<?php /* Smarty version 2.6.26, created on 2012-12-17 03:19:38
         compiled from core.css */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'config_load', 'core.css', 22, false),)), $this); ?>
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

<?php echo smarty_function_config_load(array('file' => "core_css.conf",'section' => $this->_tpl_vars['style']), $this);?>

body, h2, pre, img, p, div, iframe, table.data {
  margin:0px;
  padding:0px;
  border:0px;
  border-spacing:0px;
  <?php echo $this->_config[0]['vars']['direction']; ?>

}
body, select, input, textarea {
  <?php echo $this->_config[0]['vars']['font']; ?>

}
thead {
  display:table-header-group;
}
ol {
  margin-top:0px;
  margin-bottom:0px;
}
form {
  margin:0px;
  border:0px;
}
img {
  vertical-align:middle;
}
body {
  background-color: <?php echo $this->_config[0]['vars']['bg_white']; ?>
;
}
<?php if ($this->_tpl_vars['browser'] == 'safari'): ?>
body {
  -webkit-text-size-adjust:none;
}
div {
  word-break:break-all;
}
<?php endif; ?>

body, .default10 {
  <?php echo $this->_config[0]['vars']['font_data_black']; ?>

  <?php echo $this->_config[0]['vars']['font_10']; ?>

}

a {
  text-decoration:none;
  color: <?php echo $this->_config[0]['vars']['color_blue']; ?>
;
  <?php echo $this->_config[0]['vars']['cursor']; ?>

}

a:hover {
  text-decoration:underline;
  color: <?php echo $this->_config[0]['vars']['color_blue']; ?>
;
}

iframe {
  border: <?php echo $this->_config[0]['vars']['border']; ?>
;
  background-color: <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  <?php echo $this->_config[0]['vars']['font_blue']; ?>

}

input,select,textarea {
  border: <?php echo $this->_config[0]['vars']['border']; ?>
;
  background-color: <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  <?php echo $this->_config[0]['vars']['font_blue']; ?>

  <?php echo $this->_config[0]['vars']['font_9']; ?>

}
input,select,button {
  vertical-align:middle;
}

<?php if ($this->_tpl_vars['browser'] != 'msie'): ?>
.baseline select, .baseline input {
  vertical-align:baseline;
}
.baseline img {
  vertical-align:text-top;
}
<?php endif; ?>

<?php if ($this->_tpl_vars['browser'] == 'msie' || $this->_tpl_vars['browser'] == 'konqueror' || $this->_tpl_vars['browser'] == 'safari'): ?>
input,button {
  height:19px;
}
input[type="checkbox"] {
  height:14px;
}
<?php endif; ?>

.input,textarea,input[type="button"],input[type="image"],input[type="submit"],input[type="text"],input[type="password"] {
  padding-left:5px;
  padding-right:5px;
  border-radius:10px;
}
input[type="radio"] {
  border:0px;
}
input[type="file"] {
  <?php echo $this->_config[0]['vars']['font_8']; ?>

}

.default {
  <?php echo $this->_config[0]['vars']['font_black']; ?>

  <?php echo $this->_config[0]['vars']['font_9']; ?>

}
.bold {
  font-weight:bold;
}

.chat {
  width:100%;
  height:200px;
  overflow-x:auto;
  overflow-y:auto;
  overflow:auto;
  margin-top:2px;
  margin-bottom:2px;
  border: <?php echo $this->_config[0]['vars']['border']; ?>
;
  background-color: <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  <?php echo $this->_config[0]['vars']['font_blue']; ?>

}

.checkbox {
  border:0px;
  padding:0px;
  margin:1px 0 0 0;
  background-color:transparent;
  width:13px;
  <?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
  height:13px;
  <?php endif; ?>
}

.checkbox2 {
  margin:2px 0 0 0;
}

.checkbox3 {
  margin:0 0 2px 0;
}

.input:hover,textarea:hover,input[type="button"]:hover,input[type="image"]:hover,input[type="text"]:hover,input[type="submit"]:hover,input[type="password"]:hover {
  border: <?php echo $this->_config[0]['vars']['border_blue']; ?>
;
}
.input:focus,textarea:focus,input[type="button"]:focus,input[type="image"]:focus,input[type="text"]:focus,input[type="submit"]:focus,input[type="password"]:focus {
  border: <?php echo $this->_config[0]['vars']['border_red']; ?>
;
}

.submit {
  margin:1px 10px 1px 10px;
  width:220px;
  background-color: <?php echo $this->_config[0]['vars']['button_bg_white']; ?>
;
  border: <?php echo $this->_config[0]['vars']['border']; ?>
;
  <?php echo $this->_config[0]['vars']['font_blue']; ?>

  <?php echo $this->_config[0]['vars']['font_8']; ?>

  <?php echo $this->_config[0]['vars']['cursor']; ?>

}

input[type="button"], input[type="submit"], input[type="image"] {
  background: -moz-linear-gradient(top, #FFFFFF, #EEEEEE);
  box-shadow: 1px 1px 2px rgba(0,0,0,0.6);
}

#main {
  width:100%;
  position:relative;
  z-index:1;
}
.main2 {
  padding-left:1px;
  padding-right:1px;
}

table.data {
  width:100%;
  margin:auto;
  margin-bottom:6px;
  border: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
  background-color: <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  <?php echo $this->_config[0]['vars']['font_data_black']; ?>

  <?php echo $this->_config[0]['vars']['font_10']; ?>

}

table.data_page {
  border-radius:0 0 6px 6px;
}

<?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
table.data td {
  padding-left:5px;
  padding-right:5px;
  word-break:break-all;
  <?php echo $this->_config[0]['vars']['text_align']; ?>

}
table.data .external_content td {
  padding-left:0px;
  padding-right:0px;
}
<?php endif; ?>
table.data>tbody>tr>td, table.data>thead>tr>td {
  padding-left:5px;
  padding-right:5px;
  <?php echo $this->_config[0]['vars']['text_align']; ?>

}

table.data tr.fields td {
  padding-top:0px;
  padding-bottom:0px;
  background-color: <?php echo $this->_config[0]['vars']['bg_light_blue']; ?>
;
  <?php echo $this->_config[0]['vars']['font_white']; ?>

  <?php echo $this->_config[0]['vars']['cursor']; ?>

  <?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
  word-break:normal;
  <?php endif; ?>
}
table.data tr.fields td a {
  <?php echo $this->_config[0]['vars']['font_white']; ?>

}

table.data tr.summary td {
  border-top: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
  <?php echo $this->_config[0]['vars']['font_8']; ?>

}

table.data tr.summary2 td {
  border-top: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
  <?php echo $this->_config[0]['vars']['font_9']; ?>

}

#linktext {
  <?php echo $this->_config[0]['vars']['font_data_black']; ?>

}

.notification {
  background-color: <?php echo $this->_config[0]['vars']['bg_green_click']; ?>
;
}

.menu_notification {
  padding:0 10px;
  margin-left:4px;
  border-left:1px solid <?php echo $this->_config[0]['vars']['color_grey']; ?>
;
  cursor:auto;
}

table.data td.item_groupby {
  border-bottom: <?php echo $this->_config[0]['vars']['border']; ?>
;
  border-top: <?php echo $this->_config[0]['vars']['border']; ?>
;
  padding-top:4px;
  padding-bottom:2px;
  font-weight:bold;
}

table.data td.item_groupby a {
  text-decoration:none;
  <?php echo $this->_config[0]['vars']['font_data_black']; ?>

}

table.data td.item_time {
  width:55px;
  height:44px;
  text-align:center;
  white-space:nowrap;
  border-top: <?php echo $this->_config[0]['vars']['border']; ?>
;
  border-right: <?php echo $this->_config[0]['vars']['border']; ?>
;
  <?php echo $this->_config[0]['vars']['font_9']; ?>

  <?php echo $this->_config[0]['vars']['cursor']; ?>

  <?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
  word-break:normal;
  <?php endif; ?>
}
table.data td.item_week {
  width:55px;
  height:65px;
  text-align:center;
  white-space:nowrap;
  border-top: <?php echo $this->_config[0]['vars']['border']; ?>
;
  border-right: <?php echo $this->_config[0]['vars']['border']; ?>
;
  <?php echo $this->_config[0]['vars']['font_9']; ?>

  <?php echo $this->_config[0]['vars']['cursor']; ?>

  <?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
  word-break:normal;
  <?php endif; ?>
}

table.data td.item_data {
  padding:0px;
  background-color: <?php echo $this->_config[0]['vars']['bg_white_fixed']; ?>
;
  border: <?php echo $this->_config[0]['vars']['border']; ?>
;
  <?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
  word-break:normal;
  <?php endif; ?>
  border-radius:0 6px 6px 0;
}

table.data td.item_data_spacer {
  padding:0px;
  padding-right:1px;
  border-top: <?php echo $this->_config[0]['vars']['border']; ?>
;
  <?php echo $this->_config[0]['vars']['font_7']; ?>

}

table.data tr.items_odd {
  background-color: <?php echo $this->_config[0]['vars']['bg_dark_grey']; ?>
;
}
table.data tr.hl_items {
  background-color: <?php echo $this->_config[0]['vars']['bg_hl_items']; ?>
;
}

table.data tr.id {
  padding-top:2px;
  padding-bottom:2px;
  <?php echo $this->_config[0]['vars']['font_white_bold']; ?>

  background-color: <?php echo $this->_config[0]['vars']['bg_light_blue']; ?>
;
}

table.data tr.id_header {
  <?php echo $this->_config[0]['vars']['font_white_bold']; ?>

  background-color: <?php echo $this->_config[0]['vars']['bg_light_blue']; ?>
;
}
table.data tr.id_header a {
  <?php echo $this->_config[0]['vars']['font_white_bold']; ?>

}

table.data tr.id_header_bg {
  <?php echo $this->_config[0]['vars']['font_black_bold']; ?>

  background-color: <?php echo $this->_config[0]['vars']['bg_light_blue']; ?>
;
}
table.data tr.id_header_bg a {
  <?php echo $this->_config[0]['vars']['font_black_bold']; ?>

}

.tabstyle, .tabstyle2 {
  padding-right:10px;
  padding-left:12px;
  text-align:center;
  white-space:nowrap;
  border: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
  <?php echo $this->_config[0]['vars']['font_10']; ?>

  <?php echo $this->_config[0]['vars']['cursor']; ?>

  border-radius:12px 0 0 0;
}

span.tabstyle, span.tabstyle2, a.tabstyle, a.tabstyle2 {
  margin-right:2px;
  margin-bottom:2px;
  display:inline-block;
}
a.tabstyle:hover {
  text-decoration:none;
  <?php echo $this->_config[0]['vars']['font_data_black']; ?>
  
}
a.tabstyle2:hover {
  text-decoration:none;
  <?php echo $this->_config[0]['vars']['font_white_bold']; ?>

}

.tabstyle3 {
  padding-left:2px;
  padding-right:2px;
  cursor:move;
  border: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
  background-color: <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  <?php echo $this->_config[0]['vars']['font_10']; ?>

}

.tabstyle_empty {
  margin-right:2px;
  border: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
  background-color: <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  <?php echo $this->_config[0]['vars']['font_10']; ?>

}

.tabstyle {
  background-color: <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  <?php echo $this->_config[0]['vars']['font_tab_black']; ?>

  background: -moz-linear-gradient(top, #FFFFFF, <?php echo $this->_config[0]['vars']['bg_grey_gradient']; ?>
);
}

.tabstyle2 {
  background-color: <?php echo $this->_config[0]['vars']['bg_light_blue']; ?>
;
  <?php echo $this->_config[0]['vars']['font_white_bold']; ?>

}

.path_caption {
  <?php echo $this->_config[0]['vars']['font_10']; ?>

}

.tree_caption {
  margin:1px;
  margin-bottom:3px;
  width:100%;
  <?php echo $this->_config[0]['vars']['font_black_bold']; ?>

  <?php echo $this->_config[0]['vars']['font_10']; ?>

}

.tree_folders {
  max-height:65px;
  overflow:auto;
}

.tree_views {
  width:100%;
  margin-bottom:2px;
}

<?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
#view_buttons {
  margin-top:2px;
}
<?php endif; ?>

.tree_frame {
  width:100%;
  height:100%;
  padding:3px;
  text-align:left;
  <?php if ($this->_config[0]['vars']['direction']): ?>
    border-left: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
  <?php else: ?>
    border-right: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
  <?php endif; ?>
  border-top: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
}

.tree_data {
  width:100%;
  text-align:center;
  border: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
  border-bottom:0px;
  background-color: <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  <?php echo $this->_config[0]['vars']['font_data_black']; ?>

  <?php echo $this->_config[0]['vars']['font_10']; ?>

}

table.tree_data tr.fields td {
  background-color: <?php echo $this->_config[0]['vars']['bg_light_blue']; ?>
;
  <?php echo $this->_config[0]['vars']['font_white']; ?>

  <?php echo $this->_config[0]['vars']['cursor']; ?>

}

.tree2 {
  width:99%;
}

.path_caption a {
  text-decoration:none;
  <?php echo $this->_config[0]['vars']['font_black']; ?>

  <?php echo $this->_config[0]['vars']['font_10']; ?>

}

#tree, #tree a, .tree2 {
  text-decoration:none;
  <?php echo $this->_config[0]['vars']['font_black']; ?>

  <?php echo $this->_config[0]['vars']['font_9']; ?>

}

.tree_box {
  width:100%;
  padding:4px;
  margin-bottom:2px;
  text-align:center;
  background-color: <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  border: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
  <?php echo $this->_config[0]['vars']['font_10']; ?>

  <?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
  word-break:break-all;
  <?php endif; ?>
}
.tree_box:first-line, .tree_box :first-line {
  font-weight:bold;
}

#tree_bar {
  width:100%;
  text-align:center;
  padding:2px;
  margin-bottom:2px;
  background-color: <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  border: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
  border-top:0px;
}

#tree_bar a {
  text-decoration:none;
  <?php echo $this->_config[0]['vars']['font_data_black']; ?>

  <?php echo $this->_config[0]['vars']['font_9']; ?>

}

.search_bar {
  width:100%;
  padding-top:3px;
  padding-bottom:5px;
  border-bottom: <?php echo $this->_config[0]['vars']['border']; ?>
;
}

#tree_searchengines {
  margin-left:3px;
  margin-bottom:0px;  
  width:auto;
}

#tree_searchengines input {
  margin-right:2px;
  margin-bottom:2px;
}

.tree_cpane {
  padding-bottom:3px;
  <?php echo $this->_config[0]['vars']['font_9']; ?>

  <?php echo $this->_config[0]['vars']['cursor']; ?>

}

.tree_panes {
  margin-top:5px;
  margin-left:10px;
  margin-bottom:5px;
  width:200px;
}

.tree_subpane {
  font-weight: bold;
  margin-left:2px;
  margin-bottom:5px;
  border-bottom: <?php echo $this->_config[0]['vars']['border']; ?>
;
}

#content_pane {
  overflow:hidden;
  overflow-x:hidden;
  overflow-y:hidden;
}
#tree_def, #content_def {
  overflow:auto;
  overflow-x:auto;
  overflow-y:auto;
}

#login_reminder {
  display:none;
  top:42%;
  left:25%;
  right:25%;
  width:50%;
  z-index:99;
  position:absolute;
  border: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
  background-color: <?php echo $this->_config[0]['vars']['bg_white']; ?>
;
  <?php echo $this->_config[0]['vars']['font_30']; ?>

  <?php if ($this->_tpl_vars['browser'] == 'safari'): ?>
  word-break:normal;
  position:fixed;
  <?php endif; ?>
  border-radius:10px;
}

#login {
  position:absolute;
  left:0px;
  right:0px;
  top:0px;
  bottom:0px;
  display:none;
  z-index:99;
  height:100%;
  background-color: <?php echo $this->_config[0]['vars']['bg_white']; ?>
;
}

#calendar {
  display:none;
  top:30%;
  width:180px;
  height:220px;
  z-index:99;
  position:absolute;
}
#calendar iframe {
  border:0px;
  background-color:transparent;
}
.pane_spacer {
  width:100%;
  height:4px;
  font-size:4px;
  margin:2px 0 2px 0;
  cursor:move;
  border-top: <?php echo $this->_config[0]['vars']['border']; ?>
;
  border-bottom: <?php echo $this->_config[0]['vars']['border']; ?>
;
}
#pane2 {
  width:100%;
  height:100%;
  border:1px;
  cursor:move;
  padding-left:2px;
  border-left: <?php echo $this->_config[0]['vars']['border_black']; ?>
;
}
.datebox_headline {
  font-weight:bold;
  text-align:center;
}
.datebox_headline_day, .datebox_headline_text, .datebox_headline_day2 {
  width:2%;
  border-top: <?php echo $this->_config[0]['vars']['border']; ?>
;
  border-right: <?php echo $this->_config[0]['vars']['border']; ?>
;
  <?php echo $this->_config[0]['vars']['font_data_black']; ?>

  <?php echo $this->_config[0]['vars']['font_9']; ?>

  <?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
  white-space:nowrap;
  <?php endif; ?>
  <?php if ($this->_tpl_vars['browser'] == 'safari'): ?>  
  word-break:normal;
  <?php endif; ?>
}

.datebox_footerline td {
  <?php echo $this->_config[0]['vars']['font_9']; ?>

  <?php echo $this->_config[0]['vars']['cursor']; ?>

}
.datebox_footerline_b td {
  border-top: <?php echo $this->_config[0]['vars']['border']; ?>
;
  <?php echo $this->_config[0]['vars']['font_9']; ?>

}
.datebox_headline_day, .datebox_headline_day2 {
  <?php echo $this->_config[0]['vars']['cursor']; ?>
  
}
.datebox_headline_day2 {
  font-weight:bold;
}

.datebox_head {
  text-decoration:underline;
  font-weight:bold;
}
.datebox_head_div, .datebox_head_div2 {
  width:17px;
  text-align:center;
  font-weight:bold;
  <?php echo $this->_config[0]['vars']['font_9']; ?>

  <?php echo $this->_config[0]['vars']['cursor']; ?>
  
}
.datebox_head_div {
  background-color: <?php echo $this->_config[0]['vars']['bg_light_blue']; ?>
;
  <?php echo $this->_config[0]['vars']['font_white']; ?>

}
.datebox_today {
  border: <?php echo $this->_config[0]['vars']['border_red']; ?>
;
}
.datebox_realtoday {
  font-weight:bold;
}
.datebox_disabled {
  <?php echo $this->_config[0]['vars']['font_light_grey']; ?>

}
.datebox_row td {
  <?php echo $this->_config[0]['vars']['font_9']; ?>

  <?php echo $this->_config[0]['vars']['cursor']; ?>
  
}
.datebox_rowweek td {
  border-top: <?php echo $this->_config[0]['vars']['border_red']; ?>
;
  border-bottom: <?php echo $this->_config[0]['vars']['border_red']; ?>
;
}
.datebox_rowfirst td {
  border-top: <?php echo $this->_config[0]['vars']['border']; ?>
;
}
.datebox_rowlast td {
  border-bottom: <?php echo $this->_config[0]['vars']['border']; ?>
;
}
.datebox_week {
  border-right: <?php echo $this->_config[0]['vars']['border']; ?>
;
  <?php echo $this->_config[0]['vars']['font_grey']; ?>
;
}
.datebox_days {
  border-top: <?php echo $this->_config[0]['vars']['border']; ?>
;
  <?php echo $this->_config[0]['vars']['cursor']; ?>

  <?php echo $this->_config[0]['vars']['font_grey']; ?>
;
}

.notice {
  margin:1px;
  margin-top:5px;
  <?php echo $this->_config[0]['vars']['font_7']; ?>

}
.lnotice {
  color: <?php echo $this->_config[0]['vars']['color_menu_black']; ?>
;
}
.notice2 {
  position:absolute;
  bottom:0px;
  padding:0 12px 0 4px;
  background-color: <?php echo $this->_config[0]['vars']['color_white']; ?>
;
  opacity:0.5;
  <?php echo $this->_config[0]['vars']['cursor']; ?>

  <?php echo $this->_config[0]['vars']['font_7']; ?>

  <?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
    filter:alpha(opacity=50);
  <?php endif; ?>
  border-radius:0 12px 0 0;
}
.notice3 {
  right:0px;
  padding:0 2px 0 12px;
  border-radius:12px 0 0 0;
}

.menu {
  top:0px;
  left:0px;
  z-index:2;
  position:relative;
  margin-bottom:4px;
  color: <?php echo $this->_config[0]['vars']['color_menu_black']; ?>
;
  background-color: <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  border:1px solid <?php echo $this->_config[0]['vars']['color_grey']; ?>
;
  border-top:0px;
  <?php echo $this->_config[0]['vars']['font_menu_big']; ?>

  <?php echo $this->_config[0]['vars']['cursor']; ?>

  <?php if ($this->_config[0]['vars']['direction']): ?>
    border-radius:0 0 0 6px;
  <?php else: ?>
    border-radius:0 0 6px 0;
  <?php endif; ?>
  background: -moz-linear-gradient(top, #FFFFFF, <?php echo $this->_config[0]['vars']['bg_grey_gradient']; ?>
);
}

.submenu {
  <?php if ($this->_config[0]['vars']['direction']): ?>
    top:28px;
  <?php else: ?>
    top:24px;
  <?php endif; ?>
  position:absolute;
  padding:2px;
  z-index:2;
  color: <?php echo $this->_config[0]['vars']['color_menu_black']; ?>
;
  background-color: <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  border:1px solid <?php echo $this->_config[0]['vars']['color_grey']; ?>
;
  <?php echo $this->_config[0]['vars']['font_menu']; ?>

  <?php echo $this->_config[0]['vars']['cursor']; ?>

  border-radius:0 0 6px 6px;
  background: -moz-linear-gradient(top, <?php echo $this->_config[0]['vars']['bg_grey_gradient']; ?>
, #FFFFFF);
}
.menu td {
  height:20px;
  text-align:center;
  border-radius:3px;
}
.submenu td {
  width:160px;
  border-radius:3px;
}
.menu_item {
  height:20px;
  border:1px solid transparent;
  <?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
  border:1px solid <?php echo $this->_config[0]['vars']['bg_grey']; ?>
;
  <?php endif; ?>
}

.menu_item2 {
  height:20px;
  background-color: <?php echo $this->_config[0]['vars']['bg_light_blue']; ?>
;
  color: <?php echo $this->_config[0]['vars']['color_white']; ?>
;
  border: <?php echo $this->_config[0]['vars']['border_blue']; ?>
;
}

.cursor {
  <?php echo $this->_config[0]['vars']['cursor']; ?>

}

.bg_full {
  position:absolute;
  left:0px;
  top:0px;
  width:100%;
  height:100%;
  z-index:-1;
}

#console {
  position:absolute;
  display:none;
  width:250px;
  height:100px;
  top:100px;
  z-index:10;
}
.sgslogo {
  position:absolute;
  <?php if ($this->_config[0]['vars']['direction']): ?>
    left:0px;
  <?php else: ?>
    right:6px;
  <?php endif; ?>
  z-index:1;
}
.folder_block {
  width:10px;
  height:10px;
  margin-top:2px;
}
.folder_block2 {
  width:9px;
  height:9px;
  margin-top:2px;
}
.folder_block_image {
  width:10px;
  height:10px;
  margin-top:3px;
  vertical-align:top; 
}
#search_bar {
  width:100%;
  max-width:250px;
  <?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
  width:expression(getObj("tree_frame").offsetWidth > 257? "250px": "auto")}
  <?php endif; ?>
}
.image {
  padding-left:5px;
  padding-right:5px;
  width:18px;
  height:17px;
}
.hide {
  position:absolute;
  left:-200px;
}
.gantt_bar {
  background-color: <?php echo $this->_config[0]['vars']['bg_light_blue']; ?>
;
}
.nowrap {
  white-space:nowrap;
}
.cal_item {
  border-radius:0 0 6px 0;
}
.login_table {
  border:<?php echo $this->_config[0]['vars']['border']; ?>
;
  background-color:<?php echo $this->_config[0]['vars']['bg_white_fixed']; ?>
;
  padding-top:0px;
  padding-bottom:0px;
  text-align:right;
  border-radius:10px;
}
#login_table_obj {
  width:100%;
  top:43%;
  position:absolute;
  opacity:0;
  -moz-transition:opacity 4s;
  -webkit-transition:opacity 4s;
  -o-transition:opacity 4s;
  <?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
  filter:alpha(opacity=75);
  <?php endif; ?>
}
.login_alert {
  background-color:<?php echo $this->_config[0]['vars']['bg_white_fixed']; ?>
;
  position:absolute;
  top:0px;
  width:100%;
  opacity:0.75;
  <?php if ($this->_tpl_vars['browser'] == 'msie'): ?>
  filter:alpha(opacity=75);
  <?php endif; ?>
}
blockquote {
  margin-left:0px;
  padding-left:10px;
  border-left:2px solid <?php echo $this->_config[0]['vars']['color_grey']; ?>
;
}
.drag_asset {
  cursor:move;
  -moz-user-select:none;
}
.red {
  color: <?php echo $this->_config[0]['vars']['color_red']; ?>
;
}
a.hide_field, a.hide_field:hover {
  visibility:hidden;
  text-decoration:none;
}

/* IE6 */
.hidden {
  display:none;
}
.overflow {
  overflow:auto;
}
.full_width {
  width:100%;
}