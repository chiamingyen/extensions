<?php if (!defined('PmWiki')) exit();
/*
    PmWiki
    Copyright 2001-2009 Patrick R. Michaud
    pmichaud@pobox.com
    http://www.pmichaud.com/

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

    ----
    Note from Pm:  Trying to understand the PmWiki code?  Wish it had 
    more comments?  If you want help with any of the code here,
    write me at <pmichaud@pobox.com> with your question(s) and I'll
    provide explanations (and add comments) that answer them.
*/
SDV($FarmD,dirname(__FILE__));
SDV($WorkDir,"$FarmD/wiki.d");
if (preg_match('/\\w\\w:/', $FarmD)) exit();
@include_once("$FarmD/scripts/version.php");
$GroupPattern = '[[:upper:]][\\w]*(?:-\\w+)*';
$NamePattern = '[[:upper:]\\d_][\\w_]*(?:-_\\w+)*';
$BlockPattern = 'form|div|table|t[rdh]|p|[uo]l|d[ltd]|h[1-6r]|pre|blockquote';
$WikiWordPattern = '[[:upper:]][[:alnum:]]*(?:[[:upper:]][[:lower:]0-9]|[[:lower:]0-9][[:upper:]])[[:alnum:]]*';
$WikiDir = new PageStore('$FarmD/wiki.d/{$FullName}');
$WikiLibDirs = array(&$WikiDir,new PageStore('$FarmD/wikilib.d/{$FullName}'));
$LocalDir = 'local';
$InterMapFiles = array("$FarmD/scripts/intermap.txt",
  "$FarmD/local/farmmap.txt", '$SiteGroup.InterMap', 'local/localmap.txt');
$Newline = "\263";                                 # deprecated, 2.0.0
$KeepToken = "\235\235";  
$Now=time();
define('READPAGE_CURRENT', $Now+604800);
$TimeFmt = '%B %d, %Y, at %I:%M %p';
$TimeISOFmt = '%Y-%m-%dT%H:%M:%S';
$TimeISOZFmt = '%Y-%m-%dT%H:%M:%SZ';
$MessagesFmt = array();
$BlockMessageFmt = "<h3 class='wikimessage'>$[This post has been blocked by the administrator]</h3>";
$EditFields = array('text');
$EditFunctions = array('EditTemplate', 'RestorePage', 'ReplaceOnSave',
  'SaveAttributes', 'PostPage', 'PostRecentChanges', 'AutoCreateTargets',
  'PreviewPage');
$EnablePost = 1;
$ChangeSummary = substr(preg_replace('/[\\x00-\\x1f]|=\\]/', '', 
                                     stripmagic(@$_REQUEST['csum'])), 0, 100);
$AsSpacedFunction = 'AsSpaced';
$SpaceWikiWords = 0;
$RCDelimPattern = '  ';
$RecentChangesFmt = array(
  '$SiteGroup.AllRecentChanges' => 
    '* [[{$Group}.{$Name}]]  . . . $CurrentTime $[by] $AuthorLink: [=$ChangeSummary=]',
  '$Group.RecentChanges' =>
    '* [[{$Group}/{$Name}]]  . . . $CurrentTime $[by] $AuthorLink: [=$ChangeSummary=]');
$UrlScheme = (@$_SERVER['HTTPS']=='on' || @$_SERVER['SERVER_PORT']==443)
             ? 'https' : 'http';
$ScriptUrl = $UrlScheme.'://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
$PubDirUrl = preg_replace('#/[^/]*$#', '/pub', $ScriptUrl, 1);
$HTMLVSpace = "<vspace>";
$HTMLPNewline = '';
$MarkupFrame = array();
$MarkupFrameBase = array('cs' => array(), 'vs' => '', 'ref' => 0,
  'closeall' => array(), 'is' => array(),
  'escape' => 1);
$WikiWordCountMax = 1000000;
$WikiWordCount['PmWiki'] = 1;
$TableRowIndexMax = 1;
$UrlExcludeChars = '<>"{}|\\\\^`()[\\]\'';
$QueryFragPattern = "[?#][^\\s$UrlExcludeChars]*";
$SuffixPattern = '(?:-?[[:alnum:]]+)*';
$LinkPageSelfFmt = "<a class='selflink' href='\$LinkUrl' title='\$LinkAlt'>\$LinkText</a>";
$LinkPageExistsFmt = "<a class='wikilink' href='\$LinkUrl' title='\$LinkAlt'>\$LinkText</a>";
$LinkPageCreateFmt = 
  "<a class='createlinktext' rel='nofollow' title='\$LinkAlt'
    href='{\$PageUrl}?action=edit'>\$LinkText</a><a rel='nofollow' 
    class='createlink' href='{\$PageUrl}?action=edit'>?</a>";
