<?php namespace pixabayapi\lib;

 /**
 * Generic cURL requests class
 * 
 * This creates GET and POST requests on behalf of PHP with a standardized stdClass 
 * response that is easy to test for and reports cURL specific errors easily.
 * 
 * - Also easy to turn off SSL Verification for testing
 * - Quick setting of extra cURL options with default overrides
 * - Easy to add custom headers
 * - POST data will also work with mbstring extension if enabled for use with UTF-8 encoding
 * 
 * @author Martyr2
 * @copyright 2021 The Coders Lexicon
 * @link https://www.coderslexicon.com
 */

class CurlRequests 
{
    public static $responseHeaders = [];
    
    /**
    * Executes a GET request on URL with specified headers
    *
    * @param string $url - URL to post to
    * @param array $headers - Optional headers to add to request
    * @param array $options - Optional cURL "setopt" options to configure the request
    * @param boolean $sslVerify - Optional flag to turn off SSL verification (keep on in production)
    * @return stdClass Returns stdClass with status code and content or stdClass with errorno and errormsg set in failure.
    */
    public static function get(string $url, array $headers = [], array $options = [], bool $sslVerify = true) 
    {
        // Defaults
        $defaultOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_SSL_VERIFYPEER => $sslVerify
        ];

        $setOptions = $options + $defaultOptions;

        foreach ($headers as $headerName => $headerValue) {
            $setOptions[CURLOPT_HTTPHEADER][] = "$headerName: $headerValue";
        }

        $ch = curl_init();
        $setOptions[CURLOPT_HEADERFUNCTION] = function($curl, $header) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) // ignore invalid headers
                return $len;
        
            self::$responseHeaders[strtolower(trim($header[0]))][] = trim($header[1]);
        
            return $len;
        };

        curl_setopt_array($ch, $setOptions);

        $content = curl_exec($ch);
        $std = new \stdClass();

        if ($content === false) {
            $std->errorno = curl_errno($ch);
            $std->errormsg = curl_strerror($std->errorno);
            return $std;
        }

        $sc = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $std->status_code = $sc;
        $std->content = $content;

        return $std;
    }

    /**
    * Executes a POST request on URL with body data and with any specified headers
    *
    * @param string $url - URL to post on
    * @param string|array $data - Encoded string of data or array of parameters to post as the body
    * @param array $headers - Optional headers to add to request
    * @param array $options - Optional cURL "setopt" options to configure the request
    * @param boolean $sslVerify - Optional flag to turn off SSL verification (keep on in production)
    * @return stdClass Returns stdClass with status code and content or stdClass with errorno and errormsg on failure.
    */
    public static function post(string $url, $data, array $headers = [], array $options = [], bool $sslVerify = true) 
    {
        // Defaults
        $defaultOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_POSTFIELDS => is_array($data) ? http_build_query($data) : $data
        ];

        $setOptions = $options + $defaultOptions;

        foreach ($headers as $headerName => $headerValue) {
            $setOptions[CURLOPT_HTTPHEADER][] = "$headerName: $headerValue";
        }

        $ch = curl_init();
        $setOptions[CURLOPT_HEADERFUNCTION] = function($curl, $header) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) // ignore invalid headers
                return $len;
        
            self::$responseHeaders[strtolower(trim($header[0]))][] = trim($header[1]);
        
            return $len;
        };

        curl_setopt_array($ch, $setOptions); 

        $content = curl_exec($ch);
        $std = new \stdClass();

        if ($content === false) {
            $std->errorno = curl_errno($ch);
            $std->errormsg = curl_strerror($std->errorno);
            return $std;
        }

        $sc = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $std->status_code = $sc;
        $std->content = $content;

        return $std;
    }
}
