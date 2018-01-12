<?php
/**
 * This PHP5 class imports the basic functionality of the DBX module. The DBX module should normally
 * get compiled into the PHP for proper working, but since DBX is deprecated in PHP5 people with software
 * relying on DBX ran into problems. This PHP-library imports all DBX functionality into your script making
 * it run as if the PHP4 DBX module is loaded.
 *
 * As of 2018 this class does not meet the current programming requirements anymore. Corrections would be needed
 * to replace the old native mysql link to a mysqli or PDO link to make it working at PHP 5.4+ installations.
 *
 * Refer to the readme for more information.
 *
 * @author Tim Boormans (Direct Web Solutions)
 * @date December 2011
 * @version 0.3
 * @license GPL
 */

// define supported databases (converted from: dbx.c)
define('DBX_UNKNOWN', 0);
define('DBX_MYSQL', 1);
define('DBX_ODBC', 2);
define('DBX_PGSQL', 3);
define('DBX_MSSQL', 4);
define('DBX_FBSQL', 5);
define('DBX_SYBASECT', 7);
define('DBX_OCI8', 6);
define('DBX_SQLITE', 8);

// other defines
define('DBX_PERSISTENT', 1);
/*
// This is how the Query constants are bitwise calculated (converted from: dbx.h)
// Starting state is: '0000 0001'
// Bits are counted in this order: 128 64 32 16  8 4 2 1
// Using bitwise operators the position of 1 is shifted (in this case) to left for numerous positions

00000001 <<0 (shift 0 positions left) = 00000001 (to decimal) = 1
00000001 <<1 (shift 1 positions left) = 00000010 (to decimal) = 2
00000001 <<2 (shift 2 positions left) = 00000100 (to decimal) = 4
00000001 <<3 (shift 3 positions left) = 00001000 (to decimal) = 8
00000001 <<4 (shift 4 positions left) = 00010000 (to decimal) = 16
00000001 <<5 (shift 5 positions left) = 00100000 (to decimal) = 32
00000001 <<6 (shift 6 positions left) = 01000000 (to decimal) = 64
00000001 <<7 (shift 7 positions left) = 10000000 (to decimal) = 128
*/
define('DBX_RESULT_INFO', 1); // (1<<0)
define('DBX_RESULT_INDEX', 2); // (1<<1)
define('DBX_RESULT_ASSOC', 4); // (1<<2)
define('DBX_COLNAMES_UNCHANGED', 8); // (1<<3)
define('DBX_COLNAMES_UPPERCASE', 16); // (1<<4)
define('DBX_COLNAMES_LOWERCASE', 32); // (1<<5)
define('DBX_RESULT_UNBUFFERED', 64); // (1<<6) This flag was introduced in PHP 5.0.0, so PHP4 scripts do not alter the result with this flag given!

define('DBX_CMP_NATIVE', 1); // (1<<0)
define('DBX_CMP_TEXT', 2); // (1<<1)
define('DBX_CMP_NUMBER', 4); // (1<<2)
define('DBX_CMP_ASC', 8); // (1<<3)
define('DBX_CMP_DESC', 16); // (1<<4)

// globals
$GLOBALS['DBX_LINK'] = null;
$GLOBALS['DBX_LAST_RESULT'] = null;
$GLOBALS['DBX_LAST_FLAGS'] = null;

// backwards compatibility
$GLOBALS['DBX_ALWAYS_ASSOC'] = false;

function detect_dbx_compare_constants($constants_parameter) {
    $defines = array(
        DBX_CMP_NATIVE => 'DBX_CMP_NATIVE',
        DBX_CMP_TEXT => 'DBX_CMP_TEXT',
        DBX_CMP_NUMBER => 'DBX_CMP_NUMBER',
        DBX_CMP_ASC => 'DBX_CMP_ASC',
        DBX_CMP_DESC => 'DBX_CMP_DESC'
    );

    $detected = array();
    $cur = $constants_parameter;
    $try = 32;

    while($try > 1) {
        $minus = floor($try/2);
        if( ($cur - $minus) >= 0) {
            $detected[] = $defines[$minus];
            $cur -= $minus;
        }
        $try -= $minus;
    }

    return $detected;
}

