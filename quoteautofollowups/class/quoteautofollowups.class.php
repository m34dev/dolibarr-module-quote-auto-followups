<?php
/* Copyright (C) 2026		William Mead			<william@m34d.com>
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
 * \file        htdocs/quoteautofollowups/class/quoteautofollowups.class.php
 * \ingroup     quoteautofollowups
 * \brief       Business logic for QuoteAutoFollowups module: eligibility rules, due dates,
 *              follow-up email sending, history (agenda events + extrafields) and scheduled job
 */

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 * Class for QuoteAutoFollowups
 *
 * Also the target of the scheduled job declared in modQuoteAutoFollowups (runScheduledFollowups()),
 * which is why it exposes the $entity, $error, $errors and $output properties the cron runner uses.
 */
class QuoteAutoFollowups
{
	/** Agenda event code for a follow-up that was sent */
	const EVENT_SENT = 'AC_QAF_SENT';

	/** Agenda event code for a follow-up that could not be sent */
	const EVENT_FAILED = 'AC_QAF_FAILED';

	/** Agenda event code Dolibarr uses when a proposal is sent by email from its card */
	const EVENT_PROPAL_SENTBYMAIL = 'AC_PROPAL_SENTBYMAIL';

	/**
	 * @var array<string,string> Follow-up extrafields stored on the proposal, with their storage type
	 */
	const FIELD_TYPES = array(
		'qaf_donotfollowup' => 'int',
		'qaf_sentdate' => 'date',
		'qaf_count' => 'int',
		'qaf_lastdate' => 'datetime',
		'qaf_lasterror' => 'string',
	);

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var int Entity (set by the cron runner)
	 */
	public $entity;

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var string[] Error messages
	 */
	public $errors = array();

	/**
	 * @var string Output of the scheduled job, shown as its "last result" in the scheduled jobs list
	 */
	public $output = '';


	/**
	 * Constructor
	 *
	 * @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/*
	 * Configuration
	 */

	/**
	 * Is the automatic sending (scheduled job) enabled in the module setup.
	 *
	 * @return	bool
	 */
	public function isAutoEnabled()
	{
		return getDolGlobalInt('QUOTEAUTOFOLLOWUPS_ENABLED') == 1;
	}

	/**
	 * Maximum number of automatic follow-ups per quote.
	 *
	 * @return	int
	 */
	public function getMaxFollowups()
	{
		return max(0, getDolGlobalInt('QUOTEAUTOFOLLOWUPS_MAX', 3));
	}

	/**
	 * Maximum number of sending attempts per run of the scheduled job.
	 *
	 * @return	int
	 */
	public function getBatchSize()
	{
		return max(1, getDolGlobalInt('QUOTEAUTOFOLLOWUPS_BATCH_SIZE', 10));
	}

	/**
	 * Check the format of a delays setting: a comma separated list of strictly positive numbers of days.
	 *
	 * @param	string	$value	Value to check, e.g. "7,15,30"
	 * @return	bool
	 */
	public static function isValidDelays($value)
	{
		if (!preg_match('/^\s*\d+\s*(,\s*\d+\s*)*$/', (string) $value)) {
			return false;
		}
		foreach (explode(',', $value) as $delay) {
			if ((int) trim($delay) <= 0) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Delays (in days) between follow-ups: first entry = delay before the 1st follow-up, second = delay
	 * between the 1st and 2nd, etc. The last one is reused when the maximum exceeds the list length.
	 *
	 * @return	int[]
	 */
	public function getDelays()
	{
		$value = getDolGlobalString('QUOTEAUTOFOLLOWUPS_DELAYS', '7,15,30');
		if (!self::isValidDelays($value)) {
			dol_syslog(__METHOD__.' invalid QUOTEAUTOFOLLOWUPS_DELAYS value "'.$value.'", falling back to 7 days', LOG_WARNING);
			return array(7);
		}
		return array_map('intval', array_map('trim', explode(',', $value)));
	}

	/**
	 * Delay in days before the next follow-up, given the number of follow-ups already sent.
	 *
	 * @param	int		$count	Number of follow-ups already sent
	 * @return	int
	 */
	public function getDelayForFollowup($count)
	{
		$delays = $this->getDelays();
		return $delays[min(max(0, (int) $count), count($delays) - 1)];
	}

	/**
	 * Only quotes validated on or after this date are followed up automatically (0 = no restriction).
	 * Stored as YYYY-MM-DD by the setup page.
	 *
	 * @return	int		Timestamp (start of day, server time zone) or 0
	 */
	public function getStartDate()
	{
		$value = getDolGlobalString('QUOTEAUTOFOLLOWUPS_START_DATE');
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $reg)) {
			return (int) dol_mktime(0, 0, 0, (int) $reg[2], (int) $reg[3], (int) $reg[1], 'tzserver');
		}
		return 0;
	}

