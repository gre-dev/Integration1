<?php    
function is_method_get() {
        
    $method = $_SERVER["REQUEST_METHOD"] ?? '';
    return $method === 'GET';
        
}
        
function is_method_post() {
            
    $method = $_SERVER["REQUEST_METHOD"] ?? '';
    return $method === 'POST';
 
}

function is_url_action_provided(string $action) {
            
    $provided = $_GET['action'] ??  '' ;
    
    return $provided === $action; 
}

function param_uri(string $param) {

    $param = $_GET[$param] ?? null;
    return $param;
}


function param_post_json($json_array,string $param) {

    if ($json_array === false) return false;
    
    $jsonparam = $json_array[$param] ?? null;
    return $jsonparam;
}

?>
