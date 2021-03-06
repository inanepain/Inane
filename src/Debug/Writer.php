<?php
/**
 * This file is part of the InaneTools package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Inane\Debug
 *
 * @license MIT
 * @license http://inane.co.za/license/MIT
 *
 * @copyright 2015-2019 Philip Michael Raab <philip@inane.co.za>
 */
namespace Inane\Debug;

use function var_dump;
use function file_put_contents;
use function print_r;
use function ob_start;
use function ob_get_clean;
use function is_array;
use function json_encode;

use const PHP_EOL;

/**
 * Log to html with pre & code tags
 *
 * @package Inane\Debug
 * @version 1.0.2
 */
class Writer {
    /**
     * Single instance of writer 
     * 
     * @var Writer
     */
    private static $logger = null;

    /**
     * Writer Method: (echo, file, buffer)
     *
     * @var string
     */
    protected $method = '';

    /**
     * Die's default
     * 
     * @var bool
     */
    protected $die = true;

    /**
     * Buffer for write
     * 
     * @var string
     */
    protected $message = '';

    // Settings
    /**
     * File: path to file for write
     *
     * @var string
    */
    protected $optionFile = '';
    /**
     * Timestamp: timestamp added to entries when writting to file
     *
     * @var bool
    */
    protected bool $optionTimestamp = true;

    /**
     * Protected Contructor
     * @return void
     */
    protected function __construct() {   
    }

    /**
     * Factory: Writer::echo
     * 
     * Creates a writer in echo mode
     * 
     * Sends text to default output
     * 
     * @return Writer 
     */
    public static function echo(): Writer {
        if (!static::$logger) static::$logger = new static();
        static::$logger->method = 'ECHO';
        if (static::$logger->message != '') static::$logger->message = "<pre>".static::$logger->message."</pre>";
        return static::$logger;
    }

    /**
     * Factory: Writer::buffer
     * 
     * Creates a writer in buffer mode
     * In this mode text builds in buffer untill explicitly flushed
     * 
     * @TODO: Improve buffering: store in array possible that on final out format...
     * 
     * @return Writer 
     */
    public static function buffer(): Writer {
        if (!static::$logger) static::$logger = new static();
        static::$logger->method = 'BUFFER';
        return static::$logger;
    }

    /**
     * Factory: Writer::file
     * 
     * Creates a writer in file mode
     * This mode writes to a file and not to stream
     * 
     * @param null|string $file log file, path saved for durration of session
     * @return Writer 
     */
    public static function file(?string $file = null): self {
        if (!static::$logger) static::$logger = new static();
        static::$logger->method = 'FILE';

        if ($file === null && !defined('CONFIG_LOGGER_DEFAULT')) $file = 'log/debug.log';
        else if ($file === null && defined('CONFIG_LOGGER_DEFAULT')) $file = get_defined_constants(true)['user']['CONFIG_LOGGER_DEFAULT'];

        static::$logger->optionFile = $file;
        return static::$logger;
    }

    /**
     * Show/Hide Time in log files
     *
     * @param bool $show
     * @return Writer
     */
    public function setTimestamp(bool $show): self {
        $this->optionTimestamp = $show;
        return $this;
    }
    
    /**
     * Get text in message buffer
     * 
     * @param bool $clear optionally clear the buffer after message retrieved
     * @return string the buffered text
     */
    protected function getMessage(bool $clear = true): string {
        $message = $this->message;
        if ($clear) $this->message = '';
        return $message . PHP_EOL;
    }

    /**
     * Formate Data
     * Used to massage to data to better display in selected output
     * 
     * @param mixed $mixed 
     * @return mixed 
     */
    protected function formateData($mixed) {
        if (is_array($mixed)) $mixed = json_encode($mixed, JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_SLASHES);
        return $mixed;
    }

    /**
     * Label
     * Adds lable to output
     * 
     * @param string $label 
     * @return void 
     */
    protected function label(string $label): void {
        if ($this->method == 'ECHO') $this->message .= "<h3>${label}</h3>" . PHP_EOL;
        else if ($this->method == 'FILE' && $this->optionTimestamp) $this->message .= date('Y-m-d H:i:s') . ": ${label}: ";
        else $this->message .= "${label}: ";
    }

    /**
     * Formater: core var_dump
     * 
     * @param mixed $mixed 
     * @param null|string $label 
     * @param null|bool $die 
     * @return Writer
     */
    public function dump($mixed, ?string $label = null, ?bool $die = null): self {
        static $_die = false;
        if ($die !== null) $_die = $die;

        // if ($this->method == 'FILE' && is_array($mixed)) $mixed = $this->formateData($mixed);

        if ($label) $this->label($label);
        if ($this->method == 'ECHO') $this->message .= '<pre>';
        ob_start();
        var_dump($mixed);
        $this->message .= ob_get_clean();
        if ($this->method == 'ECHO') $this->message .= '</pre>';

        if ($this->method != 'BUFFER') $this->out();
        if ($_die === true) die();
        return $this;
    }

    /**
     * Formater: core print_r
     * 
     * @param mixed $mixed 
     * @param null|string $label 
     * @param null|bool $die 
     * @return Writer 
     */
    public function print($mixed, ?string $label = null, ?bool $die = null): self {
        static $_die = false;
        if ($die !== null) $_die = $die;

        if ($this->method == 'FILE' && is_array($mixed)) $mixed = $this->formateData($mixed);
    
        if ($label) $this->label($label);
        if ($this->method == 'ECHO') $this->message .=  '<pre>';
        $this->message .= print_r($mixed, true);
        if ($this->method == 'ECHO') $this->message .=  '</pre>';

        if ($this->method != 'BUFFER') $this->out();
        if ($_die === true) die();
        return $this;
    }

    /**
     * Send data to relavent writer
     *
     * @return void
     */
    protected function out(): void {
        if ($this->method == 'ECHO') {
            echo $this->getMessage();
        } else if ($this->method == 'FILE') {
            file_put_contents($this->optionFile, $this->getMessage(), FILE_APPEND);
        }
    }

    /**
     * Die
     *  indipendant of main functions die param
     * 
     * @param null|bool $die 
     * @return $this Writer
     */
    public function die(?bool $die = null): Writer {
        if ($die !== null) $this->die = $die;
        if ($this->die === true) die();
        return $this;
    }
}