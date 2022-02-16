<?php

use humhub\libs\Html;
use humhub\widgets\ActiveForm;
use yii\helpers\Html as yHtml;

themroc\humhub\modules\modhelper\assets\Assets::register($this);

$a= explode('\\', get_class($model));
$fname= array_pop($a);
$fname_lc= strtolower($fname);

$mod= $model->getMod();
$vars= $model->getVars();
$mform= isset($mod['form']) ? $mod['form'] : [];

if (! isset($isTabbed))
	$isTabbed= isset($standAlone) ? !$standAlone : 0;

list($depends, $disable, $code)=
	$model->collectViewVars(
		'.field-'.$fname_lc.'-%s',          // target:field
		'input[name="'.$fname.'\\[%s\\]"]', // src:radio
		'#'.$fname_lc.'-%s',                // src:other / func
	);

// hide disabled fields
if (count($disable))
	echo "<style>\n".join(",", $disable)."{display:none}\n</style>\n";

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
	$vform= isset($v['form']) ? $v['form'] : [];
	$options= isset($vform['options']) ? $model->va(@$vform['options']) : [];
	echo $model->vs($model->vDefault2($vform, $v, 'prefix'), $ind2, "\n");
	if (empty($model[$k]) && !empty($v['default']))
		$model[$k]= $model->va($v['default']);
	$type= !empty($vform['type']) ? $vform['type'] : '';
	switch ($type) {
		case 'checkbox':
			echo $ind2 . $aform->field($model, $k)->checkbox($options) . "\n";
			break;
		case 'dropdown':
		    echo $ind2 . $aform->field($model, $k)->dropDownList($model->vaTrans($vform['items'], $k), $options) . "\n";
			break;
		case 'radio':
		    echo $ind2 . $aform->field($model, $k)->radioList($model->vaTrans($vform['items'], $k), $options) . "\n";
			break;
		case 'textarea':
			echo $ind2 . $aform->field($model, $k)->textarea($options) . "\n";
			break;
		case 'widget':
			echo $ind2 . $aform->field($model, $k)->widget($vform['class'], $options) . "\n";
			break;
		case 'hidden':
			echo $ind2 . $aform->field($model, $k, ['labelOptions'=> ['style'=> 'display:none']])->hiddenInput($options) . "\n";
			break;
		default:
			echo $ind2 . $aform->field($model, $k)->input("text", $options) . "\n";
	}
	echo $model->vs($model->vDefault2($vform, $v, 'suffix'), $ind2, "\n");
}
echo $ind2 . '</div>'."\n";

echo $ind2 . '<div class="form-group">'."\n";
if (isset($mform['btn_pre']))
	echo $model->vs($mform['btn_pre'], $ind2, "\n");
echo $ind2."\t".Html::saveButton()."\n";
if ('' != $del= $model->getDeleteBtn())
	echo $ind2.$del."\n";
if (isset($mform['btn_post']))
	echo $model->vs($mform['btn_post'], $ind2, "\n");
echo $ind2 . '</div>'."\n";

ActiveForm::end();

if (! $isTabbed) {
	echo '	</div>'."\n";
	echo '</div>'."\n";
}
