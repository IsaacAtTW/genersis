<?php
namespace Samas\PHP7\Kit;

use \stdClass, \Closure, \ReflectionFunction, \ReflectionClass, \ReflectionProperty, \ReflectionMethod;

class DevKit
{
    private static $display_detail = true;
    private static $color_table = [
        'var'      => ['truth_color' => 'gray'],
        'bool'     => ['color' => 'gray'],
        'int'      => ['color' => 'blue'],
        'float'    => ['color' => 'orange'],
        'string'   => [
            'color'      => 'forestgreen',
            'esc_color'  => 'navajowhite',
            'meta_color' => 'gray'
        ],
        'array'    => [
            'color'      => 'darkred',
            'meta_color' => 'gray'
        ],
        'object'   => [
            'color'         => 'darkcyan',
            'meta_color'    => 'gray',
            'keyword_color' => 'lightskyblue',
            'attr_color'    => 'steelblue'
        ],
        'resource' => ['color' => 'purple'],
        'null'     => ['color' => 'red'],
        'function' => [
            'param_color'  => 'darkblue',
            'ref_color'    => 'gold',
            'type_color'   => 'yellowgreen',
            'value_color'  => 'sandybrown',
            'code_color'   => 'slategray',
            'notice_color' => 'gray'
        ]
    ];

    public static function is($var, bool $exit = false, int $trace_level = 0)
    {
        static::$display_detail = false;
        echo HtmlKit::charset();
        echo '<pre>';
        echo debug_backtrace()[$trace_level]['file'] . ':' . debug_backtrace()[$trace_level]['line'] . '<br>';
        echo self::dumpVar($var);
        echo '</pre>';
        if ($exit) {
            exit;
        }
    }

    public static function dump($var, bool $exit = false, int $trace_level = 0)
    {
        static::$display_detail = true;
        echo HtmlKit::charset();
        echo '<pre>';
        echo debug_backtrace()[$trace_level]['file'] . ':' . debug_backtrace()[$trace_level]['line'] . '<br>';
        echo self::dumpVar($var);
        echo '</pre>';
        if ($exit) {
            exit;
        }
    }

    private static function dumpVar($var, int $indent = 0, bool $expand = true): string
    {
        extract(static::$color_table['var']);
        $truth_value = $var ? '[T]' : '[F]';
        $type        = gettype($var);

        if ($type == 'unknown type') {
            return "<span style=\"color: $truth_color;\">$truth_value </span>(unknown type)";
        }

        $method = 'dumpVar' . ucwords($type);
        return "<span style=\"color: $truth_color;\">$truth_value </span>" . self::$method($var, $indent, $expand);
    }

    private static function dumpVarBoolean(bool $var, int $indent): string
    {
        $type = 'bool';
        extract(static::$color_table[$type]);
        $var = $var ? 'TRUE' : 'FALSE';
        $result = "$type ";
        $result .= "<span style=\"color: $color\">$var</span>";
        return $result;
    }

    private static function dumpVarInteger(int $var, int $indent): string
    {
        $type = 'int';
        extract(static::$color_table[$type]);
        $result = "$type ";
        $result .= "<span style=\"color: $color\">$var</span>";
        return $result;
    }

    private static function dumpVarDouble(float $var, int $indent): string
    {
        $type = 'float';
        extract(static::$color_table[$type]);
        $result = "$type ";
        $result .= "<span style=\"color: $color\">$var</span>";
        return $result;
    }

    private static function dumpVarString(string $var, int $indent): string
    {
        $type  = 'string';
        extract(static::$color_table[$type]);
        $output_string = StrKit::output($var);
        $output_string = str_replace(
            "\n",
            "<span style=\"color: $esc_color; font-weight: bold;\">\\n</span>",
            $output_string
        );
        $output_string = str_replace(
            "\r",
            "<span style=\"color: $esc_color; font-weight: bold;\">\\r</span>",
            $output_string
        );
        $output_string = str_replace(
            "\t",
            "<span style=\"color: $esc_color; font-weight: bold;\">\\t</span>",
            $output_string
        );

        $result = "$type ";
        $result .= "\"<span style=\"color: $color\">$output_string</span>\"";
        $result .= "<span style=\"color: $meta_color; font-style: italic; font-size: 0.9em;\"> ";
        $result .= "length: " . strlen($var) . ", " . strtolower(mb_internal_encoding()) . ": " . mb_strlen($var);
        $result .= "</span>";
        return $result;
    }

