<?php

$ini = parse_ini_file("app/config/application.ini", true);
return $ini['db_razor'];

?>