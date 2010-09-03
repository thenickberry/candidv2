<?php
    include("../config.inc");
    if (isset($_GET['type'])) {
        $type = $_GET['type'];
    } else {
    	$type = '';
    }

    css_top("Login");
?>

<h1>Login</h1>
<form action='<?= $config['base_url'] ?>/main.php?process' method=post name=login>
<table>
  <tr><td>Username:</td><td><input type=text name=username></td></tr>
  <tr><td>Password:</td><td><input type=password name=password></td></tr>
  <tr><td><input type=hidden name=refer_back value="<?= $_SERVER['HTTP_REFERER']; ?>"></td><td><input type=submit value=submit></td></tr>
</table>
</form>

<?php if (isset($_GET['msg'])) { ?>

<?php	if ($_GET['msg'] == "err1") { ?>
<font style='color:red'>Bad username or password, please try again!</font>
<?php	} ?>

<?php	if ($_GET['msg'] == "err2") { ?>
<font style='color:red'>Missing username or password, please try again!</font>
<?php	} ?>

<?php } ?>

<?php css_end(); ?>
