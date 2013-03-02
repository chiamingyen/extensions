{*
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
*}
{config_load file="core_css.conf" section=$style}

<a style="float:right;" onclick="hide('tree_info');">X</a>
<div class="tree_subpane">Info</div>
<table class="tree2" border="0" cellpadding="0" cellspacing="2" style="margin-left:4px;">
  {foreach key=key item=item from=$info}
	<tr><td>{$key}</td><td>{$item}</td></tr>
  {/foreach}
</table>
<div style="border-top: {#border#}; margin-top:5px; margin-bottom:5px;"></div>