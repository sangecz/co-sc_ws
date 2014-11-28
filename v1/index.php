<?php

require_once '../include/DbHandler.php';
require_once '../include/Config.php';
require_once '../include/Responder.php';
require_once '../include/Response.php';
require_once '../include/PassHash.php';
require_once '../include/JSONParser.php';
require '.././libs/Slim/Slim.php';

// TODO utridit adresarovou strukturu

// TODO script RUN  =list jeden
// TODO script CREATE
// TODO script UPDATE
// TODO script DEL
// TODO script LIST
// TODO protocol UPDATE
// TODO protocol DEL

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;
$user_role_id = NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = new Response();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $msg = "Access Denied. Invalid Api key";
            $response->setWs(WS_CODE_REST_AUTH,$msg,true);
            Responder::getInstance()->echoResponse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            global $user_role_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
            $user_role_id = $db->getUserRoleId($api_key);
        }
    } else {
        // api key is missing in header
        $msg = "Api key is misssing";
        $response->setWs(WS_CODE_REST_AUTH,$msg,true);
        Responder::getInstance()->echoResponse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('name', 'email', 'password'));

            $response = new Response();

            // reading post params
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $password = $app->request->post('password');

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($name, $email, $password);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $msg = "You are successfully registered";
                $response->setWs(WS_CODE_OK, $msg, false);
            } else if ($res == USER_CREATE_FAILED) {
                $msg = "An error occurred while registereing";
                $response->setWs(WS_CODE_REST_REGISTER, $msg, true);
            } else if ($res == USER_ALREADY_EXISTED) {
                $msg = "Sorry, this email already existed";
                $response->setWs(WS_CODE_REST_REGISTER, $msg, true);
            }
            // echo json response
            Responder::echoResponse(201, $response);
        });

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));

            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = new Response();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the user by email
                $user = $db->getUserByEmail($email);

                if ($user == NULL) {
                    // unknown error occurred
                    $msg = "An error occurred. Please try again";
                    $response->setWs(WS_CODE_REST_LOGIN, $msg, true);
                } else if ($user['user_role_id'] == USER_ROLE_NOBODY){
                    $msg = "Role hasn't been assigned. Contact DB administrator.";
                    $response->setWs(WS_CODE_REST_LOGIN, $msg, true);
                } else {
                    $data = array();
                    $data['name'] = $user['name'];
                    $data['email'] = $user['email'];
                    $data['apiKey'] = $user['api_key'];
                    $data['createdAt'] = $user['created_at'];
                    $data['user_role_id'] = $user['user_role_id'];
                    $response->setData($data);
                    $response->setWs(WS_CODE_OK, "Logged in correctly.", false);
                }
            } else {
                // user credentials are wrong
                $msg = 'Login failed. Incorrect credentials';
                $response->setWs(WS_CODE_REST_LOGIN, $msg, true);
            }

            Responder::echoResponse(200, $response);
        });

/*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */

/**
 * Listing all protocols of particual user
 * method GET
 * url /protocols
 */
$app->get('/protocols', 'authenticate', function() {
    global $user_id;
    $db = new DbHandler();

    // fetching all user protocols
    // TODO access controll
    $result = $db->getAllUserProtocols($user_id);

    $response = new Response();
    $msg = "Protocols fetched correctly.";
    $response->setWs(WS_CODE_OK, $msg, false);
    $data = array();
    $data["protocols"] = array();

     //looping through result and preparing protocols array
    if($result != NULL) {
        while ($protocol = $result->fetch_assoc()) {
            $tmp = array();
            $tmp["id"] = $protocol["id"];
            $tmp["name"] = $protocol["name"];
            $tmp["description"] = $protocol["description"];
            $tmp["roleId"] = $protocol["ps_role_id"];
            $tmp["type"] = $protocol["protocol_type_id"];
            $tmp["createdAt"] = $protocol["created_at"];
            if($tmp["type"] == SNMP_STR) {
                $tmp['snmpAttr'] = array();
                $tmp['snmpAttr']['port'] = $protocol['port'];
                $tmp['snmpAttr']['version'] = $protocol['version'];
                $tmp['snmpAttr']['auth'] = array();
                $tmp['snmpAttr']['auth']['level'] = $protocol['level'];
                $tmp['snmpAttr']['auth']['login'] = $protocol['login'];
                $tmp['snmpAttr']['auth']['authPasswd'] = $protocol['authPasswd'];
                $tmp['snmpAttr']['auth']['privPasswd'] = $protocol['privPasswd'];
                $tmp['snmpAttr']['auth']['privProto'] = $protocol['privProto'];
                $tmp['snmpAttr']['auth']['authProto'] = $protocol['authProto'];
                $tmp['snmpAttr']['auth']['community'] = $protocol['community'];
            }
            if($tmp["type"] == SNMP_STR) {
                $tmp['sshAttr'] = array();
                $tmp['sshAttr']['port'] = $protocol['port'];
                $tmp['sshAttr']['sshArgs'] = $protocol['sshArgs'];
                $tmp['sshAttr']['auth'] = array();
                $tmp['sshAttr']['auth']['login'] = $protocol['login'];
                $tmp['sshAttr']['auth']['passwd'] = $protocol['passwd'];
            }
            array_push($data["protocols"], $tmp);
        }

        $response->setData($data);
    } else {
        $msg = "Could not get protocols from db.";
        $response->setWs(WS_CODE_REST_DB, $msg, true);
    }

    Responder::echoResponse(200, $response);
});

