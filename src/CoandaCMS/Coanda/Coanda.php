<?php namespace CoandaCMS\Coanda;

use App, Route, Config, Redirect, Request, Session;

use CoandaCMS\Coanda\Exceptions\PageTypeNotFound;
use CoandaCMS\Coanda\Exceptions\PageAttributeTypeNotFound;

class Coanda {

	private $user;

	private $modules = [];

	private $page_types = [];

	/**
	 * @param CoandaCMSCoandaAuthenticationUser $user [description]
	 */
	public function __construct(\CoandaCMS\Coanda\Authentication\UserInterface $user)
	{
		$this->user = $user;
	}

	/**
	 * Takes the path and prepends the current admin_path from the config
	 * @param  string $path
	 * @return string
	 */
	public static function adminUrl($path)
	{
		return url(Config::get('coanda::coanda.admin_path') . '/' . $path);
	}

	/**
	 * Checks to see if we have a user
	 * @return boolean
	 */
	public function isLoggedIn()
	{
		return $this->user->isLoggedIn();
	}

	/**
	 * Returns the current user
	 * @return boolean
	 */
	public function currentUser()
	{
		return $this->user->currentUser();
	}

	/**
	 * Get all the enabled modules from the config and boots them up. Also adds to modules array for future use.
	 */
	public function loadModules()
	{
		$enabled_modules = Config::get('coanda::coanda.enabled_modules');

		foreach ($enabled_modules as $enabled_module)
		{
			$module = new $enabled_module($this);
			$module->boot();

			$this->modules[] = $module;
		}
	}

	/**
	 * Creates all the required filters
	 * @return
	 */
	public function filters()
	{
		Route::filter('admin_auth', function()
		{
		    if (!App::make('coanda')->isLoggedIn())
		    {
		    	Session::put('pre_auth_path', Request::path());

		    	return Redirect::to('/' . Config::get('coanda::coanda.admin_path') . '/login');
		    }

		});
	}

	/**
	 * Outputs all the routes, including those added by modules
	 * @return 
	 */
	public function routes()
	{
		Route::group(array('prefix' => Config::get('coanda::coanda.admin_path')), function()
		{
			// All module admin routes should be wrapper in the auth filter
			Route::group(array('before' => 'admin_auth'), function()
			{
				// Load the pages controller
				Route::controller('pages', 'CoandaCMS\Coanda\Controllers\Admin\PagesAdminController');

				foreach ($this->modules as $module)
				{
					$module->adminRoutes();
				}
			});

			// We will put the main admin controller outside the group so it can handle its own filters
			Route::controller('/', 'CoandaCMS\Coanda\Controllers\AdminController');

		});

		// Let the module output any front end 'user' routes
		foreach ($this->modules as $module)
		{
			$module->userRoutes();
		}
	}

	/**
	 * Runs through all the bindings
	 * @param  Illuminate\Foundation\Application $app
	 */
	public function bindings($app)
	{
		$app->bind('CoandaCMS\Coanda\Authentication\UserInterface', 'CoandaCMS\Coanda\Authentication\Eloquent\EloquentUser');
		$app->bind('CoandaCMS\Coanda\Pages\Repositories\PageRepositoryInterface', 'CoandaCMS\Coanda\Pages\Repositories\Eloquent\EloquentPageRepository');

		// Let the module output any front end 'user' routes
		foreach ($this->modules as $module)
		{
			$module->bindings($app);
		}
	}

	/**
	 * Loads all the avaibla page types from the config
	 * @return void
	 */
	public function loadPageTypes()
	{
		$page_types = Config::get('coanda::coanda.page_types');

		foreach ($page_types as $page_type)
		{
			$type = new $page_type($this);

			// TODO: validate the definition to ensure all the specified page attribute types are available.

			$this->page_types[$type->identifier] = $type;
		}
	}

	/**
	 * Returns the available page types
	 * @return Array
	 */
	public function availablePageTypes()
	{
		return $this->page_types;
	}

	/**
	 * Gets a specific page type by identifier
	 * @param  string $type The identifier of the page type
	 * @return CoandaCMS\Coanda\Pages\PageTypeInterface
	 */
	public function getPageType($type)
	{
		if (array_key_exists($type, $this->page_types))
		{
			return $this->page_types[$type];
		}

		throw new PageTypeNotFound;
	}

	/**
	 * Loads the attributes from the config file
	 * @return void
	 */
	public function loadPageAttributeTypes()
	{
		$page_attribute_types = Config::get('coanda::coanda.page_attribute_types');

		foreach ($page_attribute_types as $page_attribute_type)
		{
			$attribute_type = new $page_attribute_type;

			$this->page_attribute_types[$attribute_type->identifier] = $attribute_type;
		}
	}

	/**
	 * Get a specific attribute by identifier
	 * @param  string $type_identifier
	 * @return CoandaCMS\Coanda\Pages\PageAttributeTypeInterface
	 */
	public function getPageAttributeType($type_identifier)
	{
		if (array_key_exists($type_identifier, $this->page_attribute_types))
		{
			return $this->page_attribute_types[$type_identifier];
		}

		throw new PageAttributeTypeNotFound;
	}
}