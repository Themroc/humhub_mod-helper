### Description
Takes care of config-forms. No more tedious creation of view-files
needed. Just give it your model and a few hints on how your attributes
should be presented. The simplest model-file could look like

	<?php
	namespace ME\humhub\modules\MYMODULE\models;

	class AdminModel extends \themroc\humhub\modules\modhelper\models\AdminForm
	{
		const MH_API= 1;

		public $some_text;
	}

The Controller:

	<?php

	namespace ME\humhub\modules\MYMODULE\controllers;

	use Yii;
	use humhub\modules\admin\components\Controller;
	use themroc\humhub\modules\modhelper\behaviors\MhAdminController;
	use ME\humhub\modules\MYMODULE\models\AdminForm;

	class AdminController extends Controller
	{
		public $adminOnly= true;
		public $isTabbed= true;
		public $modelClass= AdminForm::class;

		public function init ()
		{
			if (null != Yii::$app->getModule('mod-helper'))
				$this->attachBehavior('MhAdmin', new MhAdminController());

			return parent::init();
		}

		public function actionIndex ()
		{
			if (null == Yii::$app->getModule('mod-helper'))
				return $this->render('error', [
					'msg'=> 'Please install and activate the <a href="https://github.com/Themroc/humhub_mod-helper" target="_blank">Mod-Helper plugin</a>.',
				]);

			return $this->MHactionIndex();
		}
	}

In that case, a configure-form with one string field labeled "Some Text"
and a "save"-button will be rendered. When clicked, the content of
$some_text will be saved in table "setting".

Usually though, a bit more configuration will be needed. Like,

	<?php

	namespace ME\humhub\modules\MYMODULE\models;

	use humhub\modules\ui\form\widgets\IconPicker;

	class AdminModel extends \themroc\humhub\modules\modhelper\models\AdminForm
	{
		const MH_API= 1;

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
				'prefix'=> '<div class="some-box">',
				'label'=> 'Show useless text field',
				'rules'=> ['in', 'range'=> [0, 1]],
				'form'=> ['type'=> 'checkbox'],
			],
			'some_text'=> [
				'label'=> 'Some random text',
				'hints'=> 'Type in whatever you like, it will be ignored anyway.',
				'form'=> ['visible'=> ['text_enable'=> 1]],
				'suffix'=> '</div>',
			],
		];
	}

This will give an icon picker and box containing a string field that can
be hidden. Plus the usual "save"-button, of course. More examples can be
found in https://github.com/Themroc/humhub_iframe/blob/master/models/AdminForm.php.

Possible config options are:

	$model->mod[
		'_'=>				object	= Yii::$app->getModule()
		'settings'=>			object	= Yii::$app->getModule()->settings
		'id'=>				string	= Yii::$app->controller->module->id
		'name'=>			string	If unset, = ucfirst(mod['id'])
		'trans'=>			string	Translation category. If unset, derived from mod['id'].
							See https://docs.humhub.org/docs/develop/i18n/

		'options'=> [
			'tab_attr'=>		string	Attribute name whose content selects a tab
			'tab_sort'=>		mixed	If string: Attribute name whose content determines tab order
							If callable: `function ($tab_list)` returns sorted tab list
		],

		'form'=> [
			'btn_pre'=>		string	Will be emitted verbatim before the "save" button code
			'btn_post'=>		string	Will be emitted verbatim after the "save" button code
		],
	];

	$model->vars[
		'attribute_name'=> [
			'rules'=>		mixed	A single string like 'number' will give a ['number']-rule.
							A single array will be passed as is.
							A nested array gives multiple rules. Ex. [ ['required'], ['in', 'range'=> [0, 1]] ]
							See https://www.yiiframework.com/doc/api/2.0/yii-base-model#rules()-detail
			'label'=>		string	If unset, will be derived from attribute name.
							See https://www.yiiframework.com/doc/api/2.0/yii-base-model#attributeLabels()-detail
			'hints'=>		string	Explanatory text.
							See https://www.yiiframework.com/doc/api/2.0/yii-base-model#attributeHints()-detail
			'trans'=>		string	Translation category. If unset, taken from mod['trans'].
			'default'=>		string	This will show up if attribute content is empty

			'options'=> [
				'nosave'=>	number	If !=0, attribute content will not be stored in db
				'notrim'=>	number	If !=0, attribute content will not be trimmed prior to saving
			],

			'form'=> [
				'type'=>	string	One of 'checkbox', 'dropdown', 'radio', 'textarea', 'widget', 'hidden' or 'text' (the default)
				'class'=>	class	yii widget class if type=='widget'
							See https://www.yiiframework.com/doc/api/2.0/yii-widgets-activefield#widget()-detail
				'items'=>	array	Item list for dropdown or radio. Can be either a string like ".Label 1.Another label"
							an array like [ 0=> 'Label 1', 1=> 'Another label' ] or some reference to a function
							that returns such an array/string. Items will be translated using the category in 'trans'.
				'options'=>	array	Will be passed to yii widget's $options variable
							See https://www.yiiframework.com/doc/api/2.0/yii-widgets-activefield
				'visible'=>	array	List of conditions all of which must be fulfilled for field to be visible.
							Ex. ['block_enable'=> 3, 'text_enable'=> 1]
				'depends'=>	array	Old, deprecated name for 'visible'
				'prefix'=>	string	Will be emitted verbatim before the widget code
				'suffix'=>	string	Will be emitted verbatim after the widget code
			],

			'function'=> [	// function follows form :)
				'depends'=>	array	List of attributes whose content will determine the content of this attribute. Ex.
							['protocol', 'domain']
				'code'=>	string	Javascript code to create field content. Will be used as body of a function that
							will be called whenever one of the fields in 'depends' changes. Must return the result.
							Substrings in the form of '@attribute_name@' will be replaced by the actual attribute
							name in javascript. Ex. 'return $("@protocol@").val() + "://" + $("@domain@").val() + "/"'
			],

			// deprecated:
			'prefix'=>		string	See 'form'
			'suffix'=>		string	See 'form'
		];
	];

All strings and arrays in `vars` can also be a callable. If a string is given
where an array is expected, it will be split using the first character as
separator.

`mod` and `vars` can also be supplied by `protected function`s that
should return an array like above. If those arrays are already present,
elements of them will get overridden by the corresponding element from
the function.

### Installation

Unzip this into */protected/modules/mod-helper and activate the
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
