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
            Log::error('Bulk import mixpanel error');
        }

        return $content;
    }
}
