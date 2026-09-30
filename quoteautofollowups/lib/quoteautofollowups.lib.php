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
 * \file    quoteautofollowups/lib/quoteautofollowups.lib.php
 * \ingroup quoteautofollowups
 * \brief   Library files with common functions for QuoteAutoFollowups
 */

/**
 * Prepare admin pages header
 *
 * @return array<array{string,string,string}>
 */
function quoteAutoFollowupsAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("quoteautofollowups@quoteautofollowups");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/quoteautofollowups/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'Setup';
	$h++;

	$head[$h][0] = dol_buildpath("/quoteautofollowups/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'quoteautofollowups@quoteautofollowups');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'quoteautofollowups@quoteautofollowups', 'remove');

	return $head;
}

/**
 * Value-from-post callback for a FormSetup item validating an integer within bounds, since FormSetup
 * has no validation of its own (see the TODO in FormSetupItem::setValueFromPost()).
 *
 * @param	FormSetupItem	$item	Setup item
 * @param	int				$min	Minimum value
 * @param	int				$max	Maximum value
 * @return	callable
 */
function quoteAutoFollowupsIntegerValidator($item, $min, $max)
{
	return function () use ($item, $min, $max) {
		global $langs;
		$value = trim(GETPOST($item->confKey, 'alpha'));
		if (!preg_match('/^\d+$/', $value) || (int) $value < $min || (int) $value > $max) {
			setEventMessages($langs->trans('QAFErrorIntegerBetween', $item->getNameText(), $min, $max), null, 'errors');
			return -1;
		}
		$item->fieldValue = (string) ((int) $value);
		return 1;
	};
}

/**
 * Problems in the configuration that would prevent follow-ups from being sent, shown on the setup page.
 *
 * @param	DoliDB				$db		Database handler
 * @param	QuoteAutoFollowups	$helper	Business helper
 * @return	string[]					Translated warnings (HTML)
 */
function quoteAutoFollowupsGetSetupWarnings($db, $helper)
{
	global $conf, $langs;

	$warnings = array();

	if (getDolGlobalString('MAIN_DISABLE_ALL_MAILS')) {
		$warnings[] = $langs->trans('QAFWarningMailsDisabled');
	}
	if (getDolGlobalString('MAIN_MAIL_FORCE_SENDTO')) {
		// CMailFile then sends everything to this address and drops cc/bcc
		$warnings[] = $langs->trans('QAFWarningMailsForcedTo', getDolGlobalString('MAIN_MAIL_FORCE_SENDTO'));
	}

	// Scheduled job declared by the module descriptor, which the admin may have disabled
	$sql = "SELECT COUNT(rowid) as nb FROM ".$db->prefix()."cronjob";
	$sql .= " WHERE methodename = 'runScheduledFollowups' AND classesname LIKE '%quoteautofollowups%'";
	$sql .= " AND status = 1 AND entity IN (0, ".((int) $conf->entity).")";
	$resql = $db->query($sql);
	$obj = $resql ? $db->fetch_object($resql) : null;
	if (!isModEnabled('cron') || !$obj || (int) $obj->nb == 0) {
		$warnings[] = $langs->trans('QAFWarningCronDisabled').' <a href="'.DOL_URL_ROOT.'/cron/list.php">'.$langs->trans('CronList').'</a>';
	}

	$mode = getDolGlobalString('QUOTEAUTOFOLLOWUPS_SENDER', 'company');
	if ($mode == 'company' && !isValidEmail(getDolGlobalString('MAIN_INFO_SOCIETE_MAIL'))) {
		$warnings[] = $langs->trans('QAFWarningNoCompanyEmail').' <a href="'.DOL_URL_ROOT.'/admin/company.php">'.$langs->trans('CompanyFoundation').'</a>';
	} elseif (($mode == 'robot' || $mode == 'salesrep') && !isValidEmail(getDolGlobalString('MAIN_MAIL_EMAIL_FROM'))) {
		$warnings[] = $langs->trans('QAFWarningNoRobotEmail').' <a href="'.DOL_URL_ROOT.'/admin/mails.php">'.$langs->trans('EMails').'</a>';
	}

	if (!getDolGlobalInt('QUOTEAUTOFOLLOWUPS_EMAIL_TEMPLATE')) {
		$warnings[] = $langs->trans('QAFWarningNoTemplate');
	}

	$delays = $helper->getDelays();
	if (!QuoteAutoFollowups::isValidDelays(getDolGlobalString('QUOTEAUTOFOLLOWUPS_DELAYS', '7,15,30'))) {
		$warnings[] = $langs->trans('QAFErrorInvalidDelays').' ('.implode(',', $delays).')';
	}

	return $warnings;
}

/**
 * Substitution variables most useful in a follow-up email template, with their description.
 * Every standard substitution variable of commercial proposals can be used as well.
 *
 * @return	array<string,string>	Variable => translation key of its description
 */
function quoteAutoFollowupsGetSubstitutionHelp()
{
	return array(
		'__REF__' => 'QAFSubstRef',
		'__REF_CLIENT__' => 'QAFSubstRefClient',
		'__THIRDPARTY_NAME__' => 'QAFSubstThirdpartyName',
		'__CONTACTCIVNAME__' => 'QAFSubstContactCivName',
		'__EXPIRATION_DATE__' => 'QAFSubstExpirationDate',
		'__ONLINE_SIGN_URL__' => 'QAFSubstOnlineSignUrl',
		'__QAF_FOLLOWUP_NUMBER__' => 'QAFSubstFollowupNumber',
		'__MYCOMPANY_NAME__' => 'QAFSubstMyCompanyName',
		'__SENDEREMAIL_SIGNATURE__' => 'QAFSubstSenderSignature',
	);
}
