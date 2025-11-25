<?php

declare(strict_types=1);

namespace Yiisoft\Db\Syntax;

use function explode;
use function preg_match;
use function preg_replace;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;
use function substr_count;
use function trim;

/**
 * Parses column definition string. For example, `string(255)` or `int unsigned`.
 *
 * @psalm-type ExtraInfo = array{
 *     check?: string,
 *     collation?: string,
 *     comment?: string,
 *     defaultValueRaw?: string,
 *     extra?: string,
 *     notNull?: bool,
 *     unique?: bool,
 *     unsigned?: bool
 * }
 */
abstract class AbstractColumnDefinitionParser implements ColumnDefinitionParserInterface
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
     *     collation?: string,
     *     comment?: string,
     *     defaultValueRaw?: string,
     *     dimension?: positive-int,
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
        [$type, $typeParams, $dimension, $extraInfo] = $this->parseDefinition($definition);

        $type = strtolower($type);
        $info = ['type' => $type];

        if ($typeParams !== '' && $typeParams !== null) {
            $info += $this->parseTypeParams($type, $typeParams);
        }

        if (isset($dimension)) {
            /** @psalm-var positive-int */
            $info['dimension'] = substr_count($dimension, '[');
        }

        return $info + $this->extraInfo($extraInfo);
    }

    /**
     * Parse the column definition into its components:
     *  - type
     *  - type parameters
     *  - dimension
     *  - extra information
     *
     * @psalm-return list{string, string|null, string|null, string|null}
     */
    protected function parseDefinition(string $definition): array
    {
        preg_match('/^(\w*)(?:\(([^)]+)\))?(\[[\d\[\]]*\])?\s*/', $definition, $matches);

        return [
            $matches[1],
            $matches[2] ?? null,
            $matches[3] ?? null,
            substr($definition, strlen($matches[0])),
        ];
    }

    /**
     * @psalm-param non-empty-string $params
     * @psalm-return array{enumValues?: list<string>, size?: int, scale?: int}
     */
    abstract protected function parseTypeParams(string $type, string $params): array;

    /**
     * @psalm-return ExtraInfo
     */
    protected function extraInfo(string $extra): array
    {
        if (empty($extra)) {
            return [];
        }

        $info = [];
        $bracketsPattern = '(\(((?>[^()]+)|(?-2))*\))';
        $defaultPattern = "/\\s*\\bDEFAULT\\s+('(?:[^']|'')*'|\"(?:[^\"]|\"\")*\"|[^(\\s]*$bracketsPattern?\\S*)/i";

        $extra = $this->parseStringValue($extra, $defaultPattern, 'defaultValueRaw', $info);
        $extra = $this->parseStringValue($extra, "/\\s*\\bCOMMENT\\s+'((?:[^']|'')*)'/i", 'comment', $info);
        $extra = $this->parseStringValue($extra, "/\\s*\\bCHECK\\s+$bracketsPattern/i", 'check', $info);
        $extra = $this->parseStringValue($extra, '/\s*\bCOLLATE\s+(\S+)/i', 'collation', $info);
        $extra = $this->parseBoolValue($extra, '/\s*\bUNSIGNED\b/i', 'unsigned', $info);
        $extra = $this->parseBoolValue($extra, '/\s*\bUNIQUE\b/i', 'unique', $info);
        $extra = $this->parseBoolValue($extra, '/\s*\bNOT\s+NULL\b/i', 'notNull', $info);

        if (empty($info['notNull'])) {
            $extra = $this->parseBoolValue($extra, '/\s*\bNULL\b/i', 'notNull', $info);

            if (!empty($info['notNull'])) {
                $info['notNull'] = false;
            }
        }

        /** @psalm-var ExtraInfo $info */
        if (!empty($info['comment'])) {
            $info['comment'] = str_replace("''", "'", $info['comment']);
        }

        if (!empty($info['check'])) {
            $info['check'] = substr($info['check'], 1, -1);
        }

        if (!empty($extra)) {
            $info['extra'] = $extra;
        }

        return $info;
    }

    /**
     * @psalm-param non-empty-string $pattern
     */
    protected function parseStringValue(string $extra, string $pattern, string $name, array &$info): string
    {
        if (!empty($extra) && preg_match($pattern, $extra, $matches) === 1) {
            $info[$name] = $matches[1];
            return trim(str_replace($matches[0], '', $extra));
        }

        return $extra;
    }

    /**
     * @psalm-param non-empty-string $pattern
     */
    protected function parseBoolValue(string $extra, string $pattern, string $name, array &$info): string
    {
        if (empty($extra)) {
            return '';
        }

        /** @psalm-suppress PossiblyNullArgument */
        $extra = trim(preg_replace($pattern, '', $extra, 1, $count));

        if ($count > 0) {
            $info[$name] = true;
        }

        return $extra;
    }

    /**
     * @psalm-return array{size: int, scale?: int}
     */
    protected function parseSizeInfo(string $params): array
    {
        $values = explode(',', $params);

        $info = [
            'size' => (int) $values[0],
        ];

        if (isset($values[1])) {
            $info['scale'] = (int) $values[1];
        }

        return $info;
    }
}
