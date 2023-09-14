<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

CONST KYC_STATUS_NEW            = 0;
CONST KYC_STATUS_SEND           = 1;
CONST KYC_STATUS_REJECT         = 2;
CONST KYC_STATUS_STEP_BACK      = 3;
CONST KYC_STATUS_NEED_REVIEW    = 4;
CONST KYC_STATUS_APROVED        = 5;

CONST POS_TYPE_CRM              = 0;

// Register the custom endpoint
add_action('rest_api_init', 'netopiaCustomEndpoint');

function netopiaCustomEndpoint()
{
    // Register the credential endpoints
    register_rest_route('netopiapayments/v1', '/credential', array(
        'methods' => 'POST',
        'callback' => 'getCredentialCallback',
        'permission_callback' => '__return_true'
    ));

    // Register the credential endpoint
    register_rest_route('netopiapayments/v1', '/updatecredential', array(
        'methods' => 'POST',
        'callback' => 'updateCredentialCallback',
        'permission_callback' => '__return_true'
    ));
}


// Callback function to update credentials
function updateCredentialCallback($request)
{
    // Predefine response data
    $data = array();

    // Create a new response object
    $response = new WP_REST_Response();
    
    // Get the request parameters
    $params = $request->get_params();
    
    // Retrieve and process data
    $data = array(
        'params' => $params,
        'timestamp' => time(),
    );

    

    // Get the existing settings option
    $settings_serialized = get_option('woocommerce_netopiapayments_settings');

    // If the option exists and is not empty, unserialize the data
    if ($settings_serialized !== false && !empty($settings_serialized)) {
        $settings = maybe_unserialize($settings_serialized);
    } else {
        // If the option doesn't exist or is empty, start with an empty array
        $settings = array();
    }

    // Update the settings with the provided values
    $settings['account_id'] = $params['signature'];
    $settings['live_api_key'] = $params['apiKeyLive'];
    $settings['sandbox_api_key'] = $params['apiKeySandbox'];
    $settings['ntp_notify_value'] = $params['notifyMerchant'];

    // Save the updated settings options
    update_option('woocommerce_netopiapayments_settings', $settings);

    

    $response = $data;
    wp_send_json($response);

}

// Callback function to handle the get credential request
function getCredentialCallback($request)
{
    // Predefine response data
    $data = array();

    // Create a new response object
    $response = new WP_REST_Response();

    // Get the request parameters
    $params = $request->get_params();
    
     // validate data
    if (!validateParams($params)) {
        $data = array(
            'status' => false,
            'message' => 'Username or Password is not valid',
            'details' => array(),
            'timestamp' => time(),
        );
        $response->data = $data;
        $response->set_status(400);
        return $response;
    } 
    
    /**
     * Steps
     * 1 - Login to Netopia , get Cookie Value
     * 2 - get Signatures
     * 3 - get Live Api Keys
     * 4 - get sandbox Api Keys
     * 5 - generate a general JSON
     * 6 - return the Complate Json
     */

     $ntpLiveAccessRes = ntpPlatformnLogin($params, true );
     if ($ntpLiveAccessRes['code'] !== 200) {
        $data = array(
            'status' => false,
            'message' => $ntpLiveAccessRes['message'],
            'details' => $ntpLiveAccessRes,
            'timestamp' => time(),
        );
        wp_send_json($data);
     }

     $ntpSignatures = validateSignature(getNtpSignature($ntpLiveAccessRes['data']['accessKey'], $isLive = true));
     $ntpLiveApiKeys = validateApiKey(getNTPApiKey($ntpLiveAccessRes['data']['accessKey'], $isLive = true));

    // Get Sandbox Data 
    $ntpSandboxAccessRes = ntpPlatformnLogin($params, false );
     if ($ntpSandboxAccessRes['code'] !== 200) {
        $data = array(
            'status' => false,
            'message' => $ntpLiveAccessRes['message'],
            'details' => $ntpLiveAccessRes,
            'timestamp' => time(),
        );
        $ntpSandboxApiKeys = $data;
     } else {
        $ntpSandboxApiKeys = validateApiKey(getNTPApiKey($ntpSandboxAccessRes['data']['accessKey'], $isLive = false));
     }
     
     

    // Retrieve and process data
    $data = array(
        'status' => true,
        'signature' => $ntpSignatures,
        'apiKeyLive' => $ntpLiveApiKeys,
        'apiKeySandbox' => $ntpSandboxApiKeys,
        'timestamp' => time(),
    );

    $response->data = $data;
    $response->set_status(200);
    return $response;
}


function validateParams(array $params)
{
    $expectedKeys = array("username","password");
    if(count($params) != count($expectedKeys) )
        return false;
    if(count(array_diff($expectedKeys, array_keys($params))) != 0 )
        return false;
    return true;
}

