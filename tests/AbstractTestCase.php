<?php

namespace AvtoDev\AmqpRabbitManager\Tests;

use AvtoDev\AmqpRabbitManager\ServiceProvider;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class AbstractTestCase extends BaseTestCase
{
    use Traits\CreatesApplicationTrait;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->app->register(ServiceProvider::class);
    }
}