	/**
	 * Is the scheduled job allowed to send now: within the configured hours (server time zone) and,
	 * if configured, on a working day (Monday to Friday).
	 *
	 * @param	int|null	$now	Timestamp to test (default: now)
	 * @return	bool
	 */
	public function isInSendingWindow($now = null)
	{
		$now = ($now === null ? dol_now() : $now);
		$tmp = dol_getdate($now, true);
		$hour = (int) $tmp['hours'];
		$weekday = (int) $tmp['wday']; // 0 = Sunday, 6 = Saturday

		if (getDolGlobalInt('QUOTEAUTOFOLLOWUPS_WORKDAYS_ONLY', 1) && ($weekday == 0 || $weekday == 6)) {
			return false;
		}

		$start = min(23, max(0, getDolGlobalInt('QUOTEAUTOFOLLOWUPS_HOUR_START', 9)));
		$end = min(24, max(0, getDolGlobalInt('QUOTEAUTOFOLLOWUPS_HOUR_END', 18)));
		if ($start == $end) {
			return true;
		}
		if ($start < $end) {
			return $hour >= $start && $hour < $end;
		}
		// Window spanning midnight, e.g. 20 -> 6
		return $hour >= $start || $hour < $end;
	}


	/*
	 * Follow-up state of a quote
	 */

	/**
	 * Convert a date value read from the database or from array_options into a timestamp.
	 *
	 * @param	mixed	$value	Timestamp, SQL date string, '' or null
	 * @return	int				Timestamp, or 0 if empty
	 */
	private function toTimestamp($value)
	{
		if (empty($value)) {
			return 0;
		}
		if (is_numeric($value)) {
			return (int) $value;
		}
		return (int) $this->db->jdate($value);
	}

	/**
	 * Date of the most recent agenda event with the given code linked to a quote.
	 *
	 * @param	int		$propalid	Quote id
	 * @param	string	$code		Event code
	 * @return	int					Timestamp, or 0 if none
	 */
	private function getLastEventDate($propalid, $code)
	{
		$sql = "SELECT MAX(a.datep) as lastdate FROM ".$this->db->prefix()."actioncomm as a";
		$sql .= " WHERE a.fk_element = ".((int) $propalid)." AND a.elementtype = 'propal'";
		$sql .= " AND a.code = '".$this->db->escape($code)."'";
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
			return 0;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		return $obj ? $this->toTimestamp($obj->lastdate) : 0;
	}

	/**
	 * Compute when the next follow-up of a quote is due. Shared by the quote card and the scheduled
	 * job so both always agree.
	 *
	 * Reference date = the most recent of:
	 * - the date the quote was sent to the customer (qaf_sentdate, entered by the user or filled when the
	 *   quote is sent by email from Dolibarr), or the validation date if unknown;
	 * - the last time the quote was sent by email from Dolibarr (AC_PROPAL_SENTBYMAIL event);
	 * - the last follow-up sent (qaf_lastdate);
	 * - the last failed follow-up (AC_QAF_FAILED event), so a failure is retried after the next delay
	 *   instead of at every run of the hourly job.
	 * Events older than the validation date (from a previous version of the quote) are naturally
	 * outweighed by the validation date itself.
	 *
	 * @param	int		$count		Number of follow-ups already sent
	 * @param	int		$datevalid	Validation date
	 * @param	int		$sentdate	Date sent to customer (qaf_sentdate), 0 if unknown
	 * @param	int		$lastsent	Last AC_PROPAL_SENTBYMAIL event date, 0 if none
	 * @param	int		$lastdate	Last follow-up date (qaf_lastdate), 0 if none
	 * @param	int		$lastfailed	Last AC_QAF_FAILED event date, 0 if none
	 * @return	int					Due date timestamp, 0 if it cannot be computed
	 */
	public function computeDueDate($count, $datevalid, $sentdate, $lastsent, $lastdate, $lastfailed)
	{
		$reference = max(($sentdate ? $sentdate : $datevalid), $lastsent, $lastdate, $lastfailed);
		if (empty($reference)) {
			return 0;
		}
		return $reference + $this->getDelayForFollowup($count) * 24 * 3600;
	}

	/**
	 * Follow-up state of a quote, from its extrafields (the quote must have been fetched with its
	 * extrafields, which Propal::fetch() does by default).
	 *
	 * @param	Propal	$object		Quote
	 * @return	array{donotfollowup:int,count:int,sentdate:int,lastdate:int,lasterror:string}
	 */
	public function getState(Propal $object)
	{
		$options = is_array($object->array_options) ? $object->array_options : array();
		return array(
			'donotfollowup' => (int) ($options['options_qaf_donotfollowup'] ?? 0),
			'count' => (int) ($options['options_qaf_count'] ?? 0),
			'sentdate' => $this->toTimestamp($options['options_qaf_sentdate'] ?? 0),
			'lastdate' => $this->toTimestamp($options['options_qaf_lastdate'] ?? 0),
			'lasterror' => (string) ($options['options_qaf_lasterror'] ?? ''),
		);
	}

	/**
	 * Next automatic follow-up date of a quote.
	 *
	 * @param	Propal	$object		Quote
	 * @return	int					Timestamp, 0 if it cannot be computed
	 */
	public function getNextFollowupDate(Propal $object)
	{
		$state = $this->getState($object);
		return $this->computeDueDate(
			$state['count'],
			$this->toTimestamp($object->date_validation),
			$state['sentdate'],
			$this->getLastEventDate($object->id, self::EVENT_PROPAL_SENTBYMAIL),
			$state['lastdate'],
			$this->getLastEventDate($object->id, self::EVENT_FAILED)
		);
	}

