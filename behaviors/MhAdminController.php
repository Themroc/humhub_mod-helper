<?php

namespace themroc\humhub\modules\modhelper\behaviors;

use Yii;
use yii\helpers\Url;
use yii\base\Behavior;

class MhAdminController extends Behavior
{
	const MH_MAX_API= 1;

	public $cfg= [
		'api'=> 1,
		'isTabbed'=> 0,
		'isContainer'=> 0,
	];
	public $model;
	public $module;
	public $widget;

	/**
	 * Render admin only page
	 *
	 * @return string
	 */
	public function MHactionIndex ($modelClass, $widget= null)
	{
		$this->widget= $widget;
		$that= $this->owner;

		if (defined("$modelClass::MH_API"))
			$this->cfg['api']= $modelClass::MH_API;
		if ($this->cfg['api'] > static::MH_MAX_API)
			return $that->render('@mod-helper/views/error', [
				'msg'=> 'API mismatch. Please install the latest version of the'
					.' <a href="https://github.com/Themroc/humhub_mod-helper" target="_blank">Mod-Helper plugin</a>.',
			]);

		if (isset($that->isContainer))
			$this->cfg['isContainer']= $that->isContainer ? 1 : 0;
		if (isset($that->isTabbed))
			$this->cfg['isTabbed']= $that->isTabbed ? 1 : 0;

		$mdu= $this->module= $this->cfg['isContainer'] ? $that->contentContainer : $that->module;
		$that->subLayout= isset($that->subLayoutOvr)
			? $that->subLayoutOvr
			: $this->cfg['isContainer']
				? '@humhub/modules/space/views/space/_layout'
				: '@mod-helper/views/subLayout';

		$tab= null;
		if ($this->cfg['isTabbed']) {
			$req= $that->request;
			if ('' == $tab= $req->get('tab'))
				$tab= $req->get('frame');
		}
		$pfx= empty($tab) ? '' : $tab.'/';

		if ($that->request->get('delete') == 1) {
			$model= $this->model= new $modelClass($pfx, ['mh_ctr'=> $that]);
			foreach (array_keys($model->getVars()) as $v)
				$mdu->settings->delete($tab.'/'.$v);
			if ($this->cfg['isTabbed']) {
				$tabs= $model->mod['mh']->getTabs($mdu);
				if (false !== $k= array_search($tab, $tabs)) {
					unset($tabs[$k]);
					$model->mod['mh']->setTabs($mdu, $tabs);
				}
			}

			return $this->goHome();
		}

		$model= $this->model= new $modelClass($pfx, ['mh_ctr'=> $that]);
		if ($model->load($that->request->post()) && $model->validate() && $model->save()) {
			$that->view->saved();

			return $this->goHome();
		}

		return $that->render('@mod-helper/views/form', [
			'api'=> $this->cfg['api'],
			'isTabbed'=> $this->cfg['isTabbed'],
			'model'=> $model,
		]);
	}

	public function goHome ()
	{
		$that= $this->owner;
		$mdu= $this->module;

		return $that->redirect($this->cfg['isContainer']
			? $mdu->createUrl($that->homeUrl)
			: Url::to($that->request->url)
		);
	}

	public function getCfg ()
	{
		return $this->cfg;
	}

	public function getModel ()
	{
		return $this->model;
	}

	public function getWidget ()
	{
		return $this->widget;
	}

	public function getConfVar ($var)
	{
		$that= $this->owner;

		return $this->cfg['isContainer']
			? $this->module->getSetting($var, $that->id)
			: $this->module->settings->get($var);
	}

	public function setConfVar ($var, $value)
	{
		$that= $this->owner;

		if ($this->cfg['isContainer'])
			$this->module->setSetting($var, $value, $that->id);
		else
			$this->module->settings->set($var, $value);
	}

	public function deleteConfVar ($var)
	{
		$that= $this->owner;

		if ($this->cfg['isContainer'])
			Yii::$app->getModule($that->id)->settings->contentContainer($this->module)->delete($var);
		else
			$this->module->settings->delete($var, $value);
	}
}
