<?php

return array(

    'appNameIOS'     => array(
        'environment' =>'development',
        'certificate' =>base_path().'/TutorPortal.pem',
        'passPhrase'  =>'password',
        'service'     =>'apns'
    ),
    'appNameAndroid' => array(
        'environment' =>'production',
        'apiKey'      =>'yourAPIKey',
        'service'     =>'gcm'
    )

);
