<?php

namespace Saxulum\SaxulumWebProfiler\Provider;

use Saxulum\DoctrineMongodbOdmManagerRegistry\Doctrine\ManagerRegistry;
use Saxulum\SaxulumWebProfiler\DataCollector\DoctrineDataCollector;
use Saxulum\SaxulumWebProfiler\DataCollector\DoctrineMongoDbDataCollector;
use Saxulum\SaxulumWebProfiler\DataCollector\DoctrineMongoDbStandardDataCollector;
use Saxulum\SaxulumWebProfiler\Logger\DbalLogger;
use Saxulum\SaxulumWebProfiler\Logger\DoctrineMongoDbAggregateLogger;
use Saxulum\SaxulumWebProfiler\Logger\DoctrineMongoDbLogger;
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
        if (isset($app['profiler'])) {

            $app['twig'] = $app->share($app->extend('twig', function (\Twig_Environment $twig) {
                $twig->addExtension(new DoctrineExtension());

                return $twig;
            }));

            $app['twig.loader.filesystem'] = $app->share($app->extend('twig.loader.filesystem',
                function (\Twig_Loader_Filesystem $twigLoaderFilesystem) {
                    $twigLoaderFilesystem->addPath(dirname(__DIR__). '/Resources/views', 'SaxulumWebProfilerProvider');

                    return $twigLoaderFilesystem;
                }
            ));

            $app['data_collectors'] = $app->extend('data_collectors',
                $app->share(function(array $collectors) use ($app) {
                    if(isset($app['doctrine'])) {
                        $app['saxulum.orm.logger'] = function ($app) {
                            return new DbalLogger($app['monolog'], $app['stopwatch']);
                        };

                        $collectors['db'] = $app->share(function ($app) {
                            $dataCollector = new DoctrineDataCollector($app['doctrine']);
                            foreach ($app['doctrine']->getConnectionNames() as $name) {
                                $logger = $app['saxulum.orm.logger'];
                                $app['doctrine']->getConnection($name)->getConfiguration()->setSQLLogger($logger);
                                $dataCollector->addLogger($name, $logger);
                            }

                            return $dataCollector;
                        });
                    }

                    if(isset($app['doctrine_mongodb'])) {
                        $app['saxulum.webprofiler.mongodb.odm.logger.batchthreshold'] = 4;

                        $app['saxulum.mongodb.odm.logger'] = $app->share(function ($app) {
                            $logger = new DoctrineMongoDbLogger($app['monolog']);
                            $logger->setBatchInsertThreshold($app['saxulum.mongodb.odm.logger.batchthreshold']);

                            return $logger;
                        });

                        $app['saxulum.mongodb.odm.loggers'] = $app->share(function ($app) {
                            $loggers = array();
                            $loggers[] = $app['saxulum.mongodb.odm.logger'];
                            $loggers[] = $app['saxulum.mongodb.odm.datacolletor'];

                            return $loggers;
                        });

                        $app['saxulum.mongodb.odm.aggregatelogger'] = $app->share(function ($app) {
                            $logger = new DoctrineMongoDbAggregateLogger($app['saxulum.mongodb.odm.loggers']);

                            return $logger;
                        });

                        $aggregatedLogger = $app['saxulum.mongodb.odm.aggregatelogger'];
                        $app['doctrine_mongodb'] = $app->extend('doctrine_mongodb',
                            $app->share(function(ManagerRegistry $registry) use($aggregatedLogger) {
                                foreach ($registry->getConnectionNames() as $name) {
                                    $registry->getConnection($name)->getConfiguration()->setLoggerCallable(array($aggregatedLogger, 'logQuery'));
                                }
                            }
                        ));

                        $collectors['mongodb'] = $app->share(function ($app) {
                            $dataCollector = new DoctrineMongoDbDataCollector();
                            $dataCollector->setBatchInsertThreshold($app['saxulum.mongodb.odm.logger.batchthreshold']);

                            return $dataCollector;
                        });
                    }

                    return $collectors;
                }
            ));

            $app['data_collector.templates'] = $app->extend('data_collector.templates',
                $app->share(function(array $dataCollectorTemplates) use ($app) {
                    if(isset($app['doctrine'])) {
                        $dataCollectorTemplates[] = array('db', '@SaxulumWebProfilerProvider/Collector/db.html.twig');
                    }
                    if(isset($app['doctrine_mongodb'])) {
                        $dataCollectorTemplates[] = array('mongodb', '@SaxulumWebProfilerProvider/Collector/mongodb.html.twig');
                    }

                    return $dataCollectorTemplates;
                }
            ));
        }
    }
}
