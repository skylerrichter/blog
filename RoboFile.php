<?php

use Michelf\Markdown;
use Robo\Tasks;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;
use Twig_Environment as TwigEnvironment;
use Twig_Loader_Filesystem as TwigLoaderFilesystem;

class RoboFile extends Tasks
{
	/**
	 * Create a new Twig instance.
	 * TODO: Make this a factory method so we only create one Twig instance per build.
     * 
	 * @return TwigEnvironment
	 */
	protected function getRenderer()
	{
        return new TwigEnvironment(new TwigLoaderFilesystem(__DIR__ . '/src/templates'));
    }

	/**
	 * Returns the write path for a post.
     * TODO: Find a better way to deal with the src/ fragment.
     * 
	 * @param $file
	 * @return string
	 */
	protected function makePath($path)
	{
        return sprintf('docs/%s/index.html', str_replace('src/', '', $path));
	}

	/**
	 * Get the content for a post and transform it with Markdown.
	 * 
	 * @param $file
	 * @return string
	 */
	public function getContent($file)
	{
		return Markdown::defaultTransform(file_get_contents($file));
	}

	/**
	 * Get the metadata for a post and YAML parse it.
	 * 
	 * @param $file string
	 * @return array
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
     * Serve.
     * TODO: Start a static development server.
     * 
     * @return void
     */
    public function serve()
    {
        //
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
     * Find all the raw posts.
     * 
     * @return array
     */
    protected function getPosts()
    {
    	return iterator_to_array(Finder::create()->directories()->in('src/posts'));
    }

    /**
     * Fetch the content and metadata for a post.
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
     * TODO: Prevent the src/ fragment from showing up in templates.
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
