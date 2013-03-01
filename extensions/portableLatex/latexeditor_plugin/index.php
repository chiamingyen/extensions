<?php
// 2012.06.02
// latexeditor plugin
// 新增功能: 假如所希望編輯的 plugin 尚未建立, 則此編輯程式將自行建立 plugin 目錄與檔案

$editor_pass = $cf['myeditor']['pass'];
session_start();
// 這裡的輸入 $texfile 為類似 simple.tex 中的 simple, 然後由系統自動加上 .tex 的 LaTex 檔案
function latexeditorMain($texfile)
{
global $menu;
 
$menu=$_GET["menu"];
$file=$_GET["file"];
// 若 downloads 目錄下根本就沒有 $texfile, 則自行建立空的 $texfile
// 若 $texfile 為空, 則回覆錯誤訊息
if ($texfile == "")
{
    $output = "Error! Please contact the system administrator!";
    return $output;
}
if (file_exists("downloads/".$texfile.".tex")) {
    // 繼續執行
} else {
    // 假如沒有所要編輯的 LaTex 檔案, 則由 template.tex 複製一份
    if (!copy("downloads/template.tex", "downloads/".$texfile.".tex")) 
    {
        $output = "can not create ".$texfile.".tex file";
        return $output;
    }
    // 建立空 LaTex 檔案
    //$ourFileHandle = fopen("./downloads/".$texfile, 'w') or die("can not create ".$texfile." file");
    //fclose($ourFileHandle);
}

$output="正在編輯 ".$texfile.".tex latex 文件<br /><br />";
  switch($menu)
  {
    case "latexeditorform":
        if($_SESSION["latexeditortoken"])
            $output.=latexeditorForm($texfile);
            else
            $output=latexeditorLogin();
    break;
 
    case "latexeditorsave":
        if($_SESSION["latexeditortoken"])
            $output.=latexeditorSave($texfile);
            else
            $output=latexlogin2();
    break;
 
    case "latexeditorcheck":
            $output=latexeditorCheck($texfile);
    break;
 
    case "latexeditorlogout":
            $output=latexeditorLogout();
    break;
 
    default:
            $output=latexeditorLogin();
  }
 
return $output;
}
 
function latexeditorLogin()
{
    global $sn,$su;
 
    $output= "請輸入登入密碼";
    $output.="<form method=POST action=".$sn."?".$su."&menu=latexeditorcheck><br>";
    $output.="密碼:<input type=password name=editorpass>";
    $output.="<input type=submit value=send>";
    $output.="</form>";
    return $output;
}
 
function latexeditorLogout()
{
    session_destroy();
    $output="已經登出<br>";
    $output.=latexeditorLogin();
    return $output;
}
 
function latexeditorCheck($texfile)
{
    global $editor_pass;
    
    $password = $_POST["editorpass"];
    $output = $password;
 
    if($password==$editor_pass)
    {
        $_SESSION["latexeditortoken"]=true;
        $output=latexeditorPrintmenu($texfile);
    }
    else
    {
        $_SESSION["latexeditortoken"]=false;
        $output=latexeditorLogin();
    }
    return $output;
}
 
function latexeditorPrintmenu($texfile)
{
global $sn,$su;
 
$output.="<br /><a href=".$sn."?".$su."&menu=latexeditorform>編輯 ".$texfile.".tex 檔案</a>|";
$output.="<a href=".$sn."?".$su."&menu=latexeditorlogout>logout</a>|<br /><br />";
 
return $output;
}
 
function latexeditorForm($texfile)
{
    global $sn,$su;
 
    $output="<form method=post action=".$sn."?".$su."&menu=latexeditorsave>";
    //$output.=dirname(__FILE__);
 
    $fp = fopen ("downloads/".$texfile.".tex", "r");
    $contents = fread($fp, filesize("downloads/".$texfile.".tex"));
    fclose($fp);
    $output.="<textarea cols=50 rows=20 name=\"content\">";
    //這裡為了在html區域展示程式碼,若要轉回來,則使用htmlspecialchars_decode()
    $output.=htmlspecialchars($contents);
    $output.="</textarea>";
    $output.="<br><input type=submit value=send>";
    $output.="</form><br /><br />";
    $output.=latexeditorPrintmenu($texfile);
 
return $output;
}
 
function latexeditorSave($texfile)
{
    global $sn,$su;
 
if(ini_get('magic_quotes_gpc')=="1")
{
    $content = stripslashes(htmlspecialchars_decode($_POST["content"]));
}
else
{
    $content = htmlspecialchars_decode($_POST["content"]);
}
    $fp = fopen ("downloads/".$texfile.".tex", "w");
    fwrite($fp,$content);
    fclose($fp);
    $output .= date("H:i:s").":已經存檔,請在以下編輯區,繼續編輯<br />";
    // 進行 latex 檔案編譯流程
    exec("V:/portable_latex/MiKTeX/texmf/miktex/bin/xelatex.exe -no-pdf -interaction=nonstopmode -output-directory=downloads/ downloads/".$texfile.".tex");
    // 利用相對目錄執行 C GD 繪圖程式
    exec("V:/portable_latex/MiKTeX/texmf/miktex/bin/xdvipdfmx.exe -vv -E -o downloads/".$texfile.".pdf downloads/".$texfile.".xdv");
    // 建立 pdf 連結, 以及 log 連結
    $output .= "<br /><a href=\"?download=".$texfile.".pdf\">".$texfile.".pdf</a> | ";
    $output .= "<a href=\"?download=".$texfile.".log\">".$texfile.".log</a><br /><br />";
// 以下回到編輯區
    $output.="<form method=post action=".$sn."?".$su."&menu=latexeditorsave>";
    //$output.=dirname(__FILE__);
 
    $fp = fopen ("downloads/".$texfile.".tex", "r");
    $contents = fread($fp, filesize("downloads/".$texfile.".tex"));
    fclose($fp);
    $output.="<textarea cols=50 rows=20 name=\"content\">";
    //這裡為了在html區域展示程式碼,若要轉回來,則使用htmlspecialchars_decode()
    $output.=htmlspecialchars($contents);
    $output.="</textarea>";
    $output.="<br><input type=submit value=send>";
    $output.="</form>";
    $output.=latexeditorPrintmenu($texfile);
 
return $output;
}