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

use Comely\IO\Yaml\Exception\ParserException;
use Comely\IO\Yaml\Parser\Buffer;

/**
 * Class Parser
 * @package Comely\IO\Yaml
 */
class Parser
{
    /** @var string */
    private $yamlPath;
    /** @var string */
    private $baseDirectory;
    /** @var bool */
    private $evaluateBooleans;
    /** @var string */
    private $eolChar;

    /**
     * Parser constructor.
     * @param string $yamlFile absolute path to .yaml or .yml file
     */
    public function __construct(string $yamlFile)
    {
        // Set YAML file path and base directory
        $this->setYamlPath($yamlFile);

        // Default configuration
        $this->evaluateBooleans = false;
        $this->eolChar = PHP_EOL;
    }

    /**
     * Validates path, checks if starting Yaml file exists
     * @param string $yamlPath
     * @throws ParserException
     */
    private function setYamlPath(string $yamlPath): void
    {
        $chars = preg_quote(':._-\/\\\\', '#');
        $pattern = '#^[\w' . $chars . ']+\.(yaml|yml)$#';
        if (!preg_match($pattern, $yamlPath)) {
            throw new ParserException('Given path to YAML file is invalid');
        }

        // Get absolute path to file, resolve symbolic links (if any)
        $givenPath = $yamlPath;
        $yamlPath = realpath($yamlPath);
        if (!$yamlPath) {
            throw new ParserException(
                sprintf('YAML file "%s" not found in directory "%s"', basename($givenPath), dirname($givenPath))
            );
        }

        $this->yamlPath = $yamlPath;
        $this->baseDirectory = dirname($this->yamlPath);
    }

    /**
     * Evaluate string values to booleans?
     *
     * Will convert following values to bool(true) or bool(false)
     * "true","false","on","off","yes","no"
     *
     * @param bool $evaluate
     * @return Parser
     */
    public function evaluateBooleans(bool $evaluate = true): self
    {
        $this->evaluateBooleans = $evaluate;
        return $this;
    }

    /**
     * Set EOL character
     * @param string $eol
     * @return Parser
     */
    public function setEOL(string $eol = PHP_EOL): self
    {
        if (!in_array($eol, ["\n", "\r\n"])) {
            throw new ParserException('Invalid EOL character');
        }

        $this->eolChar = $eol;
        return $this;
    }

    /**
     * @throws ParserException
     */
    public function generate(): array
    {
        // Input YAML source
        $read = file_get_contents($this->yamlPath);
        if (!$read) {
            throw new ParserException(
                sprintf(
                    'Failed to read Yaml file "%s" in directory "%s',
                    basename($this->yamlPath),
                    dirname($this->yamlPath)
                )
            );
        }

        // Buffer lines and start processing
        $buffer = (new Buffer())->feed(explode($this->eolChar, $read));
        unset($read); // $read is now garbage

        $output = $this->parseBuffer($buffer); // return array

        return $output;
    }

    /**
     * @param Buffer $buffer
     * @return array|null|string
     * @throws ParserException
     */
    private function parseBuffer(Buffer $buffer)
    {
        $parsed = []; // Output array

        try {
            foreach ($buffer as $line) {
                // Sub-buffer Logic
                $subBuffer = $buffer->subBuffer(); // Sub-buffering?
                if ($subBuffer) {
                    if (!$line->key && !$line->value) {
                        $subBuffer->addToBuffer($line);
                        continue;
                    }

                    if ($line->indent > $subBuffer->indent()) {
                        $subBuffer->addToBuffer($line);
                        continue;
                    }

                    $parsed[$subBuffer->parent()] = $this->parseBuffer($subBuffer);
                    $buffer->clearSubBuffer();
                }

                // No key, no value
                if (!$line->key && !$line->value) {
                    continue; // Empty line
                }

                // Has key but no value
                if ($line->key && !$line->value) {
                    $buffer->createSubBuffer($line->indent, $line->num, $line->key);
                    continue;
                }

                // Has both key and value
                if ($line->key && $line->value) {
                    // Long string buffer
                    if (in_array($line->value, [">", "|"])) {
                        $buffer->createSubBuffer($line->indent, $line->num, $line->key, $line->value);
                        continue;
                    }

                    // Set key/value pair
                    $parsed[$line->key] = $this->getLineValue($line);
                    continue;
                }

                // Has value but no key
                if (!$line->key && $line->value) {
                    // Long strings buffer
                    if (in_array($buffer->type(), [">", "|"])) {
                        $parsed[] = $line->value;
                        continue;
                    }

                    // Sequences
                    if ($line->value[0] === "-") {
                        $line->value = trim(substr($line->value, 1));
                        $value = $this->getLineValue($line);
                        if ($buffer->parent() === "imports") {
                            if (!is_string($value)) {
                                throw new Buffer\LineException(
                                    $line,
                                    'Variable "imports" must be sequence of Yaml files'
                                );
                            }

                            try {
                                $parser = Yaml::Parse($this->baseDirectory . DIRECTORY_SEPARATOR . $value)
                                    ->setEOL($this->eolChar)
                                    ->evaluateBooleans($this->evaluateBooleans);
                                $value = $parser->generate(); // returns array
                            } catch (ParserException $e) {
                                throw new ParserException(
                                    sprintf(
                                        '%s imported in "%s" on line %d',
                                        $e->getMessage(),
                                        basename($this->yamlPath),
                                        $line->num
                                    )
                                );
                            }
                        }

                        $parsed[] = $value;
                        continue;
                    }
                }
            }
        } catch (Buffer\LineException $e) {
            throw new ParserException(
                sprintf('%s in file "%s" on line %d', $e->getMessage(), basename($this->yamlPath), $e->lineNum)
            );
        }

        // Check for any sub buffer at end of lines
        $subBuffer = $buffer->subBuffer();
        if ($subBuffer) {
            $parsed[$subBuffer->parent()] = $this->parseBuffer($subBuffer);
        }

        // Empty arrays will return null
        if (!count($parsed)) {
            $parsed = null;
        }

        // Long string buffers
        if (is_array($parsed) && in_array($buffer->type(), [">", "|"])) {
            $glue = $buffer->type() === ">" ? " " : $this->eolChar;
            $parsed = implode($glue, $parsed);
        }

        // Result cannot be empty if no-parent
        if (!$parsed && !$buffer->parent()) {
            throw new ParserException(
                sprintf('Corrupt YAML file format or line endings in "%s"', basename($this->yamlPath))
            );
        }

        // Merge imports
        $imports = $parsed["imports"] ?? null;
        if (is_array($imports)) {
            unset($parsed["imports"]);
            array_push($imports, $parsed);
            $parsed = call_user_func_array("array_replace_recursive", $imports);
        }

        return $parsed;
    }

    /**
     * @param Buffer\Line $line
     * @return bool|float|int|null|string
     * @throws Buffer\LineException
     */
    private function getLineValue(Buffer\Line $line)
    {
        $processedValue = $line->processValue($this->evaluateBooleans);
        return $processedValue;
    }
}