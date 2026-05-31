<?php
require_once '../includes/auth.php';
session_destroy();
header('Location: /smartcampus/index.php');
exit();
