<?php
/**
 * An example of a normal MySQL query using the DBX abstraction layer.
 *
 * @author Tim Boormans (Direct Web Solutions)
 * @license GPL
 */
require_once('../src/DBX.class.php');

// fill in your database credentials
$link = dbx_connect(DBX_MYSQL, "database_host", "database_name", "database_user", "database_password") or die("Cannot connect with the database.");

// Buffered query (default) -> change the query below to an existing table in your database
$sql = "SELECT * FROM table1 LIMIT 100 OFFSET 0";
$result = dbx_query($link, $sql);
if(is_object($result)) {
    foreach($result->data as $elem_nr => $arr) {
        print_r($arr);
    }
}
