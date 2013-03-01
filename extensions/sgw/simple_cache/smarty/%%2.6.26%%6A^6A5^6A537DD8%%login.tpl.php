<?php /* Smarty version 2.6.26, created on 2012-12-17 03:19:40
         compiled from login.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'config_load', 'login.tpl', 23, false),array('modifier', 'modify::htmlquote', 'login.tpl', 34, false),array('modifier', 'sys_remove_trans', 'login.tpl', 100, false),array('modifier', 'modify::nl2br', 'login.tpl', 100, false),)), $this); ?>
<?php echo smarty_function_config_load(array('file' => "core_css.conf",'section' => $this->_tpl_vars['sys']['style']), $this);?>

<html>
<head>
<title>Simple Groupware - Login</title>
<!-- 
	This website is brought to you by Simple Groupware
	Simple Groupware is an open source Groupware and Web Application Framework created by Thomas Bley and licensed under GNU GPL v2.
	Simple Groupware is copyright 2002-2012 by Thomas Bley.	Extensions and translations are copyright of their respective owners.
	More information and documentation at http://www.simple-groupware.de/
-->
<link media="all" href="images.php?css_style=<?php echo ((is_array($_tmp=$this->_tpl_vars['sys']['style'])) ? $this->_run_mod_handler('modify::htmlquote', true, $_tmp) : modify::htmlquote($_tmp)); ?>
&browser=<?php echo ((is_array($_tmp=$this->_tpl_vars['sys']['browser']['name'])) ? $this->_run_mod_handler('modify::htmlquote', true, $_tmp) : modify::htmlquote($_tmp)); ?>
&<?php echo ((is_array($_tmp=@CORE_VERSION)) ? $this->_run_mod_handler('modify::htmlquote', true, $_tmp) : modify::htmlquote($_tmp)); ?>
" rel="stylesheet" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="generator" content="Simple Groupware 0.743" />
<meta name="viewport" content="initial-scale=1.0; minimum-scale=1.0; maximum-scale=1.0;" />
<link href="ext/images/favicon.ico" rel="shortcut icon">
<?php echo '
<script>
function getObj(id) {
  return document.getElementById(id);
}
function set_html(obj,txt) {
  if (!obj) return;
  obj.innerHTML = txt;
}
function load() {
  getObj("login_table_obj").style.opacity = 0.75;
  if (top.sys && top.sys.username!="anonymous") {
    getObj("username").value = top.sys.username;
    getObj("redirect").checked = false;
    getObj("password").focus();
  } else {
    document.getElementById("username").focus();
  }
}
function sys_alert(str) {
  alert(str.replace(new RegExp("{t"+"}|{/t"+"}","g"), ""));
}
function generate_password(field) {
  var keys = "abcdefghijklmnopqrstuvwxyz1234567890@";
  var temp = "";
  for (i=0;i<8;i++) {
    temp += keys.charAt(Math.floor(Math.random()*keys.length));
  }
  sys_alert("The new password is: " + temp);
  getObj(field).value = temp;
  getObj(field+"_confirm").value = temp;
}
function validate_signup() {
  if (getObj(\'spassword\').value!="" && getObj(\'spassword\').value==getObj(\'spassword_confirm\').value) return true;
  sys_alert("password not confirmed.");
  return false;
}
</script>
<style>
body {
  overflow:hidden;
  overflow-y:hidden;
  overflow-x:hidden;
  height:1px;
}
</style>
'; ?>

</head>
<body onload="load();">
<div class="bg_full"><img src="<?php echo $this->_config[0]['vars']['bg_login']; ?>
" style="width:100%; height:100%;"></div>
<noscript>
<div style="background-color:#FFFFFF; text-align:center;">
<h2>Please enable Javascript in your browser.</h2>
</div>
</noscript>

<?php if ($this->_tpl_vars['alert']): ?>
<div class="login_alert" style="text-align:center">
  <table style="margin:auto; text-align:center"><tr><td>
  <div class="default10">
  <?php $_from = $this->_tpl_vars['alert']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['item']):
?>
    <?php echo ((is_array($_tmp=((is_array($_tmp=((is_array($_tmp=$this->_tpl_vars['item'])) ? $this->_run_mod_handler('sys_remove_trans', true, $_tmp) : sys_remove_trans($_tmp)))) ? $this->_run_mod_handler('modify::htmlquote', true, $_tmp) : modify::htmlquote($_tmp)))) ? $this->_run_mod_handler('modify::nl2br', true, $_tmp) : modify::nl2br($_tmp)); ?>
<br>
  <?php endforeach; endif; unset($_from); ?>
  </div>
  </td></tr></table>
</div>
<?php endif; ?>

<div id="login_table_obj" style="text-align:center; <?php if (@SELF_REGISTRATION): ?><?php if ($this->_tpl_vars['sys']['browser']['is_mobile']): ?>top:10%;<?php else: ?>top:33%;<?php endif; ?><?php endif; ?>">
  <table style="margin:auto;"><tr><td class="login_table" style="<?php if (! $this->_tpl_vars['sys']['browser']['is_mobile']): ?>padding:0 75px;<?php endif; ?>">
    <a target="_blank" href="<?php echo $this->_config[0]['vars']['logo_link']; ?>
"><img src="<?php echo $this->_config[0]['vars']['logo_login']; ?>
"></a><br>
    <form method="post" action="index.php?<?php echo $this->_tpl_vars['urladdon']; ?>
&folder=<?php echo ((is_array($_tmp=$this->_tpl_vars['login'][0])) ? $this->_run_mod_handler('modify::htmlquote', true, $_tmp) : modify::htmlquote($_tmp)); ?>
&view=<?php echo ((is_array($_tmp=$this->_tpl_vars['login'][1])) ? $this->_run_mod_handler('modify::htmlquote', true, $_tmp) : modify::htmlquote($_tmp)); ?>
&find=<?php echo ((is_array($_tmp=$this->_tpl_vars['login'][2])) ? $this->_run_mod_handler('modify::htmlquote', true, $_tmp) : modify::htmlquote($_tmp)); ?>
&page=<?php echo ((is_array($_tmp=$this->_tpl_vars['page'])) ? $this->_run_mod_handler('modify::htmlquote', true, $_tmp) : modify::htmlquote($_tmp)); ?>
<?php echo ((is_array($_tmp=$this->_tpl_vars['login_item'])) ? $this->_run_mod_handler('modify::htmlquote', true, $_tmp) : modify::htmlquote($_tmp)); ?>
">
	<input type="hidden" name="loginform" value="true">
	<input type="text" id="username" name="username" style="margin-bottom:2px;" required="true">
	<input type="password" id="password" name="password" style="margin-bottom:2px;" required="true">
	<input type="submit" value=" L o g i n " style="margin-bottom:2px;">
	<div class="default" style="padding:2px; padding-top:3px;">
	<?php if ($this->_tpl_vars['page'] == ""): ?><input class="checkbox" id="redirect" type="checkbox" name="redirect" value="1" style="margin:0px; margin-bottom:2px;" <?php if (! $this->_tpl_vars['login'][0] && ! $this->_tpl_vars['login'][2]): ?>checked<?php endif; ?>> <label for="redirect" class="default10">Redirect to home directory</label><?php endif; ?>
	</div>
	</form>
  </td></tr>
  </table>

  <?php if (@SELF_REGISTRATION): ?>
  <?php echo '<br><table style="margin:auto;"><tr><td class="login_table" style="'; ?><?php if (! $this->_tpl_vars['sys']['browser']['is_mobile']): ?><?php echo 'padding:0 75px;'; ?><?php endif; ?><?php echo '"><form method="post" action="index.php" onsubmit="return validate_signup();"><input type="hidden" name="signupform" value="true"><input type="hidden" name="redirect" value="1"><table style="width:100%;"><tr><td style="text-align:center;" colspan="2">Self registration<hr></td></tr><tr><td class="default10">Username</td><td><input type="text" name="username" value=""/></td></tr><tr><td class="default10" rowspan="2">Password</td><td><input type="password" id="spassword" name="password" value=""/>'; ?><?php echo ' '; ?><?php echo '<input type="button" value="&lt;" onclick="generate_password(\'spassword\');"/></td></tr><tr><td><input type="password" id="spassword_confirm" value=""/></td></tr><tr><td class="default10">E-mail</td><td><input type="text" name="email" value=""/></td></tr><tr><td></td><td><input type="submit" value=" R e g i s t e r "></td></tr></table></form></td></tr></table>'; ?>

  <?php endif; ?>
</div>
<div class="notice2"><a href="<?php echo $this->_config[0]['vars']['login_notice_link']; ?>
" class="lnotice" target="_blank"><?php echo $this->_config[0]['vars']['login_notice']; ?>
</a></div>
<div class="notice2 notice3"><a href="http://www.simple-groupware.de" class="lnotice" target="_blank" onmouseover="set_html(this,'Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.');" onmouseout="set_html(this,'Powered by Simple Groupware.');">Powered by Simple Groupware.</a></div>
</body>
</html>