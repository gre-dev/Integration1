<?php
require_once __DIR__ . '/BaseAPI.php';

require_once PROJECT_ROOTDIR . '/Classes/Account.php';
require_once PROJECT_ROOTDIR . '/Classes/Exceptions/DBException.php';
require_once PROJECT_ROOTDIR . '/Classes/Exceptions/InvalidHttpMethodException.php';
    
class AccountAPI extends BaseAPI {

    /**
     * login api method, performs the account login
     *
     * @param string $email the account email
     * @param string $password the account password
     **/
    
    public function login (string $email, string $password) {

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

    /**
     * register account api method, it also wraps the Account's register method with a firstname and lastname of username.
     *
     * @param string $email the account email
     * @param string $password the account password
     * @param string $firstname the firstname
     * @param string $lastname the lastname
     **/

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

    /**
     * checks if the client is logged in
     *
     * @return true if the client logged in
     * @return false if the client is not logged in
     **/
    
    private function check_if_logged_in() {
        
        return isset($_SESSION) &&
            isset($_SESSION['login_email']) &&
            isset($_SESSION['login_password']) &&
            isset($_SESSION['login_token']);
    }

    /**
     * makes the logged in account signs out.
     *
     **/
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
    
    /**
     * returns an array suitable for an error json response.
     * 
     * @param int $error_code the error code
     * @param string $message the error message
     *
     * @return the error response array, to be used with the json_encode function
     **/

    public function arr_error_response(int $error_code , string $message) {

        $array = array (
            'success' => false,
            'errorCode' => $error_code,
            'info'      => $message);

        return $array;
    }

    /**
     * returns an array suitable for a successful json response.
     * 
     * @return the successful response, to be used with the json_encode function
     **/
    
    public function arr_success_response() {

        $array = array (
            'success' => true,
            'errorCode' => 0,
            'info'      => '' );

        return $array;
    }
               
}


?>
