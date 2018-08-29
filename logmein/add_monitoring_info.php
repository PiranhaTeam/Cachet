<?php
    define('MONITORING_SERVICES', 1);
    define('NUCLEUS', 2);
    define('PANTERAS', 3);

    $file_path = 'logmein/ed1_rc1_monitor_seed.json';
    $config = json_decode(file_get_contents($file_path));
    add_info($config);
    file_put_contents($file_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    function add_info(&$config) {
        foreach($config->components as $component){
            switch($component->type){
                case MONITORING_SERVICES:
                    add_info_monitoring_services($component);
                    break;
                case NUCLEUS:
                    add_info_nucleus($component);
                    break;
                case PANTERAS:
                    add_info_panteras($component);
                    break;
            }
        }
        foreach($config->groups as $group){
            add_info($group);
        }
    }

    function add_info_monitoring_services(&$component){
        $ch = curl_init("couched1db.qai.expertcity.com:5984/services/" . $component->description);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = json_decode(curl_exec($ch));

        $component->link = is_valid_url($data->accessPointUrl)?$data->accessPointUrl:"";
        $component->monitoring_url = is_valid_url($data->monitoringUrl)?$data->monitoringUrl:"";
        $component->expected_body = "Functional=1";
        $component->headers = json_decode("{}");
    }

    function add_info_nucleus(&$component) {
        $component->link = "http://g2wplus-regdiscovery1-rc.avad.expertcity.com:8080/";
        $component->monitoring_url = "http://g2wplus-regdiscovery1-rc.avad.expertcity.com:8080/eureka/apps/" . $component->description;
        $component->expected_body = "<status>UP</status>";
        $component->headers = [
            "Content-Type"  =>  "text/xml"
        ];
    }

    function add_info_panteras(&$component) {
        $component->link = "https://g2m-ed1-earth-consul.serversdev.getgo.com/ui/#/us-west-2-ed1-earth-blue/services";
        $component->monitoring_url = "https://g2m-ed1-earth-consul.serversdev.getgo.com/v1/health/checks/" . $component->description;
        $component->expected_body = "\"Status\":\"passing\"";
        $component->headers = [
            "Content-Type"  =>  "application/json"
        ];
    }

    function is_valid_url(string $url) {
        return preg_match('/^https?:\/\/.*$/', $url);
    }

?>