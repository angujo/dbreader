<?php


namespace Angujo\DBReader\Models;


class DBColumn extends PropertyReader
{
    public function __construct(array $details)
    {
        parent::__construct($details);
    }
}