<?php
declare(strict_types=1);

namespace Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation;

use Zim\SymfonyTracingCoreBundle\RootContextProvider;

class DoctrinePostFlushEventListener
{
    public function __construct(
        private readonly DoctrineSpanStack $spanStack,
        private readonly RootContextProvider $rootContextProvider,
    )
    {
    }

    public function postFlush(): void
    {
        if (false === $this->rootContextProvider->hasContext()) {
            return;
        }

        $span = $this->spanStack->popSpan();

        if ($span === null) {
            return;
        }

        $span->end();
    }
}
