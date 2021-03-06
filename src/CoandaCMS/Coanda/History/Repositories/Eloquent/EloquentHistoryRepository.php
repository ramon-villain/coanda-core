<?php namespace CoandaCMS\Coanda\History\Repositories\Eloquent;

use CoandaCMS\Coanda\History\Repositories\HistoryRepositoryInterface;
use CoandaCMS\Coanda\History\Repositories\Eloquent\Models\History as HistoryModel;
use CoandaCMS\Coanda\Users\UserManager;

/**
 * Class EloquentHistoryRepository
 * @package CoandaCMS\Coanda\History\Repositories\Eloquent
 */
class EloquentHistoryRepository implements HistoryRepositoryInterface {

    /**
     * @var Models\History
     */
    private $model;

    private $user_manager;

    /**
     * @param HistoryModel $model
     * @param UserManager $user_manager
     */
    public function __construct(HistoryModel $model, UserManager $user_manager)
	{
		$this->model = $model;
        $this->user_manager = $user_manager;
	}

    /**
     * Adds a new history record
     * @param string $for
     * @param integer $for_id
     * @param integer $user_id
     * @param $action
     * @param mixed $data
     * @return mixed
     */
	public function add($for, $for_id, $user_id, $action, $data = '')
	{
		$history = new $this->model;
		$history->for = $for;
		$history->for_id = $for_id;
		$history->user_id = $user_id;
		$history->action = $action;
		$history->data = is_array($data) ? json_encode($data) : $data;

		$history->save();

		return $history;
	}

    /**
     * Returns all the history for a specified for and for_id
     * @param  string $for
     * @param  integer $for_id
     * @param bool $limit
     * @return mixed
     */
	public function get($for, $for_id, $limit = false)
	{
		return $this->model->whereFor($for)->whereForId($for_id)->orderBy('created_at', 'desc')->take($limit)->get();
	}

    /**
     * @param $for
     * @param $for_id
     * @param int $limit
     * @return mixed
     */
    public function getPaginated($for, $for_id, $limit = 10)
	{
		return $this->model->whereFor($for)->whereForId($for_id)->orderBy('created_at', 'desc')->paginate($limit);
	}

    /**
     * @param $for
     * @param $for_id
     * @return mixed
     */
    public function users($for, $for_id)
	{
		return $this->user_manager->getByIds($this->model->whereFor($for)->whereForId($for_id)->groupBy('user_id')->lists('user_id'));
	}

}