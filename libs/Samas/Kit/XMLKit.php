<?php
namespace Samas\PHP7\Kit;

use \SimpleXMLElement;

class XMLKit
{
    public static function build(string $tag_name, $content, int $level = 0): string
    {
        $indent = str_repeat('    ', $level);
        if (is_array($content)) {
            $node_content = '';
            foreach ($content as $child_tag => $child_content) {
                if (is_int($child_tag)) {
                    foreach ($child_content as $grand_child_tag => $grand_child_content) {
                        $node_content .= self::build($grand_child_tag, $grand_child_content, $level + 1);
                    }
                } else {
                    $node_content .= self::build($child_tag, $child_content, $level + 1);
                }
            }
            return is_int($tag_name) ?
                   $node_content :
                   "$indent<$tag_name>\n$node_content$indent</$tag_name>\n";
        }
        return "$indent<$tag_name>$content</$tag_name>\n";
    }

    public static function parse(SimpleXMLElement $xml): array
    {
        $array = [];
        foreach ($xml as $name => $element) {
            $node = &$array[$name];
            if (count($node) === 1) {
                $node = [$node];
                $node = &$node[];
            }
            $node = $element->count() ? self::parse($element) : trim($element);
        }

        return $array;
    }
}
