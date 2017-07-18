<?php

use Robo\Tasks;

use Michelf\Markdown;
use Mustache_Engine as MustacheEngine;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class RoboFile extends Tasks
{
	/**
	 * @var array
	 */
	protected $contexts = [];

	/**
	 * Return a new MustacheEngine instance.
	 * 
	 * @return MustacheEngine
	 */
	protected function getRenderer($template)
	{
        return (new MustacheEngine())->loadTemplate($this->getTemplate($template));
	}

	/**
	 * Load layout.
	 * 
	 * @return string
	 */
	public function getTemplate($template)
	{
		return file_get_contents(sprintf('templates/%s.mustache', $template));
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
            'build/%s/index.html', 
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
    	// $this->buildIndex();
    }

    /**
	 * Compile assets.
	 * 
	 * @return void
     */
    public function compileAssets()
    {
    	$this
			->taskScss(['scss/index.scss' => sprintf('build/css/index.css')])
            ->importDir('scss/imports')
            ->run();
    }

    /**
     * Get directories.
     * 
     * @return array
     */
    protected function getPosts()
    {
    	return Finder::create()->directories()->in('posts');
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
    	foreach ($this->getPosts() as $post) {
    		$this
    	 		->taskWriteToFile($this->makePath($post))
     	 		->line($this->getRenderer('post')->render($this->getContext($post)))
		     	->run();
    	}

        $posts = [];

        foreach ($this->getPosts() as $post) {
            array_push($posts, $this->getContext($post));
        }

        $this
            ->taskWriteToFile('build/index.html')
            ->line($this->getRenderer('index')->render(['posts' => $posts]))
            ->run();
    }
} 
