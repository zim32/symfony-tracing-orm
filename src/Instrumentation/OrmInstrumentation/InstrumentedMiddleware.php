<?php
declare(strict_types=1);

namespace Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Zim\SymfonyTracingCoreBundle\RootContextProvider;
use Zim\SymfonyTracingCoreBundle\ScopedTracerInterface;

class InstrumentedMiddleware implements Middleware
{
    public function __construct(
        private readonly ScopedTracerInterface $tracer,
        private readonly RootContextProvider $rootContextProvider,
        private readonly array $ignoredPatterns,
    )
    {
    }

    public function wrap(Driver $driver): Driver
    {
        return new InstrumentedDriver(
            $driver,
            $this->tracer,
            $this->rootContextProvider,
            $this->ignoredPatterns
        );
    }
}
