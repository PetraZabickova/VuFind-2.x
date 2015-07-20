<?php
/**
 * Row Definition for user
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Db_Row
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace CPK\Db\Row;

use VuFind\Db\Row\User as BaseUser, VuFind\Exception\Auth as AuthException, VuFind\Db\Row\UserCard;

/**
 * Row Definition for user
 *
 * @category VuFind2
 * @package Db_Row
 * @author Demian Katz <demian.katz@villanova.edu>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link http://vufind.org Main Site
 */
class User extends BaseUser
{

    /**
     * Save library card with the given information
     *
     * @param int $id
     *            Card ID
     * @param string $cardName
     *            Card name
     * @param string $cat_username
     *            Username
     * @param string $cat_password
     *            Password
     * @param string $home_library
     *            Home Library
     * @param string $eppn
     * @param array $canConsolidateMoreTimes
     *
     * @return int Card ID
     * @throws \VuFind\Exception\LibraryCard
     */
    public function saveLibraryCard($id, $cardName, $cat_username = '', $cat_password = '', $home_library = '', $eppn = '', $canConsolidateMoreTimes = [])
    {
        if (! $this->libraryCardsEnabled()) {
            throw new \VuFind\Exception\LibraryCard('Library Cards Disabled');
        }
        $userCard = $this->getDbTable('UserCard');

        $eppnScope = split("@", $eppn)[1];

        // Check that the user has only one instituion account unless his organization is in $canConsolidateMoreTimes
        if (! in_array($eppnScope, $canConsolidateMoreTimes)) {
            if ($home_library !== 'Dummy') {

                // Not being Dummy we know home_library is unique across institutions
                $hasAccountAlready = $userCard->select([
                    'user_id' => $this->id,
                    'home_library' => $home_library
                ])->count() > 0;
            } else {

                // We need to find out the same user's Dummy institution if any, thus compare eppnScope to user's eppns
                $hasAccountAlready = $userCard->select([
                    'user_id' => $this->id,
                    'eppn LIKE ?' => '_@' . $eppnScope
                ])->count() > 0;
            }

            if ($hasAccountAlready) {
                throw new \VuFind\Exception\LibraryCard('Cannot connect two accounts from the same institution');
            }
        }

        $row = null;
        if ($id !== null) {
            $row = $userCard->select([
                'user_id' => $this->id,
                'id' => $id
            ])->current();
        }

        if (empty($row)) {

            if (empty($cat_username))
                throw new \VuFind\Exception\LibraryCard('Cannot create library card without cat_username');

            if (empty($home_library))
                throw new \VuFind\Exception\LibraryCard('Cannot create library card without home_library');

            if (empty($eppn))
                throw new \VuFind\Exception\LibraryCard('Cannot create library card without eppn');

            $row = $userCard->createRow();
            $row->user_id = $this->id;
            $row->created = date('Y-m-d H:i:s');
        }

        $row->card_name = $cardName;

        // Not empty checks serves to don't update the field unless desired
        if (! empty($cat_username)) {
            $row->cat_username = $cat_username;
        }

        if (! empty($home_library)) {
            $row->home_library = $home_library;
        }

        if (! empty($eppn)) {
            if (substr($eppn, 0, 4) === 'DEL_')
                $row->eppn = substr($eppn, 4);
            else
                $row->eppn = $eppn;
        }

        if (! empty($cat_password)) {
            if ($this->passwordEncryptionEnabled()) {
                $row->cat_password = null;
                $row->cat_pass_enc = $this->encryptOrDecrypt($cat_password, true);
            } else {
                $row->cat_password = $cat_password;
                $row->cat_pass_enc = null;
            }
        }

        $row->save();

        $this->activateBestLibraryCard();

        return $row->id;
    }

    /**
     * Creates library card for User with $cat_username & $prefix identified by $eppn.
     *
     * eduPersonPrincipalName is later used to identify loggedin user.
     *
     * Returns library card id on success. Otherwise throws an AuthException.
     *
     * @param string $cat_username
     * @param string $prefix
     * @param string $eppn
     * @param string $email
     * @param array $canConsolidateMoreTimes
     *
     * @return mixed int | boolean
     * @throws AuthException
     */
    public function createLibraryCard($cat_username, $prefix, $eppn, $email, $canConsolidateMoreTimes)
    {
        try {
            if (empty($eppn))
                throw new AuthException("Cannot create library card with empty eppn");

            if (empty($this->id))
                throw new AuthException("Cannot create library card with empty user row id");

            if (empty($email))
                $email = '';

            return $this->saveLibraryCard(null, $email, $cat_username, null, $prefix, $eppn, $canConsolidateMoreTimes);
        } catch (\VuFind\Exception\LibraryCard $e) {
            throw new AuthException($e->getMessage());
        }
    }

    /**
     * Changes specified card's name to provided one.
     *
     * @param number $id
     * @param string $cardName
     */
    public function editLibraryCardName($id, $cardName)
    {
        $this->saveLibraryCard($id, $cardName);
    }

