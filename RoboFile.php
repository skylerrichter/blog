<?php

use Robo\Tasks;

use Michelf\Markdown;
use Mustache_Engine as MustacheEngine;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class RoboFile extends Tasks
{
	/**
	 * @var string
	 */
	protected $buildPath = 'docs';

	/**
	 * @var string
	 */
	protected $contentPath = 'posts';

	/**
	 * @var string
	 */
	protected $layoutPath = 'templates/layout.mustache';

	/**
	 * Return a new MustacheEngine instance.
	 * 
	 * @return MustacheEngine
	 */
	protected function getLayout()
	{
        return (new MustacheEngine())->loadTemplate($this->loadLayout());
	}

	/**
	 * Load layout.
	 * 
	 * @return string
	 */
	public function loadLayout()
	{
	   return file_get_contents($this->layoutPath);
	}

	/**
	 * Make path.
	 * 
	 * @param $file
	 * @return string
	 */
	protected function makePath($directory)
	{
        return sprintf(
            '%s/%s/index.html', 
            $this->buildPath, 
            $directory->getPathname()
        );
	}

	/**
	 * Get content.
	 * 
	 * @param $file
	 * @return string
	 */
	public function getContent($file)
	{
		return Markdown::defaultTransform(file_get_contents($file));
	}

	/**
	 * Get metadata.
	 * 
	 * @param $file string
	 * @return \Symfony\Component\Yaml
	 */
	public function getMetadata($file)
	{
		return Yaml::parse(file_get_contents($file));
	}

    /**
	 * Build.
	 * 
	 * @return void
     */
    public function build()
    {
    	$this->compileAssets();
    	$this->processContent();
    }

    /**
	 * Compile assets.
	 * 
	 * @return void
     */
    public function compileAssets()
    {
    	$this
			->taskScss(['scss/index.scss' => sprintf('%s/css/index.css', $this->buildPath)])
            ->importDir('scss/imports')
            ->run();
    }

    /**
     * Get directories.
     * 
     * @return array
     */
    protected function getDirectories()
    {
    	return Finder::create()->directories()->in($this->contentPath);
    }

    /**
     * Get context.
     * 
     * @param $directory
     * @return array
     */
    public function getContext($directory)
    {
    	return [
    		'content' => $this->getContent(sprintf('%s/index.md', $directory->getPathname())),
    		'metadata' => $this->getMetadata(sprintf('%s/metadata.yml', $directory->getPathname()))
    	];
    }

    /**
     * Process content.
     * 
     * @return void
     */
    public function processContent()
    {
    	foreach ($this->getDirectories() as $directory) {
    		$this
    	 		->taskWriteToFile($this->makePath($directory))
     	 		->line($this->getLayout()->render($this->getContext($directory)))
		     	->run();
    	}	
    }
} 
