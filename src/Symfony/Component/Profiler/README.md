Profiler Component
==================

Profiler collects information about each request made to your
HttpKernel-based application and store them for later analysis.

The profiler is mainly used in the development environment to help you debug
your code and enhance performance; use it in the production environment to
explore problems after the fact.

    use Symfony\Component\Profiler\Profiler;
    use Symfony\Component\Profiler\DataCollector;
    use Symfony\Component\Profiler\Storage\FileProfilerStorage;

    $storage = new FileProfilerStorage('file:/path/to/storage/profiles');
    $profiler = new Profiler($storage);

    // add some data collectors
    $profiler->add(new DataCollector\RequestDataCollector($requestStack));
    $profiler->add(new DataCollector\MemoryDataCollector());
    // ...

    // handle a Request with HttpKernel to get back a Response
    $response = $kernel->handle($request);

    // gather runtime information and create a profile
    $profile = $profiler->profile();

    // profiles are uniquely identified by a token
    $token = $profile->getToken();

    // gather additional information and save to the Storage.
    $profiler->save($profile);

    // in another process, get back a profile
    $profile = $profiler->load($token);

Resources
---------

You can run the unit tests with the following command:

    $ cd path/to/Symfony/Component/Profiler/
    $ composer.phar install
    $ phpunit