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
		$mod['cvar']= $ctr->getBehavior('MhAdmin') ? $ctr : $this;
		$mod['cfg']= $mod['cvar']->getCfg();

		$mod['tab']= preg_replace('|/$|', '', $prefix);
		$mod['prefix']= $mod['tab']!=='' ? $mod['tab'].'/' : '';
		if (! isset($mod['id']))
			$mod['id']= $mdu->id;
		if (! isset($mod['name']))
			$mod['name']= ucfirst($mod['id']);
		if (! isset($mod['trans']))
			$mod['trans']= join(
				'',
				array_map(function ($i) { return ucfirst($i); }, preg_split('![_-]!', $ctr->module->id))
			)
			. 'Module.base';

		$v= method_exists($this, 'vars') ? $this->vars() : [];
#		$keys= array_unique(array_merge(array_keys($v), array_keys($vars), array_keys($this->attributes)));
		$keys= array_unique(array_merge(array_keys($v), array_keys($vars)));
		foreach ($keys as $attr) {
			if (isset($v[$attr]))
				$vars[$attr]= $v[$attr];
			else if (! isset($vars[$attr]))
				$vars[$attr]= [];

			$va= &$vars[$attr];
			if (! isset($va['options']))
				$va['options']= [];
			if (! isset($va['form']))
				$va['form']= [];
			if (! isset($va['rules']))
				$va['rules']= [['safe']];
			else
				if (! is_array($va['rules']))
					$va['rules']= [$va['rules']];
		}

		$m= method_exists($this, 'mod') ? $this->mod() : [];
		foreach ($m as $k => $v)
			$mod[$k]= $v;

		if (! isset($mod['options']))
			$mod['options']= [];
		if (! isset($mod['form']))
			$mod['form']= [];

		$vars['_mh_tab_']['form']['type']= 'hidden';
		$vars['_mh_tab_']['options']['nosave']= 1;