function validateSignature($signatureArrRes) {
    
    // check if account has any "Pointc de vanzare"
    if ($signatureArrRes['data']['count'] <= 0 ){
        return null;
    }

    if (is_array($signatureArrRes['data']['items']) && count($signatureArrRes['data']['items']) <= 0 ){
        return null;
    }
        

    // $validatedSignaure = array();    
    // foreach ($signatureArrRes['data']['items'] as $signature) {
    //     if ($signature['isActive'] && $signature['isApproved']) {
    //         if (($signature['kybStatus'] == KYC_STATUS_APROVED) && $signature['type'] == POS_TYPE_CRM ) {
    //             $validatedSignaure[] = $signature;
    //         }
    //     }
    // }
    // return $validatedSignaure;
    return $signatureArrRes['data']['items'] ;
}

function validateApiKey($apiKeyArrRes) {
    
    if (is_array($apiKeyArrRes['data']) && count($apiKeyArrRes['data']) <= 0 )
        return null;

    $validatedApiKeys = array();    
    foreach ($apiKeyArrRes['data']['items'] as $apiKey) {
            $validatedApiKeys[] = $apiKey;
    }
    return $validatedApiKeys;
}

function ntpPlatformnLogin($params, $isLive) {
    // JSON data to send
    $data = [
            'login' => [
                'username' => $params['username'],
                'password' => $params['password'],
                'code'     => ''
            ]
        ];

    // Convert data to JSON format
    $jsonData = json_encode($data);

    // Initialize cURL
    $ch = curl_init();

    // Set the URL
    if ($isLive)
        $url = 'https://admin.netopia-payments.com/api/auth/login';
    else 
        $url = 'https://sandbox.netopia-payments.com/api/auth/login';

    curl_setopt($ch, CURLOPT_URL, $url);

    // Set the request method to POST
    curl_setopt($ch, CURLOPT_POST, 1);

    // Set the request body (JSON data)
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    // Include headers in the response
    curl_setopt($ch, CURLOPT_HEADER, 1);

    // Return the response instead of outputting it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if ($response === false) {
    die('cURL Error: ' . curl_error($ch));
    }

    // Separate headers and body
    list($headers, $body) = explode("\r\n\r\n", $response, 2);

    // Extract cookies from the headers
    preg_match_all('/^set-cookie:\s*([^;]*)/mi', $headers, $matches);
    $cookies = [];
    foreach ($matches[1] as $match) {
    parse_str($match, $cookie);
    $cookies = array_merge($cookies, $cookie);
    }

    // Close cURL
    curl_close($ch);

    // Define NTP response
    $ntpPlatformResponse = array();

    // get response body
    $bodyArr = json_decode($body, true);

    // Check response HTTP Code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    
    if ($httpCode != 200) {
        $ntpPlatformResponse['code'] = $httpCode;
        $ntpPlatformResponse['message'] = $bodyArr['message'];
        $ntpPlatformResponse['data'] = array();
        // return json_encode($ntpPlatformResponse);
        return $ntpPlatformResponse;
    }

    // Handle the General Error
    if ($httpCode == 401) {
        $ntpPlatformResponse['code'] = 401;
        $ntpPlatformResponse['message'] = "Verific, if you already not signing in Platform. ";
        $ntpPlatformResponse['data'] = array();
        // return json_encode($ntpPlatformResponse);
        return $ntpPlatformResponse;
    }

    // Handle the extracted cookies
    if (!array_key_exists('ADM_SESS_ID', $cookies)) {
        $ntpPlatformResponse['code'] = 404;
        $ntpPlatformResponse['message'] = "Access key didn't found";
        $ntpPlatformResponse['data'] = array();
        // return json_encode($ntpPlatformResponse);
        return $ntpPlatformResponse;
    }

    if (is_null($cookies['ADM_SESS_ID']) || empty($cookies['ADM_SESS_ID'])) {
        $ntpPlatformResponse['code'] = 406;
        $ntpPlatformResponse['message'] = "Access key is Not Acceptable";
        $ntpPlatformResponse['data'] = array();
        // return json_encode($ntpPlatformResponse);
        return $ntpPlatformResponse;
    }

    // return access key
    $ntpPlatformResponse['code'] = $httpCode;
    $ntpPlatformResponse['message'] = "";
    $ntpPlatformResponse['data']['accessKey'] = $cookies['ADM_SESS_ID'];
    // return json_encode($ntpPlatformResponse);
    return $ntpPlatformResponse;
}

