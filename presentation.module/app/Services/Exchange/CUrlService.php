<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Services\Exchange;

class CUrlService
{
    protected $curl;
    protected $host;
    protected $headers;

    public function init($urlHost, $login = '', $pass = '', $headers = [])
    {
        $this->curl = @curl_init();
        $this->host = $urlHost;

        if ($login) {
            $this->setHeader('Authorization: Basic '.base64_encode($login.':'.$pass));
        }
        if ($headers) {
            $this->setHeader($headers);
        }
        return $this;
    }

    public function send($endpoint, $data, $method = 'GET', $isJson = false, $headers = [])
    {
        if ($data && !is_array($data)) {
            if (substr($data, 0, 1) == '{') {
                $isJson = true;
            }
        }

        if ($isJson) {
            $headers[] = 'Content-type: application/json';
        }

        $paramCUrl = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->host.$endpoint
        ];

        if ($headers) {
            $this->setHeader($headers);
        }

        $_headers = $this->getHeaders();
        if ($_headers) {
            $paramCUrl[CURLOPT_HEADER] = true;
            $paramCUrl[CURLOPT_HTTPHEADER] = $_headers;
        }

        switch ($method) {
            case 'POST':
                $paramCUrl[CURLOPT_POST] = true;
                $paramCUrl[CURLOPT_POSTFIELDS] = $data;
                break;
            case 'GET':
                if ($data) {
                    $paramCUrl[CURLOPT_URL] .= '?' . http_build_query($data);
                }
                break;
            default:
                $paramCUrl[CURLOPT_CUSTOMREQUEST] = $method;
                if ($data) {
                    $paramCUrl[CURLOPT_POSTFIELDS] = $isJson ? json_encode($data) : $data;
                }
        }

        curl_setopt_array($this->curl, $paramCUrl);
        $arResult = curl_exec($this->curl);


        $headerSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $body = substr($arResult, $headerSize);
        curl_close($this->curl);

        return json_decode($body, true);
    }

    protected function setHeader($header)
    {
        if (is_array($header)) {
            $this->headers = array_merge($this->headers, $header);
        } else {
            $this->headers[] = $header;
        }
    }

    protected function getHeaders()
    {
        return $this->headers;
    }
}
