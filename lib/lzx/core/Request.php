<?php declare(strict_types=1);

namespace lzx\core;

use Laminas\Diactoros\ServerRequestFactory;

class Request
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const URL_INVALID_CHAR = '%';

    public $domain;
    public $ip;
    public $method;
    public $uri;
    public $referer;
    public $data;
    public int $uid = 0;
    public $timestamp;
    public $agent;

    private $req;
    private $hasBadUrl;
    private $isRobot;

    private function __construct()
    {
        $this->req = ServerRequestFactory::fromGlobals();

        $params = $this->req->getServerParams();
        $this->domain = $params['SERVER_NAME'];
        $this->ip = $params['REMOTE_ADDR'];
        $this->method = $this->req->getMethod();
        $this->uri = strtolower($params['REQUEST_URI']);
        $this->timestamp = (int) $params['REQUEST_TIME'];
        $this->agent = $params['HTTP_USER_AGENT'];

        $this->hasBadUrl = false;
        if (!self::validateUrl($this->uri)) {
            $this->hasBadUrl = true;
            $this->data = [];
            $this->isRobot = true;
            return;
        }

        $this->data = self::escapeArray($this->req->getQueryParams());

        if (in_array($this->method, [self::METHOD_POST, self::METHOD_PUT, self::METHOD_PATCH])) {
            $contentType = strtolower(explode(';', (string) $this->req->getHeader('content-type')[0])[0]);
            switch ($contentType) {
                case 'application/x-www-form-urlencoded':
                case 'multipart/form-data':
                    if ($this->method === self::METHOD_POST) {
                        $data = $this->req->getParsedBody();
                    } else {
                        $data = [];
                        parse_str((string) $this->req->getBody(), $data);
                    }
                    $this->data = array_merge($this->data, self::escapeArray($data));
                    break;
                case 'application/json':
                    $this->data = array_merge($this->data, json_decode((string) $this->req->getBody(), true));
            }
        }

        $arr = explode($this->domain, $params['HTTP_REFERER']);
        $this->referer = sizeof($arr) > 1 ? $arr[1] : null;
        $this->isRobot = (bool) preg_match('/(http|yahoo|bot|spider)/i', $params['HTTP_USER_AGENT']);
    }

    public static function getInstance(): Request
    {
        static $instance;

        if (!isset($instance)) {
            $instance = new self();
        }
        return $instance;
    }

    public function isBad(): bool
    {
        return $this->hasBadUrl;
    }

    public function isRobot(): bool
    {
        return $this->uid === 0 && ($this->hasBadUrl || $this->isRobot);
    }

    public function isGoogleBot(): bool
    {
        $host = gethostbyaddr($this->ip);
        if (!$host) {
            return false;
        }

        $domain = implode('.', array_slice(explode('.', $host), -2));
        if (!in_array($domain, ['googlebot.com', 'google.com'])) {
            return false;
        }

        $ip = gethostbyname($host);
        if (!$ip || $ip !== $this->ip) {
            return false;
        }

        return true;
    }

    private static function validateUrl(string $url): bool
    {
        return strpos($url, self::URL_INVALID_CHAR) === false &&
            substr_count($url, '?') < 2;
    }

    private static function escapeArray(array $in): array
    {
        $out = [];
        foreach ($in as $key => $val) {
            $key = is_string($key)
                ? self::escapeString($key)
                : $key;
            $val = is_string($val)
                ? self::escapeString($val)
                : self::escapeArray($val);
            $out[$key] = $val;
        }
        return $out;
    }

    private static function escapeString(string $in): string
    {
        return trim(preg_replace('/<[^>]*>/', '', $in));
    }
}
