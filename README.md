saxulum webprofiler provider
===========================

**works with plain silex-php**

[![Build Status](https://api.travis-ci.org/saxulum/saxulum-webprofiler-provider.png?branch=master)](https://travis-ci.org/saxulum/saxulum-webprofiler-provider)
[![Total Downloads](https://poser.pugx.org/saxulum/saxulum-webprofiler-provider/downloads.png)](https://packagist.org/packages/saxulum/saxulum-webprofiler-provider)
[![Latest Stable Version](https://poser.pugx.org/saxulum/saxulum-webprofiler-provider/v/stable.png)](https://packagist.org/packages/saxulum/saxulum-webprofiler-provider)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/saxulum/saxulum-webprofiler-provider/badges/quality-score.png?s=4e315f6511ecfa00815ac2fe232f6117823b7699)](https://scrutinizer-ci.com/g/saxulum/saxulum-webprofiler-provider/)

Features
--------

* Enhance the default silex web profiler with database informations

Requirements
------------

* php >=5.3
* jdorn/sql-formatter ~1.1
* psr/log 1.0.*
* silex/silex ~1.0
* silex/web-profiler ~1.0
* symfony/doctrine-bridge ~2.3


Installation
------------

The [SilexWebProfiler][1] from silex itself is needed!

```php
$app->register(new Silex\Provider\WebProfilerServiceProvider(), array(
    'profiler.cache_dir' => __DIR__.'/../cache/profiler',
    'profiler.mount_prefix' => '/_profiler', // this is the default
));
```

```php
$app->register(new Saxulum\SaxulumWebProfiler\Provider\SaxulumWebProfilerProvider());
```

[1]: https://github.com/silexphp/Silex-WebProfiler
