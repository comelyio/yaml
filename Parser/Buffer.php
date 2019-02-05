<?php
/**
 * This file is part of Comely package.
 * https://github.com/comelyio/comely
 *
 * Copyright (c) 2016-2019 Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comelyio/comely/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\IO\Yaml\Parser;

use Comely\IO\Yaml\Parser\Buffer\Line;

/**
 * Class Buffer
 * @package Comely\IO\Yaml\Parser
 */
class Buffer implements \Iterator
{
    /** @var null|string */
    private $parent;
    /** @var null|string */
    private $type;
    /** @var array */
    private $lines;
    /** @var int */
    private $indent;
    /** @var int */
    private $offset;

    /** @var null|Buffer */
    private $subBuffer;

    /** @var int */
    private $position;

    /**
     * Buffer constructor.
     * @param int $indent
     * @param int $offset
     * @param string|null $parent
     * @param string|null $type
     */
    public function __construct(int $indent = 0, int $offset = 0, string $parent = null, string $type = null)
    {
        $this->parent = $parent;
        $this->type = $type;
        $this->lines = [];
        $this->indent = $indent;
        $this->offset = $offset;
        $this->position = 0;
    }

    /**
     * @param array $lines
     * @return Buffer
     */
    public function feed(array $lines): self
    {
        $this->lines = $lines;
        return $this;
    }

    /**
     * @param int $indent
     * @param int $offset
     * @param string $parent
     * @param string|null $type
     * @return Buffer
     */
    public function createSubBuffer(int $indent, int $offset, string $parent, string $type = null): Buffer
    {
        $this->subBuffer = new Buffer($indent, $offset, $parent, $type);
        return $this->subBuffer;
    }

    /**
     * @return Buffer|null
     */
    public function subBuffer(): ?Buffer
    {
        return $this->subBuffer;
    }

    /**
     * @return void
     */
    public function clearSubBuffer(): void
    {
        $this->subBuffer = null;
    }

    /**
     * @param Line $line
     */
    public function addToBuffer(Line $line): void
    {
        if ($this->subBuffer) {
            $this->subBuffer->addToBuffer($line);
            return;
        }

        $this->lines[] = $line->raw;
    }

    /**
     * @return null|string
     */
    public function parent(): ?string
    {
        return $this->parent;
    }

    /**
     * @return null|string
     */
    public function type(): ?string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function indent(): int
    {
        return $this->indent;
    }

    /**
     * @return int
     */
    public function lineNumber(): int
    {
        return $this->position + $this->offset;
    }

    /**
     * Reset lines position
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Process current line
     * @return Line
     */
    public function current(): Line
    {
        return new Line($this->lines[$this->position], $this->lineNumber());
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Advance line pointer by 1
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->lines[$this->position]);
    }
}