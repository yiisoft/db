<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Syntax\AbstractSqlParser;

class SqlParser extends AbstractSqlParser
{
    public function getNextPlaceholder(?int &$position = null): ?string
    {
        $result = null;
        $length = $this->length - 1;

        while ($this->position < $length) {
            $pos = $this->position++;

            match ($this->sql[$pos]) {
                ':' => ($word = $this->parseWord()) === ''
                    ? $this->skipChars(':')
                    : $result = ':' . $word,
                '"', "'" => $this->skipQuotedWithoutEscape($this->sql[$pos]),
                '-' => $this->sql[$this->position] === '-'
                    ? ++$this->position && $this->skipToAfterChar("\n")
                    : null,
                '/' => $this->sql[$this->position] === '*'
                    ? ++$this->position && $this->skipToAfterString('*/')
                    : null,
                default => null,
            };

            if ($result !== null) {
                $position = $pos;

                return $result;
            }
        }

        return null;
    }
}
