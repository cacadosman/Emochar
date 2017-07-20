<?php

$myfile = fopen("logs.txt", "a") or die("Unable to open file!");
$txt = "user id date";
fwrite($myfile, "\n". $txt);
fclose($myfile);

 ?>
