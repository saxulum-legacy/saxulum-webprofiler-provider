<?php

namespace Saxulum\SaxulumWebProfiler\Provider;

use Saxulum\SaxulumWebProfiler\DataCollector\DoctrineDataCollector;
use Saxulum\SaxulumWebProfiler\Logger\DbalLogger;
use Saxulum\SaxulumWebProfiler\Twig\DoctrineExtension;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SaxulumWebProfilerProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function boot(Application $app) {}

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        if (isset($app['twig'])) {
            $app['twig'] = $app->share($app->extend('twig', function(\Twig_Environment $twig) {
                $twig->addExtension(new DoctrineExtension());

                return $twig;
            }));
        }

        if (isset($app['twig.loader.filesystem'])) {
            $app['twig.loader.filesystem'] = $app->share($app->extend('twig.loader.filesystem',
                function(\Twig_Loader_Filesystem $twigLoaderFilesystem) {
                    $twigLoaderFilesystem->addPath(dirname(__DIR__). '/Resources/views', 'SaxulumWebProfilerProvider');

                    return $twigLoaderFilesystem;
                }
            ));
        }

        if (isset($app['data_collectors'])) {
            $dataCollectors = $app['data_collectors'];
            $dataCollectors['db'] = $app->share(function ($app) {
                $dataCollector = new DoctrineDataCollector($app['doctrine']);
                foreach ($app['doctrine']->getConnectionNames() as $name) {
                    $logger = new DbalLogger($app['monolog'], $app['stopwatch']);
                    $app['doctrine']->getConnection($name)->getConfiguration()->setSQLLogger($logger);
                    $dataCollector->addLogger($name, $logger);
                }

                return $dataCollector;
            });
            $app['data_collectors'] = $dataCollectors;
        }

        if (isset($app['data_collector.templates'])) {
            $dataCollectorTemplates = $app['data_collector.templates'];
            $dataCollectorTemplates[] = array('db', '@SaxulumWebProfilerProvider/Collector/db.html.twig');
            $app['data_collector.templates'] = $dataCollectorTemplates;
        }
    }
}
