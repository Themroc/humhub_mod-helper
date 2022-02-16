<?php

namespace themroc\humhub\modules\modhelper\behaviors;

use Yii;
use yii\base\Behavior;
#use themroc\humhub\modules\modhelper\models\AdminForm;

class MhAdminController extends Behavior
{
	const MH_MAX_API= 1;

	public $api= 0;
	public $model;
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
				$this->api= $modelClass::MH_API;
		if ($this->api > static::MH_MAX_API)
			return $that->render('@mod-helper/views/error', [
				'msg'=> 'API mismatch. Please install the latest version of the'
					.' <a href="https://github.com/Themroc/humhub_mod-helper" target="_blank">Mod-Helper plugin</a>.',
			]);

		$mdu= $that->module;
		$that->subLayout= isset($that->subLayoutOvr) ? $that->subLayoutOvr : '@mod-helper/views/subLayout';
		$tab= null;
		if ($that->isTabbed) {
			$req= $that->request;
			if ('' == $tab= $req->get('tab'))
				$tab= $req->get('frame');
		}
		$pfx= empty($tab) ? '' : $tab.'/';

		if ($that->request->get('delete') == 1) {
			$model= $this->model= new $modelClass($pfx, ['mh_ctr'=> $that]);
			foreach (array_keys($model->getVars()) as $v)
				$mdu->settings->delete($tab.'/'.$v);
			if ($that->isTabbed) {
				$tabs= $model->mod['mh']->getTabs($mdu);
				if (false !== $k= array_search($tab, $tabs)) {
					unset($tabs[$k]);
					$model->mod['mh']->setTabs($mdu, $tabs);
				}
			}

			return $that->redirect($mdu->getUrl('admin'));
		}

		$model= $this->model= new $modelClass($pfx, ['mh_ctr'=> $that]);
		if ($model->load($that->request->post()) && $model->validate() && $model->save()) {
			$that->view->saved();

			return $that->redirect($mdu->getUrl('admin'));
		}

		return $that->render('@mod-helper/views/form', [
			'model'=> $model,
			'isTabbed'=> $that->isTabbed,
		]);
	}

	public function getApi ()
	{
		return $this->api;
	}

	public function getModel ()
	{
		return $this->model;
	}

	public function getWidget ()
	{
		return $this->widget;
	}
}
