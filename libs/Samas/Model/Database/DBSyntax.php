<?php
namespace Samas\PHP7\Model\Database;

use \RuntimeException;
use \Samas\PHP7\Kit\StrKit;

/**
 * SQL expression value object
 */
class DBSyntax
{
    const TYPE_TEXT        = 1;
    const TYPE_AND         = 2;
    const TYPE_OR          = 3;
    const TYPE_IN          = 4;
    const TYPE_BETWEEN     = 5;
    const TYPE_JSON_REMOVE = 6;

    private $type;
    private $json_doc;
    private $list = [];

    /**
     * __construct
     * @param  string/DBSyntax  $expression  DB expression
     * @return void
     */
    public function __construct(...$params)
    {
        $this->type = self::TYPE_TEXT;
        foreach ($params as $value) {
            $this->add($value);
        }
    }

    /**
     * define output string content of instance
     * @return  string
     */
    public function __toString(): string
    {
        return $this->getVal();
    }

    /**
     * build SQL plan text expression
     * @param  mixed  $params  parameters
     * @return DBSyntax
     */
    public static function text(...$params): self
    {
        $obj = new self;
        $obj->setType(self::TYPE_TEXT);
        foreach ($params as $value) {
            $obj->add($value);
        }
        return $obj;
    }

    /**
     * build SQL "AND" expression
     * @param  mixed  $params  elements of "AND"
     * @return DBSyntax
     */
    public static function and(...$params): self
    {
        $obj = new self;
        $obj->setType(self::TYPE_AND);
        foreach ($params as $value) {
            $obj->add($value);
        }
        return $obj;
    }

    /**
     * build SQL "OR" expression
     * @param  mixed  $params  elements of "OR"
     * @return DBSyntax
     */
    public static function or(...$params): self
    {
        $obj = new self;
        $obj->setType(self::TYPE_OR);
        foreach ($params as $value) {
            $obj->add($value);
        }
        return $obj;
    }

    /**
     * build SQL "IN" expression
     * @param  mixed  $params  elements of "IN"
     * @return DBSyntax
     */
    public static function in(...$params): self
    {
        $obj = new self;
        $obj->setType(self::TYPE_IN);
        foreach ($params as $value) {
            $obj->add($value);
        }
        return $obj;
    }

    /**
     * build SQL "BETWEEN" expression
     * @param  mixed  $lower_bound  lower bound
     * @param  mixed  $upper_bound  upper bound
     * @return DBSyntax
     */
    public static function between($lower_bound, $upper_bound): self
    {
        $obj = new self;
        $obj->setType(self::TYPE_BETWEEN)->add($lower_bound)->add($upper_bound);
        return $obj;
    }

    /**
     * build SQL "JSON_REMOVE" expression
     * @param  string  $json_doc  json document or field name
     * @param  mixed   $params    json paths to remove
     * @return DBSyntax
     */
    public static function jsonRemove(string $json_doc, ...$params): self
    {
        $obj = new self;
        $obj->setDoc($json_doc)->setType(self::TYPE_JSON_REMOVE);
        foreach ($params as $value) {
            $obj->add(StrKit::getDBJSONPath($value));
        }
        return $obj;
    }

    /**
     * set expression type
     * @param  int  $type  refer to DBSyntax constants
     * @return DBSyntax
     */
    public function setType(int $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * get expression type
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * get JSON document or field name
     * @param  string  $doc  JSON document or field name
     * @return DBSyntax
     */
    public function setDoc(string $doc): self
    {
        $this->doc = StrKit::isJSON($doc) ? "'$doc'" : StrKit::getDBTargetStr($doc);
        return $this;
    }

    /**
     * get JSON document or field name
     * @return DBSyntax
     */
    public function getDoc(): string
    {
        return $this->doc;
    }

    /**
     * add element to sequence for building expression
     * @param  mixed  $value  element to add
     * @return DBSyntax
     */
    public function add($value): self
    {
        $this->list[] = $value;
        return $this;
    }

    /**
     * get expression
     * @return string
     */
    public function getVal(): string
    {
        $plan_expression_list = [];
        foreach ($this->list as $value) {
            $list_type = [self::TYPE_AND, self::TYPE_OR];
            $plan_expression_list[] = ($value instanceof self) && in_array($value->getType(), $list_type) ?
                                      '(' . $value->getVal() . ')' :
                                      $value;
        }
        switch ($this->type) {
            case self::TYPE_TEXT:
                return implode(' ', $plan_expression_list);
                break;
            case self::TYPE_AND:
                return implode(' AND ', $plan_expression_list);
                break;
            case self::TYPE_OR:
                return implode(' OR ', $plan_expression_list);
                break;
            case self::TYPE_IN:
                return 'IN (' . implode(', ', $plan_expression_list) . ')';
                break;
            case self::TYPE_BETWEEN:
                return 'BETWEEN ' . implode(' AND ', $plan_expression_list);
                break;
            case self::TYPE_JSON_REMOVE:
                return "JSON_REMOVE({$this->doc}, '" . implode("', '", $plan_expression_list) . "')";
                break;
            default:
                throw new RuntimeException('illegal type of ' . __CLASS__);
                break;
        }
    }
}
