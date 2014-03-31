<?php namespace CoandaCMS\Coanda\Users\Repositories\Eloquent\Models;

use Eloquent;

class UserGroup extends Eloquent {

	use \CoandaCMS\Coanda\Core\Presenters\PresentableTrait;

	protected $presenter = 'CoandaCMS\Coanda\Users\Presenters\UserGroup';

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'user_groups';

	public function users()
	{
		return $this->belongsToMany('CoandaCMS\Coanda\Users\Repositories\Eloquent\Models\User');
	}

	public function getAccessListAttribute()
	{
		return json_decode($this->permissions);
	}
}