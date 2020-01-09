<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands;

use Illuminate\Contracts\Console\Kernel;
use AvtoDev\AmqpRabbitManager\Tests\AbstractTestCase;

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
