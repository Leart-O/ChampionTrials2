<?php
/**
 * Logout Page
 */
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/url_helper.php';

startSecureSession();
logoutUser();

redirect('/index.php');

