<?php

namespace WoonhomeShare;

use Structure\Structure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Template\Template;

class WoonhomeShare {

    /**
     * Allowed structures, first level structure (0)
     *
     * @var array
     */
    private $share_structures = array(
        'sale',
        'product',
        'wooninspiratie',
        'woonwiki'
    );

    /**
     * Product API
     *
     * @var string
     */
    private $product_api_url = 'https://api.woonhome.nl/';

    /**
     * Content API
     *
     * @var string
     */
    private $content_api_url = 'https://wp.woonhome.nl/rest/';

    /**
     * First level structure (0)
     *
     * @var string
     */
    private $first_level;

    /**
     * Second level structure (1)
     *
     * @var string
     */
    private $second_level;

    /**
     * Start the share app
     *
     * @return string
     */
    public function start()
    {
        $structure = Structure::getStructure();

        if (!$structure || count($structure) == 0) {
            //return false;
        }

        if (!Structure::isAllowedUserAgent()) {
            //return false;
        }

        if (!$this->isShareValid($structure)) {
            //return false;
        }

        $data = $this->callApi();
        $this->buildPage($data);
    }

    /**
     * Check if the requested URL is a valid Woonhome share
     *
     * @param array $structure Array with the structure
     *
     * @return bool
     */
    private function isShareValid($structure): bool
    {
        if (!in_array($structure[0], $this->share_structures) || count($structure) != 2) {
            return false;
        }

        // Set values
        $this->first_level = $structure[0];
        $this->second_level = $structure[1];

        return true;
    }

    /**
     * Call the correct API to get the data to share
     *
     * @return \stdClass
     * @throws \ErrorException
     */
    private function callApi(): \stdClass
    {
        $api_url = '';
        switch($this->first_level) {
            case 'sale':
            case 'product':
                $api_url = $this->product_api_url . 'product?slug=' . $this->second_level . '&type=' . $this->first_level;
                break;
            case 'wooninspiratie':
            case 'woonwiki':
                $api_url = '';
                break;
        }

        try {
            $client = new Client();
            $res = $client->request('GET', $api_url);

            $status = $res->getStatusCode();
            $body = json_decode($res->getBody());
            return $body->data;
        } catch(ClientException $e) {
            throw new \ErrorException($e->getResponse(), 400);
        }
    }

    /**
     * Pass the collected data to a template
     *
     * @param $data
     * @return void
     */
    public function buildPage($data)
    {

        $content = new Template();
        $content_template = $content->getTemplateContent("product.html");

        $parsed_content = $content->parseTemplate(json_encode($data), $content_template);

        echo $parsed_content;
    }
}