	/**
	 * Check the conditions that permanently or durably stop automatic follow-ups of a quote (everything
	 * except the due date and the module-wide on/off switch).
	 *
	 * @param	Propal	$object		Quote
	 * @param	string	$reason		Translation key of the reason why it cannot be followed up
	 * @return	bool
	 */
	public function canBeFollowedUp(Propal $object, &$reason = '')
	{
		$reason = '';
		$status = (int) $object->status;

		if (in_array($status, array(Propal::STATUS_SIGNED, Propal::STATUS_NOTSIGNED, Propal::STATUS_BILLED), true)) {
			$reason = 'QAFStopClosed';
			return false;
		}
		if ($status != Propal::STATUS_VALIDATED) {
			$reason = 'QAFStopNotValidated';
			return false;
		}
		if (!empty($object->fin_validite) && $this->toTimestamp($object->fin_validite) < dol_get_first_hour(dol_now())) {
			$reason = 'QAFStopExpired';
			return false;
		}

		$state = $this->getState($object);
		if ($state['donotfollowup']) {
			$reason = 'QAFStopDoNotFollowup';
			return false;
		}
		if ($state['count'] >= $this->getMaxFollowups()) {
			$reason = 'QAFStopMaxReached';
			return false;
		}
		$startdate = $this->getStartDate();
		if ($startdate && $this->toTimestamp($object->date_validation) < $startdate) {
			$reason = 'QAFStopBeforeStartDate';
			return false;
		}

		return true;
	}


	/*
	 * Recipients, sender and attachment
	 */

	/**
	 * Recipients of a follow-up: active external contacts of the quote with the "Customer contact
	 * follow-up" role (CUSTOMER) and a valid email.
	 *
	 * @param	Propal	$object		Quote
	 * @return	array{nbcontacts:int,emails:string[],contactids:int[]}
	 */
	public function getRecipients(Propal $object)
	{
		$result = array('nbcontacts' => 0, 'emails' => array(), 'contactids' => array());

		$contacts = $object->liste_contact(-1, 'external', 0, 'CUSTOMER');
		if (!is_array($contacts)) {
			return $result;
		}

		foreach ($contacts as $contact) {
			if (isset($contact['statuscontact']) && (int) $contact['statuscontact'] === 0) {
				continue; // Contact disabled
			}
			$result['nbcontacts']++;
			$email = trim((string) $contact['email']);
			if ($email !== '' && isValidEmail($email) && !in_array($email, $result['emails'])) {
				$result['emails'][] = $email;
				$result['contactids'][] = (int) $contact['id'];
			}
		}

		return $result;
	}

	/**
	 * Internal "Sales representative following-up proposal" (SALESREPFOLLOW) of a quote.
	 *
	 * @param	Propal	$object		Quote
	 * @return	User|null
	 */
	public function getSalesRep(Propal $object)
	{
		$userids = $object->getIdContact('internal', 'SALESREPFOLLOW');
		if (empty($userids)) {
			return null;
		}
		$salesrep = new User($this->db);
		if ($salesrep->fetch((int) reset($userids)) <= 0) {
			return null;
		}
		return $salesrep;
	}

