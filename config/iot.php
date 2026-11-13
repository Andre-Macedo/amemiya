<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IoT Module Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can define the URL for the Machine Learning microservice
    | and other IoT related settings.
    |
    */

    'ml_service_url' => env('IOT_ML_SERVICE_URL', 'http://ml-service:8000/predict-anomalia'),

    'mqtt_qos' => (int) env('IOT_MQTT_QOS', 1),

    'offline_timeout_minutes' => (int) env('IOT_OFFLINE_TIMEOUT_MINUTES', 5),

];
