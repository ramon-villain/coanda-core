<?php namespace CoandaCMS\Coanda\Pages\Repositories;

interface PageRepositoryInterface {

	public function find($id);

	public function topLevel();

	public function create($type, $user_id, $parent_page_id);

	public function getDraftVersion($page_id, $version);

	public function getVersionByPreviewKey($preview_key);

	public function saveDraftVersion($version, $data);

	public function discardDraftVersion($version);

	public function publishVersion($version);

	public function createNewVersion($page_id, $user_id);

	public function history($page_id);

}