    private static function dumpVarArray(array $var, int $indent, bool $expand = true): string
    {
        $type  = 'array';
        extract(static::$color_table[$type]);
        $count   = count($var);
        $counter = 1;
        $result = "$type ";
        if ($count == 0 || !$expand) {
            if ($count == 0) {
                $result .= "<span style=\"color: $color;\">[ ]</span>";
            } else {
                $result .= "<span style=\"color: $color;\">[ . . . ]</span>";
            }
            $result .= "<span style=\"color: $meta_color; font-style: italic; font-size: 0.9em;\"> ";
            $result .= "count: $count";
            $result .= "</span>";
            return $result;
        }

        $result .= "<span style=\"color: $color;\">[ </span>\n";
        foreach ($var as $key => $value) {
            if (is_string($key)) {
                $key = "\"<span style=\"color: $color; font-style: italic;\">$key</span>\"";
                $key .= "<span style=\"color: $color; font-style: italic;\"> => </span>";
            } else {
                $key = "<span style=\"color: $color; font-style: italic;\">$key => </span>";
            }
            $result .= str_repeat("    ", $indent + 1) . $key;
            $result .= self::dumpVar($value, $indent + 1, static::$display_detail);
            if ($counter != $count) {
                $result .= ',';
                $counter++;
            }
            $result .= "\n";
        }
        if ($indent > 0) {
            $result .= str_repeat("    ", $indent);
        }
        $result .= "<span style=\"color: $color;\">]</span>";
        $result .= "<span style=\"color: $meta_color; font-style: italic; font-size: 0.9em;\"> ";
        $result .= "count: $count";
        $result .= "</span>";
        return $result;
    }

    private static function dumpVarObject($var, int $indent, bool $expand = true): string
    {
        $type  = 'object';
        extract(static::$color_table[$type]);
        $class = get_class($var);
        if ($class == 'stdClass') {
            return self::dumpVarObjectStdClass($var, $indent);
        } elseif ($class == 'Closure') {
            return self::dumpVarObjectClosure($var, $indent);
        }

        $reflection = new ReflectionClass($class);
        $members = [
            'constants'  => $reflection->getConstants(),
            'properties' => $reflection->getProperties(),
            'methods'    => $reflection->getMethods()
        ];

        $count = array_sum([
            count($members['constants']),
            count($members['properties']),
            count($members['methods'])
        ]);
        $counter = 1;
        $result = "$type ";
        if ($count == 0 || !$expand) {
            if (!$expand) {
                $result .= "<span style=\"color: $color;\">$class { . . . }</span>";
            } else {
                $result .= "<span style=\"color: $color;\">$class { }</span>";
            }
            $result .= "<span style=\"color: $meta_color; font-style: italic; font-size: 0.9em;\"> ";
            $result .= "$class (constants: " . count($members['constants']) . ", ";
            $result .= "properties: " . count($members['properties']) . ", ";
            $result .= "methods: " . count($members['methods']) . ")";
            $result .= "</span>";
            return $result;
        }

        $result .= "<span style=\"color: $color;\">$class {</span>\n";
        foreach ($members['constants'] as $name => $value) {
            $result .= self::dumpVarObjectConstants($name, $value, $indent);
            if ($counter != $count) {
                $result .= ',';
                $counter++;
            }
            $result .= "\n";
        }
        foreach ($members['properties'] as $property) {
            $result .= self::dumpVarObjectProperties($property, $var, $indent);
            if ($counter != $count) {
                $result .= ',';
                $counter++;
            }
            $result .= "\n";
        }
        foreach ($members['methods'] as $attribute => $method) {
            $result .= self::dumpVarObjectMethods($method, $indent);
            if ($counter != $count) {
                $result .= ',';
                $counter++;
            }
            $result .= "\n";
        }
        if ($indent > 0) {
            $result .= str_repeat("    ", $indent);
        }
        $result .= "<span style=\"color: $color;\">}</span>";
            $result .= "<span style=\"color: $meta_color; font-style: italic; font-size: 0.9em;\"> ";
            $result .= "$class (constants: " . count($members['constants']) . ", ";
            $result .= "properties: " . count($members['properties']) . ", ";
            $result .= "methods: " . count($members['methods']) . ")";
            $result .= "</span>";
        return $result;
    }

