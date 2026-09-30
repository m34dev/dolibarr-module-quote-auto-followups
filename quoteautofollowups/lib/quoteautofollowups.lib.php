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