	/**
	 * Sender choices offered in the module setup, with the email each one currently resolves to.
	 *
	 * @return	array<string,string>	Setting value => label
	 */
	public function getSenderOptions()
	{
		global $langs;

		$options = array();
		$companyemail = getDolGlobalString('MAIN_INFO_SOCIETE_MAIL');
		$options['company'] = $langs->trans('QAFSenderCompany').' ('.($companyemail ? $companyemail : $langs->trans('NotDefined')).')';
		$robotemail = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
		$options['robot'] = $langs->trans('QAFSenderRobot').' ('.($robotemail ? $robotemail : $langs->trans('NotDefined')).')';
		$options['salesrep'] = $langs->trans('QAFSenderSalesRep');

		$sql = "SELECT rowid, label, email FROM ".$this->db->prefix()."c_email_senderprofile";
		$sql .= " WHERE active = 1 AND (private = 0 OR private IS NULL) AND entity IN (".getEntity('c_email_senderprofile').")";
		$sql .= " ORDER BY position ASC, label ASC";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$options['senderprofile_'.$obj->rowid] = $langs->trans('QAFSenderProfile').' '.$obj->label.' ('.$obj->email.')';
			}
			$this->db->free($resql);
		}

		return $options;
	}

	/**
	 * Active public sender profile (Setup > Emails > Sender profiles), as offered by getSenderOptions().
	 *
	 * @param	int		$id		Sender profile id
	 * @return	array{email:string,name:string,signature:string}|null	Null if not found, disabled or private
	 */
	public function getSenderProfile($id)
	{
		$sql = "SELECT label, email, signature FROM ".$this->db->prefix()."c_email_senderprofile";
		$sql .= " WHERE rowid = ".((int) $id)." AND active = 1 AND (private = 0 OR private IS NULL)";
		$sql .= " AND entity IN (".getEntity('c_email_senderprofile').")";
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
			return null;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (!$obj) {
			return null;
		}
		return array('email' => (string) $obj->email, 'name' => (string) $obj->label, 'signature' => (string) $obj->signature);
	}

	/**
	 * Resolve the configured sender of follow-up emails for a quote. Same sources as the sender
	 * selector of Dolibarr's email form (core/actions_sendmails.inc.php).
	 *
	 * @param	Propal	$object		Quote
	 * @return	array{email:string,name:string,signature:string}|null	Null if no valid email found
	 */
	public function getSender(Propal $object)
	{
		global $langs;

		$mode = getDolGlobalString('QUOTEAUTOFOLLOWUPS_SENDER', 'company');
		$sender = null;

		if ($mode == 'salesrep') {
			$salesrep = $this->getSalesRep($object);
			if ($salesrep && isValidEmail($salesrep->email)) {
				$sender = array('email' => $salesrep->email, 'name' => $salesrep->getFullName($langs), 'signature' => (string) $salesrep->signature);
			} else {
				$mode = 'robot'; // No sales representative with an email: fall back on the default sender
			}
		} elseif (preg_match('/^senderprofile_(\d+)$/', $mode, $reg)) {
			$sender = $this->getSenderProfile((int) $reg[1]);
		}

		if ($mode == 'company') {
			$sender = array('email' => getDolGlobalString('MAIN_INFO_SOCIETE_MAIL'), 'name' => getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), 'signature' => '');
		} elseif ($mode == 'robot') {
			$sender = array('email' => getDolGlobalString('MAIN_MAIL_EMAIL_FROM'), 'name' => getDolGlobalString('MAIN_MAIL_EMAIL_FROM_NAME', getDolGlobalString('MAIN_INFO_SOCIETE_NOM')), 'signature' => '');
		}

		if (empty($sender) || !isValidEmail($sender['email'])) {
			return null;
		}
		return $sender;
	}

	/**
	 * Blind carbon copy of follow-up emails, as configured in the module setup.
	 *
	 * @param	Propal	$object		Quote
	 * @return	string				Email, or '' for none
	 */
	public function getBcc(Propal $object)
	{
		$mode = getDolGlobalString('QUOTEAUTOFOLLOWUPS_BCC_MODE', 'none');
		if ($mode == 'salesrep') {
			$salesrep = $this->getSalesRep($object);
			return ($salesrep && isValidEmail($salesrep->email)) ? $salesrep->email : '';
		}
		if ($mode == 'fixed') {
			$email = getDolGlobalString('QUOTEAUTOFOLLOWUPS_BCC_EMAIL');
			return isValidEmail($email) ? $email : '';
		}
		return '';
	}

	/**
	 * PDF of the quote to attach. Uses the main document already generated (<ref>/<ref>.pdf) and
	 * generates it with the quote's model if it does not exist yet.
	 *
	 * @param	Propal		$object			Quote
	 * @param	Translate	$outputlangs	Language to generate the document in
	 * @return	string						Full path of the PDF, or '' if not available
	 */
	public function getPdfFile(Propal $object, $outputlangs)
	{
		global $conf;

		$entity = (!empty($object->entity) ? $object->entity : $conf->entity);
		$ref = dol_sanitizeFileName($object->ref);
		$file = $conf->propal->multidir_output[$entity].'/'.$ref.'/'.$ref.'.pdf';

		if (!dol_is_file($file)) {
			$model = (!empty($object->model_pdf) ? $object->model_pdf : getDolGlobalString('PROPALE_ADDON_PDF'));
			if ($model) {
				$result = $object->generateDocument($model, $outputlangs);
				if ($result <= 0) {
					dol_syslog(__METHOD__.' failed to generate document of '.$object->ref.': '.$object->error, LOG_WARNING);
				}
			}
		}

		return dol_is_file($file) ? $file : '';
	}


	/*
	 * Sending
	 */

	/**
	 * Language to write the email in: the customer's default language if set.
	 *
	 * @param	Propal	$object		Quote (thirdparty must be loaded)
	 * @return	Translate
	 */
	private function getOutputLangs(Propal $object)
	{
		global $conf, $langs;

		$newlang = '';
		if (getDolGlobalInt('MAIN_MULTILANGS') && is_object($object->thirdparty) && !empty($object->thirdparty->default_lang)) {
			$newlang = $object->thirdparty->default_lang;
		}
		if (empty($newlang)) {
			return $langs;
		}

		$outputlangs = new Translate('', $conf);
		$outputlangs->setDefaultLang($newlang);
		$outputlangs->loadLangs(array('main', 'propal', 'companies', 'quoteautofollowups@quoteautofollowups'));
		return $outputlangs;
	}

	/**
	 * SQL filter of the email templates usable for follow-ups: active public templates of commercial
	 * proposals. Private templates are excluded because the scheduled job runs as another user than the
	 * one who selected the template, and would not see it.
	 *
	 * @return	string
	 */
	private function getTemplateSqlFilter()
	{
		$sql = " WHERE type_template IN ('propal_send', 'all')";
		$sql .= " AND active = 1 AND private = 0";
		$sql .= " AND entity IN (".getEntity('c_email_templates').")";
		return $sql;
	}

	/**
	 * Email templates offered in the module setup, one per label: language variants of a template share
	 * its label, and the variant matching the customer's language is chosen when sending.
	 *
	 * @return	array<int,string>	Template id => label
	 */
	public function getTemplateOptions()
	{
		global $langs;

		$selectedid = getDolGlobalInt('QUOTEAUTOFOLLOWUPS_EMAIL_TEMPLATE');

		$sql = "SELECT rowid, label, module FROM ".$this->db->prefix()."c_email_templates";
		$sql .= $this->getTemplateSqlFilter();
		$sql .= " ORDER BY position ASC, label ASC, rowid ASC";
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
			return array();
		}

		$idbylabel = array();
		$nbbylabel = array();
		while ($obj = $this->db->fetch_object($resql)) {
			if (!empty($obj->module) && !isModEnabled($obj->module)) {
				continue;
			}
			// Keep the configured variant if it is one of them, so the select shows the current value
			if (!isset($idbylabel[$obj->label]) || (int) $obj->rowid == $selectedid) {
				$idbylabel[$obj->label] = (int) $obj->rowid;
			}
			$nbbylabel[$obj->label] = ($nbbylabel[$obj->label] ?? 0) + 1;
		}
		$this->db->free($resql);

		$options = array();
		foreach ($idbylabel as $label => $id) {
			$options[$id] = $langs->trans(preg_replace('/\(|\)/', '', $label));
			if ($nbbylabel[$label] > 1) {
				$options[$id] .= ' ('.$langs->trans('SeveralLangugeVariatFound').')';
			}
		}
		return $options;
	}

	/**
	 * Load the configured email template, in the customer's language when a variant with the same label
	 * exists for it (templates are grouped by label in Setup > Emails > Email templates).
	 *
	 * Variant priority: customer language (e.g. es_MX), then its main language (es_ES), then the variant
	 * without language, then the configured template itself. Dolibarr's own lookup sorts on position
	 * before language, which could pick the variant without language over the customer's one.
	 *
	 * @param	Translate	$outputlangs	Customer language
	 * @return	object{topic:string,content:string}|null	Null if the configured template is missing, disabled or private
	 */
	private function getEmailTemplate($outputlangs)
	{
		$templateid = getDolGlobalInt('QUOTEAUTOFOLLOWUPS_EMAIL_TEMPLATE');
		if ($templateid <= 0) {
			return null;
		}

		$lang = (is_object($outputlangs) ? (string) $outputlangs->defaultlang : '');
		$tmp = explode('_', $lang);
		$mainlang = ($tmp[0] ? $tmp[0].'_'.strtoupper($tmp[0]) : '');

		$sql = "SELECT t.rowid, t.lang, t.topic, t.content FROM ".$this->db->prefix()."c_email_templates as t";
		$sql .= $this->getTemplateSqlFilter();
		$sql .= " AND label = (SELECT t2.label FROM ".$this->db->prefix()."c_email_templates as t2 WHERE t2.rowid = ".((int) $templateid).")";
		$sql .= " ORDER BY position ASC, rowid ASC";
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
			return null;
		}

		$template = null;
		$bestscore = -1;
		$found = false;
		while ($obj = $this->db->fetch_object($resql)) {
			if ((int) $obj->rowid == $templateid) {
				$found = true;
			}
			if ($lang !== '' && $obj->lang == $lang) {
				$score = 3;
			} elseif ($mainlang !== '' && $obj->lang == $mainlang) {
				$score = 2;
			} elseif (empty($obj->lang)) {
				$score = 1;
			} elseif ((int) $obj->rowid == $templateid) {
				$score = 0;
			} else {
				continue; // Variant in another language
			}
			if ($score > $bestscore) {
				$bestscore = $score;
				$template = $obj;
			}
		}
		$this->db->free($resql);

		// The configured template itself must still be usable (active and public)
		return $found ? $template : null;
	}

	/**
	 * Send a follow-up email for a quote, with its PDF attached, to its follow-up contacts, and record
	 * the result: extrafields (counter, last date, last error) and an agenda event linked to the quote.
	 *
	 * Used by both the scheduled job and the manual "Follow up by email" button. It does not check the
	 * eligibility rules (see canBeFollowedUp()): the caller decides.
	 *
	 * @param	Propal	$object		Quote (fetched, with its extrafields)
	 * @param	User	$user		User sending (event author)
	 * @param	int		$manual		1 if triggered manually from the quote card
	 * @return	int					>0 if sent, <0 if failed ($this->error contains the reason)
	 */
	public function sendFollowup(Propal $object, User $user, $manual = 0)
	{
		global $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

		$langs->loadLangs(array('main', 'mails', 'propal', 'quoteautofollowups@quoteautofollowups'));
		$this->error = '';
		$this->errors = array();

		if (!is_object($object->thirdparty)) {
			$object->fetch_thirdparty();
		}
		$outputlangs = $this->getOutputLangs($object);

		$recipients = $this->getRecipients($object);
		if (empty($recipients['nbcontacts'])) {
			return $this->recordFailure($object, $user, $langs->transnoentitiesnoconv('QAFErrorNoFollowupContact'), $manual);
		}
		if (empty($recipients['emails'])) {
			return $this->recordFailure($object, $user, $langs->transnoentitiesnoconv('QAFErrorNoValidEmail'), $manual);
		}

		$sender = $this->getSender($object);
		if (empty($sender)) {
			return $this->recordFailure($object, $user, $langs->transnoentitiesnoconv('QAFErrorNoSender'), $manual);
		}

		$template = $this->getEmailTemplate($outputlangs);
		if (empty($template)) {
			return $this->recordFailure($object, $user, $langs->transnoentitiesnoconv('QAFErrorNoTemplate'), $manual);
		}

		$file = $this->getPdfFile($object, $outputlangs);
		if (empty($file)) {
			return $this->recordFailure($object, $user, $langs->transnoentitiesnoconv('QAFErrorNoPdf'), $manual);
		}

		$state = $this->getState($object);

		// Contact name for __CONTACTCIVNAME__, same as the core email form does
		$contactcivname = '';
		if (!empty($recipients['contactids'])) {
			require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
			$contact = new Contact($this->db);
			if ($contact->fetch($recipients['contactids'][0]) > 0) {
				$contactcivname = $contact->getFullName($outputlangs, 1);
			}
		}

		$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
		$substitutionarray['__CONTACTCIVNAME__'] = $contactcivname;
		$substitutionarray['__SENDEREMAIL_SIGNATURE__'] = $sender['signature'];
		$substitutionarray['__QAF_FOLLOWUP_NUMBER__'] = $state['count'] + 1;
		complete_substitutions_array($substitutionarray, $outputlangs, $object);

		$subject = make_substitutions($template->topic, $substitutionarray, $outputlangs);
		$message = make_substitutions($template->content, $substitutionarray, $outputlangs);
		$ishtml = dol_textishtml($message) ? 1 : 0;

		$from = dol_string_nospecial($sender['name'], ' ', array(",")).' <'.$sender['email'].'>';
		$to = implode(', ', $recipients['emails']);
		$bcc = $this->getBcc($object);

		$mailfile = new CMailFile(
			$subject,
			$to,
			$from,
			$message,
			array($file),
			array(dol_mimetype($file)),
			array(basename($file)),
			'',
			$bcc,
			0,
			$ishtml,
			'',
			'',
			'pro'.$object->id,
			'',
			'standard'
		);
		if (!empty($mailfile->error)) {
			return $this->recordFailure($object, $user, $langs->transnoentitiesnoconv('QAFErrorSendFailed', $mailfile->error), $manual);
		}
		if (!$mailfile->sendfile()) {
			return $this->recordFailure($object, $user, $langs->transnoentitiesnoconv('QAFErrorSendFailed', $mailfile->error), $manual);
		}

		$number = $state['count'] + 1;
		$result = $this->saveFields($object, array(
			'qaf_count' => $number,
			'qaf_lastdate' => dol_now(),
			'qaf_lasterror' => null,
		));
		if ($result < 0) {
			dol_syslog(__METHOD__.' follow-up sent for '.$object->ref.' but counters not saved: '.$this->error, LOG_ERR);
		}

		$label = $langs->transnoentitiesnoconv($manual ? 'QAFEventSentManualLabel' : 'QAFEventSentLabel', $number, $object->ref);
		$note = $langs->transnoentitiesnoconv('MailFrom').': '.dol_escape_htmltag($from).'<br>';
		$note .= $langs->transnoentitiesnoconv('MailTo').': '.dol_escape_htmltag($to).'<br>';
		if ($bcc) {
			$note .= $langs->transnoentitiesnoconv('MailCCC').': '.dol_escape_htmltag($bcc).'<br>';
		}
		$note .= $langs->transnoentitiesnoconv('MailTopic').': '.dol_escape_htmltag($subject).'<br>';
		$note .= $langs->transnoentitiesnoconv('AttachedFiles').': '.dol_escape_htmltag(basename($file)).'<br><br>';
		$note .= ($ishtml ? $message : dol_nl2br(dol_escape_htmltag($message)));

		$this->logEvent($object, $user, self::EVENT_SENT, $label, $note, array(
			'from' => $from,
			'to' => $to,
			'bcc' => $bcc,
			'subject' => $subject,
			'msgid' => (string) $mailfile->msgid,
			'contactids' => $recipients['contactids'],
		));

		return 1;
	}

	/**
	 * Record a failed follow-up: last error extrafield + agenda event linked to the quote.
	 *
	 * @param	Propal	$object		Quote
	 * @param	User	$user		User
	 * @param	string	$reason		Reason, already translated
	 * @param	int		$manual		1 if triggered manually
	 * @return	int					-1 (so callers can return it directly)
	 */
	private function recordFailure(Propal $object, User $user, $reason, $manual = 0)
	{
		global $langs;

		$this->error = $reason;
		$this->errors = array($reason);

		dol_syslog(__CLASS__.' follow-up of '.$object->ref.' failed: '.$reason, LOG_WARNING);

		$lasterror = dol_trunc(dol_print_date(dol_now(), 'dayhour').' - '.$reason, 250, 'right', 'UTF-8', 1);
		$this->saveFields($object, array('qaf_lasterror' => $lasterror));
		$this->error = $reason; // saveFields() may have reset it

		$label = $langs->transnoentitiesnoconv($manual ? 'QAFEventFailedManualLabel' : 'QAFEventFailedLabel', $object->ref);
		$this->logEvent($object, $user, self::EVENT_FAILED, $label, dol_escape_htmltag($reason));

		return -1;
	}

	/**
	 * Write follow-up extrafields directly, without going through updateExtraField(): no trigger, no
	 * permission or "editable" check (the read-only fields are not editable by design), and it works
	 * whether or not the extrafields row of the quote already exists.
	 *
	 * @param	Propal					$object		Quote
	 * @param	array<string,mixed>		$values		Field => value (timestamp for dates, null to empty)
	 * @return	int									1 if OK, -1 if KO
	 */
	public function saveFields(Propal $object, $values)
	{
		$sqlvalues = array();
		foreach ($values as $field => $value) {
			if (!isset(self::FIELD_TYPES[$field])) {
				continue;
			}
			$type = self::FIELD_TYPES[$field];
			if ($value === null || $value === '') {
				$sqlvalues[$field] = 'NULL';
			} elseif ($type == 'int') {
				$sqlvalues[$field] = (string) ((int) $value);
			} elseif ($type == 'date' || $type == 'datetime') {
				$sqlvalues[$field] = "'".$this->db->idate($value)."'";
			} else {
				$sqlvalues[$field] = "'".$this->db->escape($value)."'";
			}
		}
		if (empty($sqlvalues)) {
			return 1;
		}

		$table = $this->db->prefix().'propal_extrafields';
		$resql = $this->db->query("SELECT rowid FROM ".$table." WHERE fk_object = ".((int) $object->id));
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		$exists = ($this->db->num_rows($resql) > 0);
		$this->db->free($resql);

		if ($exists) {
			$sets = array();
			foreach ($sqlvalues as $field => $sqlvalue) {
				$sets[] = $field.' = '.$sqlvalue;
			}
			$sql = "UPDATE ".$table." SET ".implode(', ', $sets)." WHERE fk_object = ".((int) $object->id);
		} else {
			$sql = "INSERT INTO ".$table." (fk_object, ".implode(', ', array_keys($sqlvalues)).")";
			$sql .= " VALUES (".((int) $object->id).", ".implode(', ', $sqlvalues).")";
		}
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return -1;
		}

		foreach ($values as $field => $value) {
			if (isset(self::FIELD_TYPES[$field])) {
				$object->array_options['options_'.$field] = $value;
			}
		}

		return 1;
	}

	/**
	 * Create an agenda event linked to the quote and its thirdparty.
	 *
	 * @param	Propal					$object		Quote
	 * @param	User					$user		Author
	 * @param	string					$code		Event code (EVENT_SENT or EVENT_FAILED)
	 * @param	string					$label		Label
	 * @param	string					$note		Description (HTML)
	 * @param	array<string,mixed>		$email		Email details: from, to, bcc, subject, msgid, contactids
	 * @return	int								Event id if OK, <0 if KO
	 */
	private function logEvent(Propal $object, User $user, $code, $label, $note, $email = array())
	{
		$now = dol_now();

		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code = 'AC_OTH_AUTO';
		$actioncomm->code = $code;
		$actioncomm->label = dol_trunc($label, 255, 'right', 'UTF-8', 1);
		$actioncomm->note_private = $note;
		$actioncomm->datep = $now;
		$actioncomm->datef = $now;
		$actioncomm->durationp = 0;
		$actioncomm->percentage = -1;
		$actioncomm->socid = $object->socid;
		$actioncomm->fk_project = $object->fk_project;
		$actioncomm->authorid = $user->id;
		$actioncomm->userownerid = $user->id;
		$actioncomm->fk_element = $object->id;
		$actioncomm->elementid = $object->id;
		$actioncomm->elementtype = $object->element;

		if (!empty($email)) {
			$actioncomm->email_from = $email['from'];
			$actioncomm->email_to = $email['to'];
			$actioncomm->email_tobcc = $email['bcc'];
			$actioncomm->email_subject = $email['subject'];
			$actioncomm->email_msgid = $email['msgid'];
			foreach ($email['contactids'] as $contactid) {
				$actioncomm->socpeopleassigned[$contactid] = array('id' => $contactid);
			}
		}

		$result = $actioncomm->create($user);
		if ($result < 0) {
			dol_syslog(__METHOD__.' failed to create agenda event for '.$object->ref.': '.$actioncomm->error.' '.implode(',', $actioncomm->errors), LOG_WARNING);
		}
		return $result;
	}


	/*
	 * Scheduled job
	 */

	/**
	 * Quotes whose automatic follow-up is due, most overdue first.
	 *
	 * Fixed conditions are filtered in SQL (status, validity, start date, "do not follow up", maximum);
	 * the due date depends on the delays list and on several dates, so it is computed in PHP with
	 * computeDueDate(), the same function the quote card uses.
	 *
	 * @return	array<int,array{id:int,due:int}>|int	List, or -1 on SQL error
	 */
	public function fetchDueQuotes()
	{
		global $conf;

		$max = $this->getMaxFollowups();
		if ($max <= 0) {
			return array();
		}
		$now = dol_now();
		$startdate = $this->getStartDate();

		$sql = "SELECT p.rowid, p.date_valid, ef.qaf_sentdate, ef.qaf_count, ef.qaf_lastdate,";
		$sql .= " (SELECT MAX(a.datep) FROM ".$this->db->prefix()."actioncomm as a WHERE a.fk_element = p.rowid AND a.elementtype = 'propal' AND a.code = '".self::EVENT_PROPAL_SENTBYMAIL."') as last_sent,";
		$sql .= " (SELECT MAX(a.datep) FROM ".$this->db->prefix()."actioncomm as a WHERE a.fk_element = p.rowid AND a.elementtype = 'propal' AND a.code = '".self::EVENT_FAILED."') as last_failed";
		$sql .= " FROM ".$this->db->prefix()."propal as p";
		$sql .= " LEFT JOIN ".$this->db->prefix()."propal_extrafields as ef ON ef.fk_object = p.rowid";
		$sql .= " WHERE p.entity = ".((int) $conf->entity);
		$sql .= " AND p.fk_statut = ".((int) Propal::STATUS_VALIDATED);
		$sql .= " AND (p.fin_validite IS NULL OR p.fin_validite >= '".$this->db->idate(dol_get_first_hour($now))."')";
		if ($startdate) {
			$sql .= " AND p.date_valid >= '".$this->db->idate($startdate)."'";
		}
		$sql .= " AND COALESCE(ef.qaf_donotfollowup, 0) = 0";
		$sql .= " AND COALESCE(ef.qaf_count, 0) < ".((int) $max);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$quotes = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$due = $this->computeDueDate(
				(int) $obj->qaf_count,
				$this->toTimestamp($obj->date_valid),
				$this->toTimestamp($obj->qaf_sentdate),
				$this->toTimestamp($obj->last_sent),
				$this->toTimestamp($obj->qaf_lastdate),
				$this->toTimestamp($obj->last_failed)
			);
			if ($due && $due <= $now) {
				$quotes[] = array('id' => (int) $obj->rowid, 'due' => $due);
			}
		}
		$this->db->free($resql);

		usort($quotes, function ($a, $b) {
			return $a['due'] <=> $b['due'];
		});

		return $quotes;
	}

	/**
	 * Scheduled job (hourly): send the follow-ups that are due, at most QUOTEAUTOFOLLOWUPS_BATCH_SIZE
	 * attempts (sent + failed) per run, most overdue first. Quotes left over are handled by the next runs.
	 *
	 * @return	int		0 if OK, <0 if technical error (the cron runner treats any non-zero value as an error)
	 */
	public function runScheduledFollowups()
	{
		global $langs, $user;

		$langs->loadLangs(array('main', 'propal', 'quoteautofollowups@quoteautofollowups'));
		$this->output = '';
		$this->error = '';
		$this->errors = array();

		if (!$this->isAutoEnabled()) {
			$this->output = $langs->trans('QAFCronAutoDisabled');
			return 0;
		}
		if (!$this->isInSendingWindow()) {
			$this->output = $langs->trans('QAFCronOutsideWindow');
			return 0;
		}

		$quotes = $this->fetchDueQuotes();
		if (!is_array($quotes)) {
			return -1;
		}

		$batchsize = $this->getBatchSize();
		$now = dol_now();
		$nbsent = 0;
		$nbfailed = 0;
		$nbskipped = 0;
		$processed = 0;
		$details = array();

		foreach ($quotes as $quote) {
			if ($nbsent + $nbfailed >= $batchsize) {
				break;
			}
			$processed++;

			$propal = new Propal($this->db);
			if ($propal->fetch($quote['id']) <= 0) {
				$nbskipped++;
				continue;
			}

			// Check again on the fully loaded quote, in case it changed since the selection
			$reason = '';
			$due = $this->getNextFollowupDate($propal);
			if (!$this->canBeFollowedUp($propal, $reason) || empty($due) || $due > $now) {
				$nbskipped++;
				continue;
			}

			if ($this->sendFollowup($propal, $user) > 0) {
				$nbsent++;
				$details[] = $propal->ref.': '.$langs->trans('QAFSent');
			} else {
				$nbfailed++;
				$details[] = $propal->ref.': '.$this->error;
			}
		}

		$remaining = count($quotes) - $processed;
		$this->error = '';
		$this->errors = array();
		$this->output = $langs->trans('QAFCronSummary', $nbsent, $nbfailed, $nbskipped, $remaining);
		if (!empty($details)) {
			$this->output .= "\n".implode("\n", $details);
		}

		return 0;
	}
}
