<?php

namespace themroc\humhub\modules\modhelper;

class Module extends \humhub\components\Module
{
	protected $mod;
	protected $vars;

	/**
	* @inheritdoc
	*/
	public function disable ()
	{
		parent::disable();
	}

	public function doSort ($mdl, $set, $srch, $tabs)
	{
		if (is_callable($srch)) {
			if (is_array($srch) || is_object($srch))
				return $srch($mdl, $tabs);		// closures and class methods

			if (!is_array($srch)) {
				$r= new \ReflectionFunction($srch);
				if ($r->isUserDefined()) {
					// TODO: Determine if the function was actually defined by our client.
					// Otherwise, it should be treated as string
					return $srch($mdl, $tabs);	// user defined functions
				}
			}
		}

		// $srch should be string or array - TODO: test for object?
		if (!is_array($srch))
			$srch= [$srch];
		$fs= [];
		foreach ($tabs as $attr) {
			$fs[$attr]= '';
			foreach ($srch as $s)
				$fs[$attr].= $set->get($attr.'/'.$s);
		}
		asort($fs);

		return array_keys($fs);
	}

	public function saveTab ($mdl, $tab)
	{
		$mod= $mdl->mod;
		$mod['prefix']= empty($tab) ? '' : $tab.'/';

		$tabs= $this->getTabs($mod['_']);
		if (array_search($tab, $tabs) === false)
			$tabs[]= $tab;
		if (isset($mod['options']['tab_sort']))
			$tabs= $this->doSort($mdl, $mod['settings'], $mod['options']['tab_sort'], $tabs);
		$this->setTabs($mod['_'], $tabs);

		return;
	}

	public function getTabs ($mdu)
	{
		// keep track of new api usage per module
#		$this->settings->set('o/fget/'.$mdu->id, time());

		if ('' == $tabs= $mdu->settings->get('//tabs'))
			if ('' == $tabs= $mdu->settings->get('/frames'))
				return [];

		return preg_split('!\s*/\s*!', $tabs);
	}

	public function setTabs ($mdu, $tabs)
	{
		// keep track of new api usage per module
#		$this->settings->set('o/fset/'.$mdu->id, time());

		$fr= join('/', $tabs);
		$mdu->settings->set('//tabs', $fr);
		$mdu->settings->set('/frames', $fr);		// deprecated old form
	}
}
