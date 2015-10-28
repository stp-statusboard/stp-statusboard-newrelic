<?php

namespace StpBoard\NewRelic;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use StpBoard\Base\BoardProviderInterface;
use StpBoard\Base\TwigTrait;
use StpBoard\NewRelic\Exception\NewRelicException;
use StpBoard\NewRelic\Service\Client;
use StpBoard\NewRelic\Service\NewRelicService;
use Symfony\Component\HttpFoundation\Request;

class NewRelicControllerProvider implements ControllerProviderInterface, BoardProviderInterface
{
    use TwigTrait;

    /**
     * @var NewRelicService
     */
    protected $newRelicService;

    /**
     * @var array
     */
    protected $methodsMap = [
        'rpm' => [
            'method' => 'fetchRpmForGraphWidget',
            'template' => 'chart.html.twig',
        ],
        'fe_rpm' => [
            'method' => 'fetchFeRpmForGraphWidget',
            'template' => 'chart.html.twig',
        ],
        'apdex' => [
            'method' => 'fetchApdex',
            'template' => 'value.html.twig',
        ],
        'error_rate' => [
            'method' => 'fetchErrorRate',
            'template' => 'value.html.twig',
        ],
        'throughput' => [
            'method' => 'fetchThroughput',
            'template' => 'value.html.twig',
        ],
        'response_time' => [
            'method' => 'fetchResponseTime',
            'template' => 'value.html.twig',
        ],
        'db' => [
            'method' => 'fetchDb',
            'template' => 'value.html.twig',
        ],
        'cpu' => [
            'method' => 'fetchCpu',
            'template' => 'value.html.twig',
        ],
        'memory' => [
            'method' => 'fetchMemory',
            'template' => 'value.html.twig',
        ],
        'cpu_usage' => [
            'method' => 'fetchCpuUsageForGraphWidget',
            'template' => 'chart.html.twig',
        ],
        'average_response_time' => [
            'method' => 'fetchAverageResponseTimeForGraphWidget',
            'template' => 'chart.html.twig',
        ],
        'memcached_used_memory' => [
            'method' => 'fetchMemcachedUsedMemory',
            'template' => 'value.html.twig',
        ],
        'memcached_hit_ratio' => [
            'method' => 'fetchMemcachedHitRatio',
            'template' => 'value.html.twig',
        ],
        'memcached_latency' => [
            'method' => 'fetchMemcachedLatency',
            'template' => 'chart.html.twig',
        ],
    ];

    /**
     * Returns route prefix, starting with "/"
     *
     * @return string
     */
    public static function getRoutePrefix()
    {
        return '/newrelic';
    }

    /**
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $this->newRelicService = new NewRelicService(new Client());

        $this->initTwig(__DIR__ . '/views');
        $controllers = $app['controllers_factory'];

        $controllers->get(
            '/',
            function (Application $app) {
                /** @var Request $request */
                $request = $app['request'];

                try {
                    $config = $this->getConfig($request);

                    $result = $this->newRelicService->$config['method']($config);

                    return $this->twig->render(
                        $config['template'],
                        [
                            'name' => $config['name'],
                            'data' => $result,
                        ]
                    );
                } catch (NewRelicException $e) {
                    return $this->twig->render('error.html.twig', [
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        );

        return $controllers;
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws NewRelicException
     */
    protected function getConfig(Request $request)
    {
        $name = $request->get('name');
        if (empty($name)) {
            throw new NewRelicException('Empty chart name');
        }

        $appId = $request->get('appId');
        if (empty($appId)) {
            throw new NewRelicException('Empty appId');
        }

        $apiKey = $request->get('apiKey');
        if (empty($apiKey)) {
            throw new NewRelicException('Empty apiKey');
        }

        $action = $request->get('action');
        if (empty($action)) {
            throw new NewRelicException('Empty action');
        }

        $begin = $request->get('begin', '-30minutes');

        $instanceId = $request->get('instanceId');
        if (empty($instanceId) && $action == 'memory') {
            throw new NewRelicException('Empty instanceId parameter');
        }

        if (!isset($this->methodsMap[$action])) {
            throw new NewRelicException('Unrecognized action');
        }

        $method = $this->methodsMap[$action]['method'];
        $template = $this->methodsMap[$action]['template'];

        if (!method_exists($this->newRelicService, $method)) {
            throw new NewRelicException('Unrecognized method');
        }

        return [
            'name' => $name,
            'appId' => $appId,
            'instanceId' => $instanceId,
            'apiKey' => $apiKey,
            'method' => $method,
            'template' => $template,
            'begin' => $begin,
        ];
    }
}
