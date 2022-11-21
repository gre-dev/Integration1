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
                        
            $firstname = param_post_json($json_array,'firstname');
            $lastname = param_post_json($json_array,'lastname');
                        
            $api = new AccountAPI();
            $api->register($email, $password, $firstname, $lastname);
            
        }
    }

    else {
    throw new EmptyBodyRequestException();
    }
}


else {
throw new InvalidHttpMethodException();
}

?>
