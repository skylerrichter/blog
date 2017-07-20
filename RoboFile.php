<?php

use Robo\Tasks;

use Michelf\Markdown;
use Twig_Environment as TwigEnvironment;
use Twig_Loader_Filesystem as TwigLoaderFilesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class RoboFile extends Tasks
{
	/**
	 * Get renderer.
	 * 
	 * @return MustacheEngine
	 */
	protected function getRenderer()
	{
        // return (new MustacheEngine())->loadTemplate($this->getTemplate($template));
        return new TwigEnvironment(new TwigLoaderFilesystem(__DIR__ . '/src/templates'));
    }

	/**
	 * Make path.
	 * 
	 * @param $file
	 * @return string
	 */
	protected function makePath($path)
	{
        return sprintf('docs/%s/index.html', str_replace('src/', '', $path));
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
    	$this->buildContent(
            $this->getContext()
        );
    }

    /**
     * Watch.
     * 
     * @return void
     */
    public function watch()
    {
        $this
            ->taskWatch()
            ->monitor('src', function() {
                $this->build();
            })
            ->run();
    }

    /**
	 * Compile assets.
	 * 
	 * @return void
     */
    public function compileAssets()
    {
    	$this
			->taskScss(['src/scss/index.scss' => 'docs/css/index.css'])
            ->importDir('src/scss/imports')
            ->run();
    }

    /**
     * Get directories.
     * 
     * @return array
     */
    protected function getPosts()
    {
    	return iterator_to_array(Finder::create()->directories()->in('src/posts'));
    }

    /**
     * Get post.
     * 
     * @param $directory
     * @return array
     */
    public function getPost($directory)
    {
    	return [
    		'content' => $this->getContent(sprintf('%s/index.md', $directory->getPathname())),
    		'metadata' => $this->getMetadata(sprintf('%s/metadata.yml', $directory->getPathname()))
    	];
    }

    /**
     * Get context.
     * 
     * @return array
     */
    public function getContext()
    {
        return array_map([$this, 'getPost'], $this->getPosts());
    }

    /**
     * Build content.
     * 
     * @return void
     */
    public function buildContent($context)
    {
        $this->buildPages($context);
        $this->buildIndex($context);
    }

    /**
     * Build pages.
     * 
     * @return void
     */
    public function buildPages($context)
    {
    	foreach ($context as $path => $post) {
    		$this
    	 		->taskWriteToFile($this->makePath($path))
     	 		->line($this->getRenderer()->load('post.html')->render(['post' => $post]))
		     	->run();
    	}
    }

    /**
     * Build index.
     * 
     * @return void
     */
    public function buildIndex($context)
    {
        $this
            ->taskWriteToFile('docs/index.html')
            ->line($this->getRenderer()->load('index.html')->render(['posts' => $context]))
            ->run();
    }
} 
