<?php
namespace Samas\PHP7\Kit;

use \ErrorException, \Throwable;

class ErrorKit
{
    public static function getErrorInfo(Throwable $error, bool $backtrace = false): array
    {
        $info = [
            'url'     => WebKit::getRequestURI(),
            'method'  => WebKit::getRequestMethod(),
            '_GET'    => WebKit::getGETParams(),
            '_POST'   => WebKit::getPOSTParams(),
            'level'   => 'UNKNOWN',
            'path'    => $error->getFile() . ':' . $error->getLine(),
            'message' => $error->getMessage()
        ];
        if ($error instanceof ErrorException) {
            $info['level'] = self::getReadableErrorLevel($error->getSeverity());
        } else {
            $info['level'] = get_class($error);
        }
        if ($backtrace > 0) {
            $info['trace'] = $error->getTrace();
        }
        return $info;
    }

    public static function getReadableErrorLevel(int $severity): string
    {
        $names = array();
        $consts = array_flip(
            array_slice(
                get_defined_constants(true)['Core'],
                0,
                15,
                true
            )
        );
        foreach ($consts as $code => $name) {
            if ($severity & $code) {
                $names[] = $name;
            }
        }
        return join(' | ', $names);
    }
}
