<?php
require_once 'config/config.php';
require_once 'config/functions.php';
session_destroy();
redirect('login.php');