    private static function dumpVarObjectStdClass(stdClass $var, int $indent): string
    {
        $type  = 'object';
        extract(static::$color_table[$type]);
        $var     = (array)$var;
        $count   = count($var);
        $counter = 1;
        $result  = "$type ";
        if ($count == 0) {
            $result .= "<span style=\"color: $color;\">stdClass { }</span>";
            $result .= "<span style=\"color: $meta_color; font-style: italic; font-size: 0.9em;\"> ";
            $result .= "properties: $count";
            $result .= "</span>";
            return $result;
        }

        $result .= "<span style=\"color: $color;\">stdClass {</span>\n";
        foreach ($var as $key => $value) {
            $result .= str_repeat("    ", $indent + 1);
            $result .= "<span style=\"color: $color;\">\$$key = </span>";
            $result .= self::dumpVar($value, $indent + 1);
            if ($counter != $count) {
                $result .= ',';
                $counter++;
            }
            $result .= "\n";
        }
        if ($indent > 0) {
            $result .= str_repeat("    ", $indent);
        }
        $result .= "<span style=\"color: $color;\">}</span>";
        $result .= "<span style=\"color: $meta_color; font-style: italic; font-size: 0.9em;\"> ";
        $result .= "properties: $count";
        $result .= "</span>";
        return $result;
    }

    private static function dumpVarObjectClosure(Closure $var, int $indent): string
    {
        $type  = 'object';
        extract(static::$color_table[$type]);
        extract(static::$color_table['function']);
        $function = new ReflectionFunction($var);
        $file = file($function->getFileName());
        $code = '';
        $use  = '';
        for ($line = $function->getStartLine() - 1; $line < $function->getEndLine(); $line++) {
            $code .= $file[$line];
        }
        preg_match('/(use[\s]?\([&\s\$a-z0-9_,]*\))/', $code, $matches, PREG_OFFSET_CAPTURE);
        if (count($matches)) {
            $use = ' ' . $matches[0][0];
            $use = str_replace('&', "<span style=\"color: $ref_color;\">&</span>", $use);
            $use = preg_replace('/(\$[a-z0-9_]*)/', "<span style=\"color: $param_color;\">$1</span>", $use);
        }
        $parameters = [];
        foreach ($function->getParameters() as $parameter) {
            $expression = "<span style=\"color: $param_color;\">\$" . $parameter->getName() . "</span>";
            $expression = $parameter->isArray() ?
                "<span style=\"color: $type_color;\">array </span>$expression" :
                $expression;
            $expression = $parameter->isPassedByReference() ?
                "<span style=\"color: $ref_color;\">&</span>" . $expression :
                $expression;
            if ($parameter->isDefaultValueAvailable()) {
                $default_value = $parameter->getDefaultValue();
                $expression .= "<span style=\"color: $value_color;\">";
                if (is_string($default_value)) {
                    $expression .= " = \"$default_value\"";
                } elseif (is_object($default_value)) {
                    $expression .= " = {" . get_class($default_value) . "}";
                } elseif (is_array($default_value)) {
                    $expression .= " = " . str_replace("\n", '', var_export($default_value, true));
                } else {
                    $expression .= " = $default_value";
                }
                $expression .= "</span>";
            }
            $parameters[] .= $expression;
        }
        $args = implode(', ', $parameters);

        $result = "$type ";
        $result = "<span style=\"color: $color;\">Closure </span>";
        $result .= "<span style=\"color: $keyword_color; font-weight: bold;\">function </span>";
        $result .= "<span style=\"color: $color;\">($args)$use {}</span>\n";
        $result .= str_repeat("    ", $indent + 1);
        $result .= "<span style=\"color: $notice_color;\">" . $function->getFileName();
        $result .= " (line " . $function->getStartLine() . " - " . $function->getEndLine() . "):</span>\n";
        $result .= "<div style=\"float: left;\">" . str_repeat("    ", $indent + 1) . "</div>";
        $result .= "<pre style=\"float: left;\">$code</pre>";
        $result .= "<div style=\"clear: both;\"></div>";
        if ($indent > 0) {
            $result .= str_repeat("    ", $indent);
        }
        return $result;
    }

