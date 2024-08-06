<?php
declare(strict_types=1);

namespace Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use SensitiveParameter;
use Zim\SymfonyTracingCoreBundle\RootContextProvider;
use Zim\SymfonyTracingCoreBundle\ScopedTracerInterface;

class InstrumentedDriver extends AbstractDriverMiddleware
{
    public function __construct(
        Driver $wrappedDriver,
        private readonly ScopedTracerInterface $tracer,
        private readonly RootContextProvider $rootContextProvider,
        private readonly array $ignoredPatterns,
    )
    {
        parent::__construct($wrappedDriver);
    }

    public function connect(#[SensitiveParameter] array $params)
    {
        $connection = parent::connect($params);
        return new InstrumentedConnection(
            $connection,
            $this->tracer,
            $this->rootContextProvider,
            $this->ignoredPatterns
        );
    }
}
