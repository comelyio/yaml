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

namespace Comely\IO\Yaml;

use Comely\Kernel\Extend\ComponentInterface;

/**
 * Class Yaml
 * @package Comely\IO\Yaml
 */
class Yaml implements ComponentInterface
{
    /**
     * @param string $yamlFile
     * @return Parser
     */
    public static function Parse(string $yamlFile): Parser
    {
        return new Parser($yamlFile);
    }

    /**
     * @param array $in
     * @return Compiler
     */
    public static function Compile(array $in): Compiler
    {
        return new Compiler($in);
    }
}