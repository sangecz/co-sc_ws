<?php

include_once dirname(__FILE__) . '/Response.php';

/**
 * Responder class responses via slim framework instance to a client. It's a singleton.
 *
 * @author Petr Marek
 * @license Apache 2.0 http://www.apache.org/licenses/LICENSE-2.0
 */
class Responder {


    /**
     * Singleton method, returns instance of the class.
     *
     * @access public
     * @return Responder
     */
    public static function getInstance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new Responder();
        }
        return $inst;
    }

    /**
     * Private constructor for usage in singleton pattern.
     * @access private
     */
    private function __construct()
    {

    }

    /**
     * Echoes JSON response to a client. If there was an error while processing,
     * it stops the application by Slim framework stop method.
     *
     * @param int $status_code HTTP response code
     * @param Response $response JSON response
     * @access public
     */
    public static function echoResponse($status_code, $response) {
        $app = \Slim\Slim::getInstance();
        // Http response code
        $app->status($status_code);

        // setting response content type to json
        $app->contentType('application/json');

//        var_dump($response);
        echo json_encode($response->jsonSerialize());

        if($status_code == 400 || $status_code == 404) {
            $app->stop();
        }
    }

} 