function detect_dbx_query_constants($constants_parameter) {
    $defines = array(
        DBX_RESULT_INFO => 'DBX_RESULT_INFO',
        DBX_RESULT_INDEX => 'DBX_RESULT_INDEX',
        DBX_RESULT_ASSOC => 'DBX_RESULT_ASSOC',
        DBX_COLNAMES_UNCHANGED => 'DBX_COLNAMES_UNCHANGED',
        DBX_COLNAMES_UPPERCASE => 'DBX_COLNAMES_UPPERCASE',
        DBX_COLNAMES_LOWERCASE => 'DBX_COLNAMES_LOWERCASE',
        DBX_RESULT_UNBUFFERED => 'DBX_RESULT_UNBUFFERED'
    );

    $detected = array();
    $cur = $constants_parameter;
    $try = 128;

    while($try > 1) {
        $minus = floor($try/2);
        if( ($cur - $minus) >= 0) {
            $detected[] = $defines[$minus];
            $cur -= $minus;
        }
        $try -= $minus;
    }

    return $detected;
}


/**
 * Close an open connection/database
 *
 * @param stdClass $link_identifier
 * @return int
 */
function dbx_close(stdClass $link_identifier) {
    if(mysql_close($link_identifier->handle)) {
        return 1;
    } else {
        return 0;
    }
}

/**
 * Open a connection/database
 *
 * @param mixed $module
 * @param string $host
 * @param string $database
 * @param string $username
 * @param string $password
 * @param int $persistent
 * @return bool|stdClass
 * @throws Exception
 */
function dbx_connect ($module, $host, $database, $username, $password, $persistent = 0) {
    if(strtolower($module) != 1 && strtolower($module) != 'mysql') {
        throw new Exception("This DBX alternative does not support any other database driver than MySQL.");
    }

    // connect
    if($persistent == 1 || $persistent === true || $persistent == DBX_PERSISTENT) {
        $GLOBALS['DBX_LINK'] = mysql_pconnect($host, $username, $password);
    } else {
        $GLOBALS['DBX_LINK'] = mysql_connect($host, $username, $password);
    }

    // select database
    if($GLOBALS['DBX_LINK'] != null && is_resource($GLOBALS['DBX_LINK'])) {
        if(mysql_select_db($database, $GLOBALS['DBX_LINK'])) {
            // could select db
            $result = new stdClass();
            $result->database = $database;
            $result->handle = &$GLOBALS['DBX_LINK'];
            $result->module = 1;
            return $result;

        } else {
            // could not select db
            dbx_close($GLOBALS['DBX_LINK']); // close connection
            return false;
        }
    } else {
        // could not connect
        return false;
    }
}

/**
 * Report the error message of the latest function call in the module
 *
 * @param null|stdClass $link_identifier
 * @return string
 * @throws Exception
 */
function dbx_error(stdClass $link_identifier = null) {
    if($link_identifier == null) {
        // use default link
        if($GLOBALS['DBX_LINK'] == null) {
            // no link established yet
            throw new Exception("dbx_error() did not detect any active MySQL connection.");
        } else {
            $err_str = mysql_error($GLOBALS['DBX_LINK']);
        }
    } else {
        // use parameterized link
        $err_str = mysql_error($link_identifier->handle);
    }

    return $err_str;
}

/**
 * Escape a string so it can safely be used in an sql-statement
 *
 * @param stdClass $link_identifier
 * @param string $text
 * @return null|string
 */
function dbx_escape_string(stdClass $link_identifier, $text) {
    if($link_identifier == null) {
        // no link established yet
        return null;
    } else {
        return mysql_real_escape_string($text, $link_identifier->handle);
    }
}

/**
 * Send a query and fetch all results (if any)
 *
 * @param stdClass $link_identifier
 * @param string $sql_statement
 * @param int $flags
 * @return int|stdClass
 */
