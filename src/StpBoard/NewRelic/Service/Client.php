<?php

namespace StpBoard\NewRelic\Service;

use StpBoard\NewRelic\Exception\NewRelicException;

class Client
{
    /**
     * @param string $url
     * @param array $config
     *
     * @return array
     * @throws NewRelicException
     */
    public function getJSON($url, $config)
    {
        return $this->parseJSON($this->request($url, $config));
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
        curl_setopt($curlHandle, CURLOPT_URL, $url);
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
}
