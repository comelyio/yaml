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

/**
 * Class Line
 * @package Comely\IO\Yaml\Parser\Buffer
 */
class Line
{
    /** @var null|int */
    public $indent;
    /** @var int */
    public $num;
    /** @var null|string */
    public $key;
    /** @var null|string */
    public $value;
    /** @var string */
    public $raw;

    /**
     * Line constructor.
     * @param string $line
     * @param int $num
     * @throws LineException
     */
    public function __construct(string $line, int $num = 0)
    {
        $this->raw = $line;
        $this->num = $num + 1;

        // Check for indention by tab
        $lineOffset = $line[0] ?? "";
        if ($lineOffset === "\t") {
            throw new LineException($this, 'Line cannot be indented by tabs');
        }

        // Set line's indent value
        $lineLen = strlen($line);
        $this->indent = $lineLen - strlen(ltrim($line));

        // Check if line should be processed
        if (empty($line) || ctype_space($line)) {
            return; // Blank link
        } elseif (preg_match('/^\s*\#.*$/', $line)) {
            return; // Full line comment
        }

        // Clear any inline comment
        $line = trim(preg_split("/(#)(?=(?:[^\"\']|[\"\'][^\"\']*[\"\'])*$)/", $line, 2)[0]);

        // Check if line has key
        if (preg_match('/^(\s+)?[\w\_\-\.]+\:(.*)$/', $line)) {
            // Key exists, split into key/value pair
            $line = preg_split("/:/", $line, 2);
            $this->key = trim($line[0]);
            $this->value = trim(strval($line[1] ?? ""));
        } else {
            // Key doesn't exist, set entire line as value
            $this->value = trim($line);
        }

        // Change empty line to NULL
        if (empty($this->value)) {
            $this->value = null;
        }

        unset($line); // $line is now garbage
    }

    /**
     * @param bool $evaluateBooleans
     * @return bool|float|int|null|string
     * @throws LineException
     */
    public function processValue(bool $evaluateBooleans)
    {
        if (!$this->value) {
            return null;
        }

        $lowercaseValue = strtolower($this->value);

        // Null Types
        if (in_array($lowercaseValue, ["~", "null"])) {
            return null;
        }

        // Evaluate Booleans?
        if (in_array($lowercaseValue, ["true", "false", "on", "off", "yes", "no"])) {
            if ($evaluateBooleans) {
                return in_array($lowercaseValue, ["true", "on", "yes"]) ? true : false;
            }
        }

        // Integers
        if (preg_match('/^\-?[0-9]+$/', $this->value)) {
            return intval($this->value);
        }

        // Floats
        if (preg_match('/^\-?[0-9]+\.[0-9]+$/', $this->value)) {
            return floatval($this->value);
        }

        // String
        // Is quoted string?
        if (in_array($this->value[0], ["'", '"'])) {
            if (substr($this->value, -1) !== $this->value[0]) {
                throw new LineException($this, 'Unmatched string start and end quote');
            }

            return substr($this->value, 1, -1);
        }

        return $this->value;
    }
}