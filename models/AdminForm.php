<?php

namespace themroc\humhub\modules\modhelper\models;

use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use humhub\libs\Html;
use themroc\humhub\modules\modhelper\models\MhAdminForm;

/**
 * AdminForm handles the configurable fields.
 */
class AdminForm extends MhAdminForm
{
	public function __construct ($prefix= '', $config= [])
	{
		if (!isset($config['mh_ctr'])) {
			$bt= debug_backtrace();
			$config['mh_ctr']= $bt[1]['object'];
		}

		parent::__construct($prefix, $config);
	}

	public function init ()
	{
		$mdu= $this->mod['_'];

		// keep track of old api usage per module
		$this->mod['mh']->settings->set('o/init/'.$mdu->id, time());

		return parent::init();
	}
}
