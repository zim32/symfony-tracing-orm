<?php
declare(strict_types=1);

namespace Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use OpenTelemetry\API\Trace\StatusCode;
use Zim\SymfonyTracingCoreBundle\RootContextProvider;
use Zim\SymfonyTracingCoreBundle\ScopedTracerInterface;

class InstrumentedConnection extends AbstractConnectionMiddleware
{
    public function __construct(
        Connection $wrappedConnection,
        private readonly ScopedTracerInterface $tracer,
        private readonly RootContextProvider $rootContextProvider,
        private readonly array $ignoredPatterns
    )
    {
        parent::__construct($wrappedConnection);
    }

    public function query(string $sql): Result
    {
        if (false === $this->shouldTraceQuery($sql)) {
            return parent::query($sql);
        }

        $span = $this->tracer->startSpan('SQL query');
        $span->getSpan()->setAttribute('query', $sql);

        try {
            return parent::query($sql);
        } catch (\Throwable $exception) {
            $realSpan = $span->getSpan();
            $realSpan->setStatus(StatusCode::STATUS_ERROR);
            $realSpan->recordException($exception);
            throw $exception;
        } finally {
            $span->end();
        }
    }

    public function exec(string $sql): int
    {
        if (false === $this->shouldTraceQuery($sql)) {
            return parent::exec($sql);
        }

        $span = $this->tracer->startSpan('SQL query');
        $span->getSpan()->setAttribute('query', $sql);

        try {
            return parent::exec($sql);
        } catch (\Throwable $exception) {
            $realSpan = $span->getSpan();
            $realSpan->setStatus(StatusCode::STATUS_ERROR);
            $realSpan->recordException($exception);
            throw $exception;
        } finally {
            $span->end();
        }
    }

    public function prepare(string $sql): Statement
    {
        if (false === $this->shouldTraceQuery($sql)) {
            return parent::prepare($sql);
        }

        $span = $this->tracer->startSpan('SQL query');
        $span->getSpan()->setAttribute('query', $sql);

        return new InstrumentedStatement(
            wrappedStatement: parent::prepare($sql),
            span: $span,
        );
    }

    public function commit()
    {
        if (false === $this->rootContextProvider->hasContext()) {
            return parent::commit();
        }

        $span = $this->tracer->startSpan('Database commit');

        try {
            return parent::commit();
        } catch (\Throwable $exception) {
            $realSpan = $span->getSpan();
            $realSpan->setStatus(StatusCode::STATUS_ERROR);
            $realSpan->recordException($exception);
            throw $exception;
        } finally {
            $span->end();
        }
    }

    public function rollBack()
    {
        if (false === $this->rootContextProvider->hasContext()) {
            return parent::rollBack();
        }

        $span = $this->tracer->startSpan('Database rollback');

        try {
            return parent::rollBack();
        } catch (\Throwable $exception) {
            $realSpan = $span->getSpan();
            $realSpan->setStatus(StatusCode::STATUS_ERROR);
            $realSpan->recordException($exception);
            throw $exception;
        } finally {
            $span->end();
        }
    }

    public function beginTransaction()
    {
        if (false === $this->rootContextProvider->hasContext()) {
            return parent::beginTransaction();
        }

        $span = $this->tracer->startSpan('Begin transaction');

        try {
            return parent::beginTransaction();
        } catch (\Throwable $exception) {
            $realSpan = $span->getSpan();
            $realSpan->setStatus(StatusCode::STATUS_ERROR);
            $realSpan->recordException($exception);
            throw $exception;
        } finally {
            $span->end();
        }
    }

    private function shouldTraceQuery(string $query): bool
    {
        if (false === $this->rootContextProvider->hasContext()) {
            return false;
        }

        foreach ($this->ignoredPatterns as $pattern) {
            if (preg_match('/'.$pattern.'/', $query)) {
                return false;
            }
        }

        return true;
    }
}
