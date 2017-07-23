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
     * Build index.
     * TODO: Prevent the src/ fragment from showing up in templates.
     * 
     * @return void
     */
    public function buildIndex($posts)
    {
        $this
            ->taskWriteToFile('docs/index.html')
            ->line($this->getRenderer()->load('index.html')->render([
                'posts' => $posts
            ]))
            ->run();
    }

    /**
     * Build pages.
     * 
     * @return void
     */
    public function buildPages($posts)
    {
        foreach ($posts as $path => $post) {
            $this
                ->taskWriteToFile($this->makePath($path))
                ->line($this->getRenderer()->load('post.html')->render([
                    'post' => $post
                ]))
                ->run();
        }
    }

    /**
     * Build content.
     * 
     * @return void
     */
    public function buildContent($posts)
    {
        $this->buildPages($posts);
        $this->buildIndex($posts);
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
     * Find all the raw posts.
     * 
     * @return array
     */
    protected function findRaw()
    {
        return iterator_to_array(Finder::create()->directories()->in('src/posts'));
    }

    /**
     * Get context.
     * 
     * @return array
     */
    public function getPosts()
    {
        return array_map([$this, 'getPost'], $this->findRaw());
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
     * Build.
     * 
     * @return void
     */
    public function build()
    {
        $this->compileAssets();
        $this->buildContent(
            $this->getPosts()
        );
    }

    /**
     * Watch.
     * 
     * @return void
     */
    public function watch()
    {
        $this->build();

        $this
            ->taskWatch()
            ->monitor('src', function() {
                $this->build();
            })
            ->run();
    }

    /**
     * Serve.
     * 
     * @return void
     */
    public function serve()
    {
        $this
            ->taskServer(8000)
            ->dir('docs')
            ->run();
    }
} 
