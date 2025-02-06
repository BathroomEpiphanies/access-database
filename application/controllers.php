<?php

/* View functions */
function control_tickets() {
    $door_db = DoorDatabase::get_instance();
    $title = 'Access system';
    $stylesheet = 'tickets';
    $tickets = $door_db->get_all_tickets();
    foreach($tickets as &$ticket) {
        $ticket['begin'] = strftime("%a %Y-%m-%d %H:%M",$ticket['begin']);
        $ticket['end'] = strftime("%a %Y-%m-%d %H:%M",$ticket['end']);
    }; unset($ticket); //"feature" in php breaks subsequent uses of ticket if not done
    include 'view/tickets.php.html';
}

function control_users() {
    $door_db = DoorDatabase::get_instance();
    $title = 'Access system';
    $stylesheet = 'users';
    $users = $door_db->get_all_users();
    foreach($users as &$user) {
        $tmp = explode(',', $user['groups']);
        sort($tmp);
        $user['groups'] = implode(',', $tmp);
    }; unset($user); //"feature" in php breaks subsequent uses of $user if not done
    include 'view/users.php.html';
}

function control_user() {
    $door_db = DoorDatabase::get_instance();
    $title = 'Access system';
    $stylesheet = 'users';
    $user = $door_db->get_user($_REQUEST['user_id']);
    $tags = $door_db->get_tags_per_user($_REQUEST['user_id']);
    $user_groups = $door_db->get_groups_per_user($_REQUEST['user_id']);
    $groups = $door_db->get_all_groups();
    include 'view/user.php.html';
}

function control_tags() {
    $door_db = DoorDatabase::get_instance();
    $title = 'Access system';
    $tags = $door_db->get_tags();
    foreach($tags as &$tag) {
        $tmp = explode(',', $tag['groups']);
        sort($tmp);
        $tag['groups'] = implode(',', $tmp);
    }; unset($tag); //"feature" in php breaks subsequent uses of $tag if not done
    include 'view/tags.php.html';
}

function control_doors() {
    $door_db = DoorDatabase::get_instance();
    $title = 'Access system';
    $stylesheet = 'doors';
    $doors = $door_db->get_all_doors();
    include 'view/doors.php.html';
}

function control_door() {
    $door_db = DoorDatabase::get_instance();
    $title = 'Access system';
    $stylesheet = 'doors';
    $door = $door_db->get_door($_REQUEST['door_id']);
    $tickets = $door_db->get_tickets_per_door($_REQUEST['door_id']);
    foreach($tickets as &$ticket) {
        $ticket['begin'] = strftime("%a %Y-%m-%d %H:%M",$ticket['begin']);
        $ticket['end'] = strftime("%a %Y-%m-%d %H:%M",$ticket['end']);
    }; unset($ticket); //"feature" in php breaks subsequent uses of ticket if not done
    include 'view/door.php.html';
}

function control_systems() {
    $door_db = DoorDatabase::get_instance();
    $title = 'Access system';
    $stylesheet = 'systems';
    $systems = $door_db->get_all_systems();
    include 'view/systems.php.html';
}

function control_system() {
    $door_db = DoorDatabase::get_instance();
    $title = 'Access system';
    $stylesheet = 'systems';
    $system = $door_db->get_system($_REQUEST['system_id']);
    $doors = $door_db->get_doors_per_system($_REQUEST['system_id']);
    include 'view/system.php.html';
}

function control_groups() {
    $door_db = DoorDatabase::get_instance();
    $title = 'Access system';
    $stylesheet = 'groups';
    $groups = $door_db->get_all_groups();
    include 'view/groups.php.html';
}

function control_group() {
    $door_db = DoorDatabase::get_instance();
    $title = 'Access system';
    $stylesheet = 'groups';
    $group = $door_db->get_group($_REQUEST['group_id']);
    $tickets = $door_db->get_tickets_per_group($_REQUEST['group_id']);
    $users = $door_db->get_users_per_group($_REQUEST['group_id']);
    foreach($tickets as &$ticket) {
        $ticket['begin'] = strftime("%a %Y-%m-%d %H:%M",$ticket['begin']);
        $ticket['end'] = strftime("%a %Y-%m-%d %H:%M",$ticket['end']);
    }; unset($ticket); //"feature" in php breaks subsequent uses of ticket if not done
    include 'view/group.php.html';
}


/* Delete functions */
function control_del_user() {
    $door_db = DoorDatabase::get_instance();
    $door_db->del_user($_REQUEST['user_id']);
    header('Location: /?users');
}

function control_del_tag() {
    $door_db = DoorDatabase::get_instance();
    $door_db->del_tag($_REQUEST['tag_id']);
    if(isset($_REQUEST['user_id'])) {
        header('Location: /?user&user_id='.$_REQUEST['user_id']);
    }
    else {
        header('Location: /?tags');
    }
}

function control_del_ticket() {
    $door_db = DoorDatabase::get_instance();
    $door_db->del_ticket($_REQUEST['ticket_id']);
    if(isset($_REQUEST['door_id'])) {
        header('Location: /?door&door_id='.$_REQUEST['door_id']);
    }
    elseif(isset($_REQUEST['group_id'])) {
        header('Location: /?group&group_id='.$_REQUEST['group_id']);
    }
    elseif(isset($_REQUEST['tickets'])) {
        header('Location: /?tickets');
    }
    else {
        header('Location: /');
    }
}


/* Join/Remove functions */
function control_join_user_to_group() {
    $door_db = DoorDatabase::get_instance();
    $door_db->join_user_to_group($_REQUEST['user_id'],$_REQUEST['group_id']);
    header('Location: /?user&user_id='.$_REQUEST['user_id']);
}

function control_remove_user_from_group() {
    $door_db = DoorDatabase::get_instance();
    $door_db->remove_user_from_group($_REQUEST['user_id'],$_REQUEST['group_id']);
    if(preg_match('/\?group\&/',$_SERVER['HTTP_REFERER'])) {
        header('Location: /?group&group_id='.$_REQUEST['group_id']);
    }
    else if(preg_match('/\?user\&/',$_SERVER['HTTP_REFERER'])) {
        header('Location: /?user&user_id='.$_REQUEST['user_id']);
    }
    else {
        header('Location: /');
    }
}


/* Login/out */
include '../password.php';
function control_login() {
    global $salt;
    global $hash;
    if(isset($_REQUEST['pswd']) && hash('sha512', $salt.$_REQUEST['pswd']) == $hash) {
        $_SESSION['loggedin'] = 'true';
        header('Location: '.$_SERVER['REQUEST_URI']);
    }
    else
        include 'view/login.php.html';
}

function control_logout() {
    session_destroy();
    header('Location: /');
}

?>
