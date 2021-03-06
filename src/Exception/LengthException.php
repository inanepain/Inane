<?php
/**
 * This file is part of the InaneTools package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <philip@inane.co.za>
 * @package Inane\Exception
 *
 * @license MIT
 * @license http://inane.co.za/license/MIT
 *
 * @copyright 2015-2019 Philip Michael Raab <philip@inane.co.za>
 */

namespace Inane\Exception;

/**
 * Exception thrown if a length is invalid.
 *
 * @package Inane\Exception
 * @implements \Inane\Exception\ExceptionInterface
 * @version 0.2.0
 */
class LengthException extends \LengthException implements ExceptionInterface {}
