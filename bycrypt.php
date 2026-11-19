<?php

$password = "Leosurfaceadmin0607";

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

echo $hashedPassword;
?>