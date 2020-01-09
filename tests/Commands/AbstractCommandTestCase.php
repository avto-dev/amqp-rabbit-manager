<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands;

use AvtoDev\AmqpRabbitManager\Tests\AbstractTestCase;
use Illuminate\Contracts\Console\Kernel;

abstract class AbstractCommandTestCase extends AbstractTestCase
{
    /**
     * Get console kernel container.
     *
     * @return Kernel
     */
    public function console(): Kernel
    {
        return $this->app->make(Kernel::class);
    }
}
