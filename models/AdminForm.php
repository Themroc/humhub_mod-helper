<?php

namespace themroc\humhub\modules\modhelper\models;

use Yii;
use yii\base\Model;

/**
 * AdminForm handles the configurable fields.
 */
class AdminForm extends Model
{
	protected $mod= [ ];
	protected $vars= [ ];

	public function __construct ($prefix= '', $config= [])
	{
		$this->mod['prefix']= $prefix;

		return parent::__construct($config);
	}

	public function init ()
	{
		if (! isset($this->mod['id']))
			$this->mod['id']= Yii::$app->controller->module->id;
		if (! isset($this->mod['name']))
			$this->mod['name']= ucfirst($this->mod['id']);
		$this->mod['_']= Yii::$app->getModule($this->mod['id']);
		$this->mod['settings']= $this->mod['_']->settings;
		if (! isset($this->mod['trans']))
			$this->mod['trans']=
				join('', array_map(function ($i) { return ucfirst($i); }, preg_split('![_-]!', $this->mod['id'])))
				. 'Module.base';

		$v= method_exists($this, 'vars') ? $this->vars() : [];
		foreach (array_keys($this->attributes) as $attr) {
			if (isset($v[$attr]))
				$this->vars[$attr]= $v[$attr];
			else if (! isset($this->vars[$attr]))
				$this->vars[$attr]= [];

			$va= &$this->vars[$attr];
			if (empty($va['form']))
				$va['form']= [];
			if (empty($va['trans']))
				$va['trans']= $this->mod['trans'];
		}
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
			$r[$k]= Yii::t($v['trans'], isset($v['label']) ? $v['label'] : ucfirst($k));

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
				$r[$k]= Yii::t($v['trans'], $v['hints']);

		return $r;
	}

	/**
	 * @inheritdoc
	 */
	public function loadSettings ()
	{
		foreach ($this->vars as $k => $v)
			if (null != $s= $this->mod['settings']->get($this->mod['prefix'].$k))
				$this->{$k}= $s;

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function save ()
	{
		foreach ($this->vars as $k => $v) {
			$opt= @$v['options'];
			if (! @$opt['nosave']) {
				$val= @$opt['notrim'] ? $this->{$k} : trim($this->{$k});
				$this->mod['settings']->set($this->mod['prefix'].$k, $val);
			}
		}

		return $this->loadSettings();
	}

	public function getVars ($key= null)
	{
		return $key===null ? $this->vars : $this->vars[$key];
	}

	public function getMod ($key= null)
	{
		return $key===null ? $this->mod : $this->mod[$key];
	}
}
