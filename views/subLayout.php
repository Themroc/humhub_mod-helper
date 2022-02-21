<?php

$model= $this->context->getModel();
$widget= $this->context->getWidget();

$this->beginContent('@admin/views/layouts/main.php');

echo '<div class="panel panel-default">'."\n";
echo '	<div class="panel-heading">'."\n";
echo '		<strong>'.$model->mod['name'].'</strong> '.Yii::t('ModHelperModule.base', 'module configuration')."\n";
echo '	</div>'."\n";
if (isset($widget))
	echo $widget::widget();
echo '	<div class="panel-body">'."\n";
echo '		' . $content . "\n";
echo '	</div>'."\n";
echo '</div>'."\n";

$this->endContent();
