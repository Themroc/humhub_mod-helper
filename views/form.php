<?php

use humhub\libs\Html;
use humhub\widgets\ActiveForm;

themroc\humhub\modules\modhelper\assets\Assets::register($this);

if (!isset($standAlone))
	$standAlone= 1;

$vars= $model->getVars();
$mod= $model->getMod();
$mform= isset($mod['form']) ? $mod['form'] : [];

$depends= [];
$disable= [];
foreach ($vars as $k => $v) {
	if (null === @$v['form']['depends'])
		continue;

	$off= 0;
	foreach ($v['form']['depends'] as $dk => $dv) {
		$type= @$vars[$dk]['form']['type'];
		if (@$type=='radio')
			$src= 'input[name="ConfigForm\\['.$dk.'\\]"]';
		else
			$src= '#configform-'.$dk;
		$jo= @$type=='checkbox' ? 'checked' : 'value';
		$target= '.field-configform-'.$k;
		addDep($depends, $src, [$jo, $dv, $target]);
		if ($model->{$dk} != $dv)
			$off= 1;
	}
	if ($off)
		$disable[]= $target;
}
if (count($disable)) {
	echo "<style>\n";
	foreach ($disable as $d)
		echo $d . '{display:none}' ."\n";
	echo "</style>\n";
}
$jdep= [];
foreach ($depends as $k => $v)
	$jdep[]= [ $k, $v ];
$this->registerJsConfig(['modhelper'=> ['dep'=> $jdep]]);

if ($standAlone) {
	echo '<div class="panel panel-default">'."\n";
	echo '	<div class="panel-heading"><strong>'.$mod['name'].'</strong> ' . Yii::t($mod['trans'], 'module configuration') . '</div>'."\n";
	echo '	<div class="panel-body">'."\n";
}

$aform= ActiveForm::begin(['id' => 'configure-form']);

echo '		<div class="form-group">'."\n";

foreach ($vars as $k => $v) {
	$vform= @$v['form'];
	echo getHtml(@$vform['prefix'], $model, "\t\t\t");
	$options= getParams(@$vform['options'], $model);
	switch (@$vform['type']) {
		case 'checkbox':
			echo "\t\t\t" . $aform->field($model, $k)->checkbox($options) . "\n";
			break;
		case 'dropdown':
			echo "\t\t\t" . $aform->field($model, $k)->dropDownList(getParams(@$vform['items'], $model), $options) . "\n";
			break;
		case 'radio':
			echo "\t\t\t" . $aform->field($model, $k)->radioList(getParams(@$vform['items'], $model), $options) . "\n";
			break;
		case 'textarea':
			echo "\t\t\t" . $aform->field($model, $k)->textarea($options) . "\n";
			break;
		case 'widget':
			echo "\t\t\t" . $aform->field($model, $k)->widget($vform['class'], $options) . "\n";
			break;
		default:
			echo "\t\t\t" . $aform->field($model, $k) . "\n";
	}
	echo getHtml(@$vform['suffix'], $model, "\t\t\t");
}
echo '		</div>'."\n";

echo '		<div class="form-group">'."\n";
echo			getHtml(@$mform['btn_pre'], $model, "\t\t\t");
echo '			' . Html::saveButton()."\n";
echo			getHtml(@$mform['btn_post'], $model, "\t\t\t");
echo '		</div>'."\n";

ActiveForm::end();

if ($standAlone) {
	echo '	</div>'."\n";
	echo '</div>'."\n";
}

function getHtml ($var, $model, $indent= '') {
	if ($var === null)
		return '';

	if (is_callable($var))
		return $indent . call_user_func_array($var, [$model]);

	return $indent . $var;
}

function getParams ($var, $model) {
	if ($var === null)
		return [];

	if (is_callable($var))
		return call_user_func_array($var, [$model]);

	return $var;
}

function addDep (&$dep, $index, $data) {
	if (isset($dep[$index]))
		$dep[$index][]= $data;
	else
		$dep[$index]= [ $data ];
}
