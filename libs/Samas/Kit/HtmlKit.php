<?php
namespace Samas\PHP7\Kit;

class HtmlKit
{
    private static $type_void_tags = [
        'area',
        'base',
        'br',
        'col',
        'command',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'menuitem',
        'meta',
        'param',
        'source',
        'track',
        'wbr'
    ];

    public static function php(string $code): string
    {
        return "<?php $code ?>";
    }

    public static function js(string $code): string
    {
        return self::charset() . "<script>$code</script>";
    }

    public static function charset(string $charset = ''): string
    {
        $charset = empty($charset) ? ini_get('default_charset') : $charset;
        return '<meta http-equiv="Content-Type" content="text/html; charset="' . $charset . '" />';
    }

    public static function __callStatic(string $name, array $args)
    {
        $tag = strtolower($name);
        if (in_array($tag, static::$type_void_tags)) {
            return self::voidTag($tag, $args);
        } else {
            return self::normalTag($tag, $args);
        }
    }

    private static function voidTag(string $tag, array $args): string
    {
        // public static function {tag}(array $attributes = []): string
        $default_args = [
            []
        ];
        $args = array_replace($default_args, $args);
        $attributes = $args[0] ? $args[0] : [];

        $tag_attr = self::genTagAttr($attributes);

        $code = "<$tag$tag_attr>";
        return $code;
    }

    private static function normalTag(string $tag, array $args): string
    {
        // public static function {tag}(array $attributes = [], string $inner_html = ''): string
        $default_args = [
            [],
            ''
        ];
        $args = array_replace($default_args, $args);
        $attributes = $args[0] ? $args[0] : [];
        $inner_html = $args[1] ? $args[1] : '';

        $tag_attr = self::genTagAttr($attributes);

        $code = "<$tag$tag_attr>$inner_html</$tag>";
        return $code;
    }

    private static function genTagAttr(array $attributes): string
    {
        $tag_attr = '';
        foreach ($attributes as $attr => $attr_value) {
            if (is_array($attr_value)) {
                $tag_attr .= " $attr=\"";
                foreach ($attr_value as $key => $value) {
                    $tag_attr .= " $key: " . StrKit::output($value) . ';';
                }
                $tag_attr .= '"';
            } else {
                $tag_attr .= " $attr=\"" . StrKit::output($attr_value) . '"';
            }
        }
        return $tag_attr;
    }
}
