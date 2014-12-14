<?php
/**
 * This file greatly extends tutorial mentioned below
 *
 * @author Petr Marek, Petr Marek
 * @link URL Tutorial link http://www.androidhive.info/2014/01/how-to-create-rest-api-for-android-app-using-php-slim-and-mysql-day-12-2/
 */

// TODO support more HTTP response codes: http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
// TODO distinguish  between auth and non existence in return messages

require_once '../include/db/DbHandler.php';
require_once '../Config.php';
require_once '../include/resp/Responder.php';
require_once '../include/resp/Response.php';
require_once '../include/db/PassHash.php';
require_once '../include/run/ScriptRunner.php';
require_once '../include/parsers/JSONRequestParser.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// Global Variables
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
            Responder::echoResponse(401, $response);
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
        Responder::echoResponse(400, $response);
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
$app->post('/register/', function() use ($app) {
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
$app->post('/login/', function() use ($app) {
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

/*
 * ---------------------------- LIST METHODS: GET-------------------------------
 */

/**
 * Listing all public and user's protocols
 * method GET
 * url /protocols
 */
$app->get('/protocols/', 'authenticate', function() {
    global $user_id, $user_role_id;
    $db = new DbHandler();

    // fetching protocols
    $result = NULL;
    if ($user_role_id == USER_ROLE_EXECUTOR || $user_role_id == USER_ROLE_EDITOR) {
        $result = $db->getAllUserPublicProtocols($user_id);
    }
    if ($user_role_id == USER_ROLE_ADMIN) {
        $result = $db->getAllProtocols();
    }

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
            $tmp["ps_role_id"] = $protocol["ps_role_id"];
            $tmp["type"] = $protocol["protocol_type_id"];
            $tmp["created_at"] = $protocol["created_at"];
            if($tmp["type"] == SNMP_STR) {
                $tmp['snmpAttr'] = array();
                $tmp['snmpAttr']['port'] = $protocol['port'];
                $tmp['snmpAttr']['version'] = $protocol['version'];
                $tmp['snmpAttr']['auth'] = array();
                $tmp['snmpAttr']['auth']['level'] = $protocol['level'];
                $tmp['snmpAttr']['auth']['login'] = $protocol['login'];
                $tmp['snmpAttr']['auth']['authPasswd'] = PassHash::decrypt($protocol['authPasswd']);
                $tmp['snmpAttr']['auth']['privPasswd'] = PassHash::decrypt($protocol['privPasswd']);
                $tmp['snmpAttr']['auth']['privProto'] = $protocol['privProto'];
                $tmp['snmpAttr']['auth']['authProto'] = $protocol['authProto'];
                $tmp['snmpAttr']['auth']['community'] = $protocol['community'];
            }
            if($tmp["type"] == SSH_STR) {
                $tmp['sshAttr'] = array();
                $tmp['sshAttr']['port'] = $protocol['port'];
                $tmp['sshAttr']['sshArgs'] = $protocol['sshArgs'];
                $tmp['sshAttr']['auth'] = array();
                $tmp['sshAttr']['auth']['login'] = $protocol['login'];
                $tmp['sshAttr']['auth']['passwd'] = PassHash::decrypt($protocol['passwd']);
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

/**
 * Listing all public and user's scripts
 * method GET
 * url /scripts
 */
$app->get('/scripts/', 'authenticate', function() {
    global $user_id, $user_role_id;
    $db = new DbHandler();

    // fetching scripts
    $result = NULL;
    if ($user_role_id == USER_ROLE_EXECUTOR || $user_role_id == USER_ROLE_EDITOR) {
        $result = $db->getAllUserPublicScripts($user_id);
    }
    if ($user_role_id == USER_ROLE_ADMIN) {
        $result = $db->getAllScripts();
    }

    $response = new Response();
    $msg = "Scripts fetched correctly.";
    $response->setWs(WS_CODE_OK, $msg, false);
    $data = array();
    $data["scripts"] = array();

    //looping through result and preparing scripts array
    if($result != NULL) {
        while ($script = $result->fetch_assoc()) {
            $tmp = array();
            $tmp["id"] = $script["id"];
            $tmp["ps_role_id"] = $script["ps_role_id"];
            $tmp["name"] = $script["name"];
            $tmp["description"] = $script["description"];
            $tmp["address"] = $script["address"];
            $tmp["content"] = $script["content"];
            $tmp["protocol_id"] = $script["protocol_id"];
            $tmp["created_at"] = $script["created_at"];
            array_push($data["scripts"], $tmp);
        }

        $response->setData($data);
    } else {
        $msg = "Could not get scripts from db.";
        $response->setWs(WS_CODE_REST_DB, $msg, true);
    }

    Responder::echoResponse(200, $response);
});

/**
 * Listing single script of particual user
 * method GET
 * url /scripts/:id/
 * Will return 404 if the script doesn't belongs to user
 */
$app->get('/scripts/:id/', 'authenticate', function($script_id) {
    global $user_id, $user_role_id;
    $response = array();
    $db = new DbHandler();

    $response = new Response();

    $script = $db->getScript($script_id, $user_id, $user_role_id);


    if($script == NULL) {
        $msg = "The requested resource doesn't exists";
        $response->setWs(WS_CODE_REST_DB, $msg, true);
        Responder::echoResponse(404, $response);
    }

//    $msg = "Script successfully run.";
//    $response->setWs(WS_CODE_OK, $msg,false);
    $data = array();
    $data['script'] = $script->getArray();

    $protocol = $db->getProtocol($data['script']['protocol_id']);
    if($protocol == NULL) {
        $msg = "The requested resource doesn't exists";
        $response->setWs(WS_CODE_REST_DB, $msg, true);
        Responder::echoResponse(404, $response);
    }

//    $data['protocol'] = $protocol->getArray();

    // run script and get response
    $scriptRunner = new ScriptRunner($script, $protocol);
    $response = $scriptRunner->process();

    // process response
    if(DEBUG == 1) {
        echo "FINAL_RESP::\n";
        var_dump($this->response);
    }

    if($response->getWs() != NULL) {
        Responder::echoResponse(400, $response);
    }
    $response->setWs(WS_CODE_OK, "Script run successfully", false);
    Responder::echoResponse(200, $response);
});

/*
 * ---------------------------- CREATE METHODS: POST------------------------------
 */

/**
 * Creates new protocol
 * method POST
 * url /protocols
 */
$app->post('/protocols/', 'authenticate', function() use ($app) {
    global $user_role_id;
    checkHasRightCUD($user_role_id);

    // check for required params
    verifyRequiredParams(array('protocol'));
    $protocolJSON = $app->request->post('protocol');

    $protocolObj = processProtocol($protocolJSON);

    global $user_id;
    $db = new DbHandler();

    $response = new Response();
    // creating new protocol
    $protocol_id = $db->createProtocol($user_id, $protocolObj);

    if ($protocol_id != NULL) {
        $msg = "Protocol created successfully";
        $data = array();
        $data["protocol_id"] = $protocol_id;
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
 * Creates new script
 * method POST
 * url /scripts
 */
$app->post('/scripts/', 'authenticate', function() use ($app) {
    global $user_role_id;
    checkHasRightCUD($user_role_id);

    // check for required params
    verifyRequiredParams(array('script'));

    $scriptJSON = $app->request->post('script');
    $protocolObj = processScript($scriptJSON);

    global $user_id;
    $db = new DbHandler();
    $response = new Response();

    // creating new script
    $script_id = $db->createScript($user_id, $user_role_id, $protocolObj);

    if ($script_id != NULL) {
        if($script_id === WS_CODE_REST_AUTH){
            handleAuthOrNonExistError();
        }
        $msg = "Script created successfully";
        $data = array();
        $data["script_id"] = $script_id;
        $response->setData($data);
        $response->setWs(WS_CODE_OK, $msg, false);
        Responder::echoResponse(201, $response);
    } else {
        $msg = "Failed to create script. Please try again";
        $response->setWs(WS_CODE_REST_DB, $msg, true);
        Responder::echoResponse(200, $response);
    }
});

/*
 * ---------------------------- UPDATE METHODS: PUT -----------------------------
 */

/**
 * Updating existing protocol
 * method PUT
 * params protocol
 * url - /protocols/:id/
 */
$app->put('/protocols/:id/', 'authenticate', function($protocol_id) use($app) {
            global $user_role_id;
            checkHasRightCUD($user_role_id);

            // check for required params
            verifyRequiredParams(array('protocol'));

            global $user_id;            
            $protocol = $app->request->put('protocol');
            $protocolObj = processProtocol($protocol);

            $db = new DbHandler();
            $response = new Response();

            // updating protocol
            $result = $db->updateProtocol($user_id, $user_role_id, $protocol_id, $protocolObj);

            if ($result === true) {
                // protocol updated successfully
                $msg = "Protocol updated successfully";
                $response->setWs(WS_CODE_OK, $msg, false);
            } else if($result === WS_CODE_REST_AUTH){
                    handleAuthOrNonExistError();
            } else {
                // protocol failed to update
                $msg = "Protocol failed to update. Please try again!";
                $response->setWs(WS_CODE_REST_UPDATE, $msg, false);
            }
            Responder::echoResponse(200, $response);
        });

/**
 * Updating existing script
 * method PUT
 * params script
 * url - /scripts/:id/
 */
$app->put('/scripts/:id/', 'authenticate', function($script_id) use($app) {
    global $user_role_id;
    checkHasRightCUD($user_role_id);

    // check for required params
    verifyRequiredParams(array('script'));

    global $user_id;
    $script = $app->request->put('script');
    $scriptObj = processScript($script);

    $db = new DbHandler();
    $response = new Response();

    // updating script
    $result = $db->updateScript($user_id, $user_role_id, $script_id, $scriptObj);

    if ($result === true) {
        // script updated successfully
        $msg = "Script updated successfully";
        $response->setWs(WS_CODE_OK, $msg, false);
    } else if ($result === WS_CODE_REST_AUTH){
            handleAuthOrNonExistError();
    } else {
        // script failed to update
        $msg = "Script failed to update. Please try again!";
        $response->setWs(WS_CODE_REST_UPDATE, $msg, false);
    }
    Responder::echoResponse(200, $response);
});
/*
 * ---------------------------- DELETE METHODS: DELETE ------------------------------
 */

/**
 * Deleting protocol. Users can delete public and theirs protocols
 * method DELETE
 * url /protocols
 */
$app->delete('/protocols/:id/', 'authenticate', function($protocol_id) use($app) {
    global $user_id;
    global $user_role_id;

    checkHasRightCUD($user_role_id);

    $db = new DbHandler();
    $response = new Response();

    $result = $db->deleteProtocol($user_id, $user_role_id, $protocol_id);

    if ($result === true) {
        // protocol deleted successfully
        $msg = "Protocol deleted succesfully";
        $response->setWs(WS_CODE_OK, $msg, false);
    } else if($result === WS_CODE_REST_AUTH){
        handleAuthOrNonExistError();
    } else {
        // protocol failed to delete
        $msg = "Protocol failed to delete. Please try again!";
        $response->setWs(WS_CODE_REST_DB, $msg, true);
    }
    Responder::echoResponse(200, $response);
});

/**
 * Deleting script. Users can delete public and theirs scripts
 * method DELETE
 * url /scripts
 */
$app->delete('/scripts/:id/', 'authenticate', function($script_id) use($app) {
    global $user_id;
    global $user_role_id;

    checkHasRightCUD($user_role_id);

    $db = new DbHandler();
    $response = new Response();

    $result = $db->deleteScript($user_id, $user_role_id, $script_id);

    if ($result === true) {
        // script deleted successfully
        $msg = "Script deleted succesfully";
        $response->setWs(WS_CODE_OK, $msg, false);
    } else if($result === WS_CODE_REST_AUTH){
        handleAuthOrNonExistError();
    } else {
        // script failed to delete
        $msg = "Script failed to delete. Please try again!";
        $response->setWs(WS_CODE_REST_DB, $msg, true);
    }
    Responder::echoResponse(200, $response);
});

/*
 * ---------------------------- HELPERS ---------------------------------
 */

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
        Responder::echoResponse(400, $response);
        $app->stop();
    }
}

/**
 * Parses and checks values of provided protocol
 * @param string $protocolJSON JSON string
 * @return Protocol
 */
function processProtocol($protocolJSON) {
    $protocol = json_decode($protocolJSON);

    if($protocol == NULL) {
        $response = new Response();
        $msg = "Bad JSON syntax.";
        $response->setWs(WS_CODE_JSON_SYNTAX, $msg, true);
        Responder::echoResponse(400, $response);
    }

    $parser = new JSONRequestParser();
    $parser->parseProtocol($protocol);

    return $parser->getProtocol();
}

/**
 * Parses and checks values of provided script
 * @param string $scriptJSON JSON string
 * @return Script
 */
function processScript($scriptJSON) {
    $script = json_decode($scriptJSON);

    if($script == NULL) {
        $response = new Response();
        $msg = "Bad JSON syntax.";
        $response->setWs(WS_CODE_JSON_SYNTAX, $msg, true);
        Responder::echoResponse(400, $response);
    }

    $parser = new JSONRequestParser();
    $parser->parseScript($script);

    return $parser->getScript();
}

/**
 * This shouldn't happen as soon as client uses REST api right
 * app shoudn't allow user's with these roles even proceed to this http method
 * @param $userRoleId
 */
function checkHasRightCUD($userRoleId){
    if($userRoleId == USER_ROLE_NOBODY ||  $userRoleId == USER_ROLE_EXECUTOR){
        $response = new Response();
        $msg = "You don't have rights to issue this operation.";
        $response->setWs(WS_CODE_REST_AUTH, $msg, true);
        Responder::echoResponse(400, $response);
    }
}

function handleAuthOrNonExistError(){
    $app = \Slim\Slim::getInstance();

    $response = new Response();
    $msg = "You don't have sufficient rights to do this operation or script/protocol is not accessible.";
    $response->setWs(WS_CODE_REST_AUTH, $msg, true);
    Responder::echoResponse(200, $response);

    $app->stop();
}

$app->run();
?>