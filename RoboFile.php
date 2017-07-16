<?php

use Robo\Tasks;

use Mustache_Engine as MustacheEngine;
use Symfony\Component\Finder\Finder;
use Michelf\Markdown;

class RoboFile extends Tasks
{
	/**
	 * Description.
	 * 
	 * @return MustacheEngine
	 */
	protected function getLayout()
	{
		return (new MustacheEngine())->loadTemplate('{{ post }}');
	}

	/**
	 * Description.
	 * 
	 * @return string
	 */
	protected function makePath($file)
	{
		return sprintf('build/posts/%shtml', $file->getBasename($file->getExtension()));
	}

    /**
	 * Description.
	 * 
	 * @return void
     */
    public function build()
    {
    	$this->compileAssets();

    	$files = Finder::create()->files()->name('*.md')->in('posts');

    	foreach ($files as $file) {
    		$this
    			->taskWriteToFile($this->makePath($file))
     			->line($this->getLayout()->render(['post' => 'World!']))
		    	->run();
    	}
    }

    /**
	 * Description.
	 * 
	 * @return void
     */
    public function compileAssets()
    {
    	$this
			->taskScss(['scss/index.scss' => 'build/css/index.css'])
            ->importDir('scss/imports')
            ->run();
    }
} 
