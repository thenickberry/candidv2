<?php
  include("../config.inc");

  $ip_addr = $_GET['ip_addr'];

  $query = "SELECT history_id,table_id,datetime FROM history WHERE ip_addr='$ip_addr' group by table_id order by datetime desc";
  $results = mysql_query($query) or db_error($query);
  while ($history = mysql_fetch_row($results)) {
    print "<a href='${config['base_url']}/image/view.php?image_id=$history[1]&thumb=yes'><img align=middle style='padding:1' alt='$history[0] $history[2]' src='${config['base_url']}/main.php?showImage&image_id=$history[1]&thumb=yes'></a>\n";
  }
?>
