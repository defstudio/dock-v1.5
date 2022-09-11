<?php

namespace App\Exceptions;

use Exception;

class DockerServiceException extends Exception
{
    public static function serviceNotConfigured(string $service): self
    {
        return new self("Service $service must be configured in Service::configure() method");
    }

    public static function serviceNotFound(string $name): self
    {
        return new self("Service [$name] not found");
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

    public static function invalidPath(string $path): self
    {
        return new self("Path [$path] not found on host system");
    }

    public static function missingHost(): self
    {
        debug_print_backtrace();

        return new self('Missing HOST variable in .env file');
    }
}
