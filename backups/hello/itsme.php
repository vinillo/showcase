<?php
$link = mysqli_connect("localhost","root","kippen7","rest_api");
if(isset($_GET['update']) && isset($_GET['title']) && isset($_GET['body']) && isset($_GET['created_by'])):

    mysqli_query($link,"UPDATE message set title = '".$_GET['title']."' , body = '".$_GET['body']."', created_by ='".$_GET['created_by']."' LIMIT 1");
die("done");
else:
$sth = mysqli_query($link,"SELECT * FROM message LIMIT 1");
$rows = array();
while($r = mysqli_fetch_assoc($sth)) {
    $rows = $r;
}
die(json_encode($rows));
endif;
?>