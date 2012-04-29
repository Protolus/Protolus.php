<?php
    interface DataServiceConsumer{
        function registerService($id, $location, $service); //register the service
        function getServiceData($type);
    }
