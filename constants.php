<?php

// SUCCESS CODES
define("SUCCESS", 1000);

// INFO CODES
define("DEALERS_TURN", 3000);

// WARNING CODES
define("NOTHING_AFFECTED", 4000);
define("EMPTY_SELECT", 4001);

// FAILURE CODES
define("FAILURE", 5000);
define("USERNAME_ALREADY_EXISTS", 5001);
define("USERNAME_ALREADY_IN_GAME", 5002);
define("USERNAME_ALREADY_IN_LEADERBOARD", 5003);
define("LOGIN_FAILED", 5004);
define("INVALID_TOKEN", 5005);

// SQL TYPES
define("SQL_SELECT", 9000);
define("SQL_INSERT", 9001);
define("SQL_UPDATE", 9002);
define("SQL_DELETE", 9003);

// MYSQL ERROR CODES
define("ER_DUP_ENTRY", 1062);

?>
