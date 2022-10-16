<?php

namespace Gibbon;

use Amp\Promise;
use Amp\File as AmpFile;
use Dice\Dice;
use Psr\Http\Message\UriInterface;

use function Amp\call;

class Gibbon
{
    public function __construct(protected Dice $container, protected $app_root)
    {
        $this->configuration = $this->getDefaultConf();
    }

    /**
     * @param UriInterface $uri
     * @return Promise<string>
     */
    public function handle(UriInterface $uri): Promise
    {
        return call(function () use ($uri) {
            $response = '404!!';

            $file = $this->app_root . $uri->getPath();
            if (yield AmpFile\exists($file)) {
                // now we add to our configuration the local configuration, if exists
                $configuration = $this->configuration;
                if ($local_conf = yield $this->getConfFile($file)) {
                    $configuration = array_replace(
                        $this->configuration,
                        $this->parseConfFile($local_conf)
                    );
                }

                // iterate over the rules and keep the matches
                $matchedRules = $this->matchRules($file, $configuration);

                $response = yield $this->render($file, $matchedRules);
            }
            return $response;
        });
    }

    protected function render(string $file, array $rules): Promise
    {
        return $this->runRules($file, $rules);
    }

    //---------------------------------------------------------------------------------
    // Matching and running rules on files
    //---------------------------------------------------------------------------------

    protected function matchRules(string $file, $configuration): array
    {
        // correct filename, if needed
        $file = basename($file);

        // run the rules and update matchedRules
        $matchedRules = [];
        foreach ($configuration as $pattern => $rule) {
            if (($pattern == '.') && (is_dir($file))) {
                $matchedRules[$pattern] = $rule;
            } else {
                if (preg_match('/' . $pattern . '/', $file) !== 0) {
                    $matchedRules[$pattern] = $rule;
                }
            }
        }
        return $matchedRules;
    }

    /**
     * Runs all matched rules for the file
     *
     * @param string $file
     * @param array $rules
     * @return Promise<string>
     */
    protected function runRules(string $file, array $rules): Promise
    {
        return call(function () use ($file, $rules) {
            $i = 0;
            foreach ($rules as $rule) {
                // here we explode the rule :)
                $parameters = explode(' ', $rule);

                // get the first element of the array
                $rendererClass = array_shift($parameters);

                $parameters[] = $this->app_root;    // little "hack" to pass parameter to renderer

                // here we try to get the renderer object
                // we also pass any parameters found in the rule directly to the
                // renderer constructor
                $renderer = $this->container
                    ->create('Gibbon\\Renderers\\' . $rendererClass, $parameters);

                // render this and return (needs change)
                if ($i == 0) {
                    $data = yield $renderer->render($file);
                } else {
                    $data = yield $renderer->render($file, isset($data) ? $data : null);
                }
                $i++;
            }

            return $data;
        });
    }

    //---------------------------------------------------------------------------------
    // Configuration files (.gibbon) related functions
    //---------------------------------------------------------------------------------

    // given a file or directory, this function will try to locate a gibbon conf file
    protected function getConfFile($file): Promise
    {
        return call(function () use ($file) {
            $isDirectory = yield AmpFile\isDirectory($file);
            $local_conf = $isDirectory
                ? $file . '.gibbon'
                : pathinfo($file, PATHINFO_DIRNAME) . '/.gibbon';

            $fileExists = yield AmpFile\exists($local_conf);
            return $fileExists ? $local_conf : false;
        });
    }

    protected function parseConfFile($file)
    {
        // need to register errors as exceptions
        // and also check maybe with include is better anyway

        return include $file;
    }

    protected function getDefaultConf()
    {
        return $this->parseConfFile($a = $this->app_root . '/../app/.gibbon');
    }
}
