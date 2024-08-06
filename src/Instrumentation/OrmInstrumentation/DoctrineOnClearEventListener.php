<?php
declare(strict_types=1);

namespace Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation;

class DoctrineOnClearEventListener
{
    public function __construct(
        private readonly DoctrineSpanStack $spanStack,
    )
    {
    }

    public function onClear(): void
    {
        while ($span = $this->spanStack->popSpan()) {
            $span->end();
        }
    }
}
