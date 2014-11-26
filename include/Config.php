<?php
/**
 * Database configuration
 */
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'spring');
define('DB_HOST', 'localhost');
define('DB_NAME', 'co-sc');

define('USER_CREATED_SUCCESSFULLY', 0);
define('USER_CREATE_FAILED', 1);
define('USER_ALREADY_EXISTED', 2);

// user roles (roles' foreign keys)
define('USER_ROLE_EDITOR', 1);
define('USER_ROLE_EXECUTOR', 2);
define('USER_ROLE_NOBODY', 3);

///////////////////////////////////
define('DEFAULT_PRIVPROTO', "AES");
define('DEFAULT_AUTHPROTO', "SHA");
define('DEFAULT_SSH_PORT', 22);
define('DEFAULT_SNMP_PORT', 161);
define('SNMP_STR', "snmp");
define('SSH_STR', "ssh");
define('SUPPORTED_PROTOCOLS', 2);

define('WS_CODE_OK', 0); // OK
define('WS_CODE_DEPENDENCY', 1); // Required php function 'shell_exec()' is missing or not enabled.
define('WS_CODE_JSON_SYNTAX', 2); // JSON syntax error.
define('WS_CODE_REQUIRED', 3); // Required program 'xyz' is missing;
define('WS_CODE_BAD_VALUE', 4); // Bad JSON value: 'val' for 'parameter'.
//define('', 5); // Required protocol is SSH. (for listing)
define('WS_CODE_PROTOCOL_ERR', 6); // Protocol Error, see protocol->output for detailed information.
define('WS_CODE_REMOTE_CMD_ERR', 7); // Remote command Error, see cmd->output for detailed information.


?>
