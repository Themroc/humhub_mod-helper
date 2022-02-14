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

	protected $radio_tpl;
	protected $func_tpl;

	/**
	 * @inheritdoc
	 */
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

	/**
	 * @inheritdoc
	 */
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

	public function getDeleteBtn ()
	{
		$mod= $this->mod;
		if (! isset($mod['ctr']->isTabbed))
			return '';

		$ta= $mod['options']['tab_attr'];
		if (! strlen($this->{$ta}))
			return '';

		return Html::a(
			Yii::t('ModHelperModule.base', 'Delete'),
			$mod['_']->getUrl('admin', ['tab'=> $this->{$ta}, 'delete'=> '1']),
			['class' => 'btn btn-danger pull-right', 'style'=>'margin-right:10px']
		);
	}

	public function getVars ($key= null)
	{
		return $key===null ? $this->vars : $this->vars[$key];
	}

	public function getMod ($key= null)
	{
		return $key===null ? $this->mod : $this->mod[$key];
	}

	/**
	 * vDefault Returns $default if $var is empty, $var otherwise
	 */
	public function vDefault ($a= null, $istd= null, $idef= null)
	{
		if (!isset($a))
			return null;

		if (isset($a[$istd]))
			return $a[$istd];

		return isset($a[$idef]) ? $a[$idef] : null;
	}

	/**
	 * vDefault2 Returns $default if $var is empty, $var otherwise
	 */
	public function vDefault2 ($astd= null, $adef= null, $idx= null)
	{
		if (!isset($idx))
			return null;

		if (isset($astd) && isset($astd[$idx]))
			return $astd[$idx];

		if (isset($adef) && isset($adef[$idx]))
			return $adef[$idx];

		return null;
	}

	/**
	 * vAsArray Converts $var to array. An empty one if $var is empty, $var if it
	 * is already an array and a one from a string-split otherwise. In the latter
	 * case, the 1st char is taken as separator.
	 */
	public function vAsArray ($var= null)
	{
		if (!isset($var))
			return [];

		if (is_array($var))
			return $var;

		return explode(substr($var, 0, 1), substr($var, 1));
	}

	/**
	 * vc Returns the return value if $var some kind of function, $var otherwise.
	 */
	public function vc ($var= null)
	{
		if (!isset($var))
			return null;

		if (is_callable($var))
			return call_user_func($var, $this);

		return $var;
	}

	/**
	 * vs Returns the return value if $var some kind of function, $var otherwise.
	 * In both cases the return value is prepended by $pre and appended by $post.
	 */
	public function vs ($var= null, $pre= '', $post= '')
	{
		if (!isset($var))
			return '';

		return $pre . $this->vc($var) . $post;
	}

	/**
	 * va Returns a potentially callable $var as array.
	 */
	public function va ($var= null)
	{
		return $this->vAsArray($this->vc($var));
	}

	/**
	 * vATrans Returns a potentially callable $var as array with each element translated.
	 */
	public function vaTrans ($var= null, $attrib)
	{
		$r= [];
		$c= $this->getTrans($attrib);
		foreach ($this->va($var) as $k => $v)
			$r[$k]= Yii::t($c, $v);

		return $r;
	}

	public function collectViewVars ($field_tpl, $radio_tpl, $func_tpl)
	{
		$this->radio_tpl= $radio_tpl;
		$this->func_tpl= $func_tpl;

		// collect dependencies
		$depends= [];
		$disable= [];
		foreach ($this->vars as $k => $v) {
			$vis= '';
			if (!empty($v['form']))
				$vis= $this->va($this->vDefault($v['form'], 'visible', 'depends'));
			if (empty($vis))
				continue;

			$off= 0;
			foreach ($vis as $dk => $dv) {
				list ($type, $src)= $this->getJsName($dk);
				$jo= @$type=='checkbox' ? 'checked' : 'value';
				$target= sprintf($field_tpl, $k);
				$this->addDep($depends, $src, [$jo, $dv, $target]);
				if ($this->{$dk} != $dv)
					$off= 1;
			}
			if ($off)
				$disable[]= $target;
		}

		// collect funcs
		$code= '';
		foreach ($this->vars as $k => $v) {
			if (empty($v['function']))
				continue;

			$dep= [];
			foreach ($this->va($v['function']['depends']) as $d) {
				list ($type, $src)= $this->getJsName($d);
				$this->addDep($depends, $src, ['func', $k, sprintf($func_tpl, $k)]);
				$dep['@'.$d.'@']= $src;
			}
			$c= $this->va($v['function']['code']);
			foreach ($dep as $dk => $dv)
				$c= preg_replace('/'.preg_quote($dk).'/', $dv, $c);
			$code.= ",\n\"".$k.'":function(p){'.$c.'}';
		}

		return [$depends, $disable, substr($code, 2)];
	}

	protected function getJsName ($var)
	{
		$type= @$this->vars[$var]['form']['type'];
		if (@$type=='radio')
			$src= sprintf($this->radio_tpl, $var);
		else
			$src= sprintf($this->func_tpl, $var);

		return [$type, $src];
	}

	protected function addDep (&$dep, $index, $data)
	{
		if (isset($dep[$index]))
			$dep[$index][]= $data;
		else
			$dep[$index]= [ $data ];
	}

	protected function chk_opt ($cfg, $opt)
	{
		return (isset($cfg['options']) && isset($cfg['options'][$opt]))
			? $cfg['options'][$opt]
			: null;
	}

	/**
	 * getTrans Returns translation category for attribute $attr_name.
	 */
	protected function getTrans ($attr_name)
	{
		$v= $this->vars[$attr_name];

		return isset($v['trans']) ? $v['trans'] : $this->mod['trans'];
	}

}
