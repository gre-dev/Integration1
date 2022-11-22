<?php

require_once __DIR__ . '/../bootstrap.php';

require_once PROJECT_ROOTDIR . '/Classes/API/AccountAPI.php';
require_once PROJECT_ROOTDIR . '/Classes/Exceptions/InvalidHttpMethodException.php';
require_once PROJECT_ROOTDIR . '/Classes/Exceptions/EmptyBodyRequestException.php';

require_once __DIR__ . '/helpers.php';

if (is_method_post())
{

    $input = file_get_contents('php://input');

    if ($input) {

        $json_array = json_decode($input, true) ?? null;
                    
        if ($json_array !== false) {

            $email = param_post_json($json_array,'email');

            $password = param_post_json($json_array,'password');

            if ($email !== null && $password !== null)
            {

                $api = new AccountAPI();
                $api->login($email,$password);
            }
            
            else {
                http_response_code(400);
                
                if ($email === null) {
                    die('missing email');
                }
            
                if ($password === null) {
                    die('missing password');
                }
            }
        }
    }   

        else {
            http_response_code(400);
            die('missed request body');
        }
    }

else {
    http_response_code(405);
    die();
}


?>
