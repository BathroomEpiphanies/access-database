<?php

session_start();

chdir('../application');
include 'database.php';
include 'controllers.php';
include 'local.php';

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    if(isset($_REQUEST['updatedb']))               { control_update_database();        exit; }
    if(isset($_REQUEST['deploydb']))               { control_deploy_database();        exit; }
    if(isset($_REQUEST['del_user']))               { control_del_user();               exit; }
    if(isset($_REQUEST['del_tag']))                { control_del_tag();                exit; }
    if(isset($_REQUEST['del_ticket']))             { control_del_ticket();             exit; }
    if(isset($_REQUEST['tickets']))                { control_tickets();                exit; }
    if(isset($_REQUEST['users']))                  { control_users();                  exit; }
    if(isset($_REQUEST['user']))                   { control_user();                   exit; }
    if(isset($_REQUEST['tags']))                   { control_tags();                   exit; }
    if(isset($_REQUEST['doors']))                  { control_doors();                  exit; }
    if(isset($_REQUEST['door']))                   { control_door();                   exit; }
    if(isset($_REQUEST['systems']))                { control_systems();                exit; }
    if(isset($_REQUEST['system']))                 { control_system();                 exit; }
    if(isset($_REQUEST['groups']))                 { control_groups();                 exit; }
    if(isset($_REQUEST['group']))                  { control_group();                  exit; }
    if(isset($_REQUEST['join_user_to_group']))     { control_join_user_to_group();     exit; }
    if(isset($_REQUEST['remove_user_from_group'])) { control_remove_user_from_group(); exit; }
    if(isset($_REQUEST['logout']))                 { control_logout();                 exit; }
    control_users();
}
else {
    control_login();
}

?>
