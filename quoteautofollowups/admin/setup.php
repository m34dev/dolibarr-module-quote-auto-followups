<?php
/* Copyright (C) 2026       William Mead    <william@m34d.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    quoteautofollowups/admin/setup.php
 * \ingroup quoteautofollowups
 * \brief   QuoteAutoFollowups setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/quoteautofollowups.lib.php';
require_once '../class/quoteautofollowups.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "mails", "cron", "quoteautofollowups@quoteautofollowups"));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
/** @var HookManager $hookmanager */
$hookmanager->initHooks(array('quoteautofollowupssetup', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$setupnotempty = 0;

// Access control
if (!$user->admin) {
	accessforbidden();
}


if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);
$form = new Form($db);
$helper = new QuoteAutoFollowups($db);

$yesnooptions = array(
	'1' => $langs->trans('Yes'),
	'0' => $langs->trans('No'),
);

// Enter here all parameters in your setup page

$formSetup->newItem('QAFSetupSectionSending')->setAsTitle();

// Not setAsYesNo(): that renders as an ajax on/off toggle that DELETES the constant row when switched
// off and bypasses the form save (so the start date below could not be initialized on activation).
// A plain select always writes the literal '0'/'1'.
$enableditem = $formSetup->newItem('QUOTEAUTOFOLLOWUPS_ENABLED');
$enableditem->setAsSelect($yesnooptions);
$enableditem->defaultFieldValue = '0';

$maxitem = $formSetup->newItem('QUOTEAUTOFOLLOWUPS_MAX');
$maxitem->defaultFieldValue = '3';
$maxitem->fieldAttr = array('type' => 'number', 'min' => 1, 'max' => 99, 'class' => 'flat width75');
$maxitem->setValueFromPostCallBack(quoteAutoFollowupsIntegerValidator($maxitem, 1, 99));

$delaysitem = $formSetup->newItem('QUOTEAUTOFOLLOWUPS_DELAYS');
$delaysitem->setAsString();
$delaysitem->defaultFieldValue = '7,15,30';
$delaysitem->fieldAttr = array('placeholder' => '7,15,30', 'class' => 'flat minwidth200');
$delaysitem->setValueFromPostCallBack(function () use ($delaysitem) {
	global $langs;
	$value = preg_replace('/\s+/', '', GETPOST($delaysitem->confKey, 'alpha'));
	if (!QuoteAutoFollowups::isValidDelays($value)) {
		setEventMessages($langs->trans('QAFErrorInvalidDelays'), null, 'errors');
		return -1;
	}
	$delaysitem->fieldValue = $value;
	return 1;
});

$startdateitem = $formSetup->newItem('QUOTEAUTOFOLLOWUPS_START_DATE');
$startdate = $helper->getStartDate();
$startdateitem->fieldInputOverride = $form->selectDate($startdate ? $startdate : -1, 'QUOTEAUTOFOLLOWUPS_START_DATE', 0, 0, 1, '', 1, 1, 0, '', '', '', '', 1, '', '', 'tzserver');
$startdateitem->fieldOutputOverride = ($startdate ? dol_print_date($startdate, 'day', 'tzserver') : '');
$startdateitem->setValueFromPostCallBack(function () use ($startdateitem) {
	$year = GETPOSTINT('QUOTEAUTOFOLLOWUPS_START_DATEyear');
	$month = GETPOSTINT('QUOTEAUTOFOLLOWUPS_START_DATEmonth');
	$day = GETPOSTINT('QUOTEAUTOFOLLOWUPS_START_DATEday');
	$startdateitem->fieldValue = ($year > 0 && $month > 0 && $day > 0) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : '';
	return 1;
});

$formSetup->newItem('QAFSetupSectionSchedule')->setAsTitle();

$batchitem = $formSetup->newItem('QUOTEAUTOFOLLOWUPS_BATCH_SIZE');
$batchitem->defaultFieldValue = '10';
$batchitem->fieldAttr = array('type' => 'number', 'min' => 1, 'max' => 1000, 'class' => 'flat width75');
$batchitem->setValueFromPostCallBack(quoteAutoFollowupsIntegerValidator($batchitem, 1, 1000));

$hours = array();
for ($h = 0; $h <= 24; $h++) {
	$hours[(string) $h] = sprintf('%02d:00', $h);
}
$hourstartitem = $formSetup->newItem('QUOTEAUTOFOLLOWUPS_HOUR_START');
$hourstartitem->setAsSelect(array_slice($hours, 0, 24, true));
$hourstartitem->defaultFieldValue = '9';
$hourenditem = $formSetup->newItem('QUOTEAUTOFOLLOWUPS_HOUR_END');
$hourenditem->setAsSelect(array_slice($hours, 1, 24, true));
$hourenditem->defaultFieldValue = '18';

$workdaysitem = $formSetup->newItem('QUOTEAUTOFOLLOWUPS_WORKDAYS_ONLY');
$workdaysitem->setAsSelect($yesnooptions);
$workdaysitem->defaultFieldValue = '1';

$formSetup->newItem('QAFSetupSectionEmail')->setAsTitle();

$senderitem = $formSetup->newItem('QUOTEAUTOFOLLOWUPS_SENDER');
$senderitem->setAsSelect($helper->getSenderOptions());
$senderitem->defaultFieldValue = 'company';

$templateitem = $formSetup->newItem('QUOTEAUTOFOLLOWUPS_EMAIL_TEMPLATE');
// Not setAsEmailTemplate(): it also lists the private templates of the current user, which the
// scheduled job (running as another user) could not use.
$templateitem->setAsSelect(array(0 => $langs->trans('None')) + $helper->getTemplateOptions());

$bccmodeitem = $formSetup->newItem('QUOTEAUTOFOLLOWUPS_BCC_MODE');
$bccmodeitem->setAsSelect(array(
	'none' => $langs->trans('QAFBccNone'),
	'salesrep' => $langs->trans('QAFBccSalesRep'),
	'fixed' => $langs->trans('QAFBccFixed'),
));
$bccmodeitem->defaultFieldValue = 'none';

$bccemailitem = $formSetup->newItem('QUOTEAUTOFOLLOWUPS_BCC_EMAIL');
$bccemailitem->setAsString();
$bccemailitem->setValueFromPostCallBack(function () use ($bccemailitem) {
	global $langs;
	$value = trim(GETPOST($bccemailitem->confKey, 'alpha'));
	if ($value !== '' && !isValidEmail($value)) {
		setEventMessages($langs->trans('ErrorBadEMail', $value), null, 'errors');
		return -1;
	}
	if ($value === '' && GETPOST('QUOTEAUTOFOLLOWUPS_BCC_MODE', 'aZ09') == 'fixed') {
		setEventMessages($langs->trans('QAFErrorBccEmailRequired'), null, 'errors');
		return -1;
	}
	$bccemailitem->fieldValue = $value;
	return 1;
});

$setupnotempty += count($formSetup->items);

/*
 * Actions
 */

// Same as core/actions_setmoduleoptions.inc.php does for a FormSetup, but keeping the result
if ($action == 'update') {
	$result = $formSetup->saveConfFromPost();

	// When automatic follow-ups are switched on without a start date, start from today: otherwise every
	// old validated quote still within its validity would be followed up at once.
	if ($result > 0 && getDolGlobalInt('QUOTEAUTOFOLLOWUPS_ENABLED') && !getDolGlobalString('QUOTEAUTOFOLLOWUPS_START_DATE')) {
		dolibarr_set_const($db, 'QUOTEAUTOFOLLOWUPS_START_DATE', dol_print_date(dol_now(), '%Y-%m-%d', 'tzserver'), 'chaine', 0, '', $conf->entity);
		setEventMessages($langs->trans('QAFStartDateInitialized', dol_print_date(dol_now(), 'day', 'tzserver')), null, 'warnings');
	}
	if ($result > 0) {
		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}
}

$action = 'edit';

/*
 * View
 */

$help_url = '';
$title = "QuoteAutoFollowupsSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-quoteautofollowups page-admin');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'object_quoteautofollowups@quoteautofollowups');

// Configuration header
$head = quoteAutoFollowupsAdminPrepareHead();
print dol_get_fiche_head($head, 'Setup', $langs->trans($title), -1, "setup");

// Setup page goes here

echo '<span class="opacitymedium">'.$langs->trans("QuoteAutoFollowupsSetupPage").'</span><br><br>';

// Prerequisites: an active scheduled job and a valid sender
$warnings = quoteAutoFollowupsGetSetupWarnings($db, $helper);
foreach ($warnings as $warning) {
	print info_admin($warning, 0, 0, 'warning');
}

if (!empty($formSetup->items)) {
	print $formSetup->generateOutput(true);
	print '<br>';
}

if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}

// Substitution variables usable in the email template
print load_fiche_titre($langs->trans('QAFSubstitutionsTitle'), '', '');
print '<span class="opacitymedium">'.$langs->trans('QAFSubstitutionsHelp').'</span> ';
print '<a href="'.DOL_URL_ROOT.'/admin/mails_templates.php?search_type_template=propal_send">'.img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans('QAFEditEmailTemplates').'</a><br><br>';
print '<table class="noborder centpercent">';
foreach (quoteAutoFollowupsGetSubstitutionHelp() as $key => $labelkey) {
	print '<tr class="oddeven"><td class="tdoverflowmax300"><code>'.$key.'</code></td><td>'.$langs->trans($labelkey).'</td></tr>';
}
print '</table>';

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