#		'form'=> ['type'=> 'hidden', 'options'=> ['style'=> 'display:none']],
		if ($mod['cfg']['isTabbed']) {
			if (! isset($mod['options']['tab_attr']))
				$mod['options']['tab_attr']= '_mh_tab_';
			else
				unset($vars['_mh_tab_']['rules']);
			$ta= $mod['options']['tab_attr'];
			$ra= &$vars[$ta]['rules'];
			if (! is_array($ra))
				$ra= [$ra];
			$ra[]= ['required'];
			$ra[]= [
				'match',
				'pattern'=> '|^[^/]+$|',
				'message'=> Yii::t('ModHelperModule.base', 'Must not contain a /')
			];
		} else
			unset($vars['_mh_tab_']['rules']);

		return parent::__construct($config);
	}

	/**
	 * Old API
	 */
	public function getCfg ()
	{
		$cfg= [
			'api'=> 0,
			'isTabbed'=> 0,
			'isContainer'=> 0,
		];
		$mod= $this->mod;
		$ctr= $mod['ctr'];

		if (isset($ctr->isTabbed))
			$cfg['isTabbed']= $ctr->isTabbed ? 1 : 0;
		else
		if (isset($ctr->standAlone))
			$cfg['isTabbed']= $ctr->standAlone ? 0 : 1;

		if (isset($ctr->contentContainer))
			$cfg['isContainer']= 1;

		return $cfg;
	}

	/**
	 * Old API
	 */
	public function getConfVar ($var)
	{
		return $this->mod['_']->settings->get($var);
	}

	/**
	 * Old API
	 */
	public function setConfVar ($var, $value)
	{
		$this->mod['_']->settings->set($var, $value);
	}

	/**
	 * Old API
	 */
	public function deleteConfVar ($var)
	{
		$this->mod['_']->settings->delete($var);
	}

	/**
	 * @inheritdoc
	 */
	public function init ()
	{
		$this->loadSettings();
	}

	protected function addRule ($attr, $array)
	{
		$aobj= new \ArrayObject($array);
		$acopy= $aobj->getArrayCopy();
		array_unshift($acopy, $attr);

		return $acopy;
	}

	/**
	 * @inheritdoc
	 */
	public function rules ()
	{
		$r= [];
		foreach ($this->vars as $k => $v) {
			if (! property_exists($this, $k) || empty($v['rules']))
				continue;

			if (! is_array($v['rules'][0]))
				$r[]= $this->addRule($k, $v['rules']);
			else
				foreach ($v['rules'] as $rk => $rv)
					if (! is_array($rv))
						$r[]= [$k, $rv];
					else
						$r[]= $this->addRule($k, $rv);
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
			if (property_exists($this, $k))
				$r[$k]= Yii::t($this->getTrans($k), isset($v['label']) ? $v['label'] : str_replace('_', ' ', ucfirst($k)));

		return $r;
	}

	/**
	 * @inheritdoc
	 */
	public function attributeHints ()
	{
		$r= [];
		foreach ($this->vars as $k => $v)
			if (property_exists($this, $k) && isset($v['hints']))
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
			if (! property_exists($this, $k))
				continue;
			$this->{$k}= '';
			if (null != $s= $mod['cvar']->getConfVar($mod['prefix'].$k))
				$this->{$k}= $s;
		}

		if ($mod['cfg']['isTabbed'])
			$mod['options']['_tvlast']= $this->{$mod['options']['tab_attr']};

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function save ()
	{
		$mod= $this->mod;
#		$set= $mod['settings'];
		$pfx= $mod['prefix'];
		$ta= $mod['cfg']['isTabbed'] ? $mod['options']['tab_attr'] : '';

		if ($ta) {
			if (! $this->chk_opt($this->vars[$ta], 'notrim'))
				$this->{$ta}= trim($this->{$ta});
			$pfx= $this->{$ta}.'/';
		}

		foreach ($this->vars as $k => $v)
			if (property_exists($this, $k) && ! $this->chk_opt($v, 'nosave')) {
				$val= $this->chk_opt($v, 'notrim') ? $this->{$k} : trim($this->{$k});
				if (isset($val) && $val !== "")
					$mod['cvar']->setConfVar(strtolower($pfx.$k), $val);
			}

		if ($ta) {
			if (! isset($mod['options']['_tvlast']) || $mod['options']['_tvlast'] != $this->{$ta})
				$mod['mh']->saveTab($this, strtolower($this->{$ta}));
		}

		return $this->loadSettings();
	}

	public function getDeleteBtn ()
	{
		$mod= $this->mod;
		if (! $mod['cfg']['isTabbed'])
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
	 * vDeep Returns value from nested array
	 */
	public function vDeep ($array= null, $idx1= null, $idx2= null)
	{
		if (! isset($array))
			return null;

		if (! isset($array[$idx1]))
			return null;

		if (! isset($array[$idx1][$idx2]))
			return null;

		return $array[$idx1][$idx2];
	}

	/**
	 * vDefault Returns $default if $var is empty, $var otherwise
	 */
	public function vDefault ($a= null, $istd= null, $idef= null)
	{
		if (! isset($a))
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
		if (! isset($idx))
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
		if (! isset($var))
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
		if (! isset($var))
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
		if (! isset($var))
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

	function empty ($v) {
		return ! (isset($v) && $v!=="");
	}

	public function collectViewVars ($field_tpl, $radio_tpl, $func_tpl)
	{
		$this->radio_tpl= $radio_tpl;
		$this->func_tpl= $func_tpl;

		// collect dependencies
		$depends= [];
		$disable= [];
		foreach ($this->vars as $k => $v) {
			if (! property_exists($this, $k))
				continue;
			if ($this->empty($this[$k]) && isset($v['default']))
				$this[$k]= $this->vs($v['default']);

			$vis= '';
			if (! empty($v['form']))
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
			if (! property_exists($this, $k) || empty($v['function']))
				continue;

			$dep= [];
			foreach ($this->va($v['function']['depends']) as $d) {
				list ($type, $src)= $this->getJsName($d);
				$this->addDep($depends, $src, ['func', $k, sprintf($func_tpl, $k)]);
				$dep['@'.$d.'@']= $src;
			}
			$c= $this->vs($v['function']['code']);
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
