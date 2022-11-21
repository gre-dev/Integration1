<?php

/**
 * checks for http method type, is it GET.
 *
 * @return true if the http method is GET
 * @return false if the http method is not GET
**/

function is_method_get() {
        
    $method = $_SERVER["REQUEST_METHOD"] ?? '';
    return $method === 'GET';
        
}


/**
 * checks for http method type, is it POST.
 *
 * @return true if the http method is POST
 * @return false if the http method is not POST
**/

function is_method_post() {
            
    $method = $_SERVER["REQUEST_METHOD"] ?? '';
    return $method === 'POST';
 
}

/**
 * checks if the action provided in the query string.
 *
 * @param string $action the action name (e.g. remove, create, ...etc)
 * @return boolean true if provided, false otherwise
 *
 * @uses $_GET to check the query parameter.
 **/

function is_url_action_provided(string $action) {
            
    $provided = $_GET['action'] ??  '' ;
    
    return $provided === $action; 
}

/**
 * gets a specific parameter from the query string.
 *
 * @param string $param the parameter name
 * @return mixed the parameter value, or null if it's not provided.
 *
 * @uses $_GET to get the parameter value.
 **/

function param_uri(string $param) {

    $param = $_GET[$param] ?? null;
    return $param;
}

/**
 * gets a specific parameter from a json array (returned from the json_encode function, using the default flags).
 * 
 * @param array $json_array the json parameter array
 * @param string $param the parameter name
 * @return false if the $json_array is false, this indicate the json_encode has failed before you used this function.
 *
 * @return string the parameter value.
 * @return null if the parameter not found in $json_array.
 *
 **/

function param_post_json($json_array,string $param) {

    if ($json_array === false) return false;
    
    $jsonparam = $json_array[$param] ?? null;
    return $jsonparam;
}

?>
