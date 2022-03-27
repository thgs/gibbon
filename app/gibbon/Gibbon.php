<?php

namespace Gibbon;

use Dice\Dice;
use Psr\Http\Message\UriInterface;

class Gibbon
{
    public function __construct(protected Dice $container, protected $app_root)
    {
        $this->configuration = $this->getDefaultConf();
    }

    public function handle(UriInterface $uri): string
    {
        $data = '404!!';

        $file = $this->app_root . $uri->getPath();
        if (file_exists($file)) {
            // now we add to our configuration the local configuration, if exists
            $configuration = $this->configuration;
            if ($local_conf = $this->getConfFile($file)) {
                $configuration = array_replace(
                    $this->configuration,
                    $this->parseConfFile($local_conf)
                );
            }

            // iterate over the rules and keep the matches
            $matchedRules = $this->matchRules($file, $configuration);

            $data = $this->render($file, $matchedRules);
        }
        return $data;
    }

    //---------------------------------------------------------------------------------
    // Render
    //---------------------------------------------------------------------------------

    protected function render($file, $rules)
    {
        return $this->runRules($file, $rules);
    }

    //---------------------------------------------------------------------------------
    // Matching and running rules on files
    //---------------------------------------------------------------------------------

    protected function matchRules($file, $configuration): array
    {
        // correct filename, if needed
        $file = basename($file);

        // run the rules and update matchedRules
        $matchedRules = [];
        foreach ($configuration as $pattern => $rule) {
            if (($pattern == '.') && (is_dir($file))) {
                $matchedRules[$pattern] = $rule;
            } else {
                if (preg_match('/'.$pattern.'/', $file) !== 0) {
                    $matchedRules[$pattern] = $rule;
                }
            }
        }
        return $matchedRules;
    }

    // runs all matched rules for the file
    protected function runRules($file, $rules)
    {
        $i = 0;
        foreach ($rules as $rule) {
            // here we explode the rule :)
            $parameters = explode(' ', $rule);

            // get the first element of the array
            $rendererClass = array_shift($parameters);

            // here we try to get the renderer object
            // we also pass any parameters found in the rule directly to the
            // renderer constructor
            $renderer = $this->container
                ->create('Gibbon\\Renderers\\'.$rendererClass, $parameters);

            // render this and return (needs change)
            if ($i == 0) {
                $data = $renderer->render($file);
            } else {
                $data = $renderer->render($file, $data);
            }
            $i++;
        }

        return $data;
    }

    //---------------------------------------------------------------------------------
    // Configuration files (.gibbon) related functions
    //---------------------------------------------------------------------------------

    // given a file or directory, this function will try to locate a gibbon conf file
    protected function getConfFile($file)
    {
        $local_conf = is_dir($file)
            ? $file.'.gibbon'
            : pathinfo($file, PATHINFO_DIRNAME).'/.gibbon';

        return file_exists($local_conf) ? $local_conf : false;
    }

    protected function parseConfFile($file)
    {
        // need to register errors as exceptions
        // and also check maybe with include is better anyway

        return include $file;
    }

    protected function getDefaultConf()
    {
        return $this->parseConfFile($a = $this->app_root.'/../app/.gibbon');
    }
}