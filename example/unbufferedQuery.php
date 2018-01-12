<?php
/**
 * An example of an unbuffered MySQL query using the DBX abstraction layer.
 *
 * @author Tim Boormans (Direct Web Solutions)
 * @license GPL
 */
require_once('../src/DBX.class.php');

// fill in your database credentials
$link = dbx_connect(DBX_MYSQL, "database_host", "database_name", "database_user", "database_password") or die("Cannot connect with the database.");

// Unbuffered with dbx_fetch_row() -> change the query below to an existing table in your database
$sql = "SELECT * FROM table1 LIMIT 100 OFFSET 0";
$result = dbx_query($link, $sql, DBX_RESULT_INDEX | DBX_RESULT_INFO | DBX_RESULT_ASSOC | DBX_RESULT_UNBUFFERED);
if(is_object($result)) {
    while($row_obj = dbx_fetch_row($result)) {
        print_r($row_obj->data[0]);
    }
}
