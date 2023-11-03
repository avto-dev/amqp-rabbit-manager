<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests;

use Illuminate\Support\Str;
use Interop\Amqp\AmqpQueue;
use AvtoDev\AmqpRabbitManager\QueuesFactory;
use AvtoDev\AmqpRabbitManager\QueuesFactoryInterface;
use AvtoDev\AmqpRabbitManager\Exceptions\FactoryException;

/**
 * @covers \AvtoDev\AmqpRabbitManager\QueuesFactory
 */
class QueuesFactoryTest extends AbstractTestCase
{
    /**
     * @var QueuesFactory
     */
    protected $factory;

    /**
     * @var array<string, array<string, mixed>>
     */
    protected $queues_declaration = [
        'queue-1' => [
            'name'         => 'queue-1-name',
            'flags'        => AmqpQueue::FLAG_NOPARAM,
            'arguments'    => [],
            'consumer_tag' => null,
        ],
        'queue-2' => [
            'name'         => 'queue-2-name',
            'flags'        => AmqpQueue::FLAG_AUTODELETE,
            'arguments'    => ['foo'],
            'consumer_tag' => 'blah blah tag',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new QueuesFactory($this->queues_declaration);
    }

    /**
     * @return void
     */
    public function testInstanceOf(): void
    {
        $this->assertInstanceOf(QueuesFactoryInterface::class, $this->factory);
    }

    /**
     * @return void
     */
    public function testMakeWithValidValues(): void
    {
        $queue_1 = $this->factory->make($queue_1_name = 'queue-1');
        $queue_2 = $this->factory->make($queue_2_name = 'queue-2');

        foreach ([$queue_1_name => $queue_1, $queue_2_name => $queue_2] as $name => $queue) {
            $this->assertSame($this->queues_declaration[$name]['name'], $queue->getQueueName());
            $this->assertSame($this->queues_declaration[$name]['flags'], $queue->getFlags());
            $this->assertSame($this->queues_declaration[$name]['arguments'], $queue->getArguments());
            $this->assertSame($this->queues_declaration[$name]['consumer_tag'], $queue->getConsumerTag());
        }
    }

    /**
     * @return void
     */
    public function testNamesGetter(): void
    {
        $this->assertSame(\array_keys($this->queues_declaration), $this->factory->ids());
    }

    /**
     * @return void
     */
    public function testExceptionThrownWhenPassedWrongQueueName(): void
    {
        $this->expectException(FactoryException::class);
        $this->expectExceptionMessageMatches('~queue.*not.?exists~i');

        $this->factory->make(Str::random());
    }

    /**
     * @return void
     */
    public function testExceptionThrownWhenQueueDeclaredWithoutName(): void
    {
        $this->expectException(FactoryException::class);
        $this->expectExceptionMessageMatches('~queue.*not.?set~i');

        $this->factory = new QueuesFactory([
            'foo' => [
                //'name'       => 'queue-2-name',
                'flags'        => \Interop\Amqp\AmqpQueue::FLAG_AUTODELETE,
                'arguments'    => ['foo'],
                'consumer_tag' => null,
            ],
        ]);

        $this->factory->make('foo');
    }

    /**
     * @return void
     */
    public function testExceptionThrownWhenQueueDeclaredWithEmptyName(): void
    {
        $this->expectException(FactoryException::class);
        $this->expectExceptionMessageMatches('~queue.*not.?set~i');

        $this->factory = new QueuesFactory([
            'foo' => [
                'name'         => '',
                'flags'        => \Interop\Amqp\AmqpQueue::FLAG_AUTODELETE,
                'arguments'    => ['foo'],
                'consumer_tag' => null,
            ],
        ]);

        $this->factory->make('foo');
    }

    /**
     * @small
     *
     * @return void
     */
    public function testAddAndRemoveFactory(): void
    {
        $this->factory->addFactory($queue_id = 'queue-id', ['name' => 'some-queue']);
        $this->assertContains($queue_id, $this->factory->ids());

        $this->assertInstanceOf(AmqpQueue::class, $this->factory->make($queue_id));

        $this->factory->removeFactory($queue_id);
        $this->assertNotContains($queue_id, $this->factory->ids());
    }
}
