<?php

declare(strict_types=1);

namespace Artprima\PrometheusMetricsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('artprima_prometheus_metrics');
        $rootNode = $treeBuilder->getRootNode();

        $supportedTypes = ['in_memory', 'apcu', 'redis'];

        $rootNode
            ->children()
                ->scalarNode('namespace')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                        // see: https://github.com/artprima/prometheus-metrics-bundle/issues/32
                        ->ifTrue(function ($s) {
                            return 1 !== preg_match('/^[a-zA-Z_:][a-zA-Z0-9_:]*$/', $s);
                        })
                        ->thenInvalid('Invalid namespace. Make sure it matches the following regex: ^[a-zA-Z_:][a-zA-Z0-9_:]*$')
                    ->end()
                ->end()
                ->scalarNode('type')
                    ->validate()
                        ->ifNotInArray($supportedTypes)
                        ->thenInvalid('The type %s is not supported. Please choose one of '.json_encode($supportedTypes))
                    ->end()
                    ->defaultValue('in_memory')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('redis')
                    ->children()
                        ->scalarNode('host')->end()
                        ->integerNode('port')
                            ->defaultValue(6379)
                        ->end()
                        ->floatNode('timeout')->end()
                        ->floatNode('read_timeout')
                            ->validate()
                                ->always()
                                // here we force casting `float` to `string` to avoid TypeError when working with Redis
                                // see for more details: https://github.com/phpredis/phpredis/issues/1538
                                ->then(function ($v) {
                                    return (string) $v;
                                })
                            ->end()
                        ->end()
                        ->booleanNode('persistent_connections')->end()
                        ->scalarNode('password')->end()
                        ->integerNode('database')->end()
                        ->scalarNode('prefix')
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifTrue(function ($s) {
                                    return 1 !== preg_match('/^[a-zA-Z_:][a-zA-Z0-9_:]*$/', $s);
                                })
                                ->thenInvalid('Invalid prefix. Make sure it matches the following regex: ^[a-zA-Z_:][a-zA-Z0-9_:]*$')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('ignored_routes')
                    ->prototype('scalar')->end()
                    ->defaultValue(['prometheus_bundle_prometheus'])
                ->end()
                ->booleanNode('disable_default_metrics')
                    ->defaultValue(false)
                ->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
