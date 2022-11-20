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
}
?>
