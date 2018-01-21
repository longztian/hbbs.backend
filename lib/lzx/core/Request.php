<?php declare(strict_types=1);

namespace lzx\core;

use Zend\Diactoros\ServerRequestFactory;

class Request
{
    public $domain;
    public $ip;
    public $uri;
    public $referer;
    public $post;
    public $get;
    public $files;
    public $json;
    public $uid;
    public $timestamp;
    public $isRobot;
    private $req;

    private function __construct()
    {
        $this->req = ServerRequestFactory::fromGlobals();

        $params = $this->req->getServerParams();
        $this->domain = $params['SERVER_NAME'];
        $this->ip = $params['REMOTE_ADDR'];
        $this->uri = strtolower($params['REQUEST_URI']);

        $this->timestamp = (int) $params['REQUEST_TIME'];

        $this->post = self::escapeArray($this->req->getParsedBody());
        $this->get = self::escapeArray($this->req->getQueryParams());
        $this->files = self::getUploadFiles();
        $this->json = json_decode((string) $this->req->getBody(), true);

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

    private static function getUploadFiles(): array
    {
        $files = [];
        foreach ($_FILES as $input => $file) {
            $files[$input] = [];
            if (is_array($file['error'])) {
                foreach (array_keys($file['error']) as $i) {
                    foreach (array_keys($file) as $info) {
                        $files[$input][$i][$info] = $file[$info][$i];
                    }
                }
            } else {
                $files[$input][] = $file;
            }
        }

        return $files;
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
