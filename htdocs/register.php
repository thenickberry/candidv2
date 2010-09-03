<?php

	include('../config.inc');
	ob_start();
	css_top('Registration for CANDIDv2');

	if (isset($_POST['sql'])) {
		list($sql,$errors) = check_answers($_POST['sql']);
		if (count($errors) == 0) {
			$email_q = "SELECT id,fname,lname,username FROM user WHERE email='${sql['email']}'";
			$email_r = mysql_query($email_q) or db_error($email_q);
			$email = mysql_fetch_array($email_r);
			if (empty($email['id'])) {
				$ins_q  = "INSERT INTO user (fname,lname,email,username,pword,access) VALUES ";
				$ins_q .= "('${sql['fname']}','${sql['lname']}','${sql['email']}','${sql['username']}',PASSWORD('${sql['pword']}'),1)";
				mysql_query($ins_q) or db_error($ins_q);
				header('Location: index.php');	
			} else {
				$errors['dup'] = "Duplicate email address found for ${email['username']}";
			}
			
		}
	} else {
		$fields = array('fname','lname','username','pword','verify_pword','email','verify_email');
		foreach ($fields as $field) {
			$sql[$field] = '';
			$errors[$field] = '&nbsp;';
		}
		$errors['dup'] = '';
	}

?>

	<h1>Registration</h1>

	<form action='<?= $_SERVER['PHP_SELF'] ?>' method='post'>
	<table>
		<tr>
			<td colspan='3'>&nbsp;</td>
		</tr>
		<tr>
			<td align='right'>First Name:</td>
			<td><input type='text' name='sql[fname]' value='<?= $sql['fname'] ?>'></td>
			<td><?= $errors['fname'] ?></td>
		</tr>
		<tr>
			<td align='right'>Last Name:</td>
			<td><input type='text' name='sql[lname]' value='<?= $sql['lname'] ?>'></td>
			<td><?= $errors['lname'] ?></td>
		</tr>
		<tr>
			<td colspan='3'>&nbsp;</td>
		</tr>
		<tr>
			<td align='right'>Username:</td>
			<td><input type='text' name='sql[username]' value='<?= $sql['username'] ?>'></td>
			<td><?= $errors['username'] ?></td>
		</tr>
		<tr>
			<td colspan='3'>&nbsp;</td>
		</tr>
		<tr>
			<td align='right'>Password:</td>
			<td><input type='password' name='sql[pword]' value='<?= $sql['pword'] ?>'></td>
			<td><?= $errors['pword'] ?></td>
		</tr>
		<tr>
			<td align='right'>Verify Password:</td>
			<td><input type='password' name='sql[verify_pword]' value='<?= $sql['verify_pword'] ?>'></td>
			<td><?= $errors['verify_pword'] ?></td>
		</tr>
		<tr>
			<td colspan='3'>&nbsp;</td>
		</tr>
		<tr>
			<td align='right'>Email:</td>
			<td><input type='text' name='sql[email]' value='<?= $sql['email'] ?>'></td>
			<td><?= $errors['email'] ?></td>
		</tr>
		<tr>
			<td align='right'>Verify Email:</td>
			<td><input type='text' name='sql[verify_email]' value='<?= $sql['verify_email'] ?>'></td>
			<td><?= $errors['verify_email'] ?></td>
		</tr>
		<tr>
			<td colspan='3'>&nbsp;</td>
		</tr>
		<tr>
			<td align='right' colspan='2'><input type='submit' value='Submit'></td>
		</tr>
	</table>
	</form>

<?php
	css_end();
	ob_end_flush();

	function check_answers($array) {
		foreach ($array as $key => $value) {
			if (empty($value)) {
				$errors[$key] = 'required';
				continue;
			}
			if ($key == 'email') {
				if (!strstr($value,'@')) { $errors[$key] = 'missing @ in address'; }
			} else {
				$array[$key] = htmlentities($value,ENT_QUOTES);
			}
			if (strstr($key,'verify')) {
				$key_match = str_replace('verify_','',$key);
				if ($value != $array[$key_match]) {
					$errors[$key] = 'does not match';
					$array[$key] = '';
				}
			}
			if ($key == 'username') {
				$user_q = "SELECT id FROM user WHERE username='${value}'";
				$user_r = mysql_query($user_q) or db_error($user_q);
				if (mysql_num_rows($user_r) != 0) {
					$errors[$key] = 'username already in use';
				}
			}
		}
		$data = array($array,$errors);
		return $data;
	}
?>
