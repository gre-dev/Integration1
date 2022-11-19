<?php
require_once __DIR__ . '/BaseAPI.php';

require_once PROJECT_ROOTDIR . '/Classes/Account.php';
require_once PROJECT_ROOTDIR . '/Classes/Exceptions/DBException.php';
require_once PROJECT_ROOTDIR . '/Classes/Exceptions/InvalidHttpMethodException.php';
    
class AccountAPI extends BaseAPI {

    public function login ($email,$password) {

        try {
            $account = new Account();

            $user = $account->validate_login_credentials($email,$password);

            if (empty($user)) {
                $msg = "The information provided doesn't belong to a user";
                $err_array = $this->arr_error_response(LOGIN_FAILED, $msg);
                
                $response = json_encode ($err_array, true);
            }
            
            else {
                $success = $this->arr_success_response();
                $response = json_encode ($success, true);
            }
        }
            
        catch (InvalidArgumentException $e)
        {
            $code = $e->getCode();
            switch ($code) {
   
            case ARG_EMAIL_INVALID:
                $msg = 'login email is invalid';
                break;
            case ARG_PASSWORD_EMPTY:
                $msg = 'login password is empty';
                break;
            
            default:
                $msg = 'unknown error';
                break;
            }

            $err_array = $this->arr_error_response($code,$msg);
            $response = json_encode ($err_array);

        }
        catch (DBException $e) {
            $code = $e->getCode();
            
            if ($code === DBException::DB_ERR_SELECT || $code === DBException::DB_ERR_CONN_WRONG_DATA )
            {
                $msg = 'general error';
            }

            $err_array = $this->arr_error_response($code,$msg);
            $response = json_encode ($err_array);
               
        }
        
        $this->out($response);
    }

    public function register ($email,$password, $firstname, $lastname) {
            
        try {
            $account = new Account();

            if (empty($firstname) || empty($lastname)) {
                $code = FIRST_OR_LAST_NAME_MISSING;
                $msg = 'either firstname or lastname field is empty or missing';
                
                $err_array = $this->arr_error_response($code,$msg);
                $response = json_encode ($err_array);
            }
            
            else {
                $username = "$firstname $lastname";
                $account->create_new_account($email,$password,$username);
                
                $success = $this->arr_success_response();
                $response = json_encode ($success, true);
            }
        }
            
        catch (InvalidArgumentException $e)
        {
            $code = $e->getCode();
            switch ($code) {
   
            case ARG_PASSWORD_EMPTY:
                $msg = 'password is missing';
                $msg = $e->getMessage();
                break;
            case ARG_USERNAME_EMPTY: // this will not happend but included anyway
                $msg = 'username is missing';
                break;
                
            case ARG_EMAIL_INVALID:
                $msg = 'invalid email';
                break;
            
            default:
                $msg = 'unknown error';
                break;
            }

            $err_array = $this->arr_error_response($code,$msg);
            $response = json_encode ($err_array);

        }
        catch (DBException $e) {
            $code = $e->getCode();

            if ($code === DBException::DB_ERR_INSERT || $code ===  DBException::DB_ERR_SELECT || $code === DBException::DB_ERR_CONN_WRONG_DATA)
            {
                $msg = 'error while registering a new account';
            }

            $err_array = $this->arr_error_response($code,$msg);
            $response = json_encode ($err_array);
               
        }
        
        $this->out($response);
    }
    
    private function check_if_logged_in() {
        
        return isset($_SESSION) &&
            isset($_SESSION['login_email']) &&
            isset($_SESSION['login_password']) &&
            isset($_SESSION['login_token']);
    }
    
    public function logout () {
        
        if ($this->check_if_logged_in())
        {
            $account = new Account();
            $account->logout();
            
            $success = $this->arr_success_response();
            $response = json_encode ($success, true);               
        }
        
        else {
            $code = NOT_LOGGED_IN;
            $msg = "you're not logged in";
            
            $err_array = $this->arr_error_response($code,$msg);
            $response = json_encode ($err_array);
        }
        
        $this->out($response);
    }

    public function route ()
    {

        if ($this->is_method_post())
        {

            if ($this->is_url_action_provided('login'))
            {

                $input = file_get_contents('php://input');

                if ($input) {

                    $json_array = json_decode($input, true) ?? null;
                    
                    if ($json_array !== false) {

                        $this->input = $input;
                        $this->json_array = $json_array;

                        $email = $this->param_post_json('email');

                        $password = $this->param_post_json('password');

                        $this->login($email, $password);
                    }
                }
            }

            if ($this->is_url_action_provided('register'))
            {
                $input = file_get_contents('php://input');

                if ($input) {

                    $json_array = json_decode($input, true) ?? null;
                    
                    if ($json_array !== false) {

                        $this->input = $input;
                        $this->json_array = $json_array;

                        $email = $this->param_post_json('email');
                        $password = $this->param_post_json('password');
                        $firstname = $this->param_post_json('firstname');
                        $lastname = $this->param_post_json('lastname');
                        $this->register($email, $password, $firstname, $lastname);
                    }
                }
            }
            if ($this->is_url_action_provided('logout'))
            {
                $input = file_get_contents('php://input');
                
                if ($input) {
                    
                    $this->logout();
                    }
                }
            }
            else {
                throw new InvalidHttpMethodException();
            }
        }
        
    public function arr_error_response(int $error_code , string $message) {

        $array = array (
            'success' => false,
            'errorCode' => $error_code,
            'info'      => $message);

        return $array;
    }

    public function arr_success_response() {

        $array = array (
            'success' => true,
            'errorCode' => 0,
            'info'      => '' );

        return $array;
    }

               
}


?>
