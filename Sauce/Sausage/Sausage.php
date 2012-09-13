<?php
namespace Sauce\Sausage;

require_once(dirname(__FILE__)."/API.php");

define('SAUCE_HOST', 'saucelabs.com');

class Sausage
{

    protected $username;
    protected $api_key;

    public function __construct($username, $api_key)
    {
        $this->username = $username;
        $this->api_key = $api_key;
        $this->api = new API($this->username);
    }

    protected function buildUrl($endpoint)
    {
        if ($endpoint[0] != '/')
            $endpoint = '/'.$endpoint;

        return 'https://'.SAUCE_HOST.$endpoint;
    }

    protected function makeRequest($url, $type="GET", $params=false)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username.":".$this->api_key);

        if ($type == "POST")
            curl_setopt($ch, CURLOPT_POST, 1);
        elseif ($type == "PUT")
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        elseif ($type == "DELETE")
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $headers = array();
        $headers[] = 'Content-Type: text/json';

        $data = '';
        if ($params) {
            $data = json_encode($params);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $headers[] = 'Content-length:'.strlen($data);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch))
            throw new \Exception("Got an error while making a request: ".curl_error($ch));

        curl_close($ch);

        $result = json_decode($response);

        if (!$result) {
            throw new \Exception("An error occurred parsing the response. ".
                                "Please check your parameters and try again");
        }

        if (is_object($result))
            $result = get_object_vars($result);

        return $result;
    }

    public function __call($command, $args)
    {
        $res = call_user_func_array(array($this->api, $command), $args);

        if (sizeof($res) < 1)
            throw new \Exception("Got a bad API call format from $command"); $endpoint = $res[0];

        $request_args = array_slice($res, 1);

        $url = $this->buildUrl($endpoint);

        array_unshift($request_args, $url);

        return call_user_func_array(array($this, 'makeRequest'), $request_args);
    }



}