$UrlLinkFmt = 
  "<a class='urllink' href='\$LinkUrl' title='\$LinkAlt' rel='nofollow'>\$LinkText</a>";
umask(002);
$CookiePrefix = '';
$SiteGroup = 'Site';
$SiteAdminGroup = 'SiteAdmin';
$DefaultGroup = 'Main';
$DefaultName = 'Home';
$GroupHeaderFmt = '(:include {$Group}.GroupHeader self=0 basepage={*$FullName}:)(:nl:)';
$GroupFooterFmt = '(:nl:)(:include {$Group}.GroupFooter self=0 basepage={*$FullName}:)';
$PagePathFmt = array('{$Group}.$1','$1.$1','$1.{$DefaultName}');
$PageAttributes = array(
  'passwdread' => '$[Set new read password:]',
  'passwdedit' => '$[Set new edit password:]',
  'passwdattr' => '$[Set new attribute password:]');
$XLLangs = array('en');
if (preg_match('/^C$|\.UTF-?8/i',setlocale(LC_ALL,0)))
  setlocale(LC_ALL,'en_US');
$FmtP = array();
$FmtPV = array(
  # '$ScriptUrl'    => 'PUE($ScriptUrl)',   ## $ScriptUrl is special
  '$PageUrl'      => 
    'PUE(($EnablePathInfo) 
         ? "$ScriptUrl/$group/$name"
         : "$ScriptUrl?n=$group.$name")',
  '$FullName'     => '"$group.$name"',
  '$Groupspaced'  => '$AsSpacedFunction($group)',
  '$Namespaced'   => '$AsSpacedFunction($name)',
  '$Group'        => '$group',
  '$Name'         => '$name',
  '$Titlespaced'  => 'FmtPageTitle(@$page["title"], $name, 1)',
  '$Title'        => 'FmtPageTitle(@$page["title"], $name, 0)',
  '$LastModifiedBy' => '@$page["author"]',
  '$LastModifiedHost' => '@$page["host"]',
  '$LastModified' => 'strftime($GLOBALS["TimeFmt"], $page["time"])',
  '$LastModifiedSummary' => '@$page["csum"]',
  '$LastModifiedTime' => '$page["time"]',
  '$Description' => '@$page["description"]',
  '$SiteGroup'    => '$GLOBALS["SiteGroup"]',
  '$VersionNum'   => '$GLOBALS["VersionNum"]',
  '$Version'      => '$GLOBALS["Version"]',
  '$Author'       => 'NoCache(isset($GLOBALS["Author"])?$GLOBALS["Author"]:"")',
  '$AuthId'       => 'NoCache($GLOBALS["AuthId"])',
  '$DefaultGroup' => '$GLOBALS["DefaultGroup"]',
  '$DefaultName'  => '$GLOBALS["DefaultName"]',
  '$BaseName'     => 'MakeBaseName($pn)',
  '$Action'       => '$GLOBALS["action"]',
  '$PasswdRead'   => 'PasswdVar($pn, "read")',
  '$PasswdEdit'   => 'PasswdVar($pn, "edit")',
  '$PasswdAttr'   => 'PasswdVar($pn, "attr")',
  );
$SaveProperties = array('title', 'description', 'keywords');
$PageTextVarPatterns = array(
  'var:'        => '/^(:*\\s*(\\w[-\\w]*)\\s*:[ \\t]?)(.*)($)/m',
  '(:var:...:)' => '/(\\(: *(\\w[-\\w]*) *:(?!\\))\\s?)(.*?)(:\\))/s'
  );

$Charset = 'ISO-8859-1';
$HTTPHeaders = array(
  "Expires: Tue, 01 Jan 2002 00:00:00 GMT",
  "Cache-Control: no-store, no-cache, must-revalidate",
  "Content-type: text/html; charset=ISO-8859-1;");
$CacheActions = array('browse','diff','print');
$EnableHTMLCache = 0;
$NoHTMLCache = 0;
$HTMLDoctypeFmt = 
  "<!DOCTYPE html 
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
  <html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'><head>\n";
$HTMLStylesFmt['pmwiki'] = "
  ul, ol, pre, dl, p { margin-top:0px; margin-bottom:0px; }
  code.escaped { white-space: nowrap; }
  .vspace { margin-top:1.33em; }
  .indent { margin-left:40px; }
  .outdent { margin-left:40px; text-indent:-40px; }
  a.createlinktext { text-decoration:none; border-bottom:1px dotted gray; }
  a.createlink { text-decoration:none; position:relative; top:-0.5em;
    font-weight:bold; font-size:smaller; border-bottom:none; }
  img { border:0px; }
  ";