// TODO prototyp pro RUN script
/**
 * Listing single task of particual user
 * method GET
 * url /tasks/:id
 * Will return 404 if the task doesn't belongs to user
 */
$app->get('/tasks/:id', 'authenticate', function($task_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetch task
            $result = $db->getTask($task_id, $user_id);

            if ($result != NULL) {
                $response["error"] = false;
                $response["id"] = $result["id"];
                $response["task"] = $result["task"];
                $response["status"] = $result["status"];
                $response["createdAt"] = $result["created_at"];
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoResponse(404, $response);
            }
        });


//$app->post('/scripts', 'authenticate', function() use ($app) {
//    // check for required params
//    verifyRequiredParams(array('script'));
//
//    $response = array();
//    $script = $app->request->post('script');
//
//    global $user_id;
//    $db = new DbHandler();
//
//    // creating new script
//    $script_id = $db->createScript($user_id, $script);
//
//    if ($script_id != NULL) {
//        $response["error"] = false;
//        $response["message"] = "Script created successfully";
//        $response["script_id"] = $script_id;
//        echoRespnse(201, $response);
//    } else {
//        $response["error"] = true;
//        $response["message"] = "Failed to create script. Please try again";
//        echoRespnse(200, $response);
//    }
//});


$app->post('/protocols', 'authenticate', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('protocol'));
    $protocolJSON = $app->request->post('protocol');

    $protocolObj = parseProtocol($protocolJSON);

    global $user_id;
    $db = new DbHandler();

    $response = new Response();
    // creating new protocol
    $protocol_id = $db->createProtocol($user_id, $protocolObj);

    if ($protocol_id != NULL) {
        $msg = "Protocol created successfully";
        $data = array();
        $data["script_id"] = $protocol_id;
        $response->setData($data);
        $response->setWs(WS_CODE_OK, $msg, false);
        Responder::echoResponse(201, $response);
    } else {
        $msg = "Failed to create protocol. Please try again";
        $response->setWs(WS_CODE_REST_DB, $msg, true);
        Responder::echoResponse(200, $response);
    }

});

/**
 * Updating existing protocol
 * method PUT
 * params protocol, status
 * url - /protocols/:id
 */
// TODO prototyp update protocol/script
$app->put('/protocols/:id', 'authenticate', function($protocol_id) use($app) {
            // check for required params
            verifyRequiredParams(array('protocol'));

            global $user_id;            
            $protocol = $app->request->put('protocol');
            $protocolObj = parseProtocol($protocol);

            $db = new DbHandler();
            $response = new Response();

            // updating protocol
            $result = $db->updateProtocol($user_id, $protocol_id, $protocolObj);
            if ($result) {
                // protocol updated successfully
                $msg = "Protocol updated successfully";
                $response->setWs(WS_CODE_OK, $msg, false);
            } else {
                // protocol failed to update
                $msg = "Protocol failed to update. Please try again!";
                $response->setWs(WS_CODE_REST_UPDATE, $msg, false);
            }
            Responder::echoResponse(200, $response);
        });

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
// TODO prototyp delete protocol/script
$app->delete('/tasks/:id', 'authenticate', function($task_id) use($app) {
            global $user_id;

            $db = new DbHandler();
            $response = array();
            $result = $db->deleteTask($user_id, $task_id);
            if ($result) {
                // task deleted successfully
                $response["error"] = false;
                $response["message"] = "Task deleted succesfully";
            } else {
                // task failed to delete
                $response["error"] = true;
                $response["message"] = "Task failed to delete. Please try again!";
            }
            echoResponse(200, $response);
        });

/**
 * Verifying required params posted or not
 * @param string $required_fields parameters
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        $app = \Slim\Slim::getInstance();
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = new Response();

        $msg = "Required field(s) '" . substr($error_fields, 0, -2) . "' is missing or empty";
        $response->setWs(WS_CODE_REQUIRED, $msg, true);

        Responder::echoResponse(400, $response);

        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = new Response();
        $msg = 'Email address is not valid';
        $response->setWs(WS_CODE_REST_REGISTER, $msg, true);
        Responder::getInstance()->echoResponse(400, $response);
        $app->stop();
    }
}

/**
 * Parses and checks values of provided protocol
 * @param string $protocolJSON JSON string
 * @return Protocol
 */
function parseProtocol($protocolJSON) {
    $protocol = json_decode($protocolJSON);

    if($protocol == NULL) {
        $response = new Response();
        $msg = "Bad JSON syntax.";
        $response->setWs(WS_CODE_JSON_SYNTAX, $msg, true);
        Responder::echoResponse(400, $response);
    }

    $parser = new JSONFormatChecker();
    $parser->checkProtocolMandatory($protocol);
    $parser->checkProtocolOpt($protocol);

    return $parser->getProtocol();
}

$app->run();
?>