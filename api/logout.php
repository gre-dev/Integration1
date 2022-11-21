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
        $api = new AccountAPI();
        $api->logout();
        
    }
    else {    
    throw new EmptyBodyRequestException();
    }
}

else {
throw new InvalidHttpMethodException();
}

?>