    private static function dumpVarObjectConstants(string $name, $value, int $indent): string
    {
        extract(static::$color_table['object']);
        $result = str_repeat("    ", $indent + 1);
        $result .= "<span style=\"color: $keyword_color; font-weight: bold;\">const </span>";
        $result .= "<span style=\"color: $color;\">$name = </span>";
        $result .= self::dumpVar($value, $indent + 1);
        return $result;
    }

    private static function dumpVarObjectProperties(ReflectionProperty $property, $var, int $indent): string
    {
        extract(static::$color_table['object']);
        $name = $property->getName();
        if ($property->isPrivate()) {
            $attribute = 'private';
        } elseif ($property->isProtected()) {
            $attribute = 'protected';
        } else {
            $attribute = 'public';
        }
        $attribute .= $property->isStatic() ? ' static' : '';
        $property->setAccessible(true);
        $result = str_repeat("    ", $indent + 1);
        $result .= "<span style=\"color: $attr_color; font-weight: bold;\">$attribute </span>";
        $result .= "<span style=\"color: $color;\">\$$name = </span>";
        $result .= self::dumpVar($property->getValue($var), $indent + 1, static::$display_detail);
        return $result;
    }

    private static function dumpVarObjectMethods(ReflectionMethod $method, int $indent): string
    {
        extract(static::$color_table['object']);
        extract(static::$color_table['function']);
        $name = $method->getName();
        if ($method->isPrivate()) {
            $attribute = 'private';
        } elseif ($method->isProtected()) {
            $attribute = 'protected';
        } else {
            $attribute = 'public';
        }
        $attribute = $method->isAbstract() ? "absract $attribute" : $attribute;
        $attribute = $method->isFinal() ? "final $attribute" : $attribute;
        $attribute .= $method->isStatic() ? ' static' : '';
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $expression = "<span style=\"color: $param_color;\">\$" . $parameter->getName() . "</span>";
            $expression = $parameter->isArray() ?
                "<span style=\"color: $type_color;\">array </span>$expression" :
                $expression;
            $expression = $parameter->isPassedByReference() ?
                "<span style=\"color: $ref_color;\">&</span>" . $expression :
                $expression;
            if ($parameter->isDefaultValueAvailable()) {
                $default_value = $parameter->getDefaultValue();
                $expression .= "<span style=\"color: $value_color;\">";
                if (is_string($default_value)) {
                    $expression .= " = \"$default_value\"";
                } elseif (is_object($default_value)) {
                    $expression .= " = {" . get_class($default_value) . "}";
                } elseif (is_array($default_value)) {
                    $expression .= " = " . str_replace("\n", '', var_export($default_value, true));
                } else {
                    $expression .= " = $default_value";
                }
                $expression .= "</span>";
            }
            $parameters[] .= $expression;
        }
        $args = implode(', ', $parameters);
        $result = str_repeat("    ", $indent + 1);
        $result .= "<span style=\"color: $attr_color; font-weight: bold;\">$attribute </span>";
        $result .= "<span style=\"color: $keyword_color; font-weight: bold;\">function </span>";
        $result .= "<span style=\"color: $color;\">$name($args)</span>";
        return $result;
    }

    private static function dumpVarResource($var, int $indent): string
    {
        $type = 'resource';
        extract(static::$color_table[$type]);
        $result = "$type ";
        $result .= "<span style=\"color: $color\">[" . get_resource_type($var) . "]</span>";
        return $result;
    }

    private static function dumpVarNULL($var, int $indent): string
    {
        $type = 'null';
        extract(static::$color_table[$type]);
        $result = "$type ";
        $result .= "<span style=\"color: $color\">NULL</span>";
        return $result;
    }
}