$HTMLHeaderFmt['styles'] = array(
  "<style type='text/css'><!--",&$HTMLStylesFmt,"\n--></style>");
$HTMLBodyFmt = "</head>\n<body>";
$HTMLStartFmt = array('headers:',&$HTMLDoctypeFmt,&$HTMLHeaderFmt,
  &$HTMLBodyFmt);
$HTMLEndFmt = "\n</body>\n</html>";
$PageStartFmt = array(&$HTMLStartFmt,"\n<div id='contents'>\n");
$PageEndFmt = array('</div>',&$HTMLEndFmt);

$HandleActions = array(
  'browse' => 'HandleBrowse', 'print' => 'HandleBrowse',
  'edit' => 'HandleEdit', 'source' => 'HandleSource', 
  'attr' => 'HandleAttr', 'postattr' => 'HandlePostAttr',
  'logout' => 'HandleLogoutA', 'login' => 'HandleLoginA');
$HandleAuth = array(
  'browse' => 'read', 'source' => 'read', 'print' => 'read',
  'edit' => 'edit', 'attr' => 'attr', 'postattr' => 'attr',
  'logout' => 'read', 'login' => 'login');
$ActionTitleFmt = array(
  'edit' => '| $[Edit]',
  'attr' => '| $[Attributes]',
  'login' => '| $[Login]');
$DefaultPasswords = array('admin'=>'*','read'=>'','edit'=>'','attr'=>'');
$AuthCascade = array('edit'=>'read', 'attr'=>'edit');
$AuthList = array('' => 1, 'nopass:' => 1, '@nopass' => 1);
$SessionEncode = 'base64_encode';
$SessionDecode = 'base64_decode';

$Conditions['enabled'] = '(boolean)@$GLOBALS[$condparm]';
$Conditions['false'] = 'false';
$Conditions['true'] = 'true';
$Conditions['group'] = 
  "(boolean)MatchPageNames(\$pagename, FixGlob(\$condparm, '$1$2.*'))";
$Conditions['name'] = 
  "(boolean)MatchPageNames(\$pagename, FixGlob(\$condparm, '$1*.$2'))";
$Conditions['match'] = 'preg_match("!$condparm!",$pagename)';
$Conditions['authid'] = 'NoCache(@$GLOBALS["AuthId"] > "")';
$Conditions['exists'] = "(boolean)ListPages(FixGlob(
  str_replace(array('[[',']]'), array('', ''), \$condparm) , '$1*.$2'))";
$Conditions['equal'] = 'CompareArgs($condparm) == 0';
$Conditions['auth'] = 'NoCache(CondAuth($pagename, $condparm))';

$Conditions['expr'] = 'CondExpr($pagename, $condname, $condparm)';
$Conditions['('] = 'CondExpr($pagename, $condname, $condparm)';
$Conditions['['] = 'CondExpr($pagename, $condname, $condparm)';

$MarkupTable['_begin']['seq'] = 'B';
$MarkupTable['_end']['seq'] = 'E';
Markup('fulltext','>_begin');
Markup('split','>fulltext',"\n",
  '$RedoMarkupLine=1; return explode("\n",$x);');
Markup('directives','>split');
Markup('inline','>directives');
Markup('links','>inline');
Markup('block','>links');
Markup('style','>block');
Markup('closeall', '_begin',
  '/^\\(:closeall:\\)$/e', 
  "'<:block>' . MarkupClose()");

$ImgExtPattern="\\.(?:gif|jpg|jpeg|png|GIF|JPG|JPEG|PNG)";
$ImgTagFmt="<img src='\$LinkUrl' alt='\$LinkAlt' title='\$LinkAlt' />";

$BlockMarkups = array(
  'block' => array('','','',0),
  'ul' => array('<ul><li>','</li><li>','</li></ul>',1),
  'dl' => array('<dl>','</dd>','</dd></dl>',1),
  'ol' => array('<ol><li>','</li><li>','</li></ol>',1),
  'p' => array('<p>','','</p>',0),
  'indent' => 
     array("<div class='indent'>","</div><div class='indent'>",'</div>',1),
  'outdent' => 
     array("<div class='outdent'>","</div><div class='outdent'>",'</div>',1),
  'pre' => array('<pre>','','</pre>',0),
  'table' => array("<table width='100%'>",'','</table>',0));

