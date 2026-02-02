<?php

namespace Casper\Rpc;

class HttpHandler implements Handler
{
    private string $url;

    private array $headers;

    public function __construct(string $url, array $headers = array())
    {
        $this->url = $url;
        $this->headers = $headers;
    }

    public function processCall(RpcRequest $params): RpcResponse
    {
        $curl = curl_init($this->url);

        if ($curl === false) {
            throw new \RuntimeException('Failed to initialize cURL');
        }

        $headers = [
            'Accept: application/json',
            'Content-type: application/json'
        ];

        foreach ($this->headers as $name => $value) {
            $headers[] = "$name: $value";
        }

        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params->toJson());

        $rawResponse = curl_exec($curl);

        // Handle cURL errors
        if ($rawResponse === false) {
            $error = curl_error($curl);
            throw new \RuntimeException('cURL request failed: ' . $error);
        }

        // Note: curl_close() is deprecated in PHP 8.0+ and no longer necessary
        // CurlHandle objects are automatically garbage collected

        // Ensure we have a valid string response
        if (!is_string($rawResponse)) {
            throw new \RuntimeException('Unexpected response type from cURL');
        }

        // Decode JSON response
        $decoded = json_decode($rawResponse, true);

        // Handle JSON decode errors or empty responses
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON response from RPC server: ' . $rawResponse);
        }

        return RpcResponse::fromArray($decoded);
    }
}
