<?php if (!defined('PmWiki')) exit();
/*  Copyright 2006 Patrick R. Michaud (pmichaud@pobox.com)
    This file is wsplus.php; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published
    by the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version. 

*/

SDV($RecipeInfo['WikiStylesPlus']['Version'], '2007-05-23');

SDV($WSPlusUrl, 
  (substr(__FILE__, 0, strlen($FarmD)) == $FarmD) 
  ?  '$FarmPubDirUrl/wsplus' : '$PubDirUrl/wsplus');

##  Add in CSS styles.  For IE browsers, load the csshover.htc
##  extension which enables :hover on non-anchor elements.
SDV($HTMLHeaderFmt['wsplus'], "
  <link rel='stylesheet' href='$WSPlusUrl/wsplus.css' 
    type='text/css' />
  <!--[if IE]><style type='text/css' media='screen'>
    body { behavior:url('$WSPlusUrl/csshover.htc'); }
    .rollover * { visibility: visible; }
  </style><![endif]-->
");

##  Define the sidenote, postit, and notetitle styles.  A sidenote
##  is a right-floating note with a frame and smaller text.  A
##  postit is a sidenote with a yellow background.  The notetitle
##  style displays a title in a postit or sidenote.
SDVA($WikiStyle['sidenote'], array('class'=>'frame rfloat sidenote'));
SDVA($WikiStyle['postit'], array('class'=>'frame rfloat sidenote postit'));
SDVA($WikiStyle['notetitle'], array('class'=>'notetitle', 'apply'=>'block'));

##  An iframe is a frame that is indented on both sides.
SDVA($WikiStyle['iframe'], array('class'=>'frame lrindent'));

##  Tip, important, and warning define a variety of callouts.
SDVA($WikiStyle['tip'], array('class'=>'round lrindent tip'));
SDVA($WikiStyle['important'], array('class'=>'round lrindent important'));
SDVA($WikiStyle['warning'], array('class'=>'round lrindent warning'));

##  Define some standard colors.  We could define these using CSS 
##  classes, but by defining them as 'color' attributes in a wikistyle 
##  we guarantee that they'll override other wikistyle color settings.

$x = array(
  'fuchsia'     => 'fuchsia',
  'lime'        => 'lime',
  'olive'       => 'olive',
  'teal'        => 'teal',
  'aqua'        => 'aqua',
  'darkgreen'   => '#006600',
  'bluegrass'   => '#009999',
  'teal'        => '#33ffcc',
  'darkpurple'  => '#660066',
  'periwinkle'  => '#6600cc',
  'darkgrey'    => '#666666',
  'mistgreen'   => '#669966',
  'slategrey'   => '#669999',
  'lightpurple' => '#9966cc',
  'lightgrey'   => '#999999',
  'lightblue'   => '#99ccff',
  'springgreen' => '#99ff33',
  'magenta'     => '#cc33cc',
  'grey'        => '#cccccc',
  'lightgreen'  => '#ccffcc',
  'pink'        => '#ff3399',
  'lightred'    => '#ff6666',
  'orange'      => '#ff9900',
  'lightorange' => '#ff9966',
  'gold'        => '#ffbb66');
foreach ($x as $color => $rgb) SDV($WikiStyle[$color]['color'], $rgb);

##  Define a %justify% wikistyle, for full justification on browsers
##  that support it.
SDV($WikiStyle['justify'], array('apply'=>'block', 'text-align'=>'justify'));

##  Define the %outline% wikistyle, for ordered lists.
SDV($WikiStyle['outline'], array('class'=>'outline', 'apply'=>'list'));

/*  zip-command: 
      zip -r wsplus.zip cookbook/wsplus.php pub/wsplus 
*/
