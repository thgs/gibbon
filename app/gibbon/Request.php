<?php

namespace Gibbon;

class Request
{
    public $app_root;
    public $request_uri;
    public $public_root;

    public function __construct()
    {
        // Here we will initialize request uri,
        // by removing the $_SERVER['PHP_SELF'] part of the URI
        $this->request_uri = str_replace(
            pathinfo($_SERVER['PHP_SELF'], PATHINFO_DIRNAME), '',
            $_SERVER['REQUEST_URI']);

        // we also set the path to our base dir (not the app directory)
        $this->app_root = dirname(realpath(dirname(__FILE__).'/..'));

        // and the public directory
        $this->public_root = $this->app_root.'/content';
    }
}