function dbx_query(stdClass $link_identifier, $sql_statement, $flags = 0) {
    $return = null;

    // Free previous query result
    if($GLOBALS['DBX_LAST_RESULT'] != null) {
        @mysql_free_result($GLOBALS['DBX_LAST_RESULT']->handle);
        $GLOBALS['DBX_LAST_RESULT'] = null;
    }
    // Reset flags from previous query
    if($GLOBALS['DBX_LAST_FLAGS'] != null) {
        $GLOBALS['DBX_LAST_FLAGS'] = null;
    }

    // beautify flags
    $flags_arr = detect_dbx_query_constants($flags);

    // default scenario when flags parameter is not specified
    if($flags == 0) {
        $flags_arr[] = 'DBX_RESULT_INDEX';
        $flags_arr[] = 'DBX_RESULT_INFO';
        $flags_arr[] = 'DBX_RESULT_ASSOC';
    }

    // DBX_RESULT_INDEX is always set!
    if(!in_array('DBX_RESULT_INDEX', $flags_arr)) {
        $flags_arr[] = 'DBX_RESULT_INDEX';
    }

    // php.ini fallback for case sensivity
    if(!in_array('DBX_COLNAMES_UNCHANGED', $flags_arr) && !in_array('DBX_COLNAMES_UPPERCASE', $flags_arr) && !in_array('DBX_COLNAMES_LOWERCASE', $flags_arr)) {
        if($case = ini_get('dbx.colnames_case')) {
            $case = strtolower($case);
            if($case == "unchanged") {
                $flags_arr[] = 'DBX_COLNAMES_UNCHANGED';
            } elseif($case == "uppercase") {
                $flags_arr[] = 'DBX_COLNAMES_UPPERCASE';
            } elseif($case == "lowercase") {
                $flags_arr[] = 'DBX_COLNAMES_LOWERCASE';
            }
        }
    }

    // id => name assocations (references for the id columns!)
    if(!in_array('DBX_RESULT_INFO', $flags_arr) && in_array('DBX_RESULT_ASSOC', $flags_arr)) {
        $flags_arr[] = 'DBX_RESULT_INFO';
    }

    // Execute query on the mysql server
    if(in_array('DBX_RESULT_UNBUFFERED', $flags_arr)) {
        $result = mysql_unbuffered_query($sql_statement, $link_identifier->handle);
    } else {
        $result = mysql_query($sql_statement, $link_identifier->handle);
    }

    // Select return type
    // Return an object only for SELECT-queries
    // Return 1 on succes (other than SELECT)
    // Return 0 for error
    if(preg_match('/^SELECT|SHOW|DESCRIBE|EXPLAIN/i', trim($sql_statement))) {
        // return = object
        $return = new stdClass();

    } else {
        // return = int
        if(!$result) {
            // mysql error
            $return = 0;
        } else {
            // mysql succes (not select)
            $return = 1;
        }
    }

    if(is_a($return, 'stdClass')) {

        $return->handle = $result;
        $return->cols = 0;
        $return->rows = 0;
        $return->info = array(
            'name' => array(), // '0' => 'id'
            'type' => array()  // '0' => 'int4'
        );

        // if set: DBX_RESULT_INFO or DBX_RESULT_ASSOC => copy column names and types
        if(in_array('DBX_RESULT_INFO', $flags_arr) || in_array('DBX_RESULT_ASSOC', $flags_arr)) {

            // Enumerate column names and types
            $return->cols = @mysql_num_fields($result);
            for($i = 0; $i < $return->cols; $i++) {
                $meta_obj = @mysql_fetch_field($result, $i);

                // if the ASSOC option is given, also add info per named column
                if(in_array('DBX_RESULT_ASSOC', $flags_arr)) {

                    if(in_array('DBX_COLNAMES_UPPERCASE', $flags_arr)) {
                        $return->info['name'][] = strtoupper($meta_obj->name);

                    } elseif(in_array('DBX_COLNAMES_LOWERCASE', $flags_arr)) {
                        $return->info['name'][] = strtolower($meta_obj->name);

                    } else {
                        $return->info['name'][] = $meta_obj->name;
                    }

                    $return->info['type'][] = $meta_obj->type;
                } else {
                    // add info per element
                    $return->info['name'][] = $i;
                    $return->info['type'][] = $meta_obj->type;
                }

                /*
                 * $meta_obj properties:
                blob:         $meta_obj->blob
                max_length:   $meta_obj->max_length
                multiple_key: $meta_obj->multiple_key
                name:         $meta_obj->name
                not_null:     $meta_obj->not_null
                numeric:      $meta_obj->numeric
                primary_key:  $meta_obj->primary_key
                table:        $meta_obj->table
                type:         $meta_obj->type
                unique_key:   $meta_obj->unique_key
                unsigned:     $meta_obj->unsigned
                zerofill:     $meta_obj->zerofill
                */

                // reset pointer to start position
                mysql_field_seek($result, 0);
            }
        }

        // if set: DBX_RESULT_UNBUFFERED
        if(in_array('DBX_RESULT_UNBUFFERED', $flags_arr)) {
            // do not create the data property, leave rows '0', dont copy results to the return object


        } else {
            // Copy the result rows to the return object
            $return->data = array();

            // speed up the checking process
            $create_assoc_reference = false;
            $columns_uppercase = false;
            $columns_lowercase = false;
            if(in_array('DBX_RESULT_ASSOC', $flags_arr)) {
                $create_assoc_reference = true;
            }
            if(in_array('DBX_COLNAMES_UPPERCASE', $flags_arr)) {
                $columns_uppercase = true;
            }
            if(in_array('DBX_COLNAMES_LOWERCASE', $flags_arr)) {
                $columns_lowercase = true;
            }

            // start copying
            while ($row = @mysql_fetch_assoc($result)) {
                // reset counters / elements per new row
                $i = 0;
                $row_arr = array();

                // create columns in array
                foreach($row as $column => $value) {
                    $row_arr[$i] = $value;

                    // if set: DBX_RESULT_ASSOC => create reference variable to index-variable '$i'
                    if($create_assoc_reference || $GLOBALS['DBX_ALWAYS_ASSOC']) { // as reference: if '$i' is changed, '$column' has to be changed too, automatically

                        // dont change casing for default output
                        $row_arr[$column] = &$row_arr[$i];

                        // column name to uppercase. Added as element if flag is set.
                        if($columns_uppercase) {
                            $row_arr[strtoupper($column)] = &$row_arr[$i];
                        }

                        // column name to lowercase. Added as element if flag is set.
                        if($columns_lowercase) {
                            $row_arr[strtolower($column)] = &$row_arr[$i];
                        }
                    }

                    $i++;
                }

                // add all information to return object
                $return->data[] = $row_arr;
            }

            // add rowcount to the return object
            $return->rows = count($return->data);

            // reset pointer to start position
            @mysql_data_seek($result, 0);
        }
    } else {
        // No SELECT query type, just do nothing
    }

    // store input flags and results for other functions like dbx_fetch_row()
    $GLOBALS['DBX_LAST_RESULT'] = $return; // result Object!
    $GLOBALS['DBX_LAST_FLAGS'] = $flags_arr;

    // return the integer or the result object
    return $return;
}

