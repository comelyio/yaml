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

use Comely\IO\Yaml\Exception\ParserException;
use Comely\IO\Yaml\Parser\Output\ParsedYamlObject;

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
    /** @var null|object */
    private $outputObject;
    /** @var int */
    private $outputInaccessibleKeys;
    /** @var int */
    private $convertKeys;
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
        $this->convertKeys = Yaml::KEYS_DEFAULT;
        $this->evaluateBooleans = false;
        $this->eolChar = PHP_EOL;
        $this->outputInaccessibleKeys = Yaml::OUTPUT_SET_INACCESSIBLE;
    }

    /**
     * Validates path, checks if starting Yaml file exists
     * @param string $yamlPath
     * @throws ParserException
     */
    private function setYamlPath(string $yamlPath): void
    {
        $chars = preg_quote('._-' . DIRECTORY_SEPARATOR, '#');
        $pattern = '#^[\w' . $chars . ']+\.(yaml|yml)$#';
        if (!preg_match($pattern, $yamlPath)) {
            throw new ParserException('Given path to YAML file is invalid');
        }

        if (!file_exists($yamlPath)) {
            throw new ParserException(
                sprintf('YAML file "%s" not found in directory "%s"', basename($yamlPath), dirname($yamlPath))
            );
        }

        $this->yamlPath = $yamlPath;
        $this->baseDirectory = dirname($this->yamlPath);
    }

    /**
     * Evaluate string values to booleans?
     *
     * Will convert following values to bool(true) or bool(false)
     * "true","false","1","0","on","off","yes","no","y","n"
     *
     * @return Parser
     */
    public function evaluateBooleans(): self
    {
        $this->evaluateBooleans = true;
        return $this;
    }

    /**
     * Convert keys
     * @param int $flag
     * @return Parser
     * @throws ParserException
     */
    public function convertKeys(int $flag): self
    {
        $allowed = [
            Yaml::KEYS_DEFAULT,
            Yaml::KEYS_SNAKE_CASE,
            Yaml::KEYS_CAMEL_CASE
        ];

        if (!in_array($flag, $allowed)) {
            throw new ParserException('Flag passed to "convertKeys" method must be a valid Yaml::KEYS_* flag');
        }

        $this->convertKeys = $flag;
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
     * Map values to a custom object that implements ParsedYamlObject interface
     * @param $object
     * @param int|null $flag
     * @return Parser
     */
    public function outputObject($object, int $flag = Yaml::OUTPUT_SET_INACCESSIBLE): self
    {
        if (!is_object($object)) {
            throw new ParserException('Method "outputObject" only accepts instantiated objects');
        }

        if (!$object instanceof ParsedYamlObject) {
            throw new ParserException(
                sprintf('Object "%s" must implement "ParsedYamlObject" interface', get_class($object))
            );
        }

        // Set or ignore mapping values to inaccessible properties?
        // (i.e. props. that don't exist)
        $allowed = [
            Yaml::OUTPUT_SET_INACCESSIBLE,
            Yaml::OUTPUT_IGNORE_INACCESSIBLE
        ];

        if (!in_array($flag, $allowed)) {
            throw new ParserException('Option flag to "outputObject" must be a valid Yaml::OUTPUT_* flag');
        }

        $this->outputObject = $object;
        $this->outputInaccessibleKeys = $flag;
        return $this;
    }
}