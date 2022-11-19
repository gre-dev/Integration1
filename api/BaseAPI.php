<?php
class BaseAPI {
    protected $input;
    protected $json_array;

    public function out($data, $headers = null)
    {
        header_remove('Set-Cookie');
        
        if (is_array($headers) && count($headers)) {
            foreach ($headers as $header) {
                header($header);
            }
        }

        echo $data;
        exit;
    }

public function is_method_get() {
    
 $method = $_SERVER["REQUEST_METHOD"] ?? '';
 return $method === 'GET';
 
}


public function is_method_post() {
    
 $method = $_SERVER["REQUEST_METHOD"] ?? '';
 return $method === 'POST';
 
}

public function is_url_action_provided(string $action) {
    
    $provided = $_GET['action'] ??  '' ;
    
    return $provided === $action; 
}

public function param_uri(string $param) {

    $param = $_GET[$param] ?? null;
    return $param;
}


public function param_post_json(string $param) {

    $json_array = $this->json_array;
    if ($json_array === false) return false;
    
    $jsonparam = $json_array[$param] ?? null;
    return $jsonparam;
}
}
?>
