<?php

namespace themroc\humhub\modules\modhelper\models;

use Yii;
use yii\base\Model;
use \yii\db\ActiveRecord;
use humhub\libs\Html;

/**
 * AdminForm handles the configurable fields.
 */
class MhAdminForm extends Model
{
	public $_mh_tab_;

	protected $mod= [ ];
	protected $vars= [ ];

	public function __construct ($prefix= '', $config= [])
	{
		$mod= &$this->mod;
		$vars= &$this->vars;
		$ctr= $config['mh_ctr']; unset($config['mh_ctr']);

		$mod['mh']= Yii::$app->getModule('mod-helper');
		$mod['_']= $mdu= $ctr->module;
		$mod['ctr']= $ctr;
		$mod['mdl']= $this;
		$mod['tab']= preg_replace('|/$|', '', $prefix);
		$mod['prefix']= $mod['tab']!=='' ? $mod['tab'].'/' : '';
		if (! isset($mod['id']))
			$mod['id']= $mdu->id;
		if (! isset($mod['name']))
			$mod['name']= ucfirst($mod['id']);
		$mod['settings']= $mdu->settings;
		if (! isset($mod['trans']))
			$mod['trans']= join(
				'',
				array_map(function ($i) { return ucfirst($i); }, preg_split('![_-]!', $ctr->module->id))
			)
			. 'Module.base';

		$v= method_exists($this, 'vars') ? $this->vars() : [];
		foreach (array_keys($this->attributes) as $attr) {
			if (isset($v[$attr]))
				$vars[$attr]= $v[$attr];
			else if (! isset($vars[$attr]))
				$vars[$attr]= [];

			$va= &$vars[$attr];
			if (! isset($va['options']))
				$va['options']= [];
			if (! isset($va['form']))
				$va['form']= [];
		}

		$m= method_exists($this, 'mod') ? $this->mod() : [];
		foreach ($m as $k => $v)
			$mod[$k]= $v;

		if (! isset($mod['options']))
			$mod['options']= [];
		if (! isset($mod['form']))
			$mod['form']= [];

		if (isset($mod['ctr']->isTabbed) && !isset($mod['options']['tab_attr']))
			$mod['options']['tab_attr']= '_mh_tab_';
		$vars['_mh_tab_']= [
			'label'=> '',
			'form'=> ['type'=> 'hidden', 'options'=> ['style'=> 'display:none']],
			'options'=> ['nosave'=> 1]
		];

		return parent::__construct($config);
	}

	public function init ()
	{
		$this->loadSettings();
	}

	/**
	 * @inheritdoc
	 */
	public function rules ()
	{
		$r= [];
		foreach ($this->vars as $k => $v) {
			$a= [ $k ];
			if (! isset($v['rules']))
				$a[]= 'string';
			else
			if (is_array($v['rules']))
				foreach ($v['rules'] as $rk => $rv)
					if (is_int($rk))
						$a[]= $rv;
					else
						$a[$rk]= $rv;
			else
				$a[]= $v['rules'];
			$r[]= $a;
		}

		return $r;
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels ()
	{
		$r= [];
		foreach ($this->vars as $k => $v)
			$r[$k]= Yii::t($this->getTrans($k), isset($v['label']) ? $v['label'] : ucfirst($k));

		return $r;
	}

	/**
	 * @inheritdoc
	 */
	public function attributeHints ()
	{
		$r= [];
		foreach ($this->vars as $k => $v)
			if (isset($v['hints']))
				$r[$k]= Yii::t($this->getTrans($k), $v['hints']);

		return $r;
	}

	/**
	 * @inheritdoc
	 */
	public function loadSettings ()
	{
		$mod= $this->mod;
		foreach ($this->vars as $k => $v) {
			$this->{$k}= '';
			if (null != $s= $mod['settings']->get($mod['prefix'].$k))
				$this->{$k}= $s;
		}

		if (isset($mod['ctr']->isTabbed))
			$mod['options']['_tvlast']= $this->{$mod['options']['tab_attr']};

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function save ()
	{
		$mod= $this->mod;
		$set= $mod['settings'];
		$pfx= $mod['prefix'];
		$ta= isset($mod['ctr']->isTabbed) ? $mod['options']['tab_attr'] : '';

		if ($ta) {
			if (! $this->chk_opt($this->vars[$ta], 'notrim'))
				$this->{$ta}= trim($this->{$ta});
			$pfx= $this->{$ta}.'/';
		}

		foreach ($this->vars as $k => $v)
			if (! $this->chk_opt($v, 'nosave')) {
				$val= $this->chk_opt($v, 'notrim') ? $this->{$k} : trim($this->{$k});
				if (isset($val) && $val !== "")
					$set->set($pfx.$k, $val);
			}

		if ($ta) {
			if (! isset($mod['options']['_tvlast']) || $mod['options']['_tvlast'] != $this->{$ta})
				$mod['mh']->saveTab($this, $this->{$ta});
		}

		return $this->loadSettings();
	}

	public function chk_opt ($cfg, $opt)
	{
		return (isset($cfg['options']) && isset($cfg['options'][$opt]))
			? $cfg['options'][$opt]
			: null;
	}

	public function getDeleteBtn ()
	{
		$mod= $this->mod;
		if (isset($mod['ctr']->isTabbed)) {
			$ta= $mod['options']['tab_attr'];
			if (strlen($this->{$ta}))
				return Html::a(
					Yii::t('ModHelperModule.base', 'Delete'),
					$mod['_']->getUrl('admin', ['tab'=> $this->{$ta}, 'delete'=> '1']),
					['class' => 'btn btn-danger pull-right', 'style'=>'margin-right:10px']
				);
		}

		return '';
	}

	public function getVars ($key= null)
	{
		return $key===null ? $this->vars : $this->vars[$key];
	}

	public function getMod ($key= null)
	{
		return $key===null ? $this->mod : $this->mod[$key];
	}

	public function getTrans ($attr)
	{
		$v= $this->vars[$attr];

		return isset($v['trans']) ? $v['trans'] : $this->mod['trans'];
	}
}
