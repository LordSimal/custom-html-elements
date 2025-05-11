<?php
declare(strict_types=1);

namespace LordSimal\CustomHtmlElements\Error;

use Exception;
use Throwable;

class RegexException extends Exception
{
    private string $source;

    private string $regex;

    /**
     * @param string $message
     * @param string $source
     * @param string $regex
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = '',
        string $source = '',
        string $regex = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $this->source = $source;
        $this->regex = $regex;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getRegex(): string
    {
        return $this->regex;
    }
}
