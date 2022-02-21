<?php

use humhub\libs\Html;
use humhub\widgets\ActiveForm;
use yii\helpers\Html as yHtml;

themroc\humhub\modules\modhelper\assets\Assets::register($this);

$m= $model;
$a= explode('\\', get_class($m)); #'
$fname= array_pop($a);
$fname_lc= strtolower($fname);

$mod= $m->getMod();
$vars= $m->getVars();
$mform= isset($mod['form']) ? $mod['form'] : [];

if (! isset($api))
	$api= 0;
if (! isset($isTabbed))
	$isTabbed= isset($standAlone) ? !$standAlone : 0;
if (! isset($isContainer))
	$isContainer= isset($this->context->contentContainer) ? 1 : 0;

$show_header= (! $isTabbed && $api<1) || $isContainer ? 1 : 0;

list($depends, $disable, $code)=
	$m->collectViewVars(
		'.field-'.$fname_lc.'-%s',          // target:field
		'input[name="'.$fname.'\\[%s\\]"]', // src:radio
		'#'.$fname_lc.'-%s',                // src:other / func
	);

// hide disabled fields
if (count($disable))
	echo "<style>\n".join(",", $disable)."{display:none}\n</style>\n";

if (!empty($code))
	echo "<script>\nvar modhelper_func={\n".$code."\n};\n</script>\n";

// pass dependencies to js
$jdep= [];
foreach ($depends as $k => $v)
	$jdep[]= [ $k, $v ];
$this->registerJsConfig(['modhelper'=> ['dep'=> $jdep]]);

echo "<script>\nif (typeof humhub.modules.modhelper!=='undefined') humhub.modules.modhelper.run()\n</script>\n";

$ind= "";
echo '<div class="panel panel-default">'."\n";
if ($show_header)
	echo "\t".'<div class="panel-heading"><strong>'.$mod['name'].'</strong> '
		. Yii::t('ModHelperModule.base', 'module configuration')
		. '</div>'."\n";
echo '	<div class="panel-body">'."\n";
$ind= "\t\t";

$ind2= $ind . "\t";

$aform= ActiveForm::begin(['id' => 'configure-form']);

echo $ind.'<div class="form-group">'."\n";

foreach ($vars as $k => $v) {
	$vform= isset($v['form']) ? $v['form'] : [];
	$options= isset($vform['options']) ? $m->va(@$vform['options']) : [];
	echo $m->vs($m->vDefault2($vform, $v, 'prefix'), $ind2, "\n");
	$type= isset($vform['type']) ? $vform['type'] : (property_exists($m, $k) ? '' : 'help');
	switch ($type) {
		case 'checkbox':
			echo $ind2 . $aform->field($m, $k)->checkbox($options) . "\n";
			break;
		case 'dropdown':
			echo $ind2 . $aform->field($m, $k)->dropDownList($m->vaTrans($m->vDefault($vform, 'items', 'params'), $k), $options) . "\n";
			break;
		case 'radio':
			echo $ind2 . $aform->field($m, $k)->radioList($m->vaTrans($m->vDefault($vform, 'items', 'params'), $k), $options) . "\n";
			break;
		case 'textarea':
			echo $ind2 . $aform->field($m, $k)->textarea($options) . "\n";
			break;
		case 'widget':
			echo $ind2 . $aform->field($m, $k)->widget($vform['class'], $options) . "\n";
			break;
		case 'hidden':
			echo $ind2 . $aform->field($m, $k, ['labelOptions'=> ['style'=> 'display:none']])->hiddenInput($options) . "\n";
			break;
		case 'help':
			echo $ind2 . '<div class="help-block">' . Yii::t($mod['trans'], $v['hints']) . "</div>\n";
			break;
		default:
			echo $ind2 . $aform->field($m, $k)->input("text", $options) . "\n";
	}
	echo $m->vs($m->vDefault2($vform, $v, 'suffix'), $ind2, "\n");
}
echo $ind2 . '</div>'."\n";

echo $ind2 . '<div class="form-group">'."\n";
if (isset($mform['btn_pre']))
	echo $m->vs($mform['btn_pre'], $ind2, "\n");
echo $ind2."\t".Html::saveButton()."\n";
if ('' != $del= $m->getDeleteBtn())
	echo $ind2.$del."\n";
if (isset($mform['btn_post']))
	echo $m->vs($mform['btn_post'], $ind2, "\n");
echo $ind2 . '</div>'."\n";

ActiveForm::end();

echo "\t</div>\n";
echo "</div>\n";
