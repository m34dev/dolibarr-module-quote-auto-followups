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
			// Set this to relative path of css file if module has its own css file (none: no need to load one on every page)
			'css' => array(),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(),
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

		if (!isModEnabled("quoteautofollowups")) {
			$conf->quoteautofollowups = new stdClass();
			$conf->quoteautofollowups->enabled = 0;
		}

		// Array to add new pages in new tabs
		/* BEGIN MODULEBUILDER TABS */
		$this->tabs = array();
		/* END MODULEBUILDER TABS */

		// Dictionaries
		/* BEGIN MODULEBUILDER DICTIONARIES */
		$this->dictionaries = array();
		/* END MODULEBUILDER DICTIONARIES */

		// Boxes/Widgets
		// Add here list of php file(s) stored in quoteautofollowups/core/boxes that contains a class to show a widget.
		/* BEGIN MODULEBUILDER WIDGETS */
		$this->boxes = array();
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
		);
		/* END MODULEBUILDER CRON */

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
