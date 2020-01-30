<?php

class DomainError extends DomainException
{
    /** @var string $note */
    private $note;

    protected function setNote($note)
    {
        $this->note = $note;
    }

    public function getNote()
    {
        return $this->note;
    }
}