<?php


class HttpError extends Error
{
    private $note;

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->note = $message;
    }

    public function getNote()
    {
        return $this->note;
    }
}