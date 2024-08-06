<?php
declare(strict_types=1);

namespace Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation;

use Symfony\Contracts\Service\ResetInterface;
use Zim\SymfonyTracingCoreBundle\ScopedSpan;

class DoctrineSpanStack implements ResetInterface
{
    private array $stack = [];

    public function pushSpan(ScopedSpan $span): void
    {
        $this->stack[] = $span;
    }

    public function popSpan(): ?ScopedSpan
    {
        if (count($this->stack) === 0) {
            return null;
        }

        return array_pop($this->stack);
    }

    public function reset()
    {
        $this->stack = [];
    }
}
