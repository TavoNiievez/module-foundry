<?php

declare(strict_types=1);

namespace Codeception\Module;

use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Lib\Interfaces\ORM;
use Codeception\Lib\Interfaces\RequiresPackage;
use Codeception\Module;
use Exception;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\DatabaseResetter;
use Zenstruck\Foundry\Test\TestState;

/**
 * Foundry Module allows you to easily generate and create test data using [**Foundry**](https://github.com/zenstruck/foundry).
 * Foundry uses Doctrine ORM to define, save and cleanup data. Thus, should be used with Doctrine Module or Framework modules.
 *
 * This module requires packages installed:
 *
 * ```json
 * {
 *  "zenstruck/foundry": "^1.36",
 * }
 * ```
 *
 * Generation rules can be defined in a factories file.
 * Follow [Foundry documentation](https://github.com/zenstruck/foundry) to set valid rules.
 * Random data provided by [Faker](https://github.com/FakerPHP/Faker) library.
 *
 * Configure this module to load factory definitions from a directory.
 * You should also specify Doctrine ORM as a dependency.
 *
 * ```yaml
 * modules:
 *     enabled:
 *         - Foundry:
 *             depends: Doctrine2
 *             factories:
 *                 - \App\Factory\UserFactory
 *             cleanup: true
 *         - Symfony:
 *             app_path: 'src'
 *             environment: 'test'
 *         - Doctrine2:
 *             depends: Symfony
 *             cleanup: true
 * ```
 */
class Foundry extends AbstractFoundryConfiguration
{
    /**
     * Generates and saves a record.
     *
     * ```php
     * $I->have(User::class); // creates user
     * $I->have(User::class, ['is_active' => true]); // creates active user
     * ```
     *
     * Returns an instance of created user.
     *
     * @param string $entity
     * @param array $attributes
     * @return object
     */
    public function have(string $entity, array $attributes = []): object
    {
        $factoryClass = $this->getFactoryClassByEntityClass($entity);
        /** @var ModelFactory $factory */
        $factory = $factoryClass::new($attributes);
        return $factory->create()->object();
    }

    /**
     * Generates and saves a record multiple times.
     *
     * ```php
     * $I->haveMultiple(User::class, 10); // create 10 users
     * $I->haveMultiple(User::class, 10, ['is_active' => true]); // create 10 active users
     * ```
     *
     * @param string $entity
     * @param int $times
     * @param array $attributes
     * @return object[]
     */
    public function haveMultiple(string $entity, int $times, array $attributes = []): array
    {
        $factoryClass = $this->getFactoryClassByEntityClass($entity);
        /** @var ModelFactory $factory */
        $factory = new $factoryClass();
        $proxies = $factory->createMany($times, $attributes);
        return $this->getEntitiesByProxies($proxies);
    }

    /**
     * Generates a record instance.
     *
     * This does not save it in the database. Use `have` for that.
     *
     * ```php
     * $user = $I->make(User:class); // return User instance
     * $activeUser = $I->make(User:class, ['is_active' => true]); // return active user instance
     * ```
     *
     * Returns an instance of created user without creating a record in database.
     *
     * @param string $entity
     * @param array $attributes
     * @return object
     */
    public function make(string $entity, array $attributes = []): object
    {
        $factoryClass = $this->getFactoryClassByEntityClass($entity);
        /** @var ModelFactory $factory */
        $factory = new $factoryClass();
        $memoryFactory = $factory->withoutPersisting();
        return $memoryFactory->create($attributes)->object();
    }

    /**
     * @param string $entity
     * @param int $times
     * @param array $attributes
     * @return object[]
     */
    public function makeMultiple(string $entity, int $times, array $attributes = []): array
    {
        $factoryClass = $this->getFactoryClassByEntityClass($entity);
        /** @var ModelFactory $factory */
        $factory = new $factoryClass();
        $memoryFactory = $factory->withoutPersisting();
        $proxies = $memoryFactory->createMany($times, $attributes);
        return $this->getEntitiesByProxies($proxies);
    }
}
