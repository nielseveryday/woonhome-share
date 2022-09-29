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
     * Website url
     *
     * @var string
     */
    private $website_url = 'https://woonhome.nl/';

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
    private $content_api_url = 'https://wp.woonhome.nl/wp-json/whc/v1/';

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
     * Third level structure (2)
     *
     * @var string
     */
    private $third_level;

    /**
     * Start the share app
     *
     * @return void
     */
    public function start()
    {
        // Get the structure
        $structure = Structure::getStructure();

        // Return to website if structure is not set or empty
        if (!$structure || count($structure) == 0) {
            Structure::redirect($this->website_url, '302');
            exit;
        }

        if (!Structure::isAllowedUserAgent()) {
            Structure::redirect($this->website_url . Structure::structureToPath(), '302');
            exit;
        }

        if (!$this->isShareValid($structure)) {
            Structure::redirect($this->website_url . Structure::structureToPath(), '302');
            exit;
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
        if (!in_array($structure[0], $this->share_structures)) {
            return false;
        }

        if ($structure[0] !== 'woonwiki' && count($structure) != 2) {
            return false;
        }

        if ($structure[0] === 'woonwiki' && count($structure) < 3) {
            return false;
        }

        // Set values
        $this->first_level = $structure[0];
        $this->second_level = $structure[1];
        $this->third_level = $structure[2]??'';

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
                $api_url = $this->content_api_url . $this->first_level . '?slug=' . $this->second_level . '&v=2&type=live';
                break;
            case 'woonwiki':
                $api_url = $this->content_api_url . $this->first_level . '?id=slug-' . $this->third_level;
                break;
        }

        try {
            $client = new Client();
            $res = $client->request('GET', $api_url);

            // Check the request
            $status = $res->getStatusCode();
            if ($status == 200) {
                $body = json_decode($res->getBody());

                // Check the return data per level
                if ($this->first_level == 'sale' || $this->first_level == 'product') {
                    if ($body->data->status == 200) {
                        return $body->data;
                    } else {
                        // Requested data not found (404) or an error, redirect
                        Structure::redirect($this->website_url . Structure::structureToPath(), '302');
                        exit;
                    }
                } else {
                    if (count($body->items) > 0) {
                        return $body;
                    } else {
                        // Requested data not found (404) or an error, redirect
                        Structure::redirect($this->website_url . Structure::structureToPath(), '302');
                        exit;
                    }
                }

            } else {
                // Not a successful API result, redirect
                Structure::redirect($this->website_url . Structure::structureToPath(), '302');
                exit;
            }

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
        $content_template = $content->getTemplateContent($this->first_level.".html");

        $parsed_content = $content->parseTemplate($this->first_level, json_encode($data), $content_template);

        echo $parsed_content;
    }
}