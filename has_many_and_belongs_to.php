<?php

class Has_Many_And_Belongs_To extends \Laravel\Database\Eloquent\Relationships\Has_Many_And_Belongs_To {

	/**
	 * Sync the joining table with the array of given IDs.
	 *
	 * Add attributes on sync like attach.
	 *
	 * @param  array  $ids
	 * @return bool
	 */
	public function sync($ids, $attributes = array())
	{
		$current = $this->pivot()->lists($this->other_key());
		$ids = (array) $ids;

		// First we need to attach any of the associated models that are not currently
		// in the joining table. We'll spin through the given IDs, checking to see
		// if they exist in the array of current ones, and if not we insert.
		foreach ($ids as $id)
		{
			if ( ! in_array($id, $current))
			{
				$this->attach($id, $attributes);
			}
		}

		// Next we will take the difference of the current and given IDs and detach
		// all of the entities that exists in the current array but are not in
		// the array of IDs given to the method, finishing the sync.
		$detach = array_diff($current, $ids);

		if (count($detach) > 0)
		{
			$this->detach($detach);
		}
	}

}