foreach(array('http:','https:','mailto:','ftp:','news:','gopher:','nap:',
    'file:') as $m) 
  { $LinkFunctions[$m] = 'LinkIMap';  $IMap[$m]="$m$1"; }
$LinkFunctions['<:page>'] = 'LinkPage';

$EnableUpgradeCheck = 0;

// disable search index
$EnablePageIndex = 0;
$EnablePageListProtect = 0;

/*
##  $DiffKeepDays specifies the minimum number of days to keep a page's
##  revision history.  The default is 3650 (approximately 10 years).
# $DiffKeepDays=30;                        # keep page history at least 30 days

##  Set $EnableWikiWords if you want to allow WikiWord links.
##  For more options with WikiWords, see scripts/wikiwords.php .
# $EnableWikiWords = 1;                      # enable WikiWord links

##  Set $SpaceWikiWords if you want WikiWords to automatically 
##  have spaces before each sequence of capital letters.
# $SpaceWikiWords = 1;                     # turn on WikiWord spacing

##  By default, PmWiki doesn't allow browsers to cache pages.  Setting
##  $EnableIMSCaching=1; will re-enable browser caches in a somewhat
##  smart manner.  Note that you may want to have caching disabled while
##  adjusting configuration files or layout templates.
# $EnableIMSCaching = 1;                   # allow browser caching

##  The following lines make additional editing buttons appear in the
##  edit page for subheadings, lists, tables, etc.
# $GUIButtons['h2'] = array(400, '\\n!! ', '\\n', '$[Heading]',
#                     '$GUIButtonDirUrlFmt/h2.gif"$[Heading]"');
# $GUIButtons['h3'] = array(402, '\\n!!! ', '\\n', '$[Subheading]',
#                     '$GUIButtonDirUrlFmt/h3.gif"$[Subheading]"');
# $GUIButtons['indent'] = array(500, '\\n->', '\\n', '$[Indented text]',
#                     '$GUIButtonDirUrlFmt/indent.gif"$[Indented text]"');
# $GUIButtons['outdent'] = array(510, '\\n-<', '\\n', '$[Hanging indent]',
#                     '$GUIButtonDirUrlFmt/outdent.gif"$[Hanging indent]"');
# $GUIButtons['ol'] = array(520, '\\n# ', '\\n', '$[Ordered list]',
#                     '$GUIButtonDirUrlFmt/ol.gif"$[Ordered (numbered) list]"');
# $GUIButtons['ul'] = array(530, '\\n* ', '\\n', '$[Unordered list]',
#                     '$GUIButtonDirUrlFmt/ul.gif"$[Unordered (bullet) list]"');
# $GUIButtons['hr'] = array(540, '\\n----\\n', '', '',
#                     '$GUIButtonDirUrlFmt/hr.gif"$[Horizontal rule]"');
# $GUIButtons['table'] = array(600,
#                       '||border=1 width=80%\\n||!Hdr ||!Hdr ||!Hdr ||\\n||     ||     ||     ||\\n||     ||     ||     ||\\n', '', '', 
#                     '$GUIButtonDirUrlFmt/table.gif"$[Table]"');

## If you're running a publicly available site and allow anyone to
## edit without requiring a password, you probably want to put some
## blocklists in place to avoid wikispam.  See PmWiki.Blocklist.
# $EnableBlocklist = 1;                    # enable manual blocklists
# $EnableBlocklist = 10;                   # enable automatic blocklists

## If you want to have a custom skin, then set $Skin to the name
## of the directory (in pub/skins/) that contains your skin files.
## See PmWiki.Skins and Cookbook.Skins.
# $Skin = 'pmwiki';

##  $ScriptUrl is your preferred URL for accessing wiki pages
##  $PubDirUrl is the URL for the pub directory.
# $ScriptUrl = 'http://www.mydomain.com/path/to/pmwiki.php';
# $PubDirUrl = 'http://www.mydomain.com/path/to/pub';

##  If you want to have to approve links to external sites before they
##  are turned into links, uncomment the line below.  See PmWiki.UrlApprovals.
##  Also, setting $UnapprovedLinkCountMax limits the number of unapproved
##  links that are allowed in a page (useful to control wikispam).
# include_once('scripts/urlapprove.php');
# $UnapprovedLinkCountMax = 10;

##  If you want uploads enabled on your system, set $EnableUpload=1.
##  You'll also need to set a default upload password, or else set
##  passwords on individual groups and pages.  For more information
##  see PmWiki.UploadsAdmin.
*/
$EnableUpload = 1;                       

