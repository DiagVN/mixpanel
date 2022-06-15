<?php

namespace MixPanel\Services;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use MixPanel\Base\MixPanelBase;

class MixPanelImportService extends MixPanelBase
{
    public function import(array $data)
    {
        $client = new Client([
            'base_uri' => 'https://' . $this->options['host'],
        ]);

        $response = $client->post(
            '/import?project_id=' . config('mixpanel.mix_panel_project_id'),
            [
                RequestOptions::JSON => $data,
                RequestOptions::HEADERS => [
                    'Authorization' => 'Basic ' . config('mixpanel.mix_panel_project_authorization')
                ]
            ]
        );

        $content = $response->getBody()->getContents();
        if (trim($content) != "1") {
            $this->log("Guzzle consumer import send request error", [
                'body' => $data,
                'content' => $content
            ], true);
        } else {
            $this->log("Guzzle consumer import send request success", [
                'body' => $data,
            ]);
        }

        return $content;
    }
}
