<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Exceptions;

use Throwable;
use RuntimeException;

class RabbitMqException extends RuntimeException
{
    /**
     * @param string         $connection_name
     * @param int            $code
     * @param Throwable|null $prev
     *
     * @return self
     */
    public static function connectionNotExists(string $connection_name, int $code = 0, ?Throwable $prev = null): self
    {
        return new static("RabbitMQ connection [{$connection_name}] does not exists", $code, $prev);
    }

    /**
     * @param string|null    $msg
     * @param int            $code
     * @param Throwable|null $prev
     *
     * @return self
     */
    public static function defaultConnectionNotSet(?string $msg = null, int $code = 0, ?Throwable $prev = null): self
    {
        return new static($msg ?? 'Default connection does not set', $code, $prev);
    }

    /**
     * @param string         $queie_id
     * @param int            $code
     * @param Throwable|null $prev
     *
     * @return self
     */
    public static function queueIdNotSet(string $queie_id, int $code = 0, ?Throwable $prev = null): self
    {
        return new static("Queue ID for [{$queie_id}] does not set", $code, $prev);
    }

    /**
     * @param string         $queue_name
     * @param int            $code
     * @param Throwable|null $prev
     *
     * @return self
     */
    public static function queueNotExists(string $queue_name, int $code = 0, ?Throwable $prev = null): self
    {
        return new static("RabbitMQ queue [{$queue_name}] does not exists", $code, $prev);
    }
}