    /**
     * Get all library cards associated with the user.
     * By default there are ommited all Dummy cards.
     *
     * If you wish to retrieve also Dummy cards, pass true to $includingDummyCards.
     *
     * @param boolean $includingDummyCards
     *
     * @return \Zend\Db\ResultSet\AbstractResultSet
     * @throws \VuFind\Exception\LibraryCard
     */
    public function getLibraryCards($includingDummyCards = false)
    {
        if (! $this->libraryCardsEnabled()) {
            return new \Zend\Db\ResultSet\ResultSet();
        }
        $userCard = $this->getDbTable('UserCard');
        if ($includingDummyCards)
            return $userCard->select([
                'user_id' => $this->id
            ]);
        return $userCard->select([
            'user_id' => $this->id,
            'home_library != ?' => 'Dummy'
        ]);
    }

    /**
     * Gets all library cards including Dummy cards.
     *
     * It is an alias for getLibraryCards(true)
     *
     * @return \Zend\Db\ResultSet\AbstractResultSet
     * @throws \VuFind\Exception\LibraryCard
     */
    public function getAllLibraryCards()
    {
        return $this->getLibraryCards(true);
    }

    /**
     * Delete library card.
     *
     * Note that if you supply UserCard row to this method, it will delete it
     * no matter if it is last.
     *
     * @param
     *            mixed (int $id | UserCard $userCard)
     *
     * @param boolean $doNotDeleteIfLast
     *
     * @return void
     * @throws \VuFind\Exception\LibraryCard
     */
    public function deleteLibraryCard($id, $doNotDeleteIfLast = false, $activateAnother = true)
    {
        if (! $this->libraryCardsEnabled()) {
            throw new \VuFind\Exception\LibraryCard('Library Cards Disabled');
        }

        if ($id instanceof UserCard)
            return $this->deleteLibraryCardRow($id, $activateAnother);

        $userCard = $this->getDbTable('UserCard');
        $row = $userCard->select([
            'id' => $id,
            'user_id' => $this->id
        ])->current();

        if (empty($row)) {
            throw new \Exception('Library card not found');
        }

        if ($doNotDeleteIfLast && $this->getAllLibraryCards()->count() === 1) {
            throw new \VuFind\Exception\LibraryCard('Cannot disconnect the last identity');
        }

        $row->delete();

        if ($activateAnother && $row->cat_username == $this->cat_username) {
            // Activate another card (if any) or remove cat_username and cat_password
            $this->activateBestLibraryCard();
        }
    }

    /**
     * This method deletes UserCard row.
     * If it was active card, then is activated another
     * using activateBestLibraryCard method.
     *
     * @param UserCard $libCard
     * @return number $affectedRows
     */
    public function deleteLibraryCardRow(UserCard $libCard, $activateAnother = true)
    {
        $affectedRows = $libCard->delete();

        if ($activateAnother && $libCard->cat_username == $this->cat_username) {
            // Activate another card (if any) or remove cat_username and cat_password
            $this->activateBestLibraryCard();
        }

        return $affectedRows;
    }

    /**
     * Disconnect desired identity.
     *
     * It is an alias for deleteLibraryCard($id, true, true)
     *
     * @param int $id
     *            Library card ID
     *
     * @return void
     * @throws \VuFind\Exception\LibraryCard
     */
    public function disconnectIdentity($id)
    {
        return $this->deleteLibraryCard($id, true, true);
    }

    /**
     * Checks if specified UserCard row id owns current User.
     *
     * @param int $id
     * @return boolean $hasThisLibraryCard
     */
    public function hasThisLibraryCard($id)
    {
        $userCard = $this->getDbTable('UserCard');
        return $userCard->select([
            'id' => $id,
            'user_id' => $this->id
        ])->count() !== 0;
    }

    /**
     * Activates best library card.
     * The algorithm chooses first available card,
     * if it is the only user's card. If user has more than one cards, it checks
     * for any not Dummy card & activates that one if finds any.
     *
     * If from all the cards doesn't find any non-Dummy card, nothing will happen
     * keeping in mind there has already been activated first Dummy card.
     */
    public function activateBestLibraryCard()
    {
        $libCards = $this->getAllLibraryCards();

        // If this is the first library card or no credentials are currently set,
        // activate the card now
        if ($libCards->count() == 1) {
            $this->activateLibraryCard($row->id);
        } else {

            $realCards = $this->parseRealCards($libCards);

            // Activate any realCard if current UserRow's home_library is Dummy
            if ($realCards && $this->home_library === 'Dummy') {
                $firstRealLibCardId = $realCards[0]->id;
                $this->activateLibraryCard($firstRealLibCardId);
            }
        }
    }

    /**
     * Filters out the dummy cards from passed $libCards array
     * of libCards.
     *
     * If no realCard found, returns false.
     *
     * @param array $libCards
     * @return mixed $realCards | false
     */
    public function parseRealCards($libCards)
    {
        $realCards = [];

        try {
            foreach ($libCards as $libCard) {
                if ($libCard->home_library !== 'Dummy')
                    $realCards[] = $libCard;
            }
        } catch (\Exception $e) {
            return false;
        }

        return sizeof($realCards) > 0 ? $realCards : false;
    }

    /**
     * Returns IdPLogos section from config.ini with institutions mapping to their logos.
     */
    public function getIdentityProvidersLogos()
    {
        return $this->config->IdPLogos;
    }
}