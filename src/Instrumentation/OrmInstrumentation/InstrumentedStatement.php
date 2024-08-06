<?php
declare(strict_types=1);

namespace Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use OpenTelemetry\API\Trace\StatusCode;
use Zim\SymfonyTracingCoreBundle\ScopedSpan;

class InstrumentedStatement extends AbstractStatementMiddleware
{
    public function __construct(
        Statement $wrappedStatement,
        private readonly ScopedSpan $span,
    )
    {
        parent::__construct($wrappedStatement);
    }

    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null)
    {
        $this->span->getSpan()->setAttribute(
            sprintf('param_%s', $param),
            var_export($variable, true),
        );

        return parent::bindParam($param, $variable, $type, $length);
    }

    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        $this->span->getSpan()->setAttribute(
            sprintf('param_%s', $param),
            var_export($value, true),
        );

        return parent::bindValue($param, $value, $type);
    }

    public function execute($params = null): Result
    {
        try {
            return parent::execute($params);
        } catch (\Throwable $exception) {
            $realSpan = $this->span->getSpan();
            $realSpan->setStatus(StatusCode::STATUS_ERROR);
            $realSpan->recordException($exception);
            throw $exception;
        } finally {
            $this->span->end();
        }
    }
}
