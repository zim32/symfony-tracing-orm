<?php
declare(strict_types=1);

namespace Zim\SymfonyOrmTracingBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation\DoctrineOnClearEventListener;
use Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation\DoctrinePostFlushEventListener;
use Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation\DoctrinePreFlushEventListener;
use Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation\DoctrineSpanStack;
use Zim\SymfonyOrmTracingBundle\Instrumentation\OrmInstrumentation\InstrumentedMiddleware;

class SymfonyOrmTracingBundle extends AbstractBundle
{
    protected string $extensionAlias = 'orm_tracing';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('ignored_patterns')
                    ->scalarPrototype()
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $definition = (new Definition(InstrumentedMiddleware::class))
            ->addArgument(new Reference('tracing.scoped_tracer.doctrine'))
            ->addArgument(new Reference('tracing.root_context_provider'))
            ->addArgument($config['ignored_patterns'])
            ->addTag('doctrine.middleware')
        ;

        $builder->setDefinition('tracing.orm.middleware', $definition);


        $builder
            ->register(DoctrineSpanStack::class)
            ->addTag('kernel.reset', ['method' => 'reset'])
        ;

        $definition = (new Definition(DoctrinePreFlushEventListener::class))
            ->addArgument(new Reference('tracing.scoped_tracer.doctrine'))
            ->addArgument(new Reference(DoctrineSpanStack::class))
            ->addArgument(new Reference('tracing.root_context_provider'))
            ->addTag('doctrine.event_listener', ['event' => 'preFlush'])
        ;
        $builder->setDefinition(DoctrinePreFlushEventListener::class, $definition);


        $definition = (new Definition(DoctrinePostFlushEventListener::class))
            ->addArgument(new Reference(DoctrineSpanStack::class))
            ->addArgument(new Reference('tracing.root_context_provider'))
            ->addTag('doctrine.event_listener', ['event' => 'postFlush'])
        ;
        $builder->setDefinition(DoctrinePostFlushEventListener::class, $definition);

        $definition = (new Definition(DoctrineOnClearEventListener::class))
            ->addArgument(new Reference(DoctrineSpanStack::class))
            ->addTag('doctrine.event_listener', ['event' => 'onClear'])
        ;
        $builder->setDefinition(DoctrineOnClearEventListener::class, $definition);
    }
}
