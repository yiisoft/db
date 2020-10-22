<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

/**
 * ColumnExtendedSchema class describes the metadata of a column in a database table.
 */
class ColumnExtendedSchema extends ColumnSchema
{
    private ?int $maxChars = null;
    private ?int $maxBytes = null;
    private ?string $charset = null;
    private ?string $check = null;

    /**
     * @return int|null
     */
    public function getMaxChars(): ?int
    {
        return $this->maxChars;
    }

    /**
     * @param int|null $maxChars
     */
    public function setMaxChars(?int $maxChars): void
    {
        $this->maxChars = $maxChars;
    }

    /**
     * @return int|null
     */
    public function getMaxBytes(): ?int
    {
        return $this->maxBytes;
    }

    /**
     * @param int|null $maxBytes
     */
    public function setMaxBytes(?int $maxBytes): void
    {
        $this->maxBytes = $maxBytes;
    }

    /**
     * @return string|null
     */
    public function getCharset(): ?string
    {
        return $this->charset;
    }

    /**
     * @param string|null $charset
     */
    public function setCharset(?string $charset): void
    {
        $this->charset = $charset;
    }

    public function getCheck(): ?string
    {
        return $this->check;
    }

    public function setCheck(?string $check): void
    {
        $this->check = $check;
    }
}
