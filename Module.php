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

	public function saveTab ($mdl, $tab)
	{
		$mod= $mdl->mod;
		$mod['prefix']= empty($tab) ? '' : $tab.'/';

		$tabs= $this->getTabs($mod['_']);
		if (array_search($tab, $tabs) === false)
		    $tabs[]= $tab;
		if (isset($mod['options']['tab_sort'])) {
			$s= $mod['options']['tab_sort'];
			if (is_callable($s))
				$tabs= $s($mdl, $tabs);
			else {
				$fs= [];
				foreach ($tabs as $f)
					$fs[$f]= $mod['settings']->get($f.'/'.$s);
				asort($fs);
				$tabs= array_keys($fs);
			}
		}

		$this->setTabs($mod['_'], $tabs);
		return;
	}

	public function getTabs ($mdu)
	{
		// keep track of new api usage per module
#		$this->settings->set('o/fget/'.$mdu->id, time());

#		if (null == $frames= $module->settings->get('//tabs'))	// Use this eventually
		if (null == $tabs= $mdu->settings->get('/frames'))
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
