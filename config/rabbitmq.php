<?php

use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Connection Settings
    |--------------------------------------------------------------------------
    |
    | Declared here connection settings will be used by default for all
    | connections. Each connection can override them.
    |
    | @link <https://git.io/fjtP1> Context creation
    | @link <https://git.io/fjtN0> \Enqueue\AmqpTools\ConnectionConfig
    |
    */
    'connection_defaults' => [
        'host'               => 'localhost', // The host to connect
        'port'               => 5672,    // Port on the host
        'user'               => 'guest', // The user name to use
        'pass'               => 'guest', // Password
        'vhost'              => '/',   // The virtual host on the host
        'read_timeout'       => 3.0,   // Timeout in for income activity (seconds)
        'write_timeout'      => 3.0,   // Timeout in for outcome activity (seconds)
        'connection_timeout' => 3.0,   // Connection timeout (seconds)
        'heartbeat'          => 0,     // how often to send heartbeat. 0 means off
        'persisted'          => false, // Whether it use single persisted connection or open a new one for every context
        'lazy'               => true,  // The connection will be performed as later as possible
        'qos_global'         => false, // If "false" the QoS settings apply to the current channel only
        'qos_prefetch_size'  => 0,     // The server will send a message in advance if it is equal to or smaller ...
        'qos_prefetch_count' => 1,     // Specifies a prefetch window in terms of whole messages
        'ssl_on'             => false, // Should be true if you want to use secure connections
        'ssl_verify'         => true,  // Ssl client verifies that the server cert is for the server it is known as
        'ssl_cacert'         => '',    // Location of Certificate Authority file on local filesystem
        'ssl_cert'           => '',    // Path to local certificate file on filesystem
        'ssl_key'            => '',    // Path to local private key file on filesystem
        'ssl_passphrase'     => '',    // Passphrase with which your local_cert file was encoded
    ],

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connections
    |--------------------------------------------------------------------------
    |
    | Key is connection name, and value is its configuration.
    |
    */
    'connections' => [

        'rabbit-default' => [
            'host'  => env('RABBIT_HOST', 'rabbitmq'),
            'port'  => (int) env('RABBIT_PORT', 5672),
            'vhost' => env('RABBIT_VHOST', '/'),
            'user'  => env('RABBIT_LOGIN', 'guest'),
            'pass'  => env('RABBIT_PASSWORD', 'guest'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | Connection name, that will be used by default.
    |
    */
    'default_connection' => 'rabbit-default',

    /*
    |--------------------------------------------------------------------------
    | Queue Configurations
    |--------------------------------------------------------------------------
    |
    | Key is Queue ID, and vale is its configuration. 'name' is required.
    |
    | Flags: <https://www.rabbitmq.com/amqp-0-9-1-reference.html#class.queue>
    |
    */
    'queues' => [

        'some-queue-id' => [
            'name'         => 'queue-name',
            'flags'        => AmqpQueue::FLAG_DURABLE, // Durable queues remain active when a server restarts
            'arguments'    => [
                'x-message-ttl'  => 604800000, // 7 days (60×60×24×7×1000), @link <https://www.rabbitmq.com/ttl.html>
                'x-queue-mode'   => 'lazy', // @link <https://www.rabbitmq.com/lazy-queues.html>
                'x-max-priority' => 255, // @link <https://www.rabbitmq.com/priority.html>
            ],
            'consumer_tag' => null,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Configurations
    |--------------------------------------------------------------------------
    |
    | Key is Exchanges ID, and vale is its configuration. 'name' is required.
    |
    | Types:
    |
    |  > 'direct'  - Direct the message to the queue that is bound to it;
    |  > 'fanout'  - When a Fanout exchange receives a message, a copy of this
    |                message is sent to all queues bound to it;
    |  > 'topic'   - In the Topic it is possible to use patterns for routing
    |                keys;
    |  > 'headers' - Is similar to Topic, but instead of comparing Routing Key,
    |                it compares the attributes present in the message header
    |                with the attributes present in the arguments defined when
    |                we bind a queue in exchange.
    |
    | Flags: <https://www.rabbitmq.com/amqp-0-9-1-reference.html#class.exchange>
    |
    */
    'exchanges' => [

        'some-exchange-id' => [
            'name'      => 'exchange-name',
            'type'      => AmqpTopic::TYPE_DIRECT,
            'flags'     => AmqpTopic::FLAG_DURABLE, // Durable exchanges remain active when a server restarts
            'arguments' => [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Broker Setup Settings
    |--------------------------------------------------------------------------
    |
    | Command 'rabbit:setup' uses next configuration for creating queues and
    | exchanges on RabbitMQ server. Array keys is connection names, and
    | values - is array contains queue and exchange IDs witch should be created
    | using connection.
    |
    | Structure is:
    |
    | %connection_name_1% => [
    |   'queues'    => [ %queue_id_1%, %queue_id_2% ],
    |   'exchanges' => [ %exchange_id_1%, %exchange_id_2% ],
    | ]
    |
    */
    'setup' => [
        'rabbit-default' => [
            'queues'    => [
                'some-queue-id',
            ],
            'exchanges' => [
                'some-exchange-id',
            ],
        ],
    ],
];
