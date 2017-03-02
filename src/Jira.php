<?php

namespace Univerze\Jira;

use stdClass;

class Jira
{
    /** @var string */
    private static $username;

    /** @var string */
    private static $password;

    /** @var string */
    private static $hostname;

    /** @var int */
    private static $port;

    /** @var bool */
    private static $initialised = false;

    /** @var null|stdClass */
    private static $response;

    /**
     * Search function to search issues with JQL string
     *
     * @param null $jql
     * @return mixed
     */
    public static function search($jql = NULL)
    {
        $data   = json_encode(['jql' => $jql]);
        $result = self::request('search', $data);

        static::$response = json_decode($result);
        return static::$response;
    }

    /**
     * Create function to create a single issue from array data
     *
     * @param array $data
     * @return mixed
     */
    public static function create(array $data)
    {
        $data   = json_encode(['fields' => $data]);
        $data   = str_replace('\\\\', '\\', $data);
        $result = self::request('issue', $data, 1);

        static::$response = json_decode($result);
        return static::$response;
    }

    /**
     * Update function to change existing issue attributes
     *
     * @param string $issue
     * @param array $data
     * @return mixed
     */
    public static function update($issue, array $data)
    {
        $data   = json_encode(['fields' => $data]);
        $data   = str_replace('\\\\', '\\', $data);
        $result = self::request('issue/' . $issue, $data, 0, 1);

        static::$response = json_decode($result);
        return static::$response;
    }

    /**
     * CURL request to the JIRA REST api (v2)
     *
     * @param $request
     * @param $data
     * @param int $is_post
     * @param int $is_put
     * @return mixed
     */
    private static function request($request, $data, $is_post = 0, $is_put = 0)
    {
        if (static::$initialised !== true) {
            return static::constructErrorResponse("Jira service not properly initialised");
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => static::$hostname . ':' . static::$port . '/rest/api/2/' . $request,
            CURLOPT_USERPWD        => static::$username . ':' . static::$password,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPHEADER     => ['Content-type: application/json'],
            CURLOPT_RETURNTRANSFER => 1,
        ]);

        if ($is_post) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }

        if ($is_put) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

        $response = curl_exec($ch);
        $response = static::handleHttpResponse($ch, $response);

        curl_close($ch);

        return $response;
    }

    /**
     * Handle non-200 HTTP responses from the API
     *
     * @param resource $ch
     * @param string $response
     * @return string
     */
    protected static function handleHttpResponse(resource $ch, string $response)
    {
        // Get the HTTP code and if it's a 2**, return the response
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (substr($httpcode, 0, 1) === "2") {
            return $response;
        }

        // Handle the most common http response errors
        switch ($httpcode) {
            case 400:
                return static::constructErrorResponse(
                    "Jira says it received a bad request. Please check your details."
                );
            case 401:
                return static::constructErrorResponse("Jira authentication or authorisation error.");
            case 403:
            case 405:
                return static::constructErrorResponse("That action is not permitted Jira.");
            case 404:
            case 501:
                return static::constructErrorResponse("That action does not exist on Jira.");
            case 500:
                return static::constructErrorResponse("Jira server error.");
            case 502:
            case 503:
            case 504:
                return static::constructErrorResponse("Jira service currently unavailable.");
            default:
                return static::constructErrorResponse("There was a problem with Jira.");
        }
    }

    /**
     * Construct an error response with the same format the Jira API with a custom error message
     *
     * @param string $message
     * @return string
     */
    protected static function constructErrorResponse(string $message): string
    {
        return sprintf('{"errorMessages":["%s"], "errors":{}}', $message);
    }

    /**
     * Initialise with the JIRA hostname, port, username and password
     *
     * @param string $username
     * @param string $password
     * @param string $hostname
     * @param int $port
     * @return bool
     */
    public static function initialise(string $username, string $password, string $hostname, int $port = 80): bool
    {
        // Check required values
        if (!isset($username, $password, $hostname)) {
            return false;
        }

        // Set the required values
        static::$username    = $username;
        static::$password    = $password;
        static::$hostname    = $hostname;
        static::$port        = $port;
        static::$initialised = true;

        return true;
    }

    /**
     * Check if the response is an error response
     *
     * @return bool
     */
    public static function isErrorResponse(): bool
    {
        return !(static::$response instanceof stdClass && isset(static::$response->id, static::$response->key));
    }

    /**
     * Get a Collection of errors from the response
     *
     * @return array
     */
    public static function getErrorCollection(): array
    {
        if (empty(static::$response) || !(static::$response instanceof stdClass) || !static::hasErrorMessages()) {
            return [];
        }

        $errors = [];
        if (!empty(static::$response->errorMessages) && is_array(static::$response->errorMessages)) {
            $errors = array_merge($errors, static::$response->errorMessages);
        }

        if (!empty(static::$response->errors) && static::$response->errors instanceof stdClass) {
            $errors = array_merge($errors, get_object_vars(static::$response->errors));
        }

        return $errors;
    }

    /**
     * Check if the response has error messages
     *
     * @return bool
     */
    public static function hasErrorMessages(): bool
    {
        return isset(static::$response->errorMessages) || isset(static::$response->errors);
    }

    /**
     * Get the value of a response field
     *
     * @param string $fieldName
     * @param null $default
     * @return null
     */
    public static function getResponseField(string $fieldName = '', $default = null)
    {
        // If this is an error response return the default value
        if (static::isErrorResponse()) {
            return $default;
        }

        // If no field name was given return all the fields as an array
        if ($fieldName === '') {
            return get_object_vars(static::$response);
        }

        // If the given field name does not exist in the response, return the default value
        if (!isset(static::$response->$fieldName)) {
            return $default;
        }

        // Return
        return static::$response->$fieldName;
    }
}
