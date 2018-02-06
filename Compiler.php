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

use Comely\IO\Yaml\Exception\CompilerException;
use Comely\Kernel\Toolkit\Number;

/**
 * Class Compiler
 * @package Comely\IO\Yaml
 */
class Compiler
{
    /** @var array */
    private $target;
    /** @var int */
    private $indent;
    /** @var string */
    private $eolChar;

    /**
     * Compiler constructor.
     * @param array $target
     */
    public function __construct(array $target)
    {
        $this->indent = 2;
        $this->eolChar = PHP_EOL;
        $this->target = $target;
    }

    /**
     * Set EOL character
     * @param string $eol
     * @return Compiler
     * @throws CompilerException
     */
    public function setEOL(string $eol = PHP_EOL): self
    {
        if (!in_array($eol, ["\n", "\r\n"])) {
            throw new CompilerException('Invalid EOL character');
        }

        $this->eolChar = $eol;
        return $this;
    }

    /**
     * Set indent spacer
     * @param int $indent
     * @return Compiler
     * @throws CompilerException
     */
    public function setIndent(int $indent = 2): self
    {
        if (!Number::Range($indent, 2, 8)) {
            throw new CompilerException(sprintf('"%d" is an invalid indent value', $indent));
        }

        return $this;
    }

    /**
     * @return string
     * @throws CompilerException
     */
    public function generate(): string
    {
        $headers[] = "# This YAML source has been compiled using Comely YAML component";
        $headers[] = "# https://github.com/comelyio/yaml";

        $compiled = $this->compileYaml($this->target);
        $compiled = implode($this->eolChar, $headers) . str_repeat($this->eolChar, 2) . $compiled;

        return $compiled;
    }

    /**
     * @param array $input
     * @param string|null $parent
     * @param int $tier
     * @return string
     * @throws CompilerException
     */
    private function compileYaml(array $input, string $parent = null, int $tier = 0): string
    {
        $compiled = "";
        $indent = $this->indent * $tier;

        // Last value type
        // 1: Scalar, 0: Non-scalar
        $lastValueType = 1;

        // Iterate input
        foreach ($input as $key => $value) {
            // All tier-1 keys have to be string
            if ($tier === 1 && !is_string($key)) {
                throw new CompilerException('All tier 1 keys must be string');
            }

            if (is_scalar($value) || is_null($value)) {
                // Value is scalar or NULL
                if ($lastValueType !== 1) {
                    // A blank line is last value type was not scalar
                    $compiled .= $this->eolChar;
                }

                // Current value type
                $lastValueType = 1; // This value is scalar or null

                // Necessary indents
                $compiled .= $this->indent($indent);

                // Set mapping key or sequence
                if (is_string($key)) {
                    $compiled .= sprintf('%s: ', $key);
                } else {
                    $compiled .= "- ";
                }

                // Value
                switch (gettype($value)) {
                    case "boolean":
                        $compiled .= $value === true ? "true" : "false";
                        break;
                    case "NULL":
                        $compiled .= "~";
                        break;
                    case "integer":
                    case "double":
                        $compiled .= $value;
                        break;
                    default:
                        // Definitely a string
                        if (strpos($value, $this->eolChar)) {
                            // String has line-breaks
                            $compiled .= "|" . $this->eolChar;
                            $lines = explode($this->eolChar, $value);
                            $subIndent = $this->indent(($indent + $this->indent));

                            foreach ($lines as $line) {
                                $compiled .= $subIndent;
                                $compiled .= $line . $this->eolChar;
                            }
                        } elseif (strlen($value) > 75) {
                            // Long string
                            $compiled .= ">" . $this->eolChar;
                            $lines = explode($this->eolChar, wordwrap($value, 75, $this->eolChar));
                            $subIndent = $this->indent(($indent + $this->indent));

                            foreach ($lines as $line) {
                                $compiled .= $subIndent;
                                $compiled .= $line . $this->eolChar;
                            }
                        } else {
                            // Simple string
                            $compiled .= $value;
                        }
                }

                $compiled .= $this->eolChar;
            } else {
                // Non-scalars
                if ($lastValueType === 1) {
                    // A blank link if last value type is scalar
                    $compiled .= $this->eolChar;
                }

                // Current value type
                $lastValueType = 0; // This value is Non-scalar

                if (is_object($value)) {
                    // Directly convert to an Array, JSON is cleanest possible way
                    $value = json_decode(json_encode($value), true);
                }

                // Whether value was Array, or is now Array after conversion from object
                if (is_array($value)) {
                    $compiled .= $this->indent($indent);
                    $compiled .= sprintf('%s:%s', $key, $this->eolChar);
                    $compiled .= $this->compileYaml($value, strval($key), $tier + 1);
                }
            }
        }

        if (!$compiled || ctype_space($compiled)) {
            throw new CompilerException(sprintf('Failed to compile YAML for key "%s"', $parent));
        }

        $compiled .= $this->eolChar;

        return $compiled;
    }

    /**
     * @param int $count
     * @return string
     */
    private function indent(int $count): string
    {
        return str_repeat(" ", $count);
    }
}