# New Relic Widget

## Config

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
      action: ACTION
```

Available actions are:
* rpm
* fe_rpm
* apdex
* application_busy
* error_rate
* throughput
* errors
* response_time
* db
* cpu
* memory
* average_response_time
