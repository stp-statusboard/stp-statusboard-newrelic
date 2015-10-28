# New Relic Widget

## Config

For discovering New Relic parameters, use New Relic API Explorer 
[https://rpm.newrelic.com/api/explore](https://rpm.newrelic.com/api/explore)

```
-
    id: newrelic1
    provider: \StpBoard\NewRelic\NewRelicControllerProvider
    refresh: 60
    width: 4
    params:
      name: NAME_TO_BE_DISPLAYED
      accountId: NEW_RELIC_ACCOUNT_ID
      appId: NEW_RELIC_APP_ID
      apiKey: NEW_RELIC_API_KEY
      instanceId: NEW_RELIC_INSTANCE_ID # required only for **memory** action
      action: ACTION
      begin: -30minutes
```

### Available actions are:

* rpm
* fe_rpm
* apdex
* error_rate
* throughput
* response_time
* cpu
* cpu_usage 
* average_response_time
* memory

Memcached:
* memcached_used_memory
* memcached_hit_ratio
* memcached_latency

Parameter ```begin``` is optional - default value is ```-30minutes```. It describes time period from which data should
be displayed.
