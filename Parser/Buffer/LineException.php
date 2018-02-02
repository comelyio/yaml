<?php
/**
 * This file is part of Comely package.
 * https://github.com/comelyio/comely
 *
 * Copyright (c) 2016-2018 Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comelyio/comely/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\IO\Yaml\Parser\Buffer;

use Throwable;

/**
 * Class LineException
 * @package Comely\IO\Yaml\Parser\Buffer
 */
class LineException extends \Exception
{
    /** @var int */
    public $lineNum;
    /** @var int|null */
    public $lineIndent;

    /**
     * LineException constructor.
     * @param Line $line
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(Line $line, string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->lineNum = $line->num;
        $this->lineIndent = $line->indent;
    }
}