<?php

trait InputHandleTrait {
    
    private function filter_input($input) {
        $filtered = htmlspecialchars($input);
        $filtered = addslashes($filtered);
        return $filtered;
    }

    private function revert_filtered($filtered) {
        $input = stripslashes($filtered);
        $input = htmlspecialchars_decode($input);
        return $input;
    }
}

?>
