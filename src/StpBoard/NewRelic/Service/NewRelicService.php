<?php

namespace StpBoard\NewRelic\Service;

use StpBoard\NewRelic\Exception\NewRelicException;

class NewRelicService
{
    const BASE_URL_V2 = 'https://api.newrelic.com/v2';

    const APPLICATION_INFO_URL = self::BASE_URL_V2 . '/applications.json?filter[ids]=%s';
    const RPM_FOR_GRAPH_WIDGET_URL = self::BASE_URL_V2 . '/applications/%s/metrics/data.json?names[]=HttpDispatcher&values[]=requests_per_minute&from=%s&to=%s';
    const FE_RPM_FOR_GRAPH_WIDGET_URL = self::BASE_URL_V2 . '/applications/%s/metrics/data.json?names[]=EndUser&values[]=requests_per_minute&from=%s&to=%s';
    const AVERAGE_RESPONSE_TIME_URL = self::BASE_URL_V2 . '/applications/%s/metrics/data.json?names[]=HttpDispatcher&values[]=average_response_time&from=%s&to=%s';
    const CPU_USAGE_URL = self::BASE_URL_V2 . '/applications/%s/metrics/data.json?names[]=CPU/User+Time&values[]=percent&from=%s&to=%s';
    const MEMORY_USAGE_URL = self::BASE_URL_V2 . '/applications/%s/instances/_instanceId_/metrics/data.json?names[]=Memory/Physical&values[]=used_mb_by_host&from=%s&to=%s';
    const MEMCACHED_MEMORY_USED_URL = self::BASE_URL_V2 . '/applications/%s/metrics/data.json?names[]=Component/memcached/Used+memory[megabytes]&values[]=average_value&from=%s&to=%s';
    const MEMCACHED_HIT_RATIO_URL = self::BASE_URL_V2 . '/applications/%s/metrics/data.json?names[]=Component/memcached/Hit+ratio[%%25]&values[]=average_value&from=%s&to=%s';
    const MEMCACHED_LATENCY_URL = self::BASE_URL_V2 . '/applications/%s/metrics/data.json?names[]=Component/memcached/Latency/All[ms%%7Cwrite]&values[]=average_value&from=%s&to=%s';

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param array $config
     *
     * @return array
     * @throws NewRelicException
     */
    public function fetchFeRpmForGraphWidget($config)
    {
        return $this->fetchMetricForGraph($config, self::FE_RPM_FOR_GRAPH_WIDGET_URL, 'requests_per_minute');
    }

    /**
     * @param array $config
     * @param string $url
     * @param string $valueKey
     *
     * @return array
     */
    protected function fetchMetricForGraph($config, $url, $valueKey)
    {
        $beginDate = $this->getStringForTimeInterval($config['begin']);
        $endDate = $this->getStringForTimeInterval('now');

        $url = sprintf($url, $config['appId'], $beginDate, $endDate);

        $data = $this->client->getJSON($url, $config);

        $currentDate = new \DateTime();

        $result = [];
        foreach ($data['metric_data']['metrics'][0]['timeslices'] as $singleStat) {
            $result[] = [
                'x' => 1000 * ((new \DateTime($singleStat['from']))->getTimestamp() + $currentDate->getOffset()),
                'y' => $singleStat['values'][$valueKey]
            ];
        }

        return $result;
    }

    /**
     * @param string $interval
     *
     * @return string
     */
    protected function getStringForTimeInterval($interval)
    {
        return gmdate('Y-m-d', strtotime($interval)) . 'T' . gmdate('H:i:s', strtotime($interval)) . 'Z';
    }

    /**
     * @param array $config
     *
     * @return array
     * @throws NewRelicException
     */
    public function fetchRpmForGraphWidget($config)
    {
        return $this->fetchMetricForGraph($config, self::RPM_FOR_GRAPH_WIDGET_URL, 'requests_per_minute');
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchApdex($config)
    {
        return sprintf(
            '%s [%s]',
            round($this->fetchMetricFromApplicationInfo($config, 'apdex_score'), 2),
            round($this->fetchMetricFromApplicationInfo($config, 'apdex_target'), 2)
        );
    }

    /**
     * @param array $config
     * @param string $metric
     *
     * @return string
     */
    protected function fetchMetricFromApplicationInfo($config, $metric)
    {
        $url = sprintf(self::APPLICATION_INFO_URL, $config['appId']);
        $data = $this->client->getJSON($url, $config);

        return $data['applications'][0]['application_summary'][$metric];
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchErrorRate($config)
    {
        return round($this->fetchMetricFromApplicationInfo($config, 'error_rate'), 2) . '%';
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchThroughput($config)
    {
        return round($this->fetchMetricFromApplicationInfo($config, 'throughput'), 2) . ' rpm';
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchResponseTime($config)
    {
        return round($this->fetchMetricFromApplicationInfo($config, 'response_time'), 2) . ' ms';
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchCpu($config)
    {
        return $this->fetchMetric($config, self::CPU_USAGE_URL, '%', 'percent');
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchMemory($config)
    {
        return $this->fetchMetric(
            $config,
            str_replace('_instanceId_', $config['instanceId'], self::MEMORY_USAGE_URL),
            ' MB',
            'used_mb_by_host'
        );
    }

    /**
     * @param array $config
     *
     * @return array
     * @throws NewRelicException
     */
    public function fetchCpuUsageForGraphWidget($config)
    {
        return $this->fetchMetricForGraph($config, self::CPU_USAGE_URL, 'percent');
    }

    /**
     * @param array $config
     *
     * @return array
     * @throws NewRelicException
     */
    public function fetchAverageResponseTimeForGraphWidget($config)
    {
        return $this->fetchMetricForGraph($config, self::AVERAGE_RESPONSE_TIME_URL, 'average_response_time');
    }

    /**
     * @param array $config
     *
     * @return string
     */
    public function fetchMemcachedUsedMemory($config)
    {
        return $this->fetchMetric($config, self::MEMCACHED_MEMORY_USED_URL, ' MB');
    }

    /**
     * @param array $config
     * @param string $url
     * @param string $unit
     * @param string $valueKey
     *
     * @return string
     */
    protected function fetchMetric($config, $url, $unit = '', $valueKey = 'average_value')
    {
        $beginDate = $this->getStringForTimeInterval($config['begin']);
        $endDate = $this->getStringForTimeInterval('now');

        $url = sprintf($url, $config['appId'], $beginDate, $endDate);

        $data = $this->client->getJSON($url, $config);

        $sum = 0;
        foreach ($data['metric_data']['metrics'][0]['timeslices'] as $singleStat) {
            $sum += $singleStat['values'][$valueKey];
        }

        $count = count($data['metric_data']['metrics'][0]['timeslices']);
        $average = $count ? $sum / $count : 0;

        return round($average, 2) . $unit;
    }

    /**
     * @param array $config
     *
     * @return string
     */
    public function fetchMemcachedHitRatio($config)
    {
        return $this->fetchMetric($config, self::MEMCACHED_HIT_RATIO_URL, '%');
    }

    /**
     * @param array $config
     *
     * @return array
     * @throws NewRelicException
     */
    public function fetchMemcachedLatency($config)
    {
        return $this->fetchMetricForGraph($config, self::MEMCACHED_LATENCY_URL, 'average_value');
    }
}
