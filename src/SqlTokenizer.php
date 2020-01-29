<?php

declare(strict_types=1);

namespace Yiisoft\Db;

use Yiisoft\Db\Exception\InvalidArgumentException;

/**
 * SqlTokenizer splits an SQL query into individual SQL tokens.
 *
 * It can be used to obtain an addition information from an SQL code.
 *
 * Usage example:
 *
 * ```php
 * $tokenizer = new SqlTokenizer("SELECT * FROM user WHERE id = 1");
 * $root = $tokeinzer->tokenize();
 * $sqlTokens = $root->getChildren();
 * ```
 *
 * Tokens are instances of [[SqlToken]].
 */
abstract class SqlTokenizer
{
    /**
     * @var string SQL code.
     */
    public string $sql;

    /**
     * @var int SQL code string length.
     */
    protected int $length;

    /**
     * @var int SQL code string current offset.
     */
    protected int $offset;

    /**
     * @var \SplStack stack of active tokens.
     */
    private $tokenStack;

    /**
     * @var SqlToken active token. It's usually a top of the token stack.
     */
    private $currentToken;

    /**
     * @var string[] cached substrings.
     */
    private $substrings;

    /**
     * @var string string current buffer value.
     */
    private $buffer = '';
    /**
     * @var SqlToken resulting token of a last [[tokenize()]] call.
     */
    private $token;

    /**
     * Constructor.
     *
     * @param string $sql SQL code to be tokenized.
     * @param array  $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct(string $sql)
    {
        $this->sql = $sql;
    }

    /**
     * Tokenizes and returns a code type token.
     *
     * @return SqlToken code type token.
     */
    public function tokenize()
    {
        $this->length = mb_strlen($this->sql, 'UTF-8');
        $this->offset = 0;
        $this->substrings = [];
        $this->buffer = '';

        $this->token = new SqlToken([
            'type'    => SqlToken::TYPE_CODE,
            'content' => $this->sql,
        ]);

        $this->tokenStack = new \SplStack();
        $this->tokenStack->push($this->token);
        $this->token[] = new SqlToken(['type' => SqlToken::TYPE_STATEMENT]);
        $this->tokenStack->push($this->token[0]);
        $this->currentToken = $this->tokenStack->top();

        while (!$this->isEof()) {
            if ($this->isWhitespace($length) || $this->isComment($length)) {
                $this->addTokenFromBuffer();
                $this->advance($length);
                continue;
            }

            if ($this->tokenizeOperator($length) || $this->tokenizeDelimitedString($length)) {
                $this->advance($length);
                continue;
            }

            $this->buffer .= $this->substring(1);
            $this->advance(1);
        }
        $this->addTokenFromBuffer();
        if ($this->token->getHasChildren() && !$this->token[-1]->getHasChildren()) {
            unset($this->token[-1]);
        }

        return $this->token;
    }

    /**
     * Returns whether there's a whitespace at the current offset.
     *
     * If this methos returns `true`, it has to set the `$length` parameter to the length of the matched string.
     *
     * @param int $length length of the matched string.
     *
     * @return bool whether there's a whitespace at the current offset.
     */
    abstract protected function isWhitespace(int &$length): bool;

    /**
     * Returns whether there's a commentary at the current offset.
     * If this methos returns `true`, it has to set the `$length` parameter to the length of the matched string.
     *
     * @param int $length length of the matched string.
     *
     * @return bool whether there's a commentary at the current offset.
     */
    abstract protected function isComment(int &$length): bool;

    /**
     * Returns whether there's an operator at the current offset.
     *
     * If this methos returns `true`, it has to set the `$length` parameter to the length of the matched string.
     * It may also set `$content` to a string that will be used as a token content.
     *
     * @param int $length  length of the matched string.
     * @param string $content optional content instead of the matched string.
     *
     * @return bool whether there's an operator at the current offset.
     */
    abstract protected function isOperator(int &$length, string &$content): bool;

    /**
     * Returns whether there's an identifier at the current offset.
     *
     * If this methos returns `true`, it has to set the `$length` parameter to the length of the matched string.
     * It may also set `$content` to a string that will be used as a token content.
     *
     * @param int $length length of the matched string.
     * @param string $content optional content instead of the matched string.
     *
     * @return bool whether there's an identifier at the current offset.
     */
    abstract protected function isIdentifier(int &$length, string &$content): bool;

