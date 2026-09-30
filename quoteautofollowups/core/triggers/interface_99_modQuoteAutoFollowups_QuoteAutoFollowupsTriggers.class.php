<?php
/* Copyright (C) 2026		William Mead		<william@m34d.com>
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
 * \file    htdocs/quoteautofollowups/core/triggers/interface_99_modQuoteAutoFollowups_QuoteAutoFollowupsTriggers.class.php
 * \ingroup quoteautofollowups
 * \brief   Keep the follow-up extrafields of quotes in sync with their lifecycle
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 * Class of triggers for QuoteAutoFollowups module
 */
class InterfaceQuoteAutoFollowupsTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "crm";
		$this->description = "QuoteAutoFollowups triggers: date sent to customer and follow-up counters of quotes.";
		$this->version = self::VERSIONS['prod'];
		$this->picto = 'quoteautofollowups@quoteautofollowups';
	}

	/**
	 * Function called when a Dolibarr business event is done.
	 *
	 * - PROPAL_VALIDATE: a (re)validated quote is a new version, so its follow-up cycle starts over.
	 *   This also covers cloned quotes, which copy the extrafields of the original.
	 * - PROPAL_SENTBYMAIL: remember when the quote was first sent to the customer from Dolibarr, as the
	 *   starting point of the follow-ups. The user can still change it by hand (e.g. quote sent from
	 *   another mail client). Follow-up emails sent by this module do not fire this trigger.
	 *
	 * @param string		$action		Event action code
	 * @param CommonObject	$object		Object
	 * @param User			$user		Object user
	 * @param Translate		$langs		Object langs
	 * @param Conf			$conf		Object conf
	 * @return int						Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('quoteautofollowups')) {
			return 0;
		}
		if ($action != 'PROPAL_VALIDATE' && $action != 'PROPAL_SENTBYMAIL') {
			return 0;
		}

		dol_include_once('/quoteautofollowups/class/quoteautofollowups.class.php');
		$helper = new QuoteAutoFollowups($this->db);

		if ($action == 'PROPAL_VALIDATE') {
			$result = $helper->saveFields($object, array(
				'qaf_count' => 0,
				'qaf_lastdate' => null,
				'qaf_sentdate' => null,
				'qaf_lasterror' => null,
			));
		} else {
			if (empty($object->array_options) && method_exists($object, 'fetch_optionals')) {
				$object->fetch_optionals();
			}
			if (!empty($object->array_options['options_qaf_sentdate'])) {
				return 0;
			}
			$result = $helper->saveFields($object, array('qaf_sentdate' => dol_now()));
		}

		if ($result < 0) {
			// Never block the validation or the sending of the quote because of follow-up bookkeeping
			dol_syslog("Trigger '".$this->name."' for action '".$action."' failed on id=".$object->id.": ".$helper->error, LOG_WARNING);
			return 0;
		}

		dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);
		return 1;
	}
}
