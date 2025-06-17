<?php
//<!-- included database.php<br/> -->


class DoorDatabase {
    
    private $db;
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance == null) self::$instance = new DoorDatabase();
        return self::$instance;
    }
    
    private function __construct() {
        $db_file = '../database.sqlite';
        
        if (! file_exists($db_file)) {
            $this->db = new PDO('sqlite:'. $db_file);
            
            $this->db->query(
                'CREATE TABLE Users (
                     user_id     INTEGER PRIMARY KEY,
                     first_name  TEXT NOT NULL,
                     last_name   TEXT NOT NULL,
                     class       TEXT NOT NULL
                 )'
            );
            
            $this->db->query(
                'CREATE TABLE Tags (
                     tag_id      INTEGER PRIMARY KEY,
                     name        TEXT,
                     raw         TEXT UNIQUE,
                     rfid        TEXT UNIQUE,
                     pin         TEXT
                 )'
            );
            
            $this->db->query(
                'CREATE TABLE Groups (
                     group_id    INTEGER PRIMARY KEY,
                     name        TEXT UNIQUE
                 )'
            );
            
            $this->db->query(
                'CREATE TABLE Doors (
                     door_id     INTEGER PRIMARY KEY,
                     name        TEXT UNIQUE,
                     reader_type TEXT
                 )'
            );
            
            $this->db->query(
                'CREATE TABLE Systems (
                     system_id   INTEGER PRIMARY KEY,
                     name        TEXT UNIQUE,
                     ssh_port    TEXT UNIQUE,
                     hardware    TEXT
                 )'
            );
            
            $this->db->query(
                'CREATE TABLE rUserTag (
                     user_id     INTEGER,
                     tag_id      INTEGER,
                     UNIQUE(user_id,tag_id),
                     FOREIGN KEY (user_id)  REFERENCES Users(user_id)   ON UPDATE CASCADE ON DELETE CASCADE,
                     FOREIGN KEY (tag_id)   REFERENCES Tags(tag_id)     ON UPDATE CASCADE ON DELETE CASCADE
                 )'
            );
            
            $this->db->query(
                'CREATE TABLE rUserGroup (
                     user_id     INTEGER,
                     group_id    INTEGER,
                     UNIQUE(user_id,group_id),
                     FOREIGN KEY (user_id)  REFERENCES Users(user_id)   ON UPDATE CASCADE ON DELETE CASCADE,
                     FOREIGN KEY (group_id) REFERENCES Groups(group_id) ON UPDATE CASCADE ON DELETE CASCADE
                 )'
            );
            
            $this->db->query(
                'CREATE TABLE rSystemDoor (
                     system_id     INTEGER,
                     door_id       INTEGER,
                     hardware_port INTEGER,
                     UNIQUE(system_id,door_id,port),
                     FOREIGN KEY   (system_id) REFERENCES Systems(system_id) ON UPDATE CASCADE ON DELETE CASCADE,
                     FOREIGN KEY   (door_id)   REFERENCES Doors(door_id) ON UPDATE CASCADE ON DELETE CASCADE
                 )'
            );
            
            $this->db->query(
                'CREATE TABLE Tickets (
                     ticket_id   INTEGER PRIMARY KEY AUTOINCREMENT,
                     door_id     INTEGER,
                     group_id    INTEGER,
                     begin       INTEGER NOT NULL DEFAULT 0,
                     end         INTEGER NOT NULL DEFAULT 0,
                     require_pin TEXT NOT NULL DEFAULT "true",
                     FOREIGN KEY (door_id)  REFERENCES Doors(door_id)   ON UPDATE CASCADE ON DELETE CASCADE,
                     FOREIGN KEY (group_id) REFERENCES Groups(group_id) ON UPDATE CASCADE ON DELETE CASCADE,
                     CHECK(require_pin IN ("true","false"))
                 )'
            );
            
        }
        else {
            $this->db = new PDO('sqlite:'.$db_file);
        }
    }
    
    
    public function update_database($imported_data) {
        $insert_user = $this->db->prepare('INSERT OR REPLACE INTO Users (user_id,first_name,last_name,class) VALUES (?,?,?,?)');
        $insert_tag = $this->db->prepare('INSERT OR REPLACE INTO Tags (tag_id,name,raw,rfid,pin) VALUES (?,?,?,?,?)');
        $insert_user_tag = $this->db->prepare('INSERT OR REPLACE INTO rUserTag (user_id,tag_id) VALUES (?,?)');
        
        print(count($imported_data)); print('<br/>');
        $active_users = array();
        $this->db->query('BEGIN TRANSACTION');
        $this->db->query('PRAGMA foreign_keys = ON');
        $this->db->query('DELETE FROM Tags WHERE name="_imported"');
        
        foreach($imported_data AS $data) {
            //print_r($data);
            array_push($active_users, $data['user_id']);
            $insert_user->execute(array($data['user_id'],$data['first_name'],$data['last_name'],$data['class']));
            $insert_tag->execute(array($data['user_id'],"_imported",$data['raw'],$data['rfid'],$data['pin']));
            $insert_user_tag->execute(array($data['user_id'],$data['user_id']));
        }
        $this->db->query('END TRANSACTION');
        $this->db->query('DELETE FROM Users WHERE user_id>0 AND user_id<1000000 AND user_id NOT IN ('.implode(',',$active_users).')');
    }
    
    
    /* Delete functions */
    public function del_user($user_id) {
        $statement = $this->db->prepare('DELETE FROM Users WHERE user_id=?');
        $statement->execute(array($user_id));
    }
    
    public function del_tag($tag_id) {
        $statement = $this->db->prepare('DELETE FROM Tags WHERE tag_id=?');
        $statement->execute(array($tag_id));
    }
    
    public function del_ticket($ticket_id) {
        $statement = $this->db->prepare('DELETE FROM Tickets WHERE ticket_id=?');
        $statement->execute(array($ticket_id));
    }
    
    
    /* Join/Remove functions */
    public function join_user_to_group($user_id,$group_id) {
        $statement = $this->db->prepare('INSERT INTO rUserGroup (user_id,group_id) values (?,?)');
        $statement->execute(array($user_id,$group_id));
    }
    
    public function remove_user_from_group($user_id,$group_id) {
        $statement = $this->db->prepare('DELETE FROM rUserGroup WHERE user_id=? AND group_id=?');
        $statement->execute(array($user_id,$group_id));
    }
    
    
    /* Get functions */
    public function get_all_users() {
        $statement = $this->db->prepare(
            'SELECT
                 u.user_id as user_id,
                 u.first_name as first_name,
                 u.last_name as last_name,
                 u.class as class,
                 GROUP_CONCAT(g.name) AS groups
             FROM
                 Users u LEFT JOIN
                 rUserGroup rug ON rug.user_id=u.user_id LEFT JOIN
                 Groups g on g.group_id=rug.group_id
             GROUP BY
                 u.user_id
             ORDER BY
                 u.class,
                 u.last_name,
                 u.first_name'
        );
        $statement->execute();
        return $statement->fetchAll();
    }
    
    public function get_user($user_id) {
        $statement = $this->db->prepare(
            'SELECT * FROM Users WHERE user_id=?'
        );
        $statement->execute(array($user_id));
        return $statement->fetch();
    }
    
    public function get_users_per_group($group_id) {
        $statement = $this->db->prepare(
            'SELECT
                 u.user_id AS user_id,
                 u.first_name AS first_name,
                 u.last_name AS last_name,
                 u.class AS class
             FROM
                 rUserGroup rug INNER JOIN
                 Users u ON rug.user_id=u.user_id
             WHERE
                 rug.group_id=?
             ORDER BY
                 u.class,
                 u.last_name,
                 u.first_name'
        );
        $statement->execute(array($group_id));
        return $statement->fetchAll();
    }
    
    
    public function get_tags() {
        $statement = $this->db->prepare(
            'SELECT
                 t.tag_id AS tag_id,
                 t.raw AS raw,
                 t.rfid AS rfid,
                 t.name AS tag_name,
                 u.user_id AS user_id,
                 u.first_name AS first_name,
                 u.last_name AS last_name,
                 u.class AS class,
                 GROUP_CONCAT(g.name) AS groups
             FROM
                 Tags t LEFT JOIN
                 rUserTag rut ON t.tag_id=rut.tag_id LEFT JOIN
                 rUserGroup rug ON rut.user_id=rug.user_id LEFT JOIN
                 Groups g ON rug.group_id=g.group_id LEFT JOIN
                 Users u ON rut.user_id=u.user_id
             GROUP BY
                 t.tag_id
             ORDER BY
                 u.class,
                 u.last_name,
                 u.first_name'
        );
        $statement->execute();
        return $statement->fetchAll();
    }
    
    public function get_tags_per_user($user_id) {
        $statement = $this->db->prepare(
            'SELECT
                 t.tag_id AS tag_id,
                 t.raw AS raw,
                 t.rfid AS rfid,
                 t.name AS tag_name
             FROM
                 Tags t INNER JOIN
                 rUserTag rut ON t.tag_id=rut.tag_id INNER JOIN
                 Users u ON rut.user_id=u.user_id
             WHERE
                 u.user_id=?'
        );
        $statement->execute(array($user_id));
        return $statement->fetchAll();
    }
    
    
    public function get_all_doors() {
        $statement = $this->db->prepare(
            'SELECT * FROM Doors ORDER BY name'
        );
        $statement->execute();
        return $statement->fetchAll();
    }
    
    public function get_door($door_id) {
        $statement = $this->db->prepare(
            'SELECT * FROM Doors WHERE door_id=?'
        );
        $statement->execute(array($door_id));
        return $statement->fetch();
    }
    
    
    public function get_all_systems() {
        $statement = $this->db->prepare(
            'SELECT * FROM Systems ORDER BY name'
        );
        $statement->execute();
        return $statement->fetchAll();
    }
    
    public function get_system($system_id) {
        $statement = $this->db->prepare(
            'SELECT * FROM Systems WHERE system_id=?'
        );
        $statement->execute(array($system_id));
        return $statement->fetch();
    }
    
    
    public function get_all_groups() {
        $statement = $this->db->prepare(
            'SELECT * FROM Groups ORDER BY name'
        );
        $statement->execute();
        return $statement->fetchAll();
    }
    
    public function get_group($group_id) {
        $statement = $this->db->prepare(
            'SELECT * FROM Groups WHERE group_id=? ORDER BY name'
        );
        $statement->execute(array($group_id));
        return $statement->fetch();
    }
    
    public function get_groups_per_user($user_id) {
        $statement = $this->db->prepare(
            'SELECT
                 g.name,
                 g.group_id
             FROM
                 rUserGroup rug INNER JOIN
                 Groups g ON rug.group_id=g.group_id
             WHERE
                 user_id=?
             ORDER BY
                 g.name'
        );
        $statement->execute(array($user_id));
        return $statement->fetchAll();
    }
    
    
    public function get_all_tickets() {
        $statement = $this->db->prepare(
            'SELECT
                 t.ticket_id AS ticket_id,
                 t.begin AS begin,
                 t.end AS end,
                 g.name AS group_name,
                 d.name AS door_name
             FROM
                 Tickets t INNER JOIN
                 Groups g ON t.group_id=g.group_id INNER JOIN
                 Doors d ON t.door_id=d.door_id
             ORDER BY
                 d.name,
                 g.name,
                 d.name,
                 t.begin'
        );
        $statement->execute();
        return $statement->fetchAll();
    }
    
    public function get_doors_per_system($system_id) {
        $statement = $this->db->prepare(
            'SELECT
                 s.name AS system_name,
                 d.name AS door_name,
                 d.reader_type AS reader_type,
                 rsd.hardware_port AS hardware_port
             FROM
                 Systems s INNER JOIN
                 rSystemDoor rsd ON s.system_id=rsd.system_id INNER JOIN
                 Doors d ON d.door_id=rsd.door_id
             WHERE
                 s.system_id=?
             ORDER BY
                 d.name'
        );
        $statement->execute(array($system_id));
        return $statement->fetchAll();
    }
    
    public function get_tickets_per_door($door_id) {
        $statement = $this->db->prepare(
            'SELECT
                 g.name AS group_name,
                 t.begin AS begin,
                 t.end AS end,
                 t.require_pin AS require_pin,
                 t.ticket_id AS ticket_id
             FROM
                 Tickets t INNER JOIN
                 Groups g ON t.group_id=g.group_id
             WHERE
                 t.door_id=?
             ORDER BY
                 t.group_id,
                 t.begin'
        );
        $statement->execute(array($door_id));
        return $statement->fetchAll();
    }
    
    public function get_tickets_per_group($group_id) {
        $statement = $this->db->prepare(
            'SELECT
                 d.name AS door_name,
                 t.begin AS begin,
                 t.end AS end,
                 t.require_pin AS require_pin,
                 t.ticket_id AS ticket_id
             FROM
                 Tickets t INNER JOIN
                 Doors d ON t.door_id=d.door_id
             WHERE
                 t.group_id=?
             ORDER BY
                 d.door_id,
                 t.begin'
        );
        $statement->execute(array($group_id));
        return $statement->fetchAll();
    }
    
}
?>
