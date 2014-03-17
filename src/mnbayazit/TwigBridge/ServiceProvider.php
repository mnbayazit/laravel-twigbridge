<?php namespace mnbayazit\TwigBridge;

use mnbayazit\TwigBridge\Console\ClearCommand;
use mnbayazit\TwigBridge\Extension\AuthExtension;
use mnbayazit\TwigBridge\Extension\ConfigExtension;
use mnbayazit\TwigBridge\Extension\FacadeExtension;
use mnbayazit\TwigBridge\Extension\FormExtension;
use mnbayazit\TwigBridge\Extension\HelperExtension;
use mnbayazit\TwigBridge\Extension\HtmlExtension;
use mnbayazit\TwigBridge\Extension\SessionExtension;
use mnbayazit\TwigBridge\Extension\StringExtension;
use mnbayazit\TwigBridge\Extension\TranslatorExtension;
use mnbayazit\TwigBridge\Extension\UrlExtension;
use mnbayazit\TwigBridge\Loader\ChainLoader;

/**
 * Twig integration for Laravel 4
 *
 * Based on Twig integration for Silex by Fabien Potencier <fabien@symfony.com>
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

        $app = $this->app;
        $app['twig.options'] = array();
        $app['twig.form.templates'] = array();
        $app['twig.path'] = $app->share(function($app){
            return $app['view']->getFinder()->getPaths();
        });
        $app['twig.templates'] = array();

        $app['twig'] = $app->share(function ($app) {
                $app['twig.options'] = array_replace(
                    array(
                        'debug' => $app['config']['app.debug'],
                        'cache' => $app['path.storage'].'/views/twig',
                        'base_template_class' => 'mnbayazit\TwigBridge\TwigTemplate',
                    ),
                    $app['config']->get('laravel-twigbridge::config.options', array()),
                    $app['twig.options']
                );

                $twig = new \Twig_Environment($app['twig.loader'], $app['twig.options']);
                
                foreach ($app['view']->getShared() as $key => $value)
                {
                    $twig->addGlobal($key, $value);
                }

                if ( $app['twig.options']['debug']) {
                    $twig->addExtension(new \Twig_Extension_Debug());
                }

                $defaultOptions = $app['config']->get('laravel-twigbridge::default_options', array());
                $facades = $app['config']->get('laravel-twigbridge::facades', array());
                $functions = $app['config']->get('laravel-twigbridge::functions', array());
                $filters = $app['config']->get('laravel-twigbridge::filters', array());
                
                $twig->addExtension(new AuthExtension($app['auth']));
                $twig->addExtension(new ConfigExtension($app['config']));
                $twig->addExtension(new FacadeExtension($facades));
                $twig->addExtension(new FormExtension($app['form']));
                $twig->addExtension(new HelperExtension($functions, $filters, $defaultOptions));
                $twig->addExtension(new HtmlExtension($app['html']));
                $twig->addExtension(new UrlExtension($app['url']));
                $twig->addExtension(new SessionExtension($app['session']));
                $twig->addExtension(new StringExtension());
                $twig->addExtension(new TranslatorExtension($app['translator']));

                return $twig;
            });

        $app['twig.loader.viewfinder'] = $app->share(function ($app) {
                $extension = $app['config']->get('laravel-twigbridge::config.extension', 'twig');
                return new Loader\ViewfinderLoader($app['view']->getFinder(), $app['files'], $extension);
            });

        $app['twig.loader.filesystem'] = $app->share(function ($app) {
                return new \Twig_Loader_Filesystem($app['twig.path']);
            });

        $app['twig.loader.array'] = $app->share(function ($app) {
                return new \Twig_Loader_Array($app['twig.templates']);
            });

        $app['twig.loader'] = $app->share(function ($app) {
                return new ChainLoader(array(
                    $app['twig.loader.array'],
                    $app['twig.loader.viewfinder'],
                    $app['twig.loader.filesystem'],
                ));
            });

        $this->registerCommands();
	}

    /**
     * Register the cache related console commands.
     *
     * @return void
     */
    public function registerCommands()
    {
        $commands = array('command.twig.clear');
        $this->app['command.twig.clear'] = $this->app->share(
            function ($app) {
                return new ClearCommand($app['twig'], $app['files']);
            }
        );

        if(class_exists('Symfony\Bridge\Twig\Command\LintCommand')){
            $commands[] = 'command.twig.lint';
            $this->app['command.twig.lint'] = $this->app->share(
                function ($app) {
                    $lintCommand =  new \Symfony\Bridge\Twig\Command\LintCommand('twig:lint');
                    $lintCommand->setTwigEnvironment($app['twig']);
                    return $lintCommand;
                }
            );
        }

        $this->commands($commands);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'twig',
            'twig.form.templates', 'twig.path', 'twig.templates', 'twig.options',
            'twig.loader', 'twig.loader.viewfinder', 'twig.loader.array', 'twig.loader.filesystem',
            'command.twig.clear', 'command.twig.lint'
        );
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(){

        $app = $this->app;

        $this->package('mnbayazit/laravel-twigbridge');
        $extension = $app['config']->get('laravel-twigbridge::config.extension', 'twig');

        // Register the view engine:
        $app['view']->addExtension($extension, 'twig', function () use ($app)
        {
            return new TwigEngine($app['twig']);
        });
    }

}
