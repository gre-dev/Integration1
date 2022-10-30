<?php

trait InputHandleTrait {
    
    private function filter_html_input($input) {
        return htmlspecialchars($input);
    }

    private function revert_html_filter($filtered) {
        return htmlspecialchars_decode($filtered);
    }
}

?>
