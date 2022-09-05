<?php

namespace App\Exceptions;

use Exception;

class DockerServiceException extends Exception
{

    public static function serviceNotDefined(string $service): self
    {
        return new self("Service $service must be defined in Service::configure() method");
    }

    public static function generic(string $string): self
    {
        return new self($string);
    }
}
