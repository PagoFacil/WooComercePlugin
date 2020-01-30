<?php


class PaymentError extends DomainError
{
    /**
     * PaymentError constructor.
     * @param string $message
     * @param string $note
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $note = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->setNote($note);
    }
}