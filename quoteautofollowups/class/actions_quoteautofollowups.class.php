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
 * \file    htdocs/quoteautofollowups/core/modules/modquoteautofollowups.class.php
 * \ingroup quoteautofollowups
 * \brief   Hooks
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
	 * Constructor
	 *
	 *  @param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}
	
	/**
	 * Overloading the formObjectOptions function: replacing the parent's function with the one below
	 *
	 * @param	array			$parameters		Hook metadatas (context, etc...)
	 * @param	Product			&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			&$action		Current action (if set). Generally create or edit or null
	 * @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int								< 0 on error, 0 on success, 1 to replace standard code
	 */
	function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
		global $db, $langs;
		$langs->load("quoteautofollowups@quoteautofollowups");
		if($action == 'view' || $action == '') {
		
		}
		return 0;
	}
	
	/**
	 * Overloading the printFieldListOption function: replacing the parent's function with the one below
	 *
	 * @param	array			$parameters		Hook metadatas (context, etc...)
	 * @param	Product			&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			&$action		Current action (if set). Generally create or edit or null
	 * @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int								< 0 on error, 0 on success, 1 to replace standard code
	 */
	function printFieldListOption($parameters, &$object, &$action, $hookmanager) {
		if(in_array('productservicelist', $hookmanager->contextarray)) {
			$this->resprints = '<td class="liste_titre">&nbsp</td>';
			return 0;
		} else {
			return 0;
		}
	}

	/**
	 * Overloading the printFieldListTitle function: replacing the parent's function with the one below
	 *
	 * @param	array			$parameters		Hook metadatas (context, etc...)
	 * @param	Product			&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			&$action		Current action (if set). Generally create or edit or null
	 * @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int								< 0 on error, 0 on success, 1 to replace standard code
	 */
	function printFieldListTitle($parameters, &$object, &$action, $hookmanager) {
		global $langs;
		$langs->load("quoteautofollowups@quoteautofollowups");
		return 0;
	}
	
	/**
	 * Overloading the printFieldListValue function: replacing the parent's function with the one below
	 *
	 * @param	array			$parameters		Hook metadatas (context, etc...)
	 * @param	Product			&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			&$action		Current action (if set). Generally create or edit or null
	 * @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int								< 0 on error, 0 on success, 1 to replace standard code
	 */
	function printFieldListValue($parameters, &$object, &$action, $hookmanager) {
		global $langs;
		$langs->load("quoteautofollowups@quoteautofollowups");
		if(in_array('productservicelist', $hookmanager->contextarray) || in_array('productcompositioncard', $hookmanager->contextarray)) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Overloading the loadStaticObject function: replacing the parent's function with the one below
	 *
	 * @param	array			$parameters		Hook metadatas (context, etc...)
	 * @param	Product			&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			&$action		Current action (if set). Generally create or edit or null
	 * @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int								< 0 on error, 0 on success, 1 to replace standard code
	 */
	function loadStaticObject($parameters, &$object, &$action, $hookmanager) {
		return 0;
	}
}