/**
 * Fetches rows from a query-result that had the DBX_RESULT_UNBUFFERED flag set
 *
 * @param stdClass $result_identifier
 * @return int|stdClass
 */
function dbx_fetch_row($result_identifier) {
    $return = null;

    if(in_array('DBX_RESULT_UNBUFFERED', $GLOBALS['DBX_LAST_FLAGS'])) {
        // try to fetch data
        $row = mysql_fetch_assoc($result_identifier->handle);
        if(!$row) {
            return 0; // no more rows available
        } else {
            $i = 0;
            $columns_arr = array();

            // create columns in array
            foreach($row as $column => $value) {
                $columns_arr[$i] = $value;

                // if set: DBX_RESULT_ASSOC => create reference variable to index-variable '$i'
                if(in_array('DBX_RESULT_ASSOC', $GLOBALS['DBX_LAST_FLAGS'])) {
                    $columns_arr[$column] = &$columns_arr[$i]; // as reference: if '$i' is changed, '$column' has to be changed too, automatically
                }

                $i++;
            }

            // add all information to return object
            $return = $GLOBALS['DBX_LAST_RESULT']; // copy last object
            $return->data = array(); // clean any data in object
            $return->data[] = $columns_arr; // add data row
            $return->rows = 1;
        }

    } else {
        // fetch_row() only works for queries without direct resultdata
        $return = 0;
    }

    // return
    return $return;
}

/**
 * Compare two rows for sorting purposes
 *
 * @param $row_a
 * @param $row_b
 * @param $column_key
 * @param int $flags
 * @return int
 */
function dbx_compare($row_a, $row_b, $column_key, $flags = 0) {

    // beautify flags
    $flags_arr = detect_dbx_compare_constants($flags);

    // element sort direction
    $order = 'asc';
    if(in_array('DBX_CMP_DESC', $flags_arr)) {
        $order = 'desc';
    }

    // element comparision type
    $compare_type = 'normal';
    if(in_array('DBX_CMP_TEXT', $flags_arr)) {
        $compare_type = 'text';
        $row_a[$column_key] = (string)$row_a[$column_key];
        $row_b[$column_key] = (string)$row_b[$column_key];

    } elseif(in_array('DBX_CMP_NUMBER', $flags_arr)) {
        $compare_type = 'number';
        $row_a[$column_key] = doubleval($row_a[$column_key]);
        $row_b[$column_key] = doubleval($row_b[$column_key]);
    }

    if($row_a == null || $row_b == null) {
        return 0;
    }

    if($row_a[$column_key] == $row_b[$column_key]) {
        return 0; // match
    } else {
        if(strcasecmp($row_a[$column_key], $row_b[$column_key]) < 0) {
            if($order == 'asc') {
                // asc
                return -1;
            } else {
                // desc
                return 1;
            }
        } elseif(strcasecmp($row_a[$column_key], $row_b[$column_key]) > 0) {
            if($order == 'asc') {
                // asc
                return 1;
            } else {
                // desc
                return -1;
            }
        }
    }
}


/**
 * Sort a result from a dbx_query by a custom sort function
 *
 * @param $result
 * @param $user_compare_function
 */
function dbx_sort(&$result, $user_compare_function) {
    if (version_compare(PHP_VERSION, '5.4', '<')) {
        // PHP < 5.4
        usort(&$result->data, $user_compare_function);
    } else {
        // PHP 5.4+
        usort($result->data, $user_compare_function);
    }
}