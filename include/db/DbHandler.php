<?php

/**
 * This class greatly extends tutorial mentioned below
 *
 * @author Ravi Tamada, Petr Marek
 * @link URL Tutorial link http://www.androidhive.info/2014/01/how-to-create-rest-api-for-android-app-using-php-slim-and-mysql-day-12-2/
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        require_once dirname(__FILE__) . '/Protocol.php';
        require_once dirname(__FILE__) . '/Script.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($name, $email, $password) {
        require_once 'PassHash.php';

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating API key
            $api_key = $this->generateApiKey();
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, user_role_id) "
                        ." values(?, ?, ?, ?, ".USER_ROLE_NOBODY.")");
            $stmt->bind_param("ssss", $name, $email, $password_hash, $api_key);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT name, email, api_key, created_at, user_role_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($name, $email, $api_key, $created_at, $user_role_id);
            $stmt->fetch();
            $user = array();
            $user["name"] = $name;
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["created_at"] = $created_at;
            $user["user_role_id"] = $user_role_id;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user role id by api key
     * @param String $api_key user api key
     */
    public function getUserRoleId($api_key) {
        $stmt = $this->conn->prepare("SELECT user_role_id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_role_id);
            $stmt->fetch();
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_role_id;
        } else {
            return NULL;
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /**
     * Creating new script
     * @param String $user_id user id to whom script belongs to
     * @param Script $script obj
     */
    public function createScript($user_id, $script) {

        $resp = $this->isProtocolAccessible($user_id, $script->getProtocolId());
        if($resp->num_rows <= 0) return WS_CODE_REST_AUTH;

        $sql = "INSERT INTO scripts "
              ." (ps_role_id,name ,description,address,content,protocol_id) "
              ." VALUES (?,?,?,?,?,?)";

        $stmt = $this->conn->prepare($sql);

        $ps_role_id = $script->getPsRoleId();
        $name = $script->getName();
        $description = $script->getDescription();
        $address = $script->getAddress();
        $content = $script->getContent();
        $protocol_id = $script->getProtocolId();

        try {
            $stmt->bind_param("issssi", $ps_role_id, $name, $description, $address, $content, $protocol_id);
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // script row created
            // now assign the script to user
            $new_script_id = $this->conn->insert_id;
            $res = $this->createUserScript($user_id, $new_script_id);
            if ($res) {
                // script created successfully
                return $new_script_id;
            } else {
                // script failed to create
                return NULL;
            }
        } else {
            // script failed to create
            return NULL;
        }
    }

    /**
     * Creating new protocol
     * @param int $user_id user id
     * @param int $role_id default user_role id, could be overrided inside protocol creation request
     * @param Protocol $protocol protocol object
     */
    public function createProtocol($user_id, $protocol) {
        $p = Protocol::withProtocol($protocol);

        $sql = "INSERT INTO protocols(name, description, ps_role_id, port, version, sshArgs, level, passwd, "
            . "login, authPasswd, privPasswd, privProto, authProto, community, protocol_type_id"
            . ") values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $this->conn->prepare($sql);

        $name = $p->getName();
        $desc = $p->getDesc();
        $ps_role_id = $p->getRole();
        $port = $p->getPort();
        $ver = $p->getVersion();
        $sshArgs = $p->getSshArgs();
        $lvl = $p->getLevel();
        $passwd = $p->getPasswd();
        $login = $p->getLogin();
        $authPasswd = $p->getAuthPasswd();
        $privPasswd = $p->getPrivPasswd();
        $privProto = $p->getPrivProto();
        $authProto = $p->getAuthProto();
        $community = $p->getCommunity();
        $protocol_type_id = $p->getType();

        $ret = $stmt->bind_param("ssiisssssssssss", $name, $desc, $ps_role_id, $port, $ver,
            $sshArgs, $lvl, $passwd, $login, $authPasswd, $privPasswd, $privProto, $authProto, $community,
            $protocol_type_id);

        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // protocol row created
            // now assign the protocol to user
            $new_protocol_id = $this->conn->insert_id;
            $res = $this->createUserProtocol($user_id, $new_protocol_id);
            if ($res) {
                // protocol created successfully
                return $new_protocol_id;
            } else {
                // protocol failed to create
                return NULL;
            }
        } else {
            // protocol failed to create
            return NULL;
        }
    }

    /**
     * Fetching all protocol types
     */
    public function getAllProtocolTypes() {
        $res = $this->conn->query("SELECT * FROM protocol_type");
        return $res;
    }

    /**
     * Fetching single script
     * @param String $script_id id of the script
     */
    public function getScript($script_id, $user_id) {
        $sql = "SELECT s.* "
              ." FROM scripts s, user_script us "
              ." WHERE s.id = ? "
              ." AND us.script_id = s.id AND us.user_id = ?";


        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $script_id, $user_id);
        if ($stmt->execute()) {
            $res = array();
            $id = "";
            $ps_role_id = "";
            $name = "";
            $description = "";
            $address = "";
            $content = "";
            $created_at = "";
            $protocol_id = "";
            $stmt->bind_result($id,	$ps_role_id, $name, $description, $address, $content, $created_at, $protocol_id);

            $stmt->fetch();
            $res["id"] = $id;
            $res["ps_role_id"] = $ps_role_id;
            $res["name"] = $name;
            $res["description"] = $description;
            $res["address"] = $address;
            $res["content"] = $content;
            $res["created_at"] = $created_at;
            $res["protocol_id"] = $protocol_id;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching single protocol
     * @param String $protocol_id id of the protocol
     */
    public function getProtocol($protocol_id) {
        $sql = "SELECT p.* "
            ." FROM protocols p "
            ." WHERE p.id = ? ";


        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $protocol_id);
        if ($stmt->execute()) {
            $res = array();

            $stmt->bind_result($id,	 $name, $description, $ps_role_id, $port, $version, $sshArgs, $level, $passwd,
                $login, $authPasswd, $privPasswd, $privProto, $authProto, $community, $protocol_type_id, $created_at);

            $stmt->fetch();
            $res["id"] = $id;
            $res["name"] = $name;
            $res["description"] = $description;
            $res["ps_role_id"] = $ps_role_id;
            $res["port"] = $port;
            $res["version"] = $version;
            $res["sshArgs"] = $sshArgs;
            $res["level"] = $level;
            $res["passwd"] = $passwd;
            $res["login"] = $login;
            $res["authPasswd"] = $authPasswd;
            $res["privPasswd"] = $privPasswd;
            $res["privProto"] = $privProto;
            $res["authProto"] = $authProto;
            $res["community"] = $community;
            $res["protocol_type_id"] = $protocol_type_id;
            $res["created_at"] = $created_at;

            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }


    /**
     * Fetching all protocols
     */
    public function getAllProtocols() {
        $stmt = $this->conn->prepare("SELECT * FROM protocols");
        $stmt->execute();
        $protocols = $stmt->get_result();
        $stmt->close();
        return $protocols;
    }

    /**
     * Fetching all scripts
     */
    public function getAllScripts() {
        $stmt = $this->conn->prepare("SELECT * FROM scripts");
        $stmt->execute();
        $protocols = $stmt->get_result();
        $stmt->close();
        return $protocols;
    }

    /**
     * Fetching all public or user's protocols
     */
    public function getAllUserPublicProtocols($user_id) {
        $sql = "SELECT DISTINCT p.* FROM protocols p, user_protocol up WHERE p.ps_role_id = ? "
             . "OR (p.id = up.protocol_id AND up.user_id = ? )";
        $stmt = $this->conn->prepare($sql);
        $p = PS_ROLE_PUBLIC;
        $stmt->bind_param("ii", $p, $user_id);
        $stmt->execute();
        $protocols = $stmt->get_result();
        $stmt->close();
        return $protocols;
    }

    /**
     * Fetching all public or user's protocols
     */
    public function getAllUserPublicScripts($user_id) {
        $sql = "SELECT DISTINCT s.* FROM scripts s, user_script us WHERE s.ps_role_id = ? "
            . "OR (s.id = us.script_id AND us.user_id = ? )";
        $stmt = $this->conn->prepare($sql);
        $p = PS_ROLE_PUBLIC;
        $stmt->bind_param("ii", $p, $user_id);
        $stmt->execute();
        $protocols = $stmt->get_result();
        $stmt->close();
        return $protocols;
    }

    /**
     * Updating protocol
     * @param String $user_id
     * @param String $protocol_id id of the protocol
     * @param Protocol $protocol
     */

    public function updateProtocol($user_id, $user_role_id, $protocol_id, $protocol) {
        if($user_role_id != USER_ROLE_ADMIN) {
            $resp = $this->isProtocolAccessible($user_id, $protocol_id);
            if ($resp->num_rows <= 0) return WS_CODE_REST_AUTH;
        }
//
        $sql = 'UPDATE protocols p, user_protocol up '
              .'SET p.name = ?, p.description = ?, p.ps_role_id = ?, p.port = ?, '
              .'p.version = ?, p.sshArgs = ?, p.level = ?, p.passwd = ?, '
              .'p.login = ?, p.authPasswd = ?, p.privPasswd = ?, p.privProto = ?, '
              .'p.authProto = ?, p.community = ?, p.protocol_type_id = ? '
              .'WHERE p.id = ? AND p.id = up.protocol_id AND up.user_id = ?';

        if ($stmt = $this->conn->prepare($sql)) {
            $name = $protocol->getName();
            $description = $protocol->getDesc();
            $ps_role_id = $protocol->getRole();
            $port = $protocol->getPort();
            $version = $protocol->getVersion();
            $sshArgs = $protocol->getSshArgs();
            $level = $protocol->getLevel();
            $passwd = $protocol->getPasswd();
            $login = $protocol->getLogin();
            $authPasswd = $protocol->getAuthPasswd();
            $privPasswd = $protocol->getPrivPasswd();
            $privProto = $protocol->getPrivProto();
            $authProto = $protocol->getAuthProto();
            $community = $protocol->getCommunity();
            $protocol_type_id = $protocol->getType();



            try {
                $stmt->bind_param("ssiisssssssssssii", $name, $description, $ps_role_id, $port , $version, $sshArgs,
                    $level,  $passwd, $login,
                    $authPasswd,$privPasswd,$privProto,
                    $authProto,$community,$protocol_type_id, $protocol_id, $user_id);
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }

            $stmt->execute();
            $num_affected_rows = $stmt->affected_rows;
            $stmt->close();
            return $num_affected_rows > 0;
        }
    }

    /**
     * Updating script
     * @param String $user_id
     * @param String $script_id id of the script
     * @param Script $script
     */

    public function updateScript($user_id, $user_role_id, $script_id, $script) {
        if($user_role_id != USER_ROLE_ADMIN) {

            $resp = $this->isScriptAccessible($user_id, $script_id);
            if ($resp->num_rows <= 0) return WS_CODE_REST_AUTH;

            $resp = $this->isProtocolAccessible($user_id, $script->getProtocolId());
            if ($resp->num_rows <= 0) return WS_CODE_REST_AUTH;
        }

        $sql = 'UPDATE scripts s, user_script us '
            .'SET s.name = ?, s.description = ?, s.ps_role_id = ?, '
            .'s.content = ?, s.address = ?, s.protocol_id = ? '
            .'WHERE s.id = ? AND s.id = us.script_id AND us.user_id = ?';

        if ($stmt = $this->conn->prepare($sql)) {
            $name = $script->getName();
            $description = $script->getDescription();
            $ps_role_id = $script->getPsRoleId();
            $content = $script->getContent();
            $address = $script->getAddress();
            $protocol_id = $script->getProtocolId();

            try {
                $stmt->bind_param("ssissiii", $name, $description, $ps_role_id, $content, $address, $protocol_id,
                    $script_id, $user_id);
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }

            $stmt->execute();
            $num_affected_rows = $stmt->affected_rows;
            $stmt->close();
            return $num_affected_rows > 0;
        }

    }

    /**
     * Deleting a protocol
     * @param String $protocol_id id of the protocol to delete
     */
    public function deleteProtocol($user_id, $user_role_id, $protocol_id) {
        if($user_role_id != USER_ROLE_ADMIN) {
            $resp = $this->isProtocolAccessible($user_id, $protocol_id);
            if ($resp->num_rows <= 0) {
                return WS_CODE_REST_AUTH;
            }
        }

        $sql =  "DELETE up \n"
              . "FROM user_protocol up \n"
              . "INNER JOIN protocols p \n"
              . "ON p.id = up.protocol_id \n"
              . "WHERE p.id = ? \n";

        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("i", $protocol_id);
            $stmt->execute();
            $num_affected_rows = $stmt->affected_rows;
            $stmt->close();
            if($num_affected_rows > 0) {
                $sql = "DELETE p FROM protocols p WHERE p.id = ?";
                if ($stmt = $this->conn->prepare($sql)) {
                    $stmt->bind_param("i", $protocol_id);
                    $stmt->execute();
                    $num_affected_rows = $stmt->affected_rows;
                    $stmt->close();
                        return $num_affected_rows > 0;
                }
//                else {
//                    printf("Errormessage2: %s\n", $this->conn->error);
//                }
            }
        }
    }

    /**
     * Deleting a script
     * @param String $script_id id of the script to delete
     */
    public function deleteScript($user_id, $user_role_id, $script_id) {
        if($user_role_id != USER_ROLE_ADMIN) {
            $resp = $this->isProtocolAccessible($user_id, $script_id);
            if ($resp->num_rows <= 0) {
                return WS_CODE_REST_AUTH;
            }
        }

        $sql =  "DELETE us \n"
            . "FROM user_script us \n"
            . "INNER JOIN scripts s \n"
            . "ON s.id = us.script_id \n"
            . "WHERE s.id = ? \n";

        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("i", $script_id);
            $stmt->execute();
            $num_affected_rows = $stmt->affected_rows;
            $stmt->close();
            if($num_affected_rows > 0) {
                $sql = "DELETE s FROM scripts s WHERE s.id = ?";
                if ($stmt = $this->conn->prepare($sql)) {
                    $stmt->bind_param("i", $script_id);
                    $stmt->execute();
                    $num_affected_rows = $stmt->affected_rows;
                    $stmt->close();
                    return $num_affected_rows > 0;
                }
            }
        }
    }

    /**
     * Function to assign a protocol to user
     * @param String $user_id id of the user
     * @param String $protocol_id id of the protocol
     */

    public function createUserProtocol($user_id, $protocol_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_protocol (user_id, protocol_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $protocol_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

    /**
     * Function to assign a script to user
     * @param String $user_id id of the user
     * @param String $protocol_id id of the protocol
     */

    public function createUserScript($user_id, $script_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_script (user_id, script_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $script_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

    /**
     * Checks against malicious tries to alter DB data
     */
    public function isProtocolAccessible($user_id, $protocol_id) {
        $sql = "SELECT DISTINCT p.id FROM protocols p, user_protocol up WHERE (p.ps_role_id = ? "
            . "OR (p.id = up.protocol_id AND up.user_id = ? )) AND p.id = ?";
        $stmt = $this->conn->prepare($sql);
        $p = PS_ROLE_PUBLIC;
        $stmt->bind_param("iii", $p, $user_id, $protocol_id);
        $stmt->execute();
        $protocols = $stmt->get_result();
        $stmt->close();
        return $protocols;
    }

    /**
     * Checks against malicious tries to alter DB data
     */
    public function isScriptAccessible($user_id, $script_id) {
        $sql = "SELECT DISTINCT s.id FROM scripts s, user_script us WHERE (s.ps_role_id = ? "
            . "OR (s.id = us.script_id AND us.user_id = ? )) AND s.id = ?";
        $stmt = $this->conn->prepare($sql);
        $p = PS_ROLE_PUBLIC;
        $stmt->bind_param("iii", $p, $user_id, $script_id);
        $stmt->execute();
        $protocols = $stmt->get_result();
        $stmt->close();
        return $protocols;
    }

}

?>
