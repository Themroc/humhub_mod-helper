<?php

use humhub\libs\Html;
use humhub\widgets\ActiveForm;
use yii\helpers\Html as yHtml;

themroc\humhub\modules\modhelper\assets\Assets::register($this);

$fname= 'AdminForm';

$mod= $model->getMod();
$vars= $model->getVars();
$mform= isset($mod['form']) ? $mod['form'] : [];
$vv= [ &$mod, &$vars, &$model ];

if (! isset($isTabbed))
	$isTabbed= isset($standAlone) ? !$standAlone : 0;

// collect dependencies
$depends= [];
$disable= [];
foreach ($vars as $k => $v) {
	$vis= '';
	if (!empty($v['form']))
		$vis= va(vDefault($v['form'], 'visible', 'depends'));
	if (empty($vis))
		continue;

	$off= 0;
	foreach ($vis as $dk => $dv) {
		list ($type, $src)= getSrc($vv, $dk, $fname);
		$jo= @$type=='checkbox' ? 'checked' : 'value';
		$target= '.field-'.strtolower($fname).'-'.$k;
		addDep($depends, $src, [$jo, $dv, $target]);
		if ($model->{$dk} != $dv)
			$off= 1;
	}
	if ($off)
		$disable[]= $target;
}
// hide disabled fields
if (count($disable)) {
	echo "<style>\n";
	echo implode(',', $disable) . '{display:none}' ."\n";
	echo "</style>\n";
}

// collect funcs
$code= '';
foreach ($vars as $k => $v) {
	if (empty($v['function']))
		continue;

	$dep= [];
	foreach (va($v['function']['depends']) as $d) {
		list ($type, $src)= getSrc($vv, $d, $fname);
		addDep($depends, $src, ['func', $k, '#'.strtolower($fname).'-'.$k]);
		$dep['@'.$d.'@']= $src;
	}
	$c= va($v['function']['code']);
	foreach ($dep as $dk => $dv)
		$c= preg_replace('/'.preg_quote($dk).'/', $dv, $c);
	$code.= ",\n\"".$k.'":function(p){'.$c.'}';
}
if (!empty($code))
	echo "<script>\nvar modhelper_func={\n".substr($code, 2)."\n};\n</script>\n";

// pass dependencies to js
$jdep= [];
foreach ($depends as $k => $v)
	$jdep[]= [ $k, $v ];
$this->registerJsConfig(['modhelper'=> ['dep'=> $jdep]]);
echo "<script>\nif (typeof humhub.modules.modhelper!=='undefined') humhub.modules.modhelper.run()\n</script>\n";

$ind= "";
if (! $isTabbed) {
	echo '<div class="panel panel-default">'."\n";
	echo '	<div class="panel-heading"><strong>'.$mod['name'].'</strong> '
		. Yii::t('ModHelperModule.base', 'module configuration')
		. '</div>'."\n";
	echo '	<div class="panel-body">'."\n";
	$ind= "\t\t";
}
$ind2= $ind . "\t";

$aform= ActiveForm::begin(['id' => 'configure-form']);

echo $ind.'<div class="form-group">'."\n";

foreach ($vars as $k => $v) {
	$vform= !empty($v['form']) ? $v['form'] : null;
	echo vs(vDefault2($vform, $v, 'prefix'), [$model], $ind2, "\n");
	$options= !empty($vform['options']) ? va(@$vform['options'], [$model]) : [];
	if (empty($model[$k]) && !empty($v['default']))
		$model[$k]= va($v['default'], [$model]);
	$type= !empty($vform['type']) ? $vform['type'] : '';
	switch ($type) {
		case 'checkbox':
			echo $ind2 . $aform->field($model, $k)->checkbox($options) . "\n";
			break;
		case 'dropdown':
			echo $ind2 . $aform->field($model, $k)->dropDownList(vaTrans($vv, $vform['items'], [$model], $k), $options) . "\n";
			break;
		case 'radio':
			echo $ind2 . $aform->field($model, $k)->radioList(vaTrans($vv, $vform['items'], [$model], $k), $options) . "\n";
			break;
		case 'textarea':
			echo $ind2 . $aform->field($model, $k)->textarea($options) . "\n";
			break;
		case 'widget':
			echo $ind2 . $aform->field($model, $k)->widget($vform['class'], $options) . "\n";
			break;
		case "hidden":
		        echo $ind2 . $aform->field($model, $k, ['labelOptions'=> ['style'=> 'display:none']])->hiddenInput($options) . "\n";
		        break;
		default:
			echo $ind2 . $aform->field($model, $k)->input("text", $options) . "\n";
	}
	echo vs(vDefault2($vform, $v, 'suffix'), [$model], $ind2, "\n");
}
echo $ind2 . '</div>'."\n";