/*
# $DefaultPasswords['upload'] = crypt('secret');

##  To enable markup syntax from the Creole common wiki markup language
##  (http://www.wikicreole.org/), include it here:
# include_once('scripts/creole.php');

##  By default, pages in the Category group are manually created.
##  Uncomment the following line to have blank category pages
##  automatically created whenever a link to a non-existent
##  category page is saved.  (The page is created only if
##  the author has edit permissions to the Category group.)
# $AutoCreate['/^Category\\./'] = array('ctime' => $Now);

##  Some sites may want leading spaces on markup lines to indicate
##  "preformatted text blocks", set $EnableWSPre=1 if you want to do
##  this.  Setting it to a higher number increases the number of
##  space characters required on a line to count as "preformatted text".
# $EnableWSPre = 0;                        # PmWiki 2.2.0 default (disabled)
# $EnableWSPre = 1;                        # lines beginning with space are preformatted
# $EnableWSPre = 4;                        # lines with 4 spaces are preformatted

## You'll probably want to set an administrative password that you
## can use to get into password-protected pages.  Also, by default 
## the "attr" passwords for the PmWiki and Main groups are locked, so
## an admin password is a good way to unlock those.  See PmWiki.Passwords
## and PmWiki.PasswordsAdmin.
# $DefaultPasswords['admin'] = crypt('secret');

## By default, viewers are prevented from seeing the existence
## of read-protected pages in search results and page listings,
## but this can be slow as PmWiki has to check the permissions
## of each page.  Setting $EnablePageListProtect to zero will
## speed things up considerably, but it will also mean that
## viewers may learn of the existence of read-protected pages.
## (It does not enable them to access the contents of the
## pages.)
# $EnablePageListProtect = 0;

##  In the 2.2.0-beta series, {$var} page variables are absolute by
##  default, but a future version will make them relative.  This setting
##  sets them out as relative to begin with.  (If you're starting a new
##  site, it's probably best to leave this setting alone.)
*/

# $EnableRelativePageVars = 1;

SDV($CurrentTime, strftime($TimeFmt, $Now));
SDV($CurrentTimeISO, strftime($TimeISOFmt, $Now));

/*
if (is_array($PostConfig)) {
  asort($PostConfig, SORT_NUMERIC);
  foreach ($PostConfig as $k=>$v) {
    if (!$k || !$v || $v<0) continue;
    if (function_exists($k)) $k($pagename);
    elseif (file_exists($k)) include_once($k);
  }
}
*/

$EnableGUIButtons = 1;

##  PmWiki allows a great deal of flexibility for creating custom markup.
##  To add support for '*bold*' and '~italic~' markup (the single quotes
##  are part of the markup), uncomment the following lines. 
##  (See PmWiki.CustomMarkup and the Cookbook for details and examples.)
# Markup("'~", "inline", "/'~(.*?)~'/", "<i>$1</i>");        # '~italic~'
# Markup("'*", "inline", "/'\\*(.*?)\\*'/", "<b>$1</b>");    # '*bold*'

// TODO2 http://www.pmwiki.org/wiki/Cookbook/Ai

// tb
$FmtPV['$PageUrl'] = 'PUE(pmwiki_url("?page=$pagename"))';

$UploadUrlFmt = "ext/cms/files";
$WikiLibDirs = array(new PageDbStore());
if (MAIN_SCRIPT!="cms.php") PageDbStore::$show_deactivated = true; // backend

Markup('get_content', 'directives', '/\\(:get_content\\s(.*?):\\)/e',
  "Keep(modify::htmlfield(call_user_func_array('pmwiki_get_content',P2V(array('url'=>'','regexp'=>'','regexp_format'=>'','xpath'=>'','time'=>1800,'timeout'=>10),PSS('$1')))))");

Markup('get_table', 'directives', '/\\(:get_table\\s(.*?):\\)/e',
  "Keep(modify::htmlfield(call_user_func_array('extjs::get_table',P2V(array('folder'=>'','view'=>'details','fields_hidden'=>'','filter'=>'','groupby'=>'','orderby'=>'','limit'=>'20','title'=>''),PSS('$1'))),1,1))");

Markup('include_page', 'directives', '/\\(:include_page\\s(.*?):\\)/e',
  "Keep(modify::htmlfield(call_user_func_array('pmwiki_include_page',P2V(array('url'=>'','height'=>'','style'=>''),PSS('$1'))),1,1))");

Markup('graphviz', 'block', '/\\(:graphviz\\s(.*?):\\)/e', "Keep(pmwiki_graphviz(PSS('$1')))");

set_include_path(get_include_path() . PATH_SEPARATOR . "lib/pmwiki/");
include_once("lib/pmwiki/local/config.php");
?>