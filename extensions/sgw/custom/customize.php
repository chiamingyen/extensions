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

/*
This script helps you to customize a Simple Groupware installation and to keep your changes after running updates.

More information about customizing Simple Groupware can be found at:
http://www.simple-groupware.de/cms/Customization

Using this customization method helps you to:
- keep your changes separated to the standard Simple Groupware code
- persist your changes when doing an update

This script is run when installing or updating Simple Groupware.

These commands can be used to change the Simple Groupware code base:

- Append code:
  setup::customize_replace($file,$code_before,$code_before.$append_code);

- Replace code:
  setup::customize_replace($file,$code_old,$code_new);

- Remove code:
  setup::customize_replace($file,$code_remove,"");

Examples:
---------

First create two modules under "<sgs-dir>/custom/modules/schema/new_module.xml" and
"<sgs-dir>/custom/modules/schema/news2.xml".

Then add the modification commands to this file (\n = line break):

// add a new module to module list
setup::customize_replace("modules/schema/modules.txt", "wiki|Wiki", "wiki|Wiki\nnew_module|My new module");

// replace a module in the module list
setup::customize_replace("modules/schema/modules.txt", "\nnews|News","\nnews2|News2");

// remove a module from the module list
setup::customize_replace("modules/schema/modules.txt", "\nwiki|Wiki","");

// use text areas instead of wiki areas in the news module
setup::customize_replace("modules/schema/news.xml", "simple_type=\"wikiarea\"","simple_type=\"textarea\"");

// add a second subject field before the begin field in tasks.xml
setup::customize_replace("modules/schema/tasks.xml",
  "<field name=\"begin\"","<field name=\"subject2\" displayname=\"Subject 2\" simple_type=\"text\"/>\n  <field name=\"begin\"");

Tip: Never forget to document your changes!
*/