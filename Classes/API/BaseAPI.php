<?php
class BaseAPI {
    
    /**
     * @var $input the input string, used for POST api to store the request body.
     * @var $json_array the json array, resulted from passing the $input to json_encode function
     **/
    
    protected $input;
    protected $json_array;

    /**
     * the api's out subroutine, outs $data with $headers.
     * note that this function will exit the script after the output is done, use it one time and at the end of the code.
     *
     *
     * @param $data the data to out, usually a json response.
     * @param array $headers the headers array to add
     *
     **/
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
