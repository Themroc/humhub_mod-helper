### Description
Hassle remover for module developers.

Takes care of config-forms. No more tedious creation of view-files needed.
With it, a very simple model-file could look like

    <?php
    namespace ME\humhub\modules\MYMODULE\models;
    
    class AdminModel extends \themroc\humhub\modules\modhelper\models\AdminForm
    {
    	public $some_text;
    }

The Controller:

    <?php
    namespace ME\humhub\modules\MYMODULE\controllers;
    
    use Yii;
    use ME\humhub\modules\MYMODULE\models\AdminForm;
    
    class AdminController extends \humhub\modules\admin\components\Controller
    {
    	public function actionIndex()
    	{
    		if (Yii::$app->getModule('mod-helper')===null)
    			return $this->render('error', [
    				'msg'=> 'Please install and activate the <a href="https://github.com/Themroc/humhub_mod-helper" target="_blank">Mod-Helper plugin</a>.'
    			]);
    
    		$model= new AdminForm();
    		if ($model->load(Yii::$app->request->post()) && $model->save())
    			$this->view->saved();
    
    		return $this->render('@mod-helper/views/form', [
    			'model'=> $model
    		]);
    	}
    }

In that case, you get a configure-form with one string field labeled "Some Text"
and a "save"-button. When clicked, the content of $some_text will be saved in
table "setting".

Usually though, a bit more configuration will be needed. Like,

    <?php
    
    namespace ME\humhub\modules\MYMODULE\models;
    
    use humhub\modules\ui\form\widgets\IconPicker;
    
    class AdminModel extends \themroc\humhub\modules\modhelper\models\AdminForm
    {
    	public $icon;
    	public $text_enable;
    	public $some_text;
    
    	protected $vars= [
    		'icon'=> [
    			'label'=> 'Select icon',
    			'trans'=> 'UiModule.form',
    			'form'=> ['type'=> 'widget', 'class'=> IconPicker::class],
    		],
    		'text_enable'=> [
    			'label'=> 'Show useless text field',
    			'rules'=> ['in', 'range'=> [0, 1]],
    			'form'=> ['type'=> 'checkbox'],
    		],
    		'some_text'=> [
    			'label'=> 'Some random text',
    			'hints'=> 'Type in whatever you like, it will be ignored anyway.',
    			'form'=> ['depends'=> ['text_enable'=> 1]],
    		],
    	];
    }

More examples can be found in https://github.com/Themroc/humhub_iframe/blob/master/models/AdminForm.php.

### Installation

Add folder */protected/modules/mod-helper, unzip this into it and activate the
module in Administration / Modules.

__Module website:__ <https://github.com/Themroc/humhub_mod-helper>

__Author:__ Themroc <7hemroc@gmail.com>

### Changelog

<https://github.com/Themroc/humhub_mod-helper/commits/master>

### Bugtracker

<https://github.com/Themroc/humhub_mod-helper/issues>

### ToDos
- More formulas than just var=value should be possible in 'form'=> ['depends'=> [...]]
- Decent documentation

### License

GNU AFFERO GENERAL PUBLIC LICENSE
Version 3, 19 November 2007
https://www.humhub.org/de/licences

Contains icon grapic made by https://www.flaticon.com/packs/material-design,
License: https://creativecommons.org/licenses/by/3.0/
