<?php namespace CoandaCMS\Coanda\Urls\Repositories\Eloquent;

use DB;
use CoandaCMS\Coanda\Urls\Repositories\Eloquent\Models\RedirectUrl;
use CoandaCMS\Coanda\Urls\Repositories\Eloquent\Models\Url;
use CoandaCMS\Coanda\Urls\Repositories\UrlRepositoryInterface;
use CoandaCMS\Coanda\Urls\Slugifier;
use CoandaCMS\Coanda\Urls\Exceptions\UrlAlreadyExists;
use CoandaCMS\Coanda\Urls\Exceptions\InvalidSlug;
use CoandaCMS\Coanda\Urls\Exceptions\UrlNotFound;
use CoandaCMS\Coanda\Exceptions\ValidationException;

/**
 * Class EloquentUrlRepository
 * @package CoandaCMS\Coanda\Urls\Repositories\Eloquent
 */
class EloquentUrlRepository implements UrlRepositoryInterface {

    /**
     * @var Url
     */
    private $model;
    /**
     * @var Slugifier
     */
    private $slugifier;

    /**
     * @var RedirectUrl
     */
    private $redirecturl_model;

    /**
     * @param Url $model
     * @param RedirectUrl $redirecturl_model
     * @param Slugifier $slugifier
     */
    public function __construct(Url $model, RedirectUrl $redirecturl_model, Slugifier $slugifier)
	{
		$this->model = $model;
		$this->slugifier = $slugifier;
		$this->redirecturl_model = $redirecturl_model;
	}

    /**
     * @param $for
     * @param $for_id
     * @return mixed
     */
    public function findFor($for, $for_id)
	{
		return $this->model->whereType($for)->whereTypeId($for_id)->first();
	}

    /**
     * @param  integer $id
     * @throws \CoandaCMS\Coanda\Urls\Exceptions\UrlNotFound
     * @return Array
     */
	public function findById($id)
	{
		$url = $this->model->find($id);

		if (!$url)
		{
			throw new UrlNotFound('Url #' . $id . ' not found');
		}
		
		return $url;
	}

    /**
     * @param $slug
     * @throws \CoandaCMS\Coanda\Urls\Exceptions\UrlNotFound
     * @internal param int $id
     * @return Array
     */
	public function findBySlug($slug)
	{
		// Can we match this slug directly?
		$url = $this->model->whereSlug($slug)->first();

		if ($url)
		{
			return $url;
		}

		// Lets see if we can find a wildcard....
		if (strpos($slug, '/'))
		{
			$slug_parts = explode('/', $slug);

			$wildcard_slug = '';

			foreach ($slug_parts as $slug_part)
			{
				$wildcard_slug .= ($wildcard_slug !== '' ? '/' : '') . $slug_part;

				$url = $this->model->whereSlug($wildcard_slug)->whereType('wildcard')->first();

				if ($url)
				{
					return $url;
				}
			}			
		}

		throw new UrlNotFound('Url for /' . $slug . ' not found');
	}

    /**
     * @param $slug
     * @param $for
     * @param $for_id
     * @return bool
     * @throws \CoandaCMS\Coanda\Urls\Exceptions\UrlAlreadyExists
     * @throws \CoandaCMS\Coanda\Urls\Exceptions\InvalidSlug
     */
    public function register($slug, $for, $for_id)
	{
		// Is this a valid slug?
		if (!$this->slugifier->validate($slug))
		{
			throw new InvalidSlug('The requested slug is not valid: '. $slug);
		}

		// do we already have a record for this slug?
		$existing = $this->model->whereSlug($slug)->first();

		if ($existing)
		{
			// If the existing url matches the type and id, then we don't need to do anything..
			if ($existing->type == $for && $existing->type_id == $for_id)
			{
				return $existing;
			}

			// If the existing one is a url, then we can overwrite it, otherwise it is alreay taken.
			if ($existing->type !== 'wildcard')
			{
				throw new UrlAlreadyExists('The requested URL: ' . $slug . ' is already in use.');
			}
		}

		// Do we have a record for this type and type_id
		$current_url = $this->model->whereType($for)->whereTypeId($for_id)->first();

		$url = $existing ? $existing : false;

		// If we don't have a URL, then create a new one
		if (!$url)
		{
			$url = new $this->model;
			$url->slug = $slug;
		}

		$url->type = $for;
		$url->type_id = $for_id;

		$url->save();

		// If we have an existing url, then set it as a 'redirect' to the new url object
		if ($current_url)
		{
			// Update any child URL's to have the new slug
			$this->updateSubTree($current_url->slug, $slug);

			$current_url->type = 'wildcard';
			$current_url->type_id = $url->id;
			$current_url->save();
		}

		return $url;
	}

