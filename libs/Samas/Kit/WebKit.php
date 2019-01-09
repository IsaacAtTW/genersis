<?php
namespace Samas\PHP7\Kit;

/**
 * Kit for processing web data
 */
class WebKit
{
    /**
     * output response in text type
     * @param  string $string string to output
     */
    public static function text(string $text)
    {
        header('Content-Type: text/plain');
        echo $text;
        exit;
    }

    /**
     * output response in json type
     * @param  mixed $data array or object to output
     */
    public static function json(array $data)
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * operate cookie data
     * @param  string    $key       cookie key
     * @param  mixed     $value     value to save
     * @param  bool/int  $remember  keep value with expire time, bool for using config setting
     * @return mixed
     */
    public static function cookie(string $key = '', $value = null, $remember = false)
    {
        if (empty($key)) {
            return $_COOKIE;
        }

        if ($value === null) {
            return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
        } else {
            setcookie($key, '', 0, "/", false, false);
            unset($_COOKIE[$key]);
            if (strlen($value)) {
                $expire = 0;
                if ($remember !== false) {
                    $keep_time = is_int($remember) ?
                                 $remember :
                                 AppKit::config('cookie_expire') ?? 86400 * 30;
                    $expire = time() + $keep_time;
                }
                setcookie($key, $value, $expire, "/", false, false);
                $_COOKIE[$key] = $value;
            }
        }
        return;
    }

    /**
     * get request uri (without query string)
     * @param  bool  $include_domain  include domain or not
     * @param  bool  $query_string    with query string or not
     * @return string
     */
    public static function getRequestURI(bool $include_domain = true, bool $query_string = false): string
    {
        if (php_sapi_name() === 'cli') {
            global $argv;
            return implode(' ', $argv);
        }
        $uri = $query_string ? $_SERVER['REQUEST_URI'] : strstr($_SERVER['REQUEST_URI'] . '?', '?', true);
        return $include_domain ? "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}$uri" : $uri;
    }

    public static function getRequestInfo(): array
    {
        return [
            'ip'     => self::getClientIP(),
            'url'    => self::getRequestURI(),
            'method' => self::getRequestMethod(),
            '_GET'   => self::getGETParams(),
            '_POST'  => self::getPOSTParams()
        ];
    }

    /**
     * get client ip
     * @return string
     */
    private static function getClientIP(): string
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }

    /**
     * get request method in upper case
     * @return string
     */
    public static function getRequestMethod(): string
    {
        return php_sapi_name() === 'cli' ? 'cli' : strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * get GET parameters
     * @return array
     */
    public static function getGETParams(): array
    {
        if (empty($_SERVER['QUERY_STRING'])) {
            return [];
        }
        parse_str($_SERVER['QUERY_STRING'], $GET_params);
        return $GET_params;
    }

    /**
     * get POST parameters
     * @return array
     */
    public static function getPOSTParams(): array
    {
        $rest_body = file_get_contents('php://input');
        if (StrKit::isJSON($rest_body)) {
            $POST_params = json_decode($rest_body, true);
        } elseif (is_string($rest_body)) {
            parse_str($rest_body, $POST_params);
        } else {
            $POST_params = (array)$_POST;
        }
        return $POST_params;
    }
}
