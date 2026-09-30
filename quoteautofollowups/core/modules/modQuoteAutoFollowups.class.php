<?php
/* Copyright (C) 2026       William Mead    <william@m34d.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   quoteautofollowups     Module quoteautofollowups
 *  \brief      quoteautofollowups module descriptor.
 *
 *  \file       htdocs/quoteautofollowups/core/modules/modquoteautofollowups.class.php
 *  \ingroup    quoteautofollowups
 *  \brief      Description and activation file for module QuoteAutoFollowups
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module quoteautofollowups
 */
class modQuoteAutoFollowups extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db);

		global $conf, $langs;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 340009;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'quoteautofollowups';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "interface";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '91';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		$this->familyinfo = array('M34D' => array('position' => '01', 'label' => $langs->trans("M34D")));
		// Module label (no space allowed), used if translation string 'ModuleQuoteAutoFollowupsName' not found (quoteautofollowups is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// DESCRIPTION_FLAG
		// Module description, used if translation string 'ModuleQuoteAutoFollowupsDesc' not found (quoteautofollowups is name of module).
		$this->description = "Automatic follow-ups for commercial proposals";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "Automatic follow-ups for commercial proposals";

		// Author
		$this->editor_name = 'M34D - William Mead';
		$this->editor_url = 'https://m34d.com';		// Must be an external online web site
		$this->editor_squarred_logo = 'logoSquareM34D.png@quoteautofollowups';					// Must be image filename into the module/img directory followed with @modulename. Example: 'myimage.png@quoteautofollowups'

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated', 'experimental_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0.0';
		// Url to the file with your last numberversion of this module
		$this->url_last_version = 'https://github.com/m34dev/dolibarr-module-quote-auto-followups/raw/refs/heads/main/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where quoteautofollowups is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'quoteautofollowups@quoteautofollowups';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 1,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 0,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				'/quoteautofollowups/css/quoteautofollowups.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/quoteautofollowups/js/quoteautofollowups.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			/* BEGIN MODULEBUILDER HOOKSCONTEXTS */
			'hooks' => array(
				'data' => array(
					'propalcard',
				),
				'entity' => '0',
			),
			/* END MODULEBUILDER HOOKSCONTEXTS */
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
			// Set this to 1 if the module provides a website template into doctemplates/websites/website_template-mytemplate
			'websitetemplates' => 0,
			// Set this to 1 if the module provides a captcha driver
			'captcha' => 0
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/quoteautofollowups/temp","/quoteautofollowups/subdir");
		$this->dirs = array("/quoteautofollowups/temp");

		// Config pages. Put here list of php page, stored into quoteautofollowups/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@quoteautofollowups");

		// Dependencies
		// A condition to hide module
		$this->hidden = getDolGlobalInt('MODULE_quoteautofollowups_DISABLED'); // A condition to disable module;
		// List of module class names that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR')...)
		$this->depends = array(
			'always' => array('modPropale', 'modAgenda', 'modCron'),
		);
		// List of module class names to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->requiredby = array();
		// List of module class names this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array();

		// The language file dedicated to your module
		$this->langfiles = array("quoteautofollowups@quoteautofollowups");

		// Prerequisites
		$this->phpmin = array(7, 4); // Minimum version of PHP required by module
		// $this->phpmax = array(8, 0); // Maximum version of PHP required by module
		$this->need_dolibarr_version = array(21, 0); // Minimum version of Dolibarr required by module
		// $this->max_dolibarr_version = array(19, -3); // Maximum version of Dolibarr required by module
		$this->need_javascript_ajax = 0;

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'quoteautofollowupsWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('quoteautofollowups_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('quoteautofollowups_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array(
			0 => array('QUOTEAUTOFOLLOWUPS_ENABLED', 'chaine', '0', 'Enable automatic follow-ups', 0),
			1 => array('QUOTEAUTOFOLLOWUPS_MAX', 'chaine', '3', 'Maximum number of follow-ups per quote', 0),
			2 => array('QUOTEAUTOFOLLOWUPS_DELAYS', 'chaine', '7,15,30', 'Delays in days between follow-ups', 0),
			3 => array('QUOTEAUTOFOLLOWUPS_BATCH_SIZE', 'chaine', '10', 'Maximum number of emails sent per scheduled job run', 0),
			4 => array('QUOTEAUTOFOLLOWUPS_SENDER', 'chaine', 'company', 'Sender of follow-up emails', 0),
			5 => array('QUOTEAUTOFOLLOWUPS_HOUR_START', 'chaine', '9', 'Sending window start hour', 0),
			6 => array('QUOTEAUTOFOLLOWUPS_HOUR_END', 'chaine', '18', 'Sending window end hour', 0),
			7 => array('QUOTEAUTOFOLLOWUPS_WORKDAYS_ONLY', 'chaine', '1', 'Send follow-ups on working days only', 0),
			8 => array('QUOTEAUTOFOLLOWUPS_BCC_MODE', 'chaine', 'none', 'Blind carbon copy of follow-up emails', 0),
		);

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isModEnabled("quoteautofollowups")) {
			$conf->quoteautofollowups = new stdClass();
			$conf->quoteautofollowups->enabled = 0;
		}

		// Array to add new pages in new tabs
		/* BEGIN MODULEBUILDER TABS */
		$this->tabs = array();
		/* END MODULEBUILDER TABS */
		// Example:
		// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data' => 'objecttype:+tabname1:Title1:mylangfile@quoteautofollowups:$user->hasRight(\'quoteautofollowups\', \'read\'):/quoteautofollowups/mynewtab1.php?id=__ID__');
		// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data' => 'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@quoteautofollowups:$user->hasRight(\'othermodule\', \'read\'):/quoteautofollowups/mynewtab2.php?id=__ID__',
		// To remove an existing tab identified by code tabname
		// $this->tabs[] = array('data' => 'objecttype:-tabname:NU:conditiontoremove');
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'delivery'         to add a tab in delivery view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in foundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in sale order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view


		// Dictionaries
		/* Example:
		 $this->dictionaries=array(
		 'langs' => 'quoteautofollowups@quoteautofollowups',
		 // List of tables we want to see into dictionary editor
		 'tabname' => array("table1", "table2", "table3"),
		 // Label of tables
		 'tablib' => array("Table1", "Table2", "Table3"),
		 // Request to select fields
		 'tabsql' => array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.$this->db->prefix().'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.$this->db->prefix().'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.$this->db->prefix().'table3 as f'),
		 // Sort order
		 'tabsqlsort' => array("label ASC", "label ASC", "label ASC"),
		 // List of fields (result of select to show dictionary)
		 'tabfield' => array("code,label", "code,label", "code,label"),
		 // List of fields (list of fields to edit a record)
		 'tabfieldvalue' => array("code,label", "code,label", "code,label"),
		 // List of fields (list of fields for insert)
		 'tabfieldinsert' => array("code,label", "code,label", "code,label"),
		 // Name of columns with primary key (try to always name it 'rowid')
		 'tabrowid' => array("rowid", "rowid", "rowid"),
		 // Condition to show each dictionary
		 'tabcond' => array(isModEnabled('quoteautofollowups'), isModEnabled('quoteautofollowups'), isModEnabled('quoteautofollowups')),
		 // Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
		 'tabhelp' => array(array('code' => $langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), array('code' => $langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), ...),
		 );
		 */
		/* BEGIN MODULEBUILDER DICTIONARIES */
		$this->dictionaries = array();
		/* END MODULEBUILDER DICTIONARIES */

		// Boxes/Widgets
		// Add here list of php file(s) stored in quoteautofollowups/core/boxes that contains a class to show a widget.
		/* BEGIN MODULEBUILDER WIDGETS */
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'quoteautofollowupswidget1.php@quoteautofollowups',
			//      'note' => 'Widget provided by quoteautofollowups',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);
		/* END MODULEBUILDER WIDGETS */

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		/* BEGIN MODULEBUILDER CRON */
		$this->cronjobs = array(
			0 => array(
				// Plain text: the scheduled jobs list does not load module language files
				'label' => 'Quote Auto Followups',
				'jobtype' => 'method',
				'class' => '/quoteautofollowups/class/quoteautofollowups.class.php',
				'objectname' => 'QuoteAutoFollowups',
				'method' => 'runScheduledFollowups',
				'parameters' => '',
				'comment' => 'Sends the follow-up emails of validated quotes that are due (see Quote Auto Followups module setup)',
				'frequency' => 1,
				'unitfrequency' => 3600,
				'status' => 1,
				'test' => 'isModEnabled("quoteautofollowups")',
				'priority' => 50,
			),
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/quoteautofollowups/class/myobject.class.php',
			//      'objectname' => 'MyObject',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => 'isModEnabled("quoteautofollowups")',
			//      'priority' => 50,
			//  ),
		);
		/* END MODULEBUILDER CRON */
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'isModEnabled("quoteautofollowups")', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'isModEnabled("quoteautofollowups")', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$o = 1;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = 'See follow-up information on quotes'; // Permission label
		$this->rights[$r][4] = 'followup';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->hasRight('quoteautofollowups', 'followup', 'read'))
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 2); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Send a follow-up email manually'; // Permission label
		$this->rights[$r][4] = 'followup';
		$this->rights[$r][5] = 'send'; // In php code, permission will be checked by test if ($user->hasRight('quoteautofollowups', 'followup', 'send'))
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf("%02d", ($o * 10) + 3); // Permission id (must not be already used)
		$this->rights[$r][1] = 'Change follow-up settings of a quote (do not follow up, date sent to customer)'; // Permission label
		$this->rights[$r][4] = 'followup';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->hasRight('quoteautofollowups', 'followup', 'write'))
		$r++;
		/* END MODULEBUILDER PERMISSIONS */


		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		/*
		$this->menu[$r++] = array(
			'fk_menu' => '', // Will be stored into mainmenu + leftmenu. Use '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'top', // This is a Top menu entry
			'titre' => 'ModuleQuoteAutoFollowupsName',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle"'),
			'mainmenu' => 'quoteautofollowups',
			'leftmenu' => '',
			'url' => '/quoteautofollowups/quoteautofollowupsindex.php',
			'langs' => 'quoteautofollowups@quoteautofollowups', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("quoteautofollowups")', // Define condition to show or hide menu entry. Use 'isModEnabled("quoteautofollowups")' if entry must be visible if module is enabled.
			'perms' => '1', // Use 'perms'=>'$user->hasRight("quoteautofollowups", "myobject", "read")' if you want your menu with a permission rules
			'target' => '',
			'user' => 2, // 0=Menu for internal users, 1=external users, 2=both
		);
		*/
		/* END MODULEBUILDER TOPMENU */

		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */
		/*
		$this->menu[$r++]=array(
			'fk_menu' => 'fk_mainmenu=quoteautofollowups',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',                          // This is a Left menu entry
			'titre' => 'MyObject',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu' => 'quoteautofollowups',
			'leftmenu' => 'myobject',
			'url' => '/quoteautofollowups/quoteautofollowupsindex.php',
			'langs' => 'quoteautofollowups@quoteautofollowups',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("quoteautofollowups")', // Define condition to show or hide menu entry. Use 'isModEnabled("quoteautofollowups")' if entry must be visible if module is enabled.
			'perms' => '$user->hasRight("quoteautofollowups", "myobject", "read")',
			'target' => '',
			'user' => 2,				                // 0=Menu for internal users, 1=external users, 2=both
			'object' => 'MyObject'
		);
		$this->menu[$r++]=array(
			'fk_menu' => 'fk_mainmenu=quoteautofollowups,fk_leftmenu=myobject',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',			                // This is a Left menu entry
			'titre' => 'New_MyObject',
			'mainmenu' => 'quoteautofollowups',
			'leftmenu' => 'quoteautofollowups_myobject_new',
			'url' => '/quoteautofollowups/myobject_card.php?action=create',
			'langs' => 'quoteautofollowups@quoteautofollowups',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("quoteautofollowups")', // Define condition to show or hide menu entry. Use 'isModEnabled("quoteautofollowups")' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms' => '$user->hasRight("quoteautofollowups", "myobject", "write")'
			'target' => '',
			'user' => 2,				                // 0=Menu for internal users, 1=external users, 2=both
			'object' => 'MyObject'
		);
		$this->menu[$r++]=array(
			'fk_menu' => 'fk_mainmenu=quoteautofollowups,fk_leftmenu=myobject',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',			                // This is a Left menu entry
			'titre' => 'List_MyObject',
			'mainmenu' => 'quoteautofollowups',
			'leftmenu' => 'quoteautofollowups_myobject_list',
			'url' => '/quoteautofollowups/myobject_list.php',
			'langs' => 'quoteautofollowups@quoteautofollowups',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("quoteautofollowups")', // Define condition to show or hide menu entry. Use 'isModEnabled("quoteautofollowups")' if entry must be visible if module is enabled.
			'perms' => '$user->hasRight("quoteautofollowups", "myobject", "read")'
			'target' => '',
			'user' => 2,				                // 0=Menu for internal users, 1=external users, 2=both
			'object' => 'MyObject'
		);
		*/
		/* END MODULEBUILDER LEFTMENU MYOBJECT */


		// Exports profiles provided by this module
		$r = 0;
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */
		/*
		$langs->load("quoteautofollowups@quoteautofollowups");
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = $this->picto;
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'MyObject'; $keyforclassfile='/quoteautofollowups/class/myobject.class.php'; $keyforelement='myobject@quoteautofollowups';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'MyObjectLine'; $keyforclassfile='/quoteautofollowups/class/myobject.class.php'; $keyforelement='myobjectline@quoteautofollowups'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject@quoteautofollowups';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='myobjectline'; $keyforaliasextra='extraline'; $keyforelement='myobjectline@quoteautofollowups';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('myobjectline' => array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field' => '...');
		//$this->export_examplevalues_array[$r] = array('t.field' => 'Example');
		//$this->export_help_array[$r] = array('t.field' => 'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.$this->db->prefix().'quoteautofollowups_myobject as t';
		//$this->export_sql_end[$r]  .=' LEFT JOIN '.$this->db->prefix().'quoteautofollowups_myobject_line as tl ON tl.fk_myobject = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('myobject').')';
		$r++; */
		/* END MODULEBUILDER EXPORT MYOBJECT */

		// Imports profiles provided by this module
		$r = 0;
		/* BEGIN MODULEBUILDER IMPORT MYOBJECT */
		/*
		$langs->load("quoteautofollowups@quoteautofollowups");
		$this->import_code[$r] = $this->rights_class.'_'.$r;
		$this->import_label[$r] = 'MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r] = $this->picto;
		$this->import_tables_array[$r] = array('t' => $this->db->prefix().'quoteautofollowups_myobject', 'extra' => $this->db->prefix().'quoteautofollowups_myobject_extrafields');
		$this->import_tables_creator_array[$r] = array('t' => 'fk_user_author'); // Fields to store import user id
		$import_sample = array();
		$keyforclass = 'MyObject'; $keyforclassfile='/quoteautofollowups/class/myobject.class.php'; $keyforelement='myobject@quoteautofollowups';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
		$import_extrafield_sample = array();
		$keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject@quoteautofollowups';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.$this->db->prefix().'quoteautofollowups_myobject');
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.ref' => 'Ref');
		$this->import_convertvalue_array[$r] = array(
			't.ref' => array(
				'rule'=>'getrefifauto',
				'class'=>(!getDolGlobalString('quoteautofollowups_MYOBJECT_ADDON') ? 'mod_myobject_standard' : getDolGlobalString('quoteautofollowups_MYOBJECT_ADDON')),
				'path'=>"/core/modules/quoteautofollowups/".(!getDolGlobalString('quoteautofollowups_MYOBJECT_ADDON') ? 'mod_myobject_standard' : getDolGlobalString('quoteautofollowups_MYOBJECT_ADDON')).'.php',
				'classobject'=>'MyObject',
				'pathobject'=>'/quoteautofollowups/class/myobject.class.php',
			),
			't.fk_soc' => array('rule' => 'fetchidfromref', 'file' => '/societe/class/societe.class.php', 'class' => 'Societe', 'method' => 'fetch', 'element' => 'ThirdParty'),
			't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
			't.fk_mode_reglement' => array('rule' => 'fetchidfromcodeorlabel', 'file' => '/compta/paiement/class/cpaiement.class.php', 'class' => 'Cpaiement', 'method' => 'fetch', 'element' => 'cpayment'),
		);
		$this->import_run_sql_after_array[$r] = array();
		$r++; */
		/* END MODULEBUILDER IMPORT MYOBJECT */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int<-1,1>          	1 if OK, <=0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$langs->load("quoteautofollowups@quoteautofollowups");

		// Leftover from the module template
		dolibarr_del_const($this->db, 'quoteautofollowups_OPTION', $conf->entity);

		// Create extrafields during init
		// 'list' visibility: 4 = list + card (not on creation form), 5 = list + card, read-only.
		// Negative values mean the column is available but not shown by default in the list.
		// 'perms' only controls who can see the field: editing requires the proposal "creer" right, and
		// ActionsQuoteAutoFollowups::doActions() additionally requires the module's 'write' right.
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		$readperm = '$user->hasRight("quoteautofollowups", "followup", "read")';
		$enabled = 'isModEnabled("quoteautofollowups")';
		$langfile = 'quoteautofollowups@quoteautofollowups';
		$extrafields->addExtraField('qaf_donotfollowup', 'QAFDoNotFollowup', 'boolean', 1001, '', 'propal', 0, 0, '', '', 1, $readperm, '4', 'QAFDoNotFollowupHelp', '', '', $langfile, $enabled);
		$extrafields->addExtraField('qaf_sentdate', 'QAFSentDate', 'date', 1002, '', 'propal', 0, 0, '', '', 1, $readperm, '4', 'QAFSentDateHelp', '', '', $langfile, $enabled);
		$extrafields->addExtraField('qaf_count', 'QAFCount', 'int', 1003, '10', 'propal', 0, 0, '0', '', 0, $readperm, '5', '', '', '', $langfile, $enabled);
		$extrafields->addExtraField('qaf_lastdate', 'QAFLastDate', 'datetime', 1004, '', 'propal', 0, 0, '', '', 0, $readperm, '-5', '', '', '', $langfile, $enabled);
		$extrafields->addExtraField('qaf_lasterror', 'QAFLastError', 'varchar', 1005, '255', 'propal', 0, 0, '', '', 0, $readperm, '5', '', '', '', $langfile, $enabled);

		$result = $this->initEmailTemplate();
		if ($result < 0) {
			return $result;
		}

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		/*$moduledir = dol_sanitizeFileName('quoteautofollowups');
		$myTmpObjects = array();
		$myTmpObjects['MyObject'] = array('includerefgeneration' => 0, 'includedocgeneration' => 0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_myobjects.odt';
				$dirodt = DOL_DATA_ROOT.($conf->entity > 1 ? '/'.$conf->entity : '').'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_myobjects.odt';

				if (file_exists($src) && !file_exists($dest)) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					dol_mkdir($dirodt);
					$result = dol_copy($src, $dest, '0', 0);
					if ($result < 0) {
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
						return 0;
					}
				}

				$sql = array_merge($sql, array(
					"DELETE FROM ".$this->db->prefix()."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".$this->db->prefix()."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")",
					"DELETE FROM ".$this->db->prefix()."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".$this->db->prefix()."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")"
				));
			}
		}*/

		return $this->_init($sql, $options);
	}

	/**
	 * Create the default follow-up email template (Setup > Emails > Email templates, type "Commercial
	 * proposals") on first activation, and select it in the module setup if none is selected yet.
	 *
	 * The template is written in the language of the admin activating the module, with lang = '' so it
	 * is used for every customer language; other language variants can be added from the native editor.
	 *
	 * @return int<-1,1>	1 if OK, -1 if KO
	 */
	private function initEmailTemplate()
	{
		global $conf, $langs;

		$sql = "SELECT rowid FROM ".$this->db->prefix()."c_email_templates";
		$sql .= " WHERE module = 'quoteautofollowups' AND type_template = 'propal_send'";
		$sql .= " AND entity = ".((int) $conf->entity);
		$sql .= " ORDER BY rowid ASC";
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		$obj = $this->db->fetch_object($resql);
		$templateid = $obj ? (int) $obj->rowid : 0;
		$this->db->free($resql);

		if (!$templateid) {
			$sql = "INSERT INTO ".$this->db->prefix()."c_email_templates";
			$sql .= " (entity, module, type_template, lang, private, fk_user, datec, label, position, enabled, active, topic, content, content_lines, joinfiles)";
			$sql .= " VALUES (".((int) $conf->entity).", 'quoteautofollowups', 'propal_send', '', 0, null, '".$this->db->idate(dol_now())."'";
			$sql .= ", '".$this->db->escape($langs->transnoentitiesnoconv('QAFDefaultTemplateLabel'))."', 100, '".$this->db->escape('isModEnabled("quoteautofollowups")')."', 1";
			$sql .= ", '".$this->db->escape($langs->transnoentitiesnoconv('QAFDefaultTemplateTopic'))."'";
			$sql .= ", '".$this->db->escape($langs->transnoentitiesnoconv('QAFDefaultTemplateContent'))."'";
			$sql .= ", null, 1)";
			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				return -1;
			}
			$templateid = (int) $this->db->last_insert_id($this->db->prefix()."c_email_templates");
		}

		if (!getDolGlobalInt('QUOTEAUTOFOLLOWUPS_EMAIL_TEMPLATE') && $templateid > 0) {
			dolibarr_set_const($this->db, 'QUOTEAUTOFOLLOWUPS_EMAIL_TEMPLATE', $templateid, 'chaine', 0, '', $conf->entity);
		}

		return 1;
	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted
	 *
	 *	@param	string		$options	Options when enabling module ('', 'noboxes')
	 *	@return	int<-1,1>				1 if OK, <=0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
