<?php
	
	/**
     * Converts a PHP array into a JSON encoded string.
     *
     * @param array $array The PHP array to convert.
     * @return string The JSON representation of the source array.
     */
     function toJSON($array) {
        $encoded= '';
        if (is_array ($array)) {
            if (!function_exists('json_encode')) {
                if (@ include_once ('JSON.php')) {
                    $json = new Services_JSON();
                    $encoded= $json->encode($array);
                }
            } else {
                $encoded= json_encode($array);
            }
        }
        return $encoded;
    }
 
