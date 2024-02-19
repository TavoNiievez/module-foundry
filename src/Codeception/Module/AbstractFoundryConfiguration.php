<?php
/**
 * @Author: jwamser
 * @CreateAt: 2/18/24
 * Project: module-foundry
 * File Name: AbstractFoundryModule.php
 */

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
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\DatabaseResetter;
use Zenstruck\Foundry\Test\TestState;

class AbstractFoundryConfiguration extends Module implements DependsOnModule, RequiresPackage
{
    protected array $config = [
        'cleanup' => false,
        'factories' => null
    ];

    protected string $dependencyMessage = <<<EOF
        ORM module (like Doctrine2) or Framework module with ActiveRecord support is required:
        --
        modules:
            enabled:
                - Foundry:
                    depends: Doctrine2
        --
    EOF;

    public Factory $foundry;

    /**
     * ORM module on which we depend on.
     */
    public ORM|Module $ormModule;

    public function _afterSuite(): void
    {
        if ($this->getCleanupConfig() === false) {
            return;
        }

        $this->debugSection('Foundry', 'Resetting database schema.');
        DatabaseResetter::resetSchema($this->getSymfonyKernel());
    }

    public function _beforeSuite($settings = []): void
    {
        $this->debugSection('Foundry', 'Booting foundry.');
        /** @var ContainerInterface $container */
        $container = $this->getSymfonyContainer();
        TestState::bootFromContainer($container);
    }

    public function _depends(): array
    {
        return [
            'Codeception\Lib\Interfaces\ORM' => $this->dependencyMessage,
        ];
    }

    public function _inject(ORM $orm): void
    {
        $this->ormModule = $orm;
    }

    public function _requires(): array
    {
        return [
            'Zenstruck\Foundry\Factory' => '"zenstruck/foundry": "^1.36"',
        ];
    }

    protected function getCleanupConfig(): bool
    {
        return $this->config['cleanup'] && $this->ormModule->_getConfig('cleanup');
    }

    /**
     * @param Proxy[] $proxies
     * @return object[]
     */
    protected function getEntitiesByProxies(array $proxies): array
    {
        $entities = [];
        foreach ($proxies as $proxy) {
            $entities[] = $proxy->object();
        }
        return $entities;
    }

    protected function getFactoryClassByEntityClass(string $entity): ?string
    {
        foreach ($this->config['factories'] as $factory) {
            try {
                $modelFactory = new ReflectionClass($factory);
                $getClassMethod = $modelFactory->getMethod('getClass');
                $entityName = $getClassMethod->invoke(null);
                if ($entity === $entityName) {
                    return $factory;
                }
            } catch (ReflectionException $e) {
                $this->fail($e->getMessage());
            }
        }

        return null;
    }

    public function onReconfigure($settings = []): void
    {
        if ($this->getCleanupConfig()) {
            DatabaseResetter::resetSchema($this->getSymfonyKernel());
        }
        $this->_beforeSuite($settings);
    }

    protected function getModuleSymfony(): ?Symfony
    {
        try {
            /** @var Symfony $symfonyModule */
            $symfonyModule =  $this->getModule('Symfony');
            return $symfonyModule;
        } catch (Exception $exception) {
            return null;
        }
    }

    protected function getSymfonyContainer(): SymfonyContainerInterface
    {
        return $this->getModuleSymfony()->_getContainer();
    }

    protected function getSymfonyKernel(): KernelInterface
    {
        return $this->getModuleSymfony()->kernel;
    }
}
