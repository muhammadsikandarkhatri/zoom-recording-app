<?php


namespace App\Services;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class ZoomIntegrationService
{
    protected $apiUrl;
    protected $apiKey;
    protected $apiSecret;
    protected $accountKey;
    protected $client;
    protected $curl;

    public function __construct()
    {
        $this->setConfig();
        $this->client = new Client(['base_uri' => $this->apiUrl]);
    }

    /**
     * Set configurations
     */
    protected function setConfig()
    {
        $this->apiUrl = config('services.zoom.url');
        $this->apiKey = config('services.zoom.key');
        $this->apiSecret = config('services.zoom.secret');
        $this->accountKey = config('services.zoom.account_key');
    }

    /***
     * @return array
     * Api headers
     */
    protected function headers()
    {
        return [
            "authorization" => "Bearer {$this->token()}",
            "content-type" => "application/json",
        ];
    }

    /**
     * @return string
     * Access Token generating for api authentication
     */
    protected function token()
    {
        return JWT::encode([
            "iss" => $this->apiKey,
            "exp" => time() + (60 * 60 * 24 * 7),
        ], $this->apiSecret, 'HS256');
    }

    /**
     * @param $res
     * @param string $type
     * @return \Illuminate\Support\Collection
     * Send response to the user
     */
    public function response($res, $type = '')
    {
        // Set Initial Response Data
        $data = ['code' => 200, 'message' => '', 'data' => []];

        // Incase of error
        if ($type == 'e') {
            if ($res->getCode() === 401) {
                $data['code'] = $res->getCode();
                $data['message'] = 'Not Authenticated to the Api';
            }
        }

        // Return data collection
        $data['code'] = $res->getStatusCode();
        $data['message'] = '';
        $data['data'] = json_decode($res->getBody()->getContents(), true);
        return collect($data);
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * Get all the recordings
     */
    public function getRecordings()
    {
        return $this->client->request("GET", "/v2/users/{$this->accountKey}/recordings?trash_type=meeting_recordings&to=2020-08-07&from=2020-08-01&mc=false&page_size=30", [
            'headers' => $this->headers(),
        ]);
    }

    /**
     * @param $recording
     * Download Recording into local disk
     */
    public function downloadRecording($recording)
    {

        $url = $recording['download_url'];
        $filename = "{$recording['id']}.{$recording['file_type']}";

        $tmpFile = tempnam(sys_get_temp_dir(), 'guzzle-download');
        $handle = fopen($tmpFile, 'w');

        $client = new Client(array(
            'base_uri' => '',
            'verify' => false,
            'sink' => storage_path('app/' . Storage::putFileAs('recordings', new File($tmpFile), $filename)),
            'curl.options' => array(
                'CURLOPT_RETURNTRANSFER' => true,
                'CURLOPT_FILE' => $handle
            )
        ));

        $client->get($url, ['query' => [
            'access_token' => $this->token()
        ]]);

        fclose($handle);
    }

    public function uploadRecordingToCloud($recording)
    {
        $filename = "{$recording['id']}.{$recording['file_type']}";
        $file_exists = Storage::disk('do_spaces')->exists($filename);
        if (!$file_exists) {
            return Storage::disk('do_spaces')
                ->putFileAs('recordings',
                    new File(storage_path('app/recordings/' . $filename)), $filename);
        }
    }

}
