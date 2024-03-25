<?php

/* Update database from some other source */
function control_update_database() {
    // implementation specific implementation required
    $import_data = null; 
    $door_db->update_database($import_data);
}

/* Deploy database to access systems */
function control_deploy_database() {
    exec('../tools/deploy_database.sh',$output,$return_var);
    print(implode("<br>",$output));
    print("</br>");
    print("tag database deployed</br>");
}

?>
