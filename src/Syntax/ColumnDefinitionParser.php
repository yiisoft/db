<?php

declare(strict_types=1);

namespace Yiisoft\Db\Syntax;

use function explode;
use function preg_match;
use function preg_match_all;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;
use function trim;

/**
 * Parses column definition string. For example, `string(255)` or `int unsigned`.
 */
class ColumnDefinitionParser
{
    /**
     * Parses column definition string.
     *
     * @param string $definition The column definition string. For example, `string(255)` or `int unsigned`.
     *
     * @return array The column information.
     *
     * @psalm-return array{
     *     check?: string,
     *     comment?: string,
     *     defaultValueRaw?: string,
     *     dimension?: int,
     *     enumValues?: list<string>,
     *     extra?: string,
     *     notNull?: bool,
     *     scale?: int,
     *     size?: int,
     *     type: lowercase-string,
     *     unique?: bool,
     *     unsigned?: bool,
     * }
     */
    public function parse(string $definition): array
    {
        preg_match('/^(\w*)(?:\(([^)]+)\))?\s*/', $definition, $matches);

        $type = strtolower($matches[1]);
        $info = ['type' => $type];

        if (isset($matches[2])) {
            if ($type === 'enum') {
                $info += $this->enumInfo($matches[2]);
            } else {
                $info += $this->sizeInfo($matches[2]);
            }
        }

        $extra = substr($definition, strlen($matches[0]));

        return $info + $this->extraInfo($extra);
    }

    /**
     * @psalm-return array{enumValues: list<string>}
     */
    protected function enumInfo(string $values): array
    {
        preg_match_all("/'([^']*)'/", $values, $matches);

        return ['enumValues' => $matches[1]];
    }

    /**
     * @psalm-return array{
     *     check?: string,
     *     comment?: string,
     *     defaultValueRaw?: string,
     *     extra?: string,
     *     notNull?: bool,
     *     unique?: bool,
     *     unsigned?: bool
     * }
     */
    protected function extraInfo(string $extra): array
    {
        if (empty($extra)) {
            return [];
        }

        $info = [];
        $bracketsPattern = '(\(((?>[^()]+)|(?-2))*\))';
        $defaultPattern = "/\\s*\\bDEFAULT\\s+('(?:[^']|'')*'|\"(?:[^\"]|\"\")*\"|[^(\\s]*$bracketsPattern?\\S*)/i";

        if (preg_match($defaultPattern, $extra, $matches) === 1) {
            $info['defaultValueRaw'] = $matches[1];
            $extra = str_replace($matches[0], '', $extra);
        }

        if (preg_match("/\\s*\\bCOMMENT\\s+'((?:[^']|'')*)'/i", $extra, $matches) === 1) {
            $info['comment'] = str_replace("''", "'", $matches[1]);
            $extra = str_replace($matches[0], '', $extra);
        }

        if (preg_match("/\\s*\\bCHECK\\s+$bracketsPattern/i", $extra, $matches) === 1) {
            $info['check'] = substr($matches[1], 1, -1);
            $extra = str_replace($matches[0], '', $extra);
        }

        $extra = preg_replace('/\s*\bUNSIGNED\b/i', '', $extra, 1, $count);
        if ($count > 0) {
            $info['unsigned'] = true;
        }

        $extra = preg_replace('/\s*\bUNIQUE\b/i', '', $extra, 1, $count);
        if ($count > 0) {
            $info['unique'] = true;
        }

        $extra = preg_replace('/\s*\bNOT\s+NULL\b/i', '', $extra, 1, $count);
        if ($count > 0) {
            $info['notNull'] = true;
        } else {
            $extra = preg_replace('/\s*\bNULL\b/i', '', $extra, 1, $count);
            if ($count > 0) {
                $info['notNull'] = false;
            }
        }

        $extra = trim($extra);

        if (!empty($extra)) {
            $info['extra'] = $extra;
        }

        return $info;
    }

    /**
     * @psalm-return array{size: int, scale?: int}
     */
    protected function sizeInfo(string $size): array
    {
        $values = explode(',', $size);

        $info = [
            'size' => (int) $values[0],
        ];

        if (isset($values[1])) {
            $info['scale'] = (int) $values[1];
        }

        return $info;
    }
}
