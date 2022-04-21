<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

namespace DBD\Common;

use Exception;
use Throwable;

class DBDException extends Exception
{
    /** @var array $arguments */
    protected $arguments;
    /** @var int $code */
    protected $code;
    /** @var string $file */
    protected $file = '';
    /** @var array $fullTrace */
    protected $fullTrace;
    /** @var int $line */
    protected $line;
    /** @var string $message */
    protected $message;
    /** @var string $query */
    protected $query;
    /** @var array $shortTrace */
    protected $shortTrace;
    /** @var array $trace */
    protected $trace;

    /**
     * DBDException constructor.
     *
     * @param string $message
     * @param string|null $query
     * @param null $arguments
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", string $query = null, $arguments = null, Throwable $previous = null)
    {
        parent::__construct($message, E_ERROR, $previous);

        $this->query = $query;
        $this->message = $message;
        $this->arguments = $arguments;

        $backTrace = parent::getTrace();
        $this->fullTrace = $backTrace;

        foreach ($backTrace as $trace) {
            if (isset($trace['file'])) {
                $pathInfo = pathinfo($trace['file']);
                if ($pathInfo['basename'] == "DBD.php") {
                    array_shift($backTrace);
                } else {
                    break;
                }
            }
        }
        $this->file = $backTrace[0]['file'];
        $this->line = $backTrace[0]['line'];

        $this->shortTrace = $backTrace;
    }

    /**
     * @return array|null
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    /**
     * @return array
     */
    public function getFullTrace(): array
    {
        return $this->fullTrace;
    }

    /**
     * @return string|null
     */
    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function getShortTrace(): array
    {
        return $this->shortTrace;
    }
}
