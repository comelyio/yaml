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

namespace Comely\IO\Yaml;

use Comely\Kernel\Extend\ComponentInterface;

/**
 * Class Yaml
 * @package Comely\IO\Yaml
 */
class Yaml implements ComponentInterface
{
    public const KEYS_DEFAULT = 1000;
    public const KEYS_CAMEL_CASE = 1001;
    public const KEYS_SNAKE_CASE = 1002;
    public const OUTPUT_SET_INACCESSIBLE = 1101;
    public const OUTPUT_IGNORE_INACCESSIBLE = 1102;

    /**
     * @param string $yamlFile
     * @return Parser
     */
    public static function Parse(string $yamlFile) : Parser
    {
        return new Parser($yamlFile);
    }
}