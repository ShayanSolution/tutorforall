<?php

return array(

    'appNameIOS'     => array(
//        'environment' =>'development',
        'environment' => 'production',
        'certificate' => base_path().'/storage/pem/TutorPortalProduction.pem',
        'passPhrase'  =>'password',
        'service'     =>'apns'
    ),
    'appStudentIOS'     => array(
//        'environment' =>'development',
        'environment' => 'production',
        'certificate' => base_path().'/storage/pem/TutorForAllProduction.pem',
        'passPhrase'  =>'password',
        'service'     =>'apns'
    ),
    'appNameAndroid' => array(
        'environment' =>'development',
        'apiKey'      =>'AAAAOJuNZRs:APA91bF5gFaiuvRXBDfC1kVWUsArmFS50YzwVH2Toa6Y9sg4Wq_tNRFaRSv7nxserrLhYJyro7iWkNkL4uRHSPTdmHBiTJI-weC7kGbZFVOpwTatu6Z10NoDu2EedcB4cx-RNfaa0chhmIvgOMOleGrcFM9ZKyD3tw',
        'service'     =>'gcm'
    )

);
