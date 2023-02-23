<?
    print "Hello World!!!!!!!!";
    $txt = "user id date";
    $myfile = file_put_contents('/var/log/cron.log', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
?>