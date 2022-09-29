<?php

namespace Structure;

abstract class Structure
{
    /**
     * The ip address of the server
     *
     * @var string
     */
    private static $ip_address;

    /**
     * The complete domainname, without querystring and request_uri
     *
     * @var string
     */
    private static $domain_name;

    /**
     * The whole request path, without domainname
     *
     * @var string
     */
    private static $request_path;

    /**
     * The URL, exploded by slashes
     *
     * @var array
     */
    private static $structure = array();

    /**
     * Contains the referrer, internally (so not depending on the HTTP_REFERER header)
     *
     * @var string
     */
    private static $referrer = null;

    /**
     * Store the user agent
     *
     * @var string
     */
    private static $user_agent = null;

    /**
     * Allowed social user agents
     *
     * @var array
     */
    private static $social_user_agents = array(
        'Pinterest',
        'Facebook',
        'Twitter',
    );

    /**
     * Initiate the static properties of the class Structure
     *
     * @return null
     */
    public static function init()
    {
        if (isset($_SERVER)) {
            static::$ip_address = static::getServerVar("SERVER_ADDR");
            static::$domain_name = static::getServerVar("HTTP_HOST");
            static::$request_path = preg_replace("/^(\/{1,})/", "/", static::getServerVar("REQUEST_URI"));
        }

        if (($trim_path = rtrim(static::$request_path, "/"))
            && $trim_path
            && $trim_path != static::$request_path
        ) {
            Structure::redirect($trim_path);
        }

        static::setQuery();
        static::setStructure();
        static::setUserAgent();

        // Show all errors and notification, when we working on a local enviroment
        //if (static::isEnvironment("O")) {
            ini_set("display_errors", 1);
            error_reporting(E_STRICT|E_ALL);
        //}
    }

    /**
     * Set the visiting user agent
     */
    public static function setUserAgent()
    {
        $http_user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        // Add log
        static::addLog($http_user_agent);

        static::$user_agent = 'other';
        if (strpos($http_user_agent, 'pinterest') !== false) {
            static::$user_agent = 'Pinterest';
        }
        if (strpos($http_user_agent, 'facebook') !== false) {
            static::$user_agent = 'Facebook';
        }
        if (strpos($http_user_agent, 'facebook') !== false) {
            static::$user_agent = 'Facebook';
        }
        if (strpos($http_user_agent, 'facebook') !== false) {
            static::$user_agent = 'Facebook';
        }
        if (strpos($http_user_agent, 'twitter') !== false) {
            static::$user_agent = 'Twitter';
        }

        static::addLog('UserAgent set: ' . $http_user_agent);
    }

    /**
     * Get the user agent
     *
     * @return string
     */
    public static function getUserAgent(): string
    {
        return static::$user_agent;
    }

    /**
     * Check if the user agent is a agent to use share
     */
    public static function isAllowedUserAgent()
    {
        if (in_array(static::$user_agent, static::$social_user_agents)) {
            static::addLog('UserAgent '. static::$user_agent. ' allowed.');
            return true;
        }
        static::addLog('UserAgent '. static::$user_agent. ' not allowed.');
        return false;
    }

    /**
     * Get value of global var $_SERVER
     *
     * @param string|null $key Returns a specific key
     *
     * @return string|null|array
     */
    public static function getServerVar($key = null)
    {
        // If there is no key given, then return the complete SERVER array
        if ($key === null) {
            return $_SERVER;
        }

        // Check if the given key exists
        if (array_key_exists(strtoupper($key), $_SERVER) && isset($_SERVER[strtoupper($key)])) {
            return $_SERVER[strtoupper($key)];
        }

        // Check if is console based on SHLVL if SHELL doesn't exist
        if (strtoupper($key) == "SHELL") {
            return Structure::getServerVar("SHLVL");
        }

        // If we don't find something, then return NULL
        return null;
    }