function getNtpSignature($accessKey, $isLive) {
    // JSON data to send
    $jsonData = '{}';

    // Initialize cURL
    $ch = curl_init();

    // Set the URL
    if ($isLive)
        $url = 'https://admin.netopia-payments.com/api/pos/list';
    else 
        $url = 'https://sandbox.netopia-payments.com/api/pos/list';

    curl_setopt($ch, CURLOPT_URL, $url);

    // Set the request method to POST
    curl_setopt($ch, CURLOPT_POST, 1);

    // Set the request body (JSON data)
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    // Include headers in the response
    curl_setopt($ch, CURLOPT_HEADER, 1);

    // Return the response instead of outputting it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


    // Set the cookies
    $cookies = 'ADM_SESS_ID='.$accessKey;
    curl_setopt($ch, CURLOPT_COOKIE, $cookies);

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if ($response === false) {
    die('cURL Error: ' . curl_error($ch));
    }

    // Separate headers and body
    list($headers, $body) = explode("\r\n\r\n", $response, 2);

    // Extract cookies from the headers
    preg_match_all('/^set-cookie:\s*([^;]*)/mi', $headers, $matches);
    $cookies = [];
    foreach ($matches[1] as $match) {
    parse_str($match, $cookie);
    $cookies = array_merge($cookies, $cookie);
    }

    // Close cURL
    curl_close($ch);

    // Define NTP response
    $ntpPlatformResponse = array();

    // get response body
    $bodyArr = json_decode($body, true);

    // Check response HTTP Code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode != 200) {
        $ntpPlatformResponse['code'] = $httpCode;
        $ntpPlatformResponse['message'] = $bodyArr['message'];
        $ntpPlatformResponse['data'] = array();
        // return json_encode($ntpPlatformResponse);
        return $ntpPlatformResponse;
    }

    // Handle the extracted cookies
    if (!array_key_exists('ADM_SESS_ID', $cookies)) {
        $ntpPlatformResponse['code'] = 404;
        $ntpPlatformResponse['message'] = "Access key didn't found";
        $ntpPlatformResponse['data'] = array();
        // return json_encode($ntpPlatformResponse);
        return $ntpPlatformResponse;
    }

    if(is_null($cookies['ADM_SESS_ID']) || empty($cookies['ADM_SESS_ID'])) {
        $ntpPlatformResponse['code'] = 406;
        $ntpPlatformResponse['message'] = "Access key is Not Acceptable";
        $ntpPlatformResponse['data'] = array();
        // return json_encode($ntpPlatformResponse);
        return $ntpPlatformResponse;
    }

    // return access key
    $ntpPlatformResponse['code'] = $httpCode;
    $ntpPlatformResponse['message'] = "";
    $ntpPlatformResponse['data'] = $bodyArr;
    // return json_encode($ntpPlatformResponse);
    return $ntpPlatformResponse;
}

function getNTPApiKey($accessKey, $isLive) {
    // Initialize cURL
    $ch = curl_init();

    // Set the URL
    if ($isLive)
        $url = 'https://admin.netopia-payments.com/api/user/api/key/get';
    else 
        $url = 'https://sandbox.netopia-payments.com/api/user/api/key/get';
        
    curl_setopt($ch, CURLOPT_URL, $url);

    // Set the request method to GET
    curl_setopt($ch, CURLOPT_HTTPGET, true);

    // Include headers in the response
    curl_setopt($ch, CURLOPT_HEADER, 1);

    // Return the response instead of outputting it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


    // Set the cookies
    $cookies = 'ADM_SESS_ID='.$accessKey;
    curl_setopt($ch, CURLOPT_COOKIE, $cookies);

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if ($response === false) {
    die('cURL Error: ' . curl_error($ch));
    }

    // Separate headers and body
    list($headers, $body) = explode("\r\n\r\n", $response, 2);

    // Extract cookies from the headers
    preg_match_all('/^set-cookie:\s*([^;]*)/mi', $headers, $matches);
    $cookies = [];
    foreach ($matches[1] as $match) {
    parse_str($match, $cookie);
    $cookies = array_merge($cookies, $cookie);
    }

    // Close cURL
    curl_close($ch);

    // Define NTP response
    $ntpPlatformResponse = array();

    // get response body
    $bodyArr = json_decode($body, true);

    // Check response HTTP Code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode != 200) {
        $ntpPlatformResponse['code'] = $httpCode;
        $ntpPlatformResponse['message'] = $bodyArr['message'];
        $ntpPlatformResponse['data'] = array();
        return $ntpPlatformResponse;
    }

    // Handle the extracted cookies
    if (!array_key_exists('ADM_SESS_ID', $cookies)) {
        $ntpPlatformResponse['code'] = 404;
        $ntpPlatformResponse['message'] = "Access key didn't found";
        $ntpPlatformResponse['data'] = array();
        return $ntpPlatformResponse;
    }

    if (is_null($cookies['ADM_SESS_ID']) || empty($cookies['ADM_SESS_ID'])) {
        $ntpPlatformResponse['code'] = 406;
        $ntpPlatformResponse['message'] = "Access key is Not Acceptable";
        $ntpPlatformResponse['data'] = array();
        return $ntpPlatformResponse;
    }

    // return access key
    $ntpPlatformResponse['code'] = $httpCode;
    $ntpPlatformResponse['message'] = "";
    $ntpPlatformResponse['data'] = $bodyArr;
    return $ntpPlatformResponse;
}

?>