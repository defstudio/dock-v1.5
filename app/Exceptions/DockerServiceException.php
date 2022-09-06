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

    public static function unableToDetectCurrentUserId(): self
    {
        return new self('Unable to detect current User ID (uid). Please add an USER_ID entry to your .env file with the user ID to use in containers');
    }

    public static function unableToDetectCurrentGroupId(): self
    {
        return new self('Unable to detect current Group ID (gid). Please add an USER_ID entry to your .env file with the user ID to use in containers');
    }
}
