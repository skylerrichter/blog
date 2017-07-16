<?php

use Robo\Tasks;

use Mustache_Engine as MustacheEngine;
use Symfony\Component\Finder\Finder;
use Michelf\Markdown;

class RoboFile extends Tasks
{
	/**
	 * @var string
	 */
	protected $buildPath = 'build';

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
	 * Load the layout.
	 * 
	 * @return string
	 */
	public function loadLayout()
	{
		return file_get_contents($this->layoutPath);
	}

	/**
	 * Make content path.
	 * 
	 * @return string
	 */
	protected function makePath($file)
	{
		return sprintf(
			'%s/%s/%s.html', 
			$this->buildPath, 
			$this->contentPath, 
			$file->getFilename()
		);
	}

	/**
	 * Run getContents threw Markdown.
	 * 
	 * @return string
	 */
	public function markdownFileContents($file)
	{
		return Markdown::defaultTransform($file->getContents());
	}

    /**
	 * Compile website.
	 * 
	 * @return void
     */
    public function build()
    {
    	$this->compileAssets();

    	$files = Finder::create()->files()->name('*.md')->in($this->contentPath);

    	foreach ($files as $file) {
    		$this
    			->taskWriteToFile($this->makePath($file))
     			->line($this->getLayout()->render(['content' => $this->markdownFileContents($file)]))
		    	->run();
    	}
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
} 
