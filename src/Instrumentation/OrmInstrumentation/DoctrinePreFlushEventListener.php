<?php
declare(strict_types=1);

namespace Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation;

use Zim\SymfonyTracingCoreBundle\RootContextProvider;
use Zim\SymfonyTracingCoreBundle\ScopedTracerInterface;

class DoctrinePreFlushEventListener
{
    public function __construct(
        private readonly ScopedTracerInterface $tracer,
        private readonly DoctrineSpanStack $spanStack,
        private readonly RootContextProvider $rootContextProvider,
    )
    {
    }

    public function preFlush(): void
    {
        if (false === $this->rootContextProvider->hasContext()) {
            return;
        }

        $span = $this->tracer->startSpan('Doctrine flush');
        $this->spanStack->pushSpan($span);
    }
}
