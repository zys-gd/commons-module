<?php

namespace ExtrasBundle\Cache\Redis;

use ExtrasBundle\Cache\ICacheService;
use ExtrasBundle\Cache\ICacheServiceFactory;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

class RedisCacheServiceFactory implements ICacheServiceFactory
{
    const PLACEHOLDER_DATABASE       = 1;
    const USER_SUBSCRIPTION_DATABASE = 1;
    const COUNTERS_DATABASE          = 2;

    /**
     * @var RedisConnectionProvider
     */
    private $connectionProvider;
    /**
     * @var string
     */
    private $namespace;

    /**
     * RedisCacheServiceFactory constructor.
     *
     * @param string $host
     * @param string $port
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(RedisConnectionProvider $connectionProvider, string $namespace)
    {
        $this->connectionProvider = $connectionProvider;
        $this->namespace = $namespace;
    }

    /**
     * @param array $options
     *
     * @return ICacheService
     * @throws InvalidArgumentException
     */
    public function createTranslationCacheService(array $options = []): ICacheService
    {
        return $this->createService(self::PLACEHOLDER_DATABASE, $options, sprintf('%s_%s_', $this->namespace, 'placheolders'));
    }

    /**
     * @param array $options
     *
     * @return ICacheService
     * @throws InvalidArgumentException
     */
    public function createUserSubscriptionCacheService(array $options = []): ICacheService
    {
        return $this->createService(self::USER_SUBSCRIPTION_DATABASE, $options, sprintf('%s_%s_', $this->namespace, 'subscriptions'));
    }

    /**
     * @return \Predis\Client|\Redis|\RedisCluster
     */
    public function createCounterConnection()
    {
        $connection = $this->connectionProvider->create(self::COUNTERS_DATABASE);
        return $connection;
    }

    /**
     * @param string $database
     * @param array  $options
     * @param string $namespace
     *
     * @return ICacheService
     */
    private function createService(string $database, array $options = [], $namespace = 'default'): ICacheService
    {
        $connection = $this->connectionProvider->create($database, $options);

        return new RedisCacheService(new RedisAdapter($connection, $namespace));
    }

    /**
     * @param int    $database
     * @param string $namespace
     * @param array  $options
     * @return ICacheService
     */
    public function createCacheService(int $database, string $namespace, array $options = []): ICacheService
    {
        $connection = $this->connectionProvider->create($database, $options);

        return new RedisCacheService(new RedisAdapter($connection, $namespace));

    }
}