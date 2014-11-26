<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        require_once dirname(__FILE__) . '/Protocol.php';
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
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status, role_id) "
                        ." values(?, ?, ?, ?, 1, ".USER_ROLE_NOBODY.")");
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

        return $response;
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
        $stmt = $this->conn->prepare("SELECT name, email, api_key, status, created_at, role_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($name, $email, $api_key, $status, $created_at, $role_id);
            $stmt->fetch();
            $user = array();
            $user["name"] = $name;
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["status"] = $status;
            $user["created_at"] = $created_at;
            $user["role_id"] = $role_id;
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
    public function getRoleId($api_key) {
        $stmt = $this->conn->prepare("SELECT role_id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($role_id);
            $stmt->fetch();
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $role_id;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user role name by role_id
     * @param int $role_id user's role
     */
    public function getRoleName($role_id) {
        $stmt = $this->conn->prepare("SELECT role_name FROM roles WHERE id = ?");
        $stmt->bind_param("i", $role_id);
        if ($stmt->execute()) {
            $stmt->bind_result($role_name);
            $stmt->fetch();
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $role_name;
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

    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createTask($user_id, $task) {
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
        $stmt->bind_param("s", $task);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }
//    public function createScript($user_id, $script) {
//        $stmt = $this->conn->prepare("INSERT INTO sripts(script) VALUES(?)");
//        $stmt->bind_param("s", $script);
//        $result = $stmt->execute();
//        $stmt->close();
//
//        if ($result) {
//            // script row created
//            // now assign the script to user
//            $new_script_id = $this->conn->insert_id;
//            $res = $this->createUserScript($user_id, $new_script_id);
//            if ($res) {
//                // script created successfully
//                return $new_script_id;
//            } else {
//                // script failed to create
//                return NULL;
//            }
//        } else {
//            // script failed to create
//            return NULL;
//        }
//    }
//////////////////////////////////////////////////////

    /**
     * Creating new protocol
     * @param int $user_id user id
     * @param int $role_id user's role id
     * @param Protocol $protocol protocol object
     */
    public function createProtocol($user_id, $role_id, $protocol) {
        $p = Protocol::withProtocol($protocol);

        $stmt = $this->conn->prepare("INSERT INTO `protocols`(`role_id`, `name`, `description`, `port`,"
            ." `version`,`sshArgs`, `level`, `passwd`, `login`, `authPasswd`, `privPasswd`, `authProto`,"
            . "`privProto`, `community`, `protocol_type_id`)".
            " VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $stmt->bind_param("issssssssssssss", $role_id, $p->getName(), $p->getDesc(), $p->getPort(),
            $p->getVersion(), $p->getSshArgs(), $p->getLevel(), $p->getPasswd(), $p->getLogin(),
            $p->getAuthPasswd(),  $p->getPrivPasswd(), $p->getAuthProto(), $p->getPrivProto(),
            $p->getCommunity(), $p->getType());
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
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $task, $status, $created_at);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["task"] = $task;
            $res["status"] = $status;
            $res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }

    /**
     * Fetching all user protocols
     * @param String $user_id id of the user
     */
    public function getAllUserProtocols($user_id) {
        // FIXME KURVAAAAAAAAAAAAAAAAAAAAAAAAAA
//        $stmt = $this->conn->query("SELECT p.* FROM protocols p, user_protocol up WHERE p.id = up.protocol_id AND up.user_id = ?");
//        $stmt->bind_param("i", $user_id);
//        $stmt->execute();
//        $tasks = $stmt->get_result();
//        $stmt->close();
//        return $tasks;
    }

    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `user_tasks` table method ------------------ */

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

    /**
     * Function to assign a task to user
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

}

?>
