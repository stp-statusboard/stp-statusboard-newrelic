<?php

namespace StpBoard\NewRelic\Service;

use StpBoard\NewRelic\Exception\NewRelicException;

class NewRelicService
{
    const BASE_URL = 'https://api.newrelic.com/api/v1';

    const FE_RPM_FOR_GRAPH_WIDGET_URL = '/accounts/%s/applications/%s/data.json?metrics[]=EndUser&field=requests_per_minute&begin=%s&end=%s';
    const RPM_FOR_GRAPH_WIDGET_URL = '/accounts/%s/applications/%s/data.json?metrics[]=HttpDispatcher&field=requests_per_minute&begin=%s&end=%s';
    const THRESHOLD_VALUES_URL = '/accounts/%s/applications/%s/threshold_values.xml';
    const CPU_USAGE_URL = '/accounts/%s/applications/%s/data.json?metrics[]=CPU/User Time&field=percent&begin=%s&end=%s';
    const AVERAGE_RESPONSE_TIME_URL = '/accounts/%s/applications/%s/data.json?metrics[]=HttpDispatcher&field=average_response_time&begin=%s&end=%s';

    /**
     * @param array $config
     *
     * @return array
     * @throws NewRelicException
     */
    public function fetchFeRpmForGraphWidget($config)
    {
        $beginDate = $this->getStringForTimeInterval($config['begin']);
        $endDate = $this->getStringForTimeInterval('now');

        $url = sprintf(self::FE_RPM_FOR_GRAPH_WIDGET_URL, $config['accountId'], $config['appId'], $beginDate, $endDate);

        return $this->fetchRpm($config, $url);
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
     * @param string $url
     *
     * @return array
     * @throws NewRelicException
     */
    protected function fetchRpm($config, $url)
    {
        $data = $this->parseJSON($this->request($url, $config));

        $result = [];
        foreach ($data as $singleStat) {
            $result[] = [
                'x' => 1000 * (strtotime($singleStat['begin']) + 7200),
                'y' => round($singleStat['requests_per_minute'])
            ];
        }

        return $result;
    }

    /**
     * @param string $url
     * @param array $config
     *
     * @return string
     */
    protected function request($url, $config)
    {
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, self::BASE_URL . $url);
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, ['X-Api-Key:' . $config['apiKey']]);
        $data = curl_exec($curlHandle);

        curl_close($curlHandle);

        return $data;
    }

    /**
     * @param string $data
     *
     * @return array
     * @throws NewRelicException
     */
    protected function parseJSON($data)
    {
        if ($data === false) {
            throw new NewRelicException('Can not get data from NewRelic');
        }

        $data = json_decode($data, true);
        if ($data === null) {
            throw new NewRelicException('Can not parse response from NewRelic');
        }

        return $data;
    }

    /**
     * @param string $data
     *
     * @return \SimpleXMLElement
     * @throws NewRelicException
     */
    protected function parseXML($data)
    {
        try {
            return simplexml_load_string($data);
        } catch (\Exception $e) {
            throw new NewRelicException('Can not parse response from NewRelic');
        }
    }

    /**
     * @param array $config
     *
     * @return array
     * @throws NewRelicException
     */
    public function fetchRpmForGraphWidget($config)
    {
        $beginDate = $this->getStringForTimeInterval($config['begin']);
        $endDate = $this->getStringForTimeInterval('now');

        $url = sprintf(self::RPM_FOR_GRAPH_WIDGET_URL, $config['accountId'], $config['appId'], $beginDate, $endDate);

        return $this->fetchRpm($config, $url);
    }

    /**
     * @param array $config
     *
     * @return \SimpleXMLElement
     * @throws NewRelicException
     */
    protected function fetchThresholdValues($config)
    {
        $url = sprintf(self::THRESHOLD_VALUES_URL, $config['accountId'], $config['appId']);

        return $this->parseXML($this->request($url, $config));
    }

    /**
     * @param string $name
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    protected function fetchThresholdValue($name, $config)
    {
        $values = $this->fetchThresholdValues($config);
        foreach ($values->threshold_value as $value) {
            $value = (array) $value;
            if ($value['@attributes']['name'] == $name) {
                return $value['@attributes']['formatted_metric_value'];
            }
        }

        throw new NewRelicException('Can not find value ' . $name);
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchApdex($config)
    {
        return $this->fetchThresholdValue('Apdex', $config);
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchApplicationBusy($config)
    {
        return $this->fetchThresholdValue('Application Busy', $config);
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchErrorRate($config)
    {
        return $this->fetchThresholdValue('Error Rate', $config);
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchThroughput($config)
    {
        return $this->fetchThresholdValue('Throughput', $config);
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchErrors($config)
    {
        return $this->fetchThresholdValue('Errors', $config);
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchResponseTime($config)
    {
        return $this->fetchThresholdValue('Response Time', $config);
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchDb($config)
    {
        return $this->fetchThresholdValue('DB', $config);
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchCpu($config)
    {
        return $this->fetchThresholdValue('CPU', $config);
    }

    /**
     * @param array $config
     *
     * @return string
     * @throws NewRelicException
     */
    public function fetchMemory($config)
    {
        return $this->fetchThresholdValue('Memory', $config);
    }

    /**
     * @param array $config
     *
     * @return array
     * @throws NewRelicException
     */
    public function fetchCpuUsageForGraphWidget($config)
    {
        $beginDate = $this->getStringForTimeInterval($config['begin']);
        $endDate = $this->getStringForTimeInterval('now');

        $url = sprintf(self::CPU_USAGE_URL, $config['accountId'], $config['appId'], $beginDate, $endDate);

        $data = $this->parseJSON($this->request($url, $config));

        $result = [];
        foreach ($data as $singleStat) {
            $result[] = [
                'x' => 1000 * (strtotime($singleStat['begin']) + 7200),
                'y' => round($singleStat['percent'])
            ];
        }

        return $result;
    }

    /**
     * @param array $config
     *
     * @return array
     * @throws NewRelicException
     */
    public function fetchAverageResponseTimeForGraphWidget($config)
    {
        $beginDate = $this->getStringForTimeInterval($config['begin']);
        $endDate = $this->getStringForTimeInterval('now');

        $url = sprintf(self::AVERAGE_RESPONSE_TIME_URL, $config['accountId'], $config['appId'], $beginDate, $endDate);

        $data = $this->parseJSON($this->request($url, $config));

        $result = [];
        foreach ($data as $singleStat) {
            $result[] = [
                'x' => 1000 * (strtotime($singleStat['begin']) + 7200),
                'y' => round($singleStat['average_response_time'] * 1000)
            ];
        }

        return $result;
    }
}