    /**
     * @param $slug
     * @param $new_slug
     */
    private function updateSubTree($slug, $new_slug)
	{
		$this->model->where('slug', 'like', $slug . '/%')->update(['slug' => DB::raw("REPLACE(slug, '" . $slug . "/', '" . $new_slug . "/')")]);
	}

    /**
     * @param $for
     * @param $for_id
     * @return mixed|void
     */
    public function delete($for, $for_id)
	{
		$url = $this->model->whereType($for)->whereTypeId($for_id)->first();

		if ($url)
		{
			$url->delete();
		}
	}

    /**
     * @param $slug
     * @param $for
     * @param $for_id
     * @return bool
     * @throws \CoandaCMS\Coanda\Urls\Exceptions\UrlAlreadyExists
     * @throws \CoandaCMS\Coanda\Urls\Exceptions\InvalidSlug
     */
    public function canUse($slug, $for, $for_id = false)
	{
		$slug = trim($slug, '/');

		if (!$this->slugifier->validate($slug))
		{
			throw new InvalidSlug('The slug is not valid');
		}

		// do we already have a record for this slug?
		$existing = $this->model->whereSlug($slug)->first();

		if ($existing)
		{
			if ($for_id)
			{
				// If the existing matches the type and id, then we can use it
				if ($existing->type == $for && $existing->type_id == $for_id)
				{
					return true;
				}
			}

			// If the existing type is a url, then it can be overwritten (otherwise this would be 'reserved' forever)
			if ($existing->type == 'wildcard')
			{
				return true;
			}

			return false;
		}

		return true;
	}

    /**
     * @param $per_page
     * @return mixed
     */
    public function getList($per_page)
	{
		return $this->model->orderBy('created_at', 'desc')->paginate($per_page);
	}

    /**
     * @param $type
     * @param $per_page
     * @return mixed
     */
    public function getListByType($type, $per_page)
	{
		return $this->model->whereType($type)->orderBy('created_at', 'desc')->paginate($per_page);
	}


    /**
     * @param $from
     * @param $to
     * @param string $redirect_type
     * @throws \CoandaCMS\Coanda\Exceptions\ValidationException
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function addRedirect($from, $to, $redirect_type = 'temp')
	{
		$from = trim($from, '/');

		// Can we use this URL?
		if ($this->canUse($from, 'redirecturl'))
		{
			$to = trim($to, '/');

			if ($to == '')
			{
				throw new ValidationException(['to' => 'Please specify a to url']);
			}

			$url_data = [
				'destination' => $to,
				'redirect_type' => $redirect_type
			];

			$redirect_url = $this->redirecturl_model->create($url_data);

			$this->register($from, 'redirecturl', $redirect_url->id);

			return $redirect_url;			
		}
	}

    /**
     * @param $id
     * @return RedirectUrl
     */
    public function getRedirectUrl($id)
	{
		return $this->redirecturl_model->find($id);
	}

    /**
     * @param $per_page
     * @return mixed
     */
    public function getRedirectUrls($per_page)
	{
		return $this->redirecturl_model->where('redirect_type', '=', 'temp')->orderBy('created_at', 'desc')->paginate($per_page);
	}

    /**
     * @param $id
     */
    public function removeRedirectUrl($id)
	{
		$redirect_url = $this->getRedirectUrl($id);

		if ($redirect_url)
		{
			// Delete the URL..
			$this->delete('redirecturl', $redirect_url->id);

			// Now delete the model..
            $redirect_url->delete();
		}
	}
}