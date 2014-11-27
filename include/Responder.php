<?php
/**
 * Created by PhpStorm.
 * User: sange
 * Date: 11/26/14
 * Time: 11:28 AM
 */

include_once dirname(__FILE__) . '/Response.php';

class Responder {


    public static function getInstance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new Responder();
        }
        return $inst;
    }
    private function __construct()
    {

    }

    /**
     * Echoing json response to client
     * @param Int $status_code Http response code
     * @param Response $response Json response
     */
    public static function echoResponse($status_code, $response) {
        $app = \Slim\Slim::getInstance();
        // Http response code
        $app->status($status_code);

        // setting response content type to json
        $app->contentType('application/json');

//        var_dump($response);
        echo json_encode($response->jsonSerialize());

        if($status_code == 400) {
            $app->stop();
        }
    }

} 