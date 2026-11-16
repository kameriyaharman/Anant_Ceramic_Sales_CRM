<?php
// BigRock cPanel: rename this file to config.local.php and fill in your MySQL details
// (cPanel → MySQL® Databases → create database + user, add user to database with ALL PRIVILEGES).
return [
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'cpaneluser_anantcrm',
    'db_user' => 'cpaneluser_anant',
    'db_pass' => 'your-strong-password',
    'admin_email'    => 'admin@anantceramic.com',
    'admin_password' => 'change-me-on-first-login',
    'seed_demo' => false,   // true = load demo products/staff for a trial run
];
