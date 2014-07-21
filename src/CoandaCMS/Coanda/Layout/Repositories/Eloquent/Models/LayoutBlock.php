<?php namespace CoandaCMS\Coanda\Layout\Repositories\Eloquent\Models;

use Eloquent, DB, Coanda;

class LayoutBlock extends Eloquent {

	protected $table = 'layoutblocks';

	protected $fillable = [
		'name',
		'type',
		'block_data'
	];

	private $cached_attributes;

	public function regionAssignments()
	{
		return $this->hasMany('CoandaCMS\Coanda\Layout\Repositories\Eloquent\Models\LayoutBlockRegionAssignment', 'block_id');
	}

	private function blockType()
	{
		return Coanda::layout()->blockType($this->type);
	}

	public function render()
	{
		return $this->blockType()->render([
				'block' => $this,
				'attributes' => $this->attributes()
			]);
	}

	public function getBlockTypeAttribute()
	{
		return $this->blockType();
	}

	public function attributes()
	{
		if (!$this->cached_attributes)
		{
			$definition = $this->blockType()->blueprint();
			$data = json_decode($this->block_data, true);

			if (!is_array($data))
			{
				$data = [];
			}

			foreach ($data as $identifier => $attribute_data)
			{
				$attribute_type = Coanda::getAttributeType($attribute_data['type_identifier']);

				$this->cached_attributes[$identifier] = new \stdClass;
				$this->cached_attributes[$identifier]->type = $attribute_type;
				$this->cached_attributes[$identifier]->identifier = $identifier;
				$this->cached_attributes[$identifier]->required = isset($definition[$identifier]['required']) ? $definition[$identifier]['required'] : false;
				$this->cached_attributes[$identifier]->name = $definition[$identifier]['name'];
				$this->cached_attributes[$identifier]->definition = $definition[$identifier];
				$this->cached_attributes[$identifier]->content = $attribute_type->data($attribute_data['content']);
			}
		}

		return $this->cached_attributes;
	}

	public function getAttributesAttribute()
	{
		return $this->attributes();
	}

	public function regionAssignmentsPaginated($per_page)
	{
		return $this->regionAssignments()->paginate($per_page);
	}
}