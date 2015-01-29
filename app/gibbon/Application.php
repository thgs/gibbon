<?php namespace Gibbon;

# class Application is responsible for 

use Dice\Dice;

class Application {
    
    protected $request;
    protected $configuration;
    protected $matchedRules;
    
    protected $app_root;
    
    protected $container;
    
    public function __construct(Request $request, Dice $container)
    {
        $this->request = $request;
        $this->container = $container;
        
        $this->app_root = $this->request->app_root;     # dont use request here
    }
    
    public function run()
    {
        // we construct the real filename
        $file = $this->request->public_root . $this->request->request_uri;

        // we check if file exists here
        if (file_exists($file))
        {
            // first, we load the default configuration
            $this->configuration = $this->getDefaultConf();
            
            // now we add to our configuration the local configuration, if exists
            if ($local_conf = $this->getConfFile($file))
            {
                $this->configuration = array_replace(
                    $this->configuration, $this->parseConfFile($local_conf));
            }
                        
            /* old code
            // check for configuration file
            $this->configuration = ($local_conf = $this->getConfFile($file))
                ? $this->parseConfFile($local_conf)
                : $this->getDefaultConf();
             */
            
            // iterate over the rules and keep the matches
            $this->matchRules($file);
            
            // render the bloody file
            $data = $this->render($file);            
        }
        
        // if file doesnt exist at all
        else
        {
            // throw a 404
            $data = '404!!';
        }
        
        // display the data
        echo $data;
    }

    
    //---------------------------------------------------------------------------------
    // Render
    //---------------------------------------------------------------------------------
    
    
    protected function render($file)
    {
        return $this->runRules($file);
    }

    
    
    //---------------------------------------------------------------------------------
    // Matching and running rules on files
    //---------------------------------------------------------------------------------
    
    protected function matchRules($file)
    {
        // correct filename, if needed
        $file = basename($file);
        
        // run the rules and update matchedRules
        foreach ($this->configuration as $pattern => $rule)
        {

            if (($pattern == '.') && (is_dir($file))) 
            {
                $this->matchedRules[$pattern] = $rule;
            } 
            else 
            {
                if (preg_match('/' . $pattern . '/', $file) !== 0) 
                {
                    $this->matchedRules[$pattern] = $rule;
                }
            }
        }
        
    } # end func matchRules
    
    
    
    // runs all matched rules for the file
    protected function runRules($file)
    {
        $i = 0;
        foreach ($this->matchedRules as $rule)
        {
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
            if ($i == 0)    $data = $renderer->render($file);
            else            $data = $renderer->render($file, $data);
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
                
        return file_exists($local_conf) ? $local_conf : FALSE;
    }

    protected function parseConfFile($file)
    {
        # need to register errors as exceptions
        # and also check maybe with include is better anyway
        
        return include($file);
    }

    protected function getDefaultConf()
    {
        return $this->parseConfFile($this->request->app_root . '/app/.gibbon');
    }

    
}