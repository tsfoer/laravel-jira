<?php

namespace Univerze\Jira;

class Jira
{
    /** @var string */
    protected static $username;

    /** @var string */
    protected static $password;

    /** @var string */
    protected static $hostname;

    /** @var int */
    protected static $port;

    /** @var bool */
    protected static $initialised = false;

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

        return json_decode($result);
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

        return json_decode($result);
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

        return json_decode($result);
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
            return '{"errorMessages":["Jira class not initialised"],"errors":{}}';
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

        curl_close($ch);

        return $response;
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
}
