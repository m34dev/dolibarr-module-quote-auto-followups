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
 * \file    htdocs/quoteautofollowups/class/actions_quoteautofollowups.class.php
 * \ingroup quoteautofollowups
 * \brief   Hooks on the quote card: "Follow up by email" button and its confirmation, next follow-up
 *          information, and module permission on the follow-up extrafields
 */

require_once "quoteautofollowups.class.php";

/**
 * Class ActionsQuoteAutoFollowups
 */
class ActionsQuoteAutoFollowups
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Errors
	 */
	public $errors = array();


	/**
	 * @var mixed[] Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var ?string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;

	/**
	 * @var string[] Extrafields of the quote that only users with the module 'write' permission may change
	 */
	const EDITABLE_FIELDS = array('qaf_donotfollowup', 'qaf_sentdate');


	/**
	 * Constructor
	 *
	 *  @param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Is the hook running on a loaded quote.
	 *
	 * @param	CommonObject|null	$object		Object passed to the hook
	 * @return	bool
	 */
	private function isQuote($object)
	{
		return !empty($object) && is_object($object) && $object instanceof Propal && $object->id > 0;
	}

	/**
	 * Handle the confirmed "Follow up by email" action, and enforce the module 'write' permission on the
	 * "Do not follow up" / "Sent to customer on" extrafields.
	 *
	 * comm/propal/card.php fires this hook before processing $action itself: a negative return shows
	 * $this->errors and skips the core actions.
	 *
	 * Editing an extrafield from the card only requires the proposal "creer" right (see
	 * core/tpl/extrafields_view.tpl.php), and the extrafield 'perms' property only controls visibility,
	 * so the module permission is checked here instead.
	 *
	 * @param	array{socid?:int}	$parameters		Hook metadatas
	 * @param	Propal				&$object		The quote being displayed
	 * @param	string				&$action		Current action (if set)
	 * @param	HookManager			$hookmanager	Hook manager
	 * @return	int									< 0 on error, 0 on success
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $langs;

		if (!$this->isQuote($object)) {
			return 0;
		}

		if (in_array($action, array('edit_extras', 'update_extras')) && in_array(GETPOST('attribute', 'aZ09'), self::EDITABLE_FIELDS)
			&& !$user->hasRight('quoteautofollowups', 'followup', 'write')) {
			$langs->load("errors");
			$action = '';
			$this->errors[] = $langs->trans('NotEnoughPermissions');
			return -1;
		}

		if ($action != 'confirm_qafsendfollowup' || GETPOST('confirm', 'alpha') != 'yes') {
			return 0;
		}

		if (!$user->hasRight('quoteautofollowups', 'followup', 'send') || (int) $object->status != Propal::STATUS_VALIDATED) {
			return 0;
		}

		$langs->load("quoteautofollowups@quoteautofollowups");

		$helper = new QuoteAutoFollowups($this->db);
		if ($helper->sendFollowup($object, $user, 1) > 0) {
			setEventMessages($langs->trans('QAFFollowupSent'), null);
		} else {
			setEventMessages($langs->trans('QAFFollowupFailed', $helper->error), null, 'errors');
		}

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}

	/**
	 * Build the confirmation dialog of the "Follow up by email" button.
	 *
	 * comm/propal/card.php appends $hookmanager->resPrint to its own $formconfirm when this returns 0.
	 *
	 * @param	array{formConfirm?:string,lineid?:int}	$parameters		Hook metadatas
	 * @param	Propal									&$object		The quote being displayed
	 * @param	string									&$action		Current action (if set)
	 * @param	HookManager								$hookmanager	Hook manager
	 * @return	int										< 0 on error, 0 on success (append)
	 */
	public function formConfirm($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $langs, $form;

		if ($action != 'qafsendfollowup' || !$this->isQuote($object)) {
			return 0;
		}
		if (!$user->hasRight('quoteautofollowups', 'followup', 'send')) {
			return 0;
		}

		$langs->load("quoteautofollowups@quoteautofollowups");

		$helper = new QuoteAutoFollowups($this->db);
		$recipients = $helper->getRecipients($object);
		$state = $helper->getState($object);

		$this->resprints = $form->formconfirm(
			$_SERVER["PHP_SELF"].'?id='.$object->id,
			$langs->trans('QAFConfirmSendFollowupTitle'),
			$langs->trans('QAFConfirmSendFollowup', $state['count'] + 1, dol_escape_htmltag(implode(', ', $recipients['emails']))),
			'confirm_qafsendfollowup',
			array(),
			'yes',
			1
		);

		return 0;
	}

	/**
	 * Add the "Follow up by email" button on validated quotes.
	 *
	 * comm/propal/card.php keeps printing its own buttons after this hook (append semantics), so this
	 * prints directly and returns 0.
	 *
	 * @param	array{}			$parameters		Hook metadatas (empty for this hook)
	 * @param	Propal			&$object		The quote being displayed
	 * @param	string			&$action		Current action (if set)
	 * @param	HookManager		$hookmanager	Hook manager
	 * @return	int								< 0 on error, 0 on success (append)
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $langs;

		if (!$this->isQuote($object) || (int) $object->status != Propal::STATUS_VALIDATED) {
			return 0;
		}
		if (!$user->hasRight('quoteautofollowups', 'followup', 'send')) {
			return 0;
		}

		$langs->load("quoteautofollowups@quoteautofollowups");

		$helper = new QuoteAutoFollowups($this->db);
		$recipients = $helper->getRecipients($object);

		if (empty($recipients['emails'])) {
			$tooltip = $langs->trans(empty($recipients['nbcontacts']) ? 'QAFErrorNoFollowupContact' : 'QAFErrorNoValidEmail');
			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($tooltip).'">'.$langs->trans('QAFSendFollowupButton').'</a>';
		} else {
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=qafsendfollowup&token='.newToken().'">'.$langs->trans('QAFSendFollowupButton').'</a>';
		}

		return 0;
	}

	/**
	 * Show the "Next follow-up" line on the quote card, next to the follow-up extrafields: due date of the
	 * next automatic follow-up, or why the quote is no longer followed up.
	 *
	 * core/tpl/extrafields_view.tpl.php prints $hookmanager->resPrint just before the extrafields rows,
	 * inside the card table.
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadatas
	 * @param	Propal				&$object		The quote being displayed
	 * @param	string				&$action		Current action (if set)
	 * @param	HookManager			$hookmanager	Hook manager
	 * @return	int									< 0 on error, 0 on success (append)
	 */
	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $langs;

		if (!$this->isQuote($object) || $action == 'create') {
			return 0;
		}
		if (!$user->hasRight('quoteautofollowups', 'followup', 'read')) {
			return 0;
		}
		if ((int) $object->status == Propal::STATUS_DRAFT) {
			return 0;
		}

		$langs->load("quoteautofollowups@quoteautofollowups");

		$helper = new QuoteAutoFollowups($this->db);
		$reason = '';
		if (!$helper->canBeFollowedUp($object, $reason)) {
			$value = '<span class="opacitymedium">'.$langs->trans($reason).'</span>';
		} else {
			$due = $helper->getNextFollowupDate($object);
			if (empty($due)) {
				$value = '<span class="opacitymedium">'.$langs->trans('Unknown').'</span>';
			} elseif ($due <= dol_now()) {
				$value = $langs->trans('QAFNextFollowupOverdue', dol_print_date($due, 'day'));
			} else {
				$value = dol_print_date($due, 'day');
			}
			if (!$helper->isAutoEnabled()) {
				$value .= ' <span class="opacitymedium">('.$langs->trans('QAFStopAutoDisabled').')</span>';
			}
		}

		$this->resprints = '<tr><td class="titlefield">'.$langs->trans('QAFNextFollowup').'</td><td class="valuefield">'.$value.'</td></tr>';

		return 0;
	}
}
