<?
  include("../../config.inc");
  if (!isset($_GET['user_id'])) {
    css_top("Select Person");
    echo "<table width=550><td><h1>Select a person</h1></td></table>\n".
	 "<form action='${_SERVER['PHP_SELF']}'>".
	 "<select name=user_id>".
	 getPersonList("","","1").
	 "</select>".
	 "<input type=submit value=Continue></form>";
    css_end();
    exit;
  }
  $user_id = $_GET['user_id'];
  $user = getPersonInfo($user_id);
  $title = "Details of ${user['full_name']}";
  css_top($title);


  if ($userinfo['access'] == '5') {
    $edit = "<a href='edit.php?user_id=$user_id'>edit</a>";
  } else {
    $edit = "";
  }

  if (!empty($user['image'])) {
    $img = "<img src='${config['base_url']}/main.php?displayUserImage&user_id=$user_id'>";
  } else { $img = ""; }
?>

<table>
 <td>
  <?= $img ?>
 </td><td valign=top>
  <table>
   <tr valign=top>
    <td colspan='2'>
      <h2><?= $user['full_name'] ?></h2>
	<?= $edit ?>
    </td>
   </tr>
   <tr><td>username</td><td><?= $user['username'] ?></td></tr>
   <tr><td>email</td><td><?= $user['email'] ?></td></tr>
  </table>
 </td>
 <td width=40% align=right>
  <a href='<?= $config['base_url'] ?>/main.php?browse&search=yes&people[]=<?= $user['id'] ?>'>view pictures <?= $user['full_name'] ?> is in</a>
  <br><br>
  <a href='<?= $config['base_url'] ?>/main.php?browse&search=yes&photographer=<?= $user['id'] ?>'>view pictures <?= $user['full_name'] ?> has taken</a>
  <br><br>
  <a href='<?= $config['base_url'] ?>/comment/last.php?user_id=<?= $user['id'] ?>'>last 5 comments on <?= $user['fname'] ?>'s images</a>
 </td>
</table>

<? css_end() ?>
