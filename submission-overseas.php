<?php
require __DIR__.'/app/bootstrap.php';
session_student();
if ($_SERVER['REQUEST_METHOD']==='POST') verify_csrf();
header('Location: submission-form.php',true,303);