    /**
     * Returns whether there's a string literal at the current offset.
     *
     * If this methos returns `true`, it has to set the `$length` parameter to the length of the matched string.
     * It may also set `$content` to a string that will be used as a token content.
     *
     * @param int $length  length of the matched string.
     * @param string $content optional content instead of the matched string.
     *
     * @return bool whether there's a string literal at the current offset.
     */
    abstract protected function isStringLiteral(int &$length, string &$content): bool;

    /**
     * Returns whether the given string is a keyword.
     *
     * The method may set `$content` to a string that will be used as a token content.
     *
     * @param string $string  string to be matched.
     * @param string $content optional content instead of the matched string.
     *
     * @return bool whether the given string is a keyword.
     */
    abstract protected function isKeyword(string $string, string &$content): bool;

    /**
     * Returns whether the longest common prefix equals to the SQL code of the same length at the current offset.
     *
     * @param string[] $with strings to be tested. The method **will** modify this parameter to speed up lookups.
     * @param bool $caseSensitive whether to perform a case sensitive comparison.
     * @param int|null $length length of the matched string.
     * @param string|null $content matched string.
     *
     * @return bool whether a match is found.
     */
    protected function startsWithAnyLongest(
        array &$with,
        bool $caseSensitive,
        ?int &$length = null,
        ?string &$content = null
    ): bool {
        if (empty($with)) {
            return false;
        }

        if (!is_array(reset($with))) {
            usort($with, function ($string1, $string2) {
                return mb_strlen($string2, 'UTF-8') - mb_strlen($string1, 'UTF-8');
            });
            $map = [];
            foreach ($with as $string) {
                $map[mb_strlen($string, 'UTF-8')][$caseSensitive ? $string : mb_strtoupper($string, 'UTF-8')] = true;
            }
            $with = $map;
        }
        foreach ($with as $testLength => $testValues) {
            $content = $this->substring($testLength, $caseSensitive);
            if (isset($testValues[$content])) {
                $length = $testLength;

                return true;
            }
        }

        return false;
    }

    /**
     * Returns a string of the given length starting with the specified offset.
     *
     * @param int $length string length to be returned.
     * @param bool $caseSensitive if it's `false`, the string will be uppercased.
     * @param int|null $offset SQL code offset, defaults to current if `null` is passed.
     *
     * @return string result string, it may be empty if there's nothing to return.
     */
    protected function substring(int $length, bool $caseSensitive = true, ?int $offset = null)
    {
        if ($offset === null) {
            $offset = $this->offset;
        }

        if ($offset + $length > $this->length) {
            return '';
        }

        $cacheKey = $offset.','.$length;

        if (!isset($this->substrings[$cacheKey.',1'])) {
            $this->substrings[$cacheKey.',1'] = mb_substr($this->sql, $offset, $length, 'UTF-8');
        }
        if (!$caseSensitive && !isset($this->substrings[$cacheKey.',0'])) {
            $this->substrings[$cacheKey.',0'] = mb_strtoupper($this->substrings[$cacheKey.',1'], 'UTF-8');
        }

        return $this->substrings[$cacheKey.','.(int) $caseSensitive];
    }

    /**
     * Returns an index after the given string in the SQL code starting with the specified offset.
     *
     * @param string $string string to be found.
     * @param int|null $offset SQL code offset, defaults to current if `null` is passed.
     *
     * @return int index after the given string or end of string index.
     */
    protected function indexAfter(string $string, ?int $offset = null): int
    {
        if ($offset === null) {
            $offset = $this->offset;
        }

        if ($offset + mb_strlen($string, 'UTF-8') > $this->length) {
            return $this->length;
        }

        $afterIndexOf = mb_strpos($this->sql, $string, $offset, 'UTF-8');

        if ($afterIndexOf === false) {
            $afterIndexOf = $this->length;
        } else {
            $afterIndexOf += mb_strlen($string, 'UTF-8');
        }

        return $afterIndexOf;
    }