echo $ind2 . '<div class="form-group">'."\n";
if (!empty($mform['btn_pre']))
	echo	vs($mform['btn_pre'], [$model], $ind2, "\n");
echo $ind2."\t".Html::saveButton()."\n";
if ('' != $del= $model->getDeleteBtn())
    echo $ind2.$del."\n";
if (!empty($mform['btn_post']))
	echo	vs($mform['btn_post'], [$model], $ind2, "\n");
echo $ind2 . '</div>'."\n";

ActiveForm::end();

if (! $isTabbed) {
	echo '	</div>'."\n";
	echo '</div>'."\n";
}

/**
 * vDefault Returns $default if $var is empty, $var otherwise
 */
function vDefault ($a= null, $istd= null, $idef= null) {
	if (empty($a))
		return null;

	if (!empty($a[$istd]))
		return $a[$istd];

	return !empty($a[$idef])
		? $a[$idef]
		: null;
}

/**
 * vDefault2 Returns $default if $var is empty, $var otherwise
 */
function vDefault2 ($astd= null, $adef= null, $idx= null) {
	if (empty($idx))
		return null;
	if (!empty($astd) && !empty($astd[$idx]))
		return  $astd[$idx];
	if (!empty($adef) && !empty($adef[$idx]))
		return $adef[$idx];

	return null;
}

/**
 * vAsArray Converts $var to array. An empty one if $var is empty, $var if it
 * is already an array and a one from a string-split otherwise. In the latter
 * case, the 1st char is taken as separator.
 */
function vAsArray ($var) {
	if (empty($var))
		return [];

	if (is_array($var))
		return $var;

	return explode(substr($var, 0, 1), substr($var, 1));
}

/**
 * vS Returns the return value if $var some kind of function, $var otherwise.
 */
function vc ($var, $params= null) {
	if (empty($var))
		return '';

	if (is_callable($var))
		return call_user_func_array($var, $params);

	return $var;
}

/**
 * vS Returns the return value if $var some kind of function, $var otherwise.
 * In both cases the return value is prepended by $pre and appended by $post.
 */
function vs ($var, $params= null, $pre= '', $post= '') {
	if (empty($var))
		return '';

	return $pre . vc($var, $params) . $post;
}


/**
 * va Returns a potentially callable $var as array.
 */
function va ($var, $params= null) {
	return vAsArray(vc($var, $params));
}

/**
 * vATrans Returns a potentially callable $var as array with each element translated.
 */
function vaTrans ($vv, $var, $params, $attrib) {
	list($mod, $vars, $model)= $vv;

	$r= [];
	$va= !empty($vars[$attrib]) ? $vars[$attrib] : null;
	$c= vDefault2($va, $mod, 'trans');
	foreach (va($var, $params) as $k => $v)
		$r[$k]= Yii::t($c, $v);

	return $r;
}

function getSrc ($vv, $var, $fname) {
	list($mod, $vars)= $vv;

	$type= @$vars[$var]['form']['type'];
	if (@$type=='radio')
		$src= 'input[name="'.$fname.'\\['.$var.'\\]"]';
	else
		$src= '#'.strtolower($fname).'-'.$var;

	return [$type, $src];
}

function addDep (&$dep, $index, $data) {
	if (isset($dep[$index]))
		$dep[$index][]= $data;
	else
		$dep[$index]= [ $data ];
}
