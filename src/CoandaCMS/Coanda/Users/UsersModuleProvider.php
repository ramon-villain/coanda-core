<?php namespace CoandaCMS\Coanda\Users;

use Route, App, Config;

/**
 * Class UsersModuleProvider
 * @package CoandaCMS\Coanda\Users
 */
class UsersModuleProvider implements \CoandaCMS\Coanda\CoandaModuleProvider {

    /**
     * @var string
     */
    public $name = 'users';

    /**
     * @param \CoandaCMS\Coanda\Coanda $coanda
     */
    public function boot(\CoandaCMS\Coanda\Coanda $coanda)
	{
		// Add the permissions
		$views = [
			'create',
			'edit',
			'remove',
		];

		$coanda->addModulePermissions('users', 'Users', $views);
	}

    /**
     *
     */
    public function adminRoutes()
	{
		// Load the users controller
		Route::controller('users', 'CoandaCMS\Coanda\Controllers\Admin\UsersAdminController');
	}

    /**
     *
     */
    public function userRoutes()
	{
	}

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    public function bindings(\Illuminate\Foundation\Application $app)
	{
		$app->bind('CoandaCMS\Coanda\Users\Repositories\UserRepositoryInterface', 'CoandaCMS\Coanda\Users\Repositories\Eloquent\EloquentUserRepository');
	}
}