    /**
     * Determines whether there is a delimited string at the current offset and adds it to the token children.
     *
     * @param int $length
     *
     * @return bool
     */
    private function tokenizeDelimitedString(int &$length): bool
    {
        $isIdentifier = $this->isIdentifier($length, $content);
        $isStringLiteral = !$isIdentifier && $this->isStringLiteral($length, $content);

        if (!$isIdentifier && !$isStringLiteral) {
            return false;
        }

        $this->addTokenFromBuffer();
        $this->currentToken[] = new SqlToken([
            'type'        => $isIdentifier ? SqlToken::TYPE_IDENTIFIER : SqlToken::TYPE_STRING_LITERAL,
            'content'     => is_string($content) ? $content : $this->substring($length),
            'startOffset' => $this->offset,
            'endOffset'   => $this->offset + $length,
        ]);

        return true;
    }

    /**
     * Determines whether there is an operator at the current offset and adds it to the token children.
     *
     * @param int $length
     *
     * @return bool
     */
    private function tokenizeOperator(int &$length): bool
    {
        if (!$this->isOperator($length, $content)) {
            return false;
        }

        $this->addTokenFromBuffer();

        switch ($this->substring($length)) {
            case '(':
                $this->currentToken[] = new SqlToken([
                    'type'        => SqlToken::TYPE_OPERATOR,
                    'content'     => is_string($content) ? $content : $this->substring($length),
                    'startOffset' => $this->offset,
                    'endOffset'   => $this->offset + $length,
                ]);
                $this->currentToken[] = new SqlToken(['type' => SqlToken::TYPE_PARENTHESIS]);
                $this->tokenStack->push($this->currentToken[-1]);
                $this->currentToken = $this->tokenStack->top();

                break;

            case ')':
                $this->tokenStack->pop();
                $this->currentToken = $this->tokenStack->top();
                $this->currentToken[] = new SqlToken([
                    'type'        => SqlToken::TYPE_OPERATOR,
                    'content'     => ')',
                    'startOffset' => $this->offset,
                    'endOffset'   => $this->offset + $length,
                ]);

                break;
            case ';':
                if (!$this->currentToken->getHasChildren()) {
                    break;
                }

                $this->currentToken[] = new SqlToken([
                    'type'        => SqlToken::TYPE_OPERATOR,
                    'content'     => is_string($content) ? $content : $this->substring($length),
                    'startOffset' => $this->offset,
                    'endOffset'   => $this->offset + $length,
                ]);
                $this->tokenStack->pop();
                $this->currentToken = $this->tokenStack->top();
                $this->currentToken[] = new SqlToken(['type' => SqlToken::TYPE_STATEMENT]);
                $this->tokenStack->push($this->currentToken[-1]);
                $this->currentToken = $this->tokenStack->top();

                break;
            default:
                $this->currentToken[] = new SqlToken([
                    'type'        => SqlToken::TYPE_OPERATOR,
                    'content'     => is_string($content) ? $content : $this->substring($length),
                    'startOffset' => $this->offset,
                    'endOffset'   => $this->offset + $length,
                ]);

                break;
        }

        return true;
    }

    /**
     * Determines a type of text in the buffer, tokenizes it and adds it to the token children.
     */
    private function addTokenFromBuffer()
    {
        if ($this->buffer === '') {
            return;
        }

        $isKeyword = $this->isKeyword($this->buffer, $content);

        $this->currentToken[] = new SqlToken([
            'type'        => $isKeyword ? SqlToken::TYPE_KEYWORD : SqlToken::TYPE_TOKEN,
            'content'     => is_string($content) ? $content : $this->buffer,
            'startOffset' => $this->offset - mb_strlen($this->buffer, 'UTF-8'),
            'endOffset'   => $this->offset,
        ]);
        $this->buffer = '';
    }

    /**
     * Adds the specified length to the current offset.
     *
     * @param int $length
     *
     * @throws \InvalidArgumentException
     */
    private function advance(int $length)
    {
        if ($length <= 0) {
            throw new \InvalidArgumentException('Length must be greater than 0.');
        }

        $this->offset += $length;
        $this->substrings = [];
    }

    /**
     * Returns whether the SQL code is completely traversed.
     *
     * @return bool
     */
    private function isEof(): bool
    {
        return $this->offset >= $this->length;
    }
}
