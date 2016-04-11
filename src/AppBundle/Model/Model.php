<?php

namespace AppBundle\Model;

class Model {

    private $account, $site_id, $site_secure_code;

    public function __construct($account, $site_id, $site_secure_code) {
        $this->account = $account;
        $this->site_id = $site_id;
        $this->site_secure_code = $site_secure_code;
    }

    public function makeCredentials() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml.= '<status ua="custom-1.1">';
        $xml.= '<merchant>';
        $xml.= '<account>' . $this->account . '</account>';
        $xml.= '<site_id>' . $this->site_id . '</site_id>';
        $xml.= '<site_secure_code>' . $this->site_secure_code . '</site_secure_code>';
        $xml.= '</merchant>';
        $xml.= '<transaction><id>ORDER-SAMPLE-1</id></transaction>';
        $xml.= '</status>';

        return $xml;
    }

    public function getXMLResponse($xml) {
        $url = 'https://devapi.multisafepay.com/ewx/';

        $ch = \curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}
