<?php
namespace Samas\PHP7\Kit;

/**
 * kit for processing string
 */
class StrKit
{
    const CASE_L_CAMEL = 0;
    const CASE_U_CAMEL = 1;
    const CASE_L_CHAR  = 2;
    const CASE_U_CHAR  = 3;
    const JOIN_NONE = '';
    const JOIN_DASH = '-';
    const JOIN_UL   = '_';

    /**
     * convert a string into specific format
     * @param  string  $string            string to convert, can be camel-format or concated by "-", "_", ".", or " "
     * @param  int     $case              constant of output format
     * @param  string  $join              concat character between words in output string
     * @param  bool    $num_is_word       treat number as a word or not when paring the input string
     * @param  bool    $split_upper_case  treat continuous upper case characters as different words or not
     *                                    when paring the input string
     * @return string
     */
    public static function convert(
        string $string,
        int $case = self::CASE_L_CAMEL,
        string $join = self::JOIN_NONE,
        bool $num_is_word = true,
        bool $split_upper_case = true
    ): string {
        $regular_expression = ['/([A-Z][a-z]+)/'];
        if ($num_is_word) {
            $regular_expression[] = '/(?<=[^\d])([\d])/';
            $regular_expression[] = '/(?<=[\d])([^\d])/';
        }
        if ($split_upper_case) {
            $regular_expression[] = '/(?<=[A-Z])([A-Z])/';
        } else {
            $regular_expression[] = '/(?<=[^A-Z])([A-Z])/';
        }

        // camel-case
        $temp_string = trim(preg_replace($regular_expression, ' $0', $string));
        // dot & dash & underline
        $temp_string = strtolower(str_replace('_', ' ', str_replace('-', ' ', str_replace(' . ', ' ', $temp_string))));

        if (in_array($case, [self::CASE_L_CAMEL, self::CASE_U_CAMEL])) {
            $temp_string = ucwords($temp_string);
        } elseif ($case == self::CASE_U_CHAR) {
            $temp_string = strtoupper($temp_string);
        }

        $words = explode(' ', $temp_string);
        $words = array_filter($words, function ($word) {
            return trim($word) !== '';
        });
        array_walk($words, function (&$word) {
            $word = trim($word);
        });

        $result = implode($join, $words);
        if ($case == self::CASE_L_CAMEL) {
            $result = lcfirst($result);
        }

        return $result;
    }

    /**
     * check input is json format or not
     * @param  mixed   $input       input to check
     * @param  string  $stric_type  strict check is array or object
     *                              allow values: ['array', 'object'], others will be ignored
     * @return bool
     */
    public static function isJSON($input, string $stric_type = ''): bool
    {
        if (!is_string($input)) {
            return false;
        }
        $test = json_decode($input);
        if ($stric_type == 'array') {
            $type_check = is_array($test);
        } elseif ($stric_type == 'object') {
            $type_check = is_object($test);
        } else {
            $type_check = is_array($test) || is_object($test);
        }
        return $type_check && (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * get safely table or column name
     * @param  string  $str  table or column name
     * @return string
     */
    public static function getDBTargetStr(string $str): string
    {
        if (strpos($str, ' ') !== false || // condition string
            strpos($str, ',') !== false || // condition string
            strpos($str, '.') !== false || // with db name prefix
            strpos($str, '`') !== false || // add backquote by string self
            strpos($str, '->') !== false // json field
        ) {
            return $str;
        }
        return "`$str`";
    }

    /**
     * get JSON filed path
     * @param  string  $key  JSON field expression, implode levels with "."
     * @return string
     */
    public static function getDBJSONPath(string $key): string
    {
        $segments = explode('.', $key);
        $path = '$';
        foreach ($segments as $field) {
            $path .= is_numeric($field) ? "[$field]" : ".$field";
        }
        return $path;
    }

    /**
     * check the value is pure integer or not
     * @param  mixed  $value  value to check
     * @return bool
     */
    public static function checkInt($value): bool
    {
        if ((string)(int)$value == (string)$value) {
            return true;
        }
        return false;
    }

    /**
     * get formatted date string
     * @param  mixed   $value    specifig timestamp or date string, empty value will get current time
     * @param  string  $pattern  date pattern, refer to date()
     * @param  int     $offset   offset of output string
     * @param  int     $length   length of output string
     * @return string
     */
    public static function date($value = '', string $pattern = 'Y/m/d H:i:s', int $offset = 0, int $length = 16): string
    {
        $value = empty($value) ? time() : $value;
        $time = is_numeric($value) ? $value : strtotime($value);
        $date = date($pattern, $time);
        return substr($date, $offset, $length);
    }

    /**
     * get htmlspecialchars() processed string
     * @param  string  $string  string to process
     * @return string
     */
    public static function output(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES);
    }

    /**
     * explode by multiple characters
     * @param  array   $delimiters  characters to split string
     * @param  string  $string      string to split
     * @return array
     */
    public static function explodeAll(array $delimiters, string $string): array
    {
        $ready  = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);
        return $launch;
    }

    /**
     * split camel string to words
     * @param  string  $string             camel string
     * @param  bool    $split_upper_words  treat continuous upper case characters as different words or not
     * @return array
     */
    public static function parseCamel(string $string, bool $split_upper_words = false): array
    {
        if ($split_upper_words) {
            return preg_split('/(?=[A-Z])/', $string);
        }
        return preg_split(
            '/(^[^A-Z]+|[A-Z][^A-Z]+)/',
            $string,
            -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        );
    }
}