    /**
     * Set the property QUERY of the class Structure
     *
     * @return null
     */
    private static function setQuery()
    {
        if (Structure::getServerVar("SHELL")) {
            $argv = Structure::getServerVar()["argv"] ?? [];

            unset($argv[0]);
            $query = [];
            foreach ((array)$argv as $argument) {
                $parts = explode(":", $argument);
                if (count($parts) != 2) {
                    continue;
                }
                $query[$parts[0]] = $parts[1];
            }
        } else {
            $query = $_GET;
        }
        // If we have any query items, then assign them to the property query of this object
        $skip = array();
        if (count($query) > 0) {
            foreach ($query as $index => $value) {
                if (in_array($index, $skip)) {
                    continue;
                }

                static::$query[$index] = $value;
            }
        }
    }

    /**
     * Set the property STRUCTURE of the class Structure
     *
     * @return null
     */
    private static function setStructure()
    {
        $request_path = parse_url(static::$request_path);
        if ($request_path["path"] == Structure::getServerVar("PHP_SELF")) {
            $request_path["path"] = "";
        }

        if (static::$request_path === "" || !isset($request_path["path"])) {
            return null;
        }

        $url = $request_path["path"];
        /*if (Config::exists("site", "prefix") && Config::getValue("site", "prefix")) {
            $url = preg_replace("/^" . Config::getValue("site", "prefix") . "\//", "", $url);
        }*/

        if (trim($url, "/")) {
            // Splits the URL on a slash
            static::$structure = explode("/", trim($url, "/"));
            // Decodes % values (for cyrillic languages)
            array_walk_recursive(
                static::$structure,
                function (&$value) {
                    $value = rawurldecode($value);
                }
            );
        }
    }

    protected static $environment;
    /**
     * Check in which enviroment we run the application
     *
     * @param string $environment_type Value to check
     *
     * @return boolean
     */
    public static function isEnvironment($environment_type) : bool
    {
        if (static::$environment === null) {
            static::$environment = static::getServerVar("OTAP") ?? "P";
        }

        return static::$environment === $environment_type;
    }

    /**
     * Get (a part of) the structure (as String, with the given delimiter)
     *
     * @param int|null    $position  [Optional] The position of an string in the structure
     * @param string|null $delimiter [Optional] The delimiter to make the structre a string
     *
     * @return array|string|null
     */
    public static function getStructure($position = null, $delimiter = null)
    {
        $return = null;
        if (isset($position)) {
            if ($position >= 0 && isset(static::$structure[$position])) {
                $return = static::$structure[$position];
            } elseif ($position < 0) {
                // If the position is negative, then loop thru the structure backward
                $structure = static::$structure;
                for ($index = 0; $index < abs($position); $index++) {
                    $return = array_pop($structure);

                    if (count($structure) == 0) {
                        break;
                    }
                }
            }
        } elseif (isset($delimiter)) {
            $return = implode($delimiter, static::$structure);
        } else {
            $return = static::$structure;
        }

        return $return;
    }

    /**
     * Convert structure to a path to use in a redirect or in template
     *
     * @return string
     */
    public static function structureToPath() : string
    {
        return implode('/', static::$structure);
    }

    /**
     * Execute a redirect with the given URL
     *
     * @param string  $url            The new URL
     * @param string  $header         Code for the right header
     *
     * @return null
     */
    public static function redirect($url, $header = "301")
    {
        if (!$url) {
            throw new Exception("No URL specified");
        }

        // Send a header
        $headers = array(
            "301" => Structure::getServerVar("SERVER_PROTOCOL")." 301 Moved Permanently",
            "302" => Structure::getServerVar("SERVER_PROTOCOL")." 302 Found"
        );
        if ($header && isset($headers[$header])) {
            header($headers[$header]);
        } else {
            throw new Exception("Unknown redirect code: ".$header);
        }

        // Executes the redirect
        header("Location: ".$url, true, $header);
        exit;
    }

    public static function addLog($msg)
    {
        $file = fopen("files/logs/log.txt", "a");
        fwrite($file, date('Y-m-d H:i:s') . ': ' . $msg . "\r\n");
        fclose($file);
    }

}