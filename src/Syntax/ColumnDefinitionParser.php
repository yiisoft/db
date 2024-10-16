<?php

declare(strict_types=1);

namespace Yiisoft\Db\Syntax;

use function explode;
use function preg_match;
use function preg_match_all;
use function str_ireplace;
use function stripos;
use function strlen;
use function strtolower;
use function substr;
use function trim;

/**
 * Parses column definition string. For example, `string(255)` or `int unsigned`.
 */
final class ColumnDefinitionParser
{
    /**
     * Parses column definition string.
     *
     * @param string $definition The column definition string. For example, `string(255)` or `int unsigned`.
     *
     * @return array The column information.
     *
     * @psalm-return array{
     *     enumValues?: list<string>,
     *     extra?: string,
     *     scale?: int,
     *     size?: int,
     *     type: lowercase-string,
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
    private function enumInfo(string $values): array
    {
        preg_match_all("/'([^']*)'/", $values, $matches);

        return ['enumValues' => $matches[1]];
    }

    /**
     * @psalm-return array{unsigned?: bool, extra?: string}
     */
    private function extraInfo(string $extra): array
    {
        if (empty($extra)) {
            return [];
        }

        $info = [];

        if (stripos($extra, 'unsigned') !== false) {
            $info['unsigned'] = true;
            $extra = trim(str_ireplace('unsigned', '', $extra));
        }

        if (!empty($extra)) {
            $info['extra'] = $extra;
        }

        return $info;
    }

    /**
     * @psalm-return array{size: int, scale?: int}
     */
    private function sizeInfo(string $size): array
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
