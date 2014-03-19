<?php namespace CoandaCMS\Coanda\Pages\Repositories;

interface PageRepositoryInterface {

	public function find($id);

	public function create($type, $user_id);

	public function getDraftVersion($page_id, $version);

	public function saveDraftVersion($version, $data);

}
