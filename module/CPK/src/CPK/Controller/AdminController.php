<?php
/**
 * Admin Controller
 *
 * PHP version 5
 *
 * Copyright (C) Moravian Library 2016.
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
 * @package  Controller
 * @author   Martin Kravec <martin.kravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-3.0.php GNU General Public License
 */
namespace CPK\Controller;

use MZKCommon\Controller\ExceptionsTrait, CPK\Db\Row\User;
use Zend\Config\Writer\Ini as IniWriter;
use VuFind\Exception\Auth as AuthException;
use Zend\Config\Config;
use VuFind\Mailer\Mailer;
use Zend\Mvc\MvcEvent;

/**
 * Class controls VuFind administration.
 *
 * @category VuFind2
 * @package Controller
 * @author Martin Kravec <martin.kravec@mzk.cz>, Jiří Kozlovský <mail@jkozlovsky.cz>
 * @license http://opensource.org/licenses/gpl-3.0.php GNU General Public License
 */
class AdminController extends \VuFind\Controller\AbstractBase
{
    use ExceptionsTrait;

    /**
     * Access manager instance
     *
     * @var AccessManager
     */
    protected $accessManager;

    /**
     * Initializes access manager & continues choosing an action as defined by parent
     *
     * {@inheritDoc}
     *
     * @see \Zend\Mvc\Controller\AbstractActionController::onDispatch()
     */
    public function onDispatch(MvcEvent $e)
    {
        $this->accessManager = new AccessManager($this->getAuthManager());

        return parent::onDispatch($e);
    }

    /**
     * Returns current instance of access manager
     *
     * @return \CPK\Controller\AccessManager
     */
    public function getAccessManager()
    {
        return $this->accessManager;
    }

    /**
     * Admin home.
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function homeAction()
    {
        if (! $this->accessManager->isLoggedIn())
            return $this->forceLogin();

        $this->layout()->searchbox = false;

        $configHandler = new RequestConfigHandler($this);

        $configHandler->handlePostRequestFromHome();

        return $this->createViewModel([
            'isPortalAdmin' => $this->accessManager->isPortalAdmin(),
            'ncipTemplate' => $configHandler->getNcipTemplate(),
            'alephTemplate' => $configHandler->getAlephTemplate(),
            'configs' => $configHandler->getAdminConfigs()
        ]);
    }

    public function approvalAction()
    {
        if (! $this->accessManager->isLoggedIn())
            return $this->forceLogin();

            // Must be an portal admin ..
        $this->accessManager->assertIsPortalAdmin();

        $configHandler = new RequestConfigHandler($this);

        $configHandler->handlePostRequestFromApproval();

        return $this->createViewModel([
            'isPortalAdmin' => $this->accessManager->isPortalAdmin(),
            'ncipTemplate' => $configHandler->getNcipTemplate(),
            'alephTemplate' => $configHandler->getAlephTemplate(),
            'configs' => $configHandler->getAllRequestConfigs()
        ]);
    }

    public function portalPagesAction()
    {
        if (! $this->accessManager->isLoggedIn())
            return $this->forceLogin();

            // Must be an portal admin ..
        $this->accessManager->assertIsPortalAdmin();

        $user = $this->accessManager->getUser();

        $viewModel = $this->createViewModel();
        $viewModel->setVariable('isPortalAdmin', $this->accessManager->isPortalAdmin());
        $viewModel->setVariable('user', $user);

        $portalPagesTable = $this->getTable("portalpages");

        $positions = [
            'left',
            'middle',
            'right'
        ];
        $placements = [
            'footer',
            'advanced-search'
        ];

        $subAction = $this->params()->fromRoute('subaction');
        if ($subAction == 'Edit') { // is edit in route?
            $pageId = (int) $this->params()->fromRoute('param');
            $page = $portalPagesTable->getPageById($pageId);
            $viewModel->setVariable('page', $page);

            $viewModel->setVariable('positions', $positions);
            $viewModel->setVariable('placements', $placements);

            $viewModel->setTemplate('admin/edit-portal-page');
        } else
            if ($subAction == 'Save') {
                $post = $this->params()->fromPost();
                $portalPagesTable->save($post);
                return $this->forwardTo('Admin', 'PortalPages');
            } else
                if ($subAction == 'Insert') {
                    $post = $this->params()->fromPost();
                    $portalPagesTable->insertNewPage($post);
                    return $this->forwardTo('Admin', 'PortalPages');
                } else
                    if ($subAction == 'Delete') {
                        $pageId = $this->params()->fromRoute('param');
                        if (! empty($pageId)) {
                            $portalPagesTable->delete($pageId);
                        }
                        return $this->forwardTo('Admin', 'PortalPages');
                    } else
                        if ($subAction == 'Create') {
                            $viewModel->setVariable('positions', $positions);
                            $viewModel->setVariable('placements', $placements);
                            $viewModel->setTemplate('admin/create-portal-page');
                        } else { // normal view
                            $allPages = $portalPagesTable->getAllPages('*', false);
                            $viewModel->setVariable('pages', $allPages);
                        }

        $this->layout()->searchbox = false;
        return $viewModel;
    }

    /**
     * Permissions manager
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function permissionsManagerAction()
    {
        if (! $this->accessManager->isLoggedIn())
            return $this->forceLogin();

            // Must be an portal admin ..
        $this->accessManager->assertIsPortalAdmin();

        $user = $this->accessManager->getUser();

        $viewModel = $this->createViewModel();
        $viewModel->setVariable('isPortalAdmin', $this->accessManager->isPortalAdmin());
        $viewModel->setVariable('user', $user);

        $userTable = $this->getTable('user');

        $subAction = $this->params()->fromRoute('subaction');
        if ($subAction == 'Save') {
            $post = $this->params()->fromPost();
            $userTable->saveUserWithPermissions($post['eppn'], $post['major']);
            return $this->forwardTo('Admin', 'PermissionsManager');
        } else
            if ($subAction == 'RemovePermissions') {
                $eppn = $this->params()->fromRoute('param');
                $major = NULL;
                $userTable->saveUserWithPermissions($eppn, $major);
                return $this->forwardTo('Admin', 'PermissionsManager');
            } else
                if ($subAction == 'AddUser') {
                    $viewModel->setTemplate('admin/add-user-with-permissions');
                } else
                    if ($subAction == 'EditUser') {
                        $eppn = $this->params()->fromRoute('param');
                        $major = $this->params()->fromRoute('param2');

                        $viewModel->setVariable('eppn', $eppn);
                        $viewModel->setVariable('major', $major);

                        $viewModel->setTemplate('admin/edit-user-with-permissions');
                    } else { // normal view
                        $usersWithPermissions = $userTable->getUsersWithPermissions();
                        $viewModel->setVariable('usersWithPermissions', $usersWithPermissions);
                        $viewModel->setTemplate('admin/permissions-manager');
                    }

        $this->layout()->searchbox = false;
        return $viewModel;
    }
}

/**
 * An Access Manager serving only to Admin Controller
 * in order to have full control over accessing pages
 * dedicated to adminitrators.
 *
 * @author Jiří Kozlovský <mail@jkozlovsky.cz>
 *
 */
class AccessManager
{

    /**
     * Source / identifier of main portal admin
     *
     * @var string
     */
    const PORTAL_ADMIN_SOURCE = 'cpk';

    /**
     * User
     *
     * @var \CPK\Db\Row\User
     */
    protected $user;

    /**
     * Holds names of institutions user is admin of
     *
     * @var array
     */
    protected $institutionsBeingAdminAt = [];

    /**
     * Auth Manager
     *
     * @var \CPK\Auth\Manager
     */
    protected $authManager;

    /**
     * Holds info about user being portal admin
     *
     * @var bool
     */
    protected $isPortalAdmin;

    /**
     * C'tor
     *
     * Throws AuthException only if logged in user is not admin
     * in any institution he has connected.
     *
     * @param \CPK\Auth\Manager $authManager
     *
     * @throws AuthException
     */
    public function __construct(\CPK\Auth\Manager $authManager)
    {
        $this->authManager = $authManager;

        $this->init();
    }

    /**
     * Initializes institutions where is logged in user admin and
     * throws an AuthException when user is not admin in any institution
     *
     * @throws AuthException
     */
    protected function init()
    {
        $this->user = $this->authManager->isLoggedIn();

        if (! $this->user) {
            $this->isPortalAdmin = false;
            return;
        }

        if (! empty($this->user->major)) {

            $sources = explode(',', $this->user->major);

            $this->institutionsBeingAdminAt = $sources;
        }

        $libCards = $this->user->getLibraryCards(true);
        foreach ($libCards as $libCard) {

            if (! empty($libCard->major)) {

                $sources = explode(',', $libCard->major);

                $this->institutionsBeingAdminAt = array_merge($this->institutionsBeingAdminAt, $sources);
            }
        }

        if (empty($this->institutionsBeingAdminAt)) {
            throw new AuthException('You\'re not an admin!');
        }

        // Trim all elements
        $this->institutionsBeingAdminAt = array_unique(array_map('trim', $this->institutionsBeingAdminAt));
    }

    /**
     * Returns bool whether current user is logged in
     *
     * @return boolean
     */
    public function isLoggedIn()
    {
        if (! $this->user)
            return false;

        return true;
    }

    /**
     * Returns current User
     *
     * @return \CPK\Db\Row\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * If current user is not portal admin, it throws an \VuFind\Exception\Auth
     *
     * @throws AuthException
     */
    public function assertIsPortalAdmin()
    {
        if ($this->isPortalAdmin() === false)
            throw new AuthException('You\'re not a portal admin!');
    }

    /**
     * Returns bool whether current user is an portal admin or is not
     *
     * @return boolean
     */
    public function isPortalAdmin()
    {
        if (isset($this->isPortalAdmin))
            return $this->isPortalAdmin;

        foreach ($this->institutionsBeingAdminAt as $adminSource) {
            if (strtolower($adminSource) === self::PORTAL_ADMIN_SOURCE) {

                $this->isPortalAdmin = true;
                return $this->isPortalAdmin;
            }
        }

        $this->isPortalAdmin = false;
        return $this->isPortalAdmin;
    }

    /**
     * Returns array of institution sources where is current
     * logged in user an admin
     *
     * @return array
     */
    public function getInstitutionsWithAdminRights()
    {
        return $this->institutionsBeingAdminAt;
    }
}

/**
 * An handler for handling requests from institutions admins
 * to change their configurations & approval of those configurations
 * by portal admin.
 *
 * @author Jiří Kozlovský <mail@jkozlovsky.cz>
 *
 */
class RequestConfigHandler
{

    /**
     * Controller which spawned this instance.
     *
     * @var AdminController
     */
    protected $ctrl;

    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Config Locator
     *
     * @var \VuFind\Config\PluginManager
     */
    protected $configLocator;

    /**
     * Relative path to institutions configurations
     *
     * @var array
     */
    protected $driversPath;

    /**
     * Absolute path to institutions configurations
     *
     * @var array
     */
    protected $driversAbsolutePath;

    /**
     * Object containing NCIP driver config template
     *
     * @var array
     */
    protected $ncipTemplate;

    /**
     * Object containing Aleph driver config template
     *
     * @var array
     */
    protected $alephTemplate;

    /**
     * Object holding the configuration of email to use when a configuration change is desired by some institution admin
     *
     * @var array
     */
    protected $emailConfig;

    /**
     * Mailer to notify about changes made by institutions admins
     *
     * @var Mailer
     */
    protected $mailer;

    /**
     * Array of institution sources where is current user an admin
     *
     * @var array
     */
    protected $institutionsBeingAdminAt;

    /**
     * C'tor
     *
     * @param \VuFind\Controller\AbstractBase $controller
     */
    public function __construct(AdminController $controller)
    {
        $this->ctrl = $controller;

        $this->serviceLocator = $this->ctrl->getServiceLocator();

        $this->initConfigs();
    }

    /**
     * Initialize configurations
     *
     * @return void
     */
    protected function initConfigs()
    {
        $this->configLocator = $this->serviceLocator->get('VuFind\Config');

        $multibackend = $this->configLocator->get('MultiBackend')->toArray();

        // get the drivers path
        $this->driversPath = empty($multibackend['General']['drivers_path']) ? '.' : $multibackend['General']['drivers_path'];

        // we need it to be an absolute path ..
        $this->driversAbsolutePath = $_SERVER['VUFIND_LOCAL_DIR'] . '/config/vufind/' . $this->driversPath . '/';

        // get the templates
        $this->ncipTemplate = $this->configLocator->get('xcncip2_template')->toArray();
        $this->alephTemplate = $this->configLocator->get('aleph_template')->toArray();

        // setup email
        $this->emailConfig = $this->configLocator->get('config')['Config_Change_Mailer']->toArray();

        if ($this->emailConfig['enabled'] && (empty($this->emailConfig['from']) || empty($this->emailConfig['to']))) {
            throw new \Exception('Invalid Config_Change_Mailer configuration!');
        }

        $this->mailer = $this->serviceLocator->get('VuFind\Mailer');

        $this->institutionsBeingAdminAt = $this->ctrl->getAccessManager()->getInstitutionsWithAdminRights();
    }

    /**
     * Handles POST request from a home action
     *
     * It basically processess any config change desired
     *
     * @param array $post
     */
    public function handlePostRequestFromHome()
    {
        // Do we have some POST?
        if (! empty($post = $this->ctrl->params()->fromPost())) {

            // Is there a query for a config modification?
            if (isset($post['requestChange'])) {

                unset($post['requestChange']);

                $this->processChangeRequest($post);
            } else
                if (isset($post['requestChangeCancel'])) {
                    // Or there is query for cancelling a config modification?

                    unset($post['requestChangeCancel']);

                    $this->processCancelChangeRequest($post);
                }
        }
    }

    /**
     * Handles POST request from an approval action
     */
    public function handlePostRequestFromApproval()
    {
        // Do we have some POST?
        if (! empty($post = $this->ctrl->params()->fromPost())) {

            if (! isset($post['source']))
                return;

            $source = $post['source'];

            $contactPerson = $post['Catalog']['contactPerson'];

            // Is there a query for a config modification?
            if (isset($post['approved'])) {

                $result = $this->approveRequest($post);

                if ($result) {

                    $this->sendRequestApprovedMail($source, $post['message'], $contactPerson);

                    $msg = $this->translate('approval_succeeded');
                    $this->flashMessenger()->addSuccessMessage($msg);

                    $this->commitNewConfig($source);
                } else {

                    $msg = $this->translate('approval_failed');
                    $this->flashMessenger()->addErrorMessage($msg);

                    $suggestion = "Try to execute 'sudo chown -R www-data \"$this->driversAbsolutePath\"'";

                    $this->flashMessenger()->addErrorMessage($suggestion);
                }
            } else
                if (isset($post['denied'])) {

                    $this->deleteRequestConfig([
                        'source' => $source
                    ]);

                    $this->sendRequestDeniedMail($source, $post['message'], $contactPerson);

                    $msg = $this->translate('request_successfully_denied');
                    $this->flashMessenger()->addSuccessMessage($msg);
                }
        }
    }

    /**
     * Returns all configs associated with current admin
     *
     * @return array
     */
    public function getAdminConfigs()
    {
        $configs = [];

        // Fetch all configs
        foreach ($this->institutionsBeingAdminAt as $adminSource) {

            // Exclude portal configs as they doesn't exist
            if (strtolower($adminSource) !== AccessManager::PORTAL_ADMIN_SOURCE) {

                $configs[$adminSource] = $this->getInstitutionConfig($adminSource);
            }
        }

        return $configs;
    }

    /**
     * Returns an NCIP template configuration file
     *
     * @return array
     */
    public function getNcipTemplate()
    {
        return $this->ncipTemplate;
    }

    /**
     * Returns an Aleph template configuration file
     *
     * @return array
     */
    public function getAlephTemplate()
    {
        return $this->alephTemplate;
    }

    /**
     * Returns all configs being requested
     *
     * @return array
     */
    public function getAllRequestConfigs()
    {
        $configs = [];

        $requestsPath = $this->driversAbsolutePath . 'requests/';

        $files = scandir($requestsPath, SCANDIR_SORT_NONE);

        foreach ($files as $file) {
            if (substr($file, - 4) === '.ini') {

                $source = substr($file, 0, - 4);

                $configs[$source] = $this->getInstitutionConfig($source, false);
            }
        }

        return $configs;
    }

    /**
     * Process a configuration change request
     *
     * @param array $post
     */
    protected function processChangeRequest($post)
    {
        if (! $this->changedSomethingComapredToActive($post)) {
            $requestUnchanged = $this->translate('request_config_denied_unchanged');
            $this->flashMessenger()->addErrorMessage($requestUnchanged);
            return;
        } elseif ($this->changedHiddenConfiguration($post)) {
            $requestUnchanged = $this->translate('request_config_denied_unauthorized');
            $this->flashMessenger()->addErrorMessage($requestUnchanged);
            return;
        }

        $post['Catalog']['requester'] = $_SESSION['Account']['userId'];

        $success = $this->createNewRequestConfig($post);

        if ($success) {

            $requestCreated = $this->translate('request_config_created');
            $this->flashMessenger()->addSuccessMessage($requestCreated);

            $this->sendNewRequestMail($post['source']);
        }
    }

    /**
     * Process a cancel for a configuration change
     *
     * @param array $post
     */
    protected function processCancelChangeRequest($post)
    {
        $success = $this->deleteRequestConfig($post);

        if ($success) {

            $requestCancelled = $this->translate('request_config_change_cancelled');
            $this->flashMessenger()->addSuccessMessage($requestCancelled);

            $this->sendRequestCancelledMail($post['source']);
        }
    }

    /**
     * Returns true if provided configuration differs from the activeOne
     *
     * @param array $config
     *
     * @return boolean
     */
    protected function changedSomethingComapredToActive($config)
    {
        $isAleph = isset($config['Catalog']['dlfport']);

        $template = $isAleph ? $this->alephTemplate : $this->ncipTemplate;

        $defs = $template['Definitions'];

        $hidden = $defs['hidden'];

        // Has the request changed something?
        foreach ($this->getActiveConfig($config['source']) as $section => $keys) {

            if (array_search($section, $hidden) !== false)
                continue;

            foreach ($keys as $key => $value) {
                if (array_search($section . ':' . $key, $hidden) !== false)
                    continue;

                if ($defs[$section][$key] === 'checkbox') {
                    $config[$section][$key] = isset($config[$section][$key]) ? '1' : '0';
                }

                if ($value != $config[$section][$key]) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns true if provided configuration has hidden parameters present within it.
     *
     * It should prevent curious institution administrators from changing values they're not supposed to change.
     *
     * @param array $config
     *
     * @return boolean
     */
    protected function changedHiddenConfiguration($config)
    {
        unset($config['source']);

        $isAleph = isset($config['Catalog']['dlfport']);

        $template = $isAleph ? $this->alephTemplate : $this->ncipTemplate;

        $defs = $template['Definitions'];

        $hidden = $defs['hidden'];

        // Has the request changed something?
        foreach ($config as $section => $keys) {
            foreach ($keys as $key => $value) {
                if (array_search($section . ':' . $key, $hidden) !== false)
                    return true;
            }
        }

        return false;
    }

    /**
     * Approves an configuration request made by institution admin
     *
     * @param string $source
     *
     * @return boolean $result
     */
    protected function approveRequest($post)
    {
        $requestedConfig = $this->getInstitutionConfig($post['source'], false)['requested'];
        $requesterId = $requestedConfig['Catalog']['requester'];

        // Create new config with the initial requester Id
        $post['Catalog']['requester'] = $requesterId;

        $succeeded = $this->createNewRequestConfig($post, $post['message']);

        if (! $succeeded)
            return false;

        $source = $post['source'];

        $requestFilename = $this->driversAbsolutePath . 'requests/' . $source . '.ini';
        $activeFilename = $this->driversAbsolutePath . $source . '.ini';

        $isCopied = copy($requestFilename, $activeFilename);

        if (! $isCopied) {

            // Perform chown using mv & cp if www-data owns the dir
            $deleteMeFilename = $activeFilename . '_deleteMe';

            $fullCmd = "mv \"" . $activeFilename . "\" \"" . $deleteMeFilename . "\"";
            $fullCmd .= " && ";
            $fullCmd .= "cp \"" . $deleteMeFilename . "\" \"" . $activeFilename . "\"";
            $fullCmd .= " && ";
            $fullCmd .= "rm \"" . $deleteMeFilename . "\"";

            exec($fullCmd, $result);

            $isCopied = copy($requestFilename, $activeFilename);

            if (! $isCopied)
                return $isCopied;
        }

        $isDeleted = $this->deleteRequestConfig([
            'source' => $source
        ]);

        return $isDeleted;
    }

    /**
     * Deletes request configuration from the requests dir
     *
     * @param array $config
     * @throws \Exception
     *
     * @return boolean
     */
    protected function deleteRequestConfig($config)
    {
        $source = $config['source'];

        if (empty($source))
            return false;

        unset($config['source']);

        if (! in_array($source, $this->institutionsBeingAdminAt) && ! $this->ctrl->isPortalAdmin()) {
            throw new \Exception('You don\'t have permissions to change config of ' . $source . '!');
        }

        $filename = $this->driversAbsolutePath . 'requests/' . $source . '.ini';

        return unlink($filename);
    }

    /**
     * Saves new configuration
     *
     * @param array $config
     */
    protected function createNewRequestConfig($config, $comment = null)
    {
        $source = $config['source'];

        if (empty($source))
            return false;

        unset($config['source']);

        if (! in_array($source, $this->institutionsBeingAdminAt)) {
            throw new \Exception('You don\'t have permissions to change config of ' . $source . '!');
        }

        $config = $this->parseConfigSections($config, $source);

        if (isset($config['Availability']['source'])) {
            $config['Availability']['source'] = $source;
        }

        $requestsPath = $this->driversAbsolutePath . 'requests/';

        $filename = $requestsPath . $source . '.ini';

        $dirStatus = is_dir($requestsPath) || mkdir($requestsPath, 0777, true);

        if (! $dirStatus) {
            throw new \Exception("Cannot create '$requestsPath' directory. Please fix the permissions by running: 'sudo mkdir $requestsPath && sudo chown -R www-data $requestsPath'");
        }

        $config = $this->cleanData($config);
        $config = new Config($config, false);

        try {
            (new IniWriter())->toFile($filename, $config);
        } catch (\Exception $e) {
            throw new \Exception("Cannot write to file '$filename'. Please fix the permissions by running: 'sudo chown -R www-data $requestsPath'");
        }

        if ($comment !== null && ! empty($comment)) {

            // Remove new lines
            $comment = '; ' . preg_replace('/\s+/', ' ', $comment);

            // Add some comment to the comment
            $comment = '; Description added while approving by admin with userId "' . $_SESSION['Account']['userId'] . '":' . PHP_EOL . $comment;

            file_put_contents($filename, $comment . PHP_EOL, FILE_APPEND);
        }

        return $config;
    }

    /**
     * Clean data
     * Cleanup: Remove double quotes
     *
     * @param Array $data
     *            Data
     *
     * @return Array
     */
    protected function cleanData(array $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->cleanData($value);
            } else {
                $data[$key] = str_replace('"', '', $value);
            }
        }
        return $data;
    }

    /**
     * Unsets all the keys within a config that matches the template's Definition's hidden array
     *
     * @param array $config
     *
     * @return array $filteredConfig
     */
    protected function filterHiddenParameters($config)
    {
        $isAleph = isset($config['Catalog']['dlfport']);

        $template = $isAleph ? $this->alephTemplate : $this->ncipTemplate;

        $hidden = $template['Definitions']['hidden'];

        if (empty($hidden))
            return $config;

        $filteredConfig = [];

        foreach ($config as $section => $keys) {

            // There may be hidden whole sections
            if (array_search($section, $hidden) !== false)
                continue;

            foreach ($keys as $key => $value) {

                // Is hidden current key?
                if (array_search($section . ':' . $key, $hidden) === false) {
                    $filteredConfig[$section][$key] = $value;
                }
            }
        }

        return $filteredConfig;
    }

    /**
     * Uses git to version config changes
     *
     * @param string $source
     */
    protected function commitNewConfig($source)
    {
        exec('dpkg -l | egrep "ii\s+git\s"', $grepInstalled);
        if (empty($grepInstalled))
            throw new \Exception('You have to first install git');

        if (strpos($result[0], 'fatal') === 0) {
            throw new \Exception("$this->driversAbsolutePath is not a git repository !");
        }

        exec("cd $this->driversAbsolutePath && git status --porcelain", $gitStatus);

        $file = $source . '.ini';

        // Do we already have this config added to git tracked files?
        foreach ($gitStatus as $fileStatus) {

            if (strpos('?? ' . $fileStatus, $file) !== false) {

                // Now add the file to the tracked files
                exec("cd $this->driversAbsolutePath && git add $file", $gitStatus);
            }
        }

        $commitMessage = "\"Approved config change for $source\"";

        exec("cd $this->driversAbsolutePath && git commit \"$file\" -m $commitMessage", $commitResult);

        $this->flashMessenger()->addWarningMessage("Added commit $commitMessage, but changes are not pushed yet");
        // git push should be called by cron

        return true;
    }

    /**
     * Parses config from the POST.
     *
     * Note that it cuts out the configuration which is not included within the template
     *
     * @param array $config
     * @param string $source
     */
    protected function parseConfigSections($config, $source)
    {
        $isAleph = isset($config['Catalog']['dlfport']);

        $template = $isAleph ? $this->alephTemplate : $this->ncipTemplate;

        $defs = $template['Definitions'];

        // Prepare template for effective iteration
        unset($template['Definitions']);

        $parsedCfg = [];

        // Rename 'relative_path_template' to 'relative_path'
        if (isset($template['Parent_Config']['relative_path_template'])) {

            $template['Parent_Config']['relative_path'] = $template['Parent_Config']['relative_path_template'];

            unset($template['Parent_Config']['relative_path_template']);
        }

        foreach ($template as $section => $keys) {
            foreach ($keys as $key => $value) {

                if ($defs[$section][$key] === 'checkbox') {
                    $parsedCfg[$section][$key] = isset($config[$section][$key]) ? '1' : '0';
                } else {
                    // Set new configuration or default if not provided
                    $parsedCfg[$section][$key] = isset($config[$section][$key]) ? $config[$section][$key] : $value;
                }
            }
        }

        // Add prefix for IdResolver
        $parsedCfg['IdResolver']['prefix'] = $source;

        return $parsedCfg;
    }

    /**
     * Returns an associative array of institution configuration.
     *
     * If was configuration not found, then is returned empty array.
     *
     * @param string $source
     *
     * @return array
     */
    protected function getInstitutionConfig($source, $filterHidden = true)
    {
        $activeCfg = $this->getActiveConfig($source);

        $requestCfgPath = $this->driversPath . '/requests/' . $source;

        try {
            $requestCfg = $this->configLocator->get($requestCfgPath)->toArray();
        } catch (\Exception $e) {

            // There is probably a parent config definition without the parent config
            $missingParent = $this->getMissingParentConfigName($source);

            if (! $missingParent)
                throw $e;

                // So create one dummy parent config
            $this->createMissingParent($this->driversAbsolutePath . '/requests/' . $missingParent);

            // Now try it again
            $requestCfg = $this->configLocator->get($requestCfgPath)->toArray();
        }

        if ($filterHidden)
            return [
                'active' => $this->filterHiddenParameters($activeCfg),
                'requested' => $this->filterHiddenParameters($requestCfg)
            ];
        else
            return [
                'active' => $activeCfg,
                'requested' => $requestCfg
            ];
    }

    /**
     * Retrieves active config of an institution
     *
     * @param string $source
     *
     * @return array
     */
    protected function getActiveConfig($source)
    {
        return $this->configLocator->get($this->driversPath . '/' . $source)->toArray();
    }

    /**
     * Returns filename of missing parent config within a requests' config
     *
     * @return false|string
     */
    protected function getMissingParentConfigName($source)
    {
        $filename = $this->driversAbsolutePath . 'requests/' . $source . '.ini';

        $missingParent = false;

        $fh = fopen($filename, 'r') or die($php_errormsg);
        while (! feof($fh)) {
            $line = fgets($fh, 4096);
            if (preg_match('/relative_path/', $line)) {

                $missingParent = explode('"', $line)[1];
                break;
            }
        }
        fclose($fh);

        return $path . $missingParent;
    }

    /**
     * Creates empty
     *
     * @param string $filename
     * @throws \Exception
     */
    protected function createMissingParent($filename)
    {
        try {
            file_put_contents($filename, '');
        } catch (\Exception $e) {
            throw new \Exception("Cannot write to file '$filename'. Please fix the permissions by running: 'sudo chown -R www-data $path'");
        }
    }

    /**
     * Sends an information email about a configuration request change has beed cancelled
     *
     * @param string $source
     */
    protected function sendRequestCancelledMail($source)
    {
        if ($this->emailConfig['enabled']) {

            $subject = 'Zrušení žádosti o změnu konfigurace u instituce ' . $source;

            $message = 'Administrátor č. ' . $_SESSION['Account']['userId'] . ' instituce "' . $source . '" zrušil žádost o změnu konfigurace.';

            return $this->sendMailToPortalAdmin($subject, $message);
        }

        return false;
    }

    /**
     * Sends an information email about a new configuration request
     *
     * @param string $source
     */
    protected function sendNewRequestMail($source)
    {
        if ($this->emailConfig['enabled']) {

            $subject = 'Žádost o změnu konfigurace u instituce ' . $source;

            $message = 'Administrátor č. ' . $_SESSION['Account']['userId'] . ' instituce "' . $source . '" vytvořil žádost o změnu konfigurace.';

            return $this->sendMailToPortalAdmin($subject, $message);
        }

        return false;
    }

    /**
     * Sends an information email about a configuration request has been approved
     *
     * @param string $source
     * @param string $message
     * @param string $to
     */
    protected function sendRequestApprovedMail($source, $message, $to)
    {
        if ($this->emailConfig['enabled']) {

            $subject = 'Schválení žádosti o změnu konfigurace u instituce ' . $source;

            $message = 'Vážený administrátore č. ' . $_SESSION['Account']['userId'] . ',\r\n\r\n právě jsme Vám schválili Vaši žádost o změnu konfigurace v instituci ' . $source . '\r\n\r\n' . $message;

            return $this->sendMailToContactPerson($subject, $message, $to);
        }

        return false;
    }

    /**
     * Sends an information email about a configuration request has been denied
     *
     * @param string $source
     * @param string $message
     * @param string $to
     */
    protected function sendRequestDeniedMail($source, $message, $to)
    {
        if ($this->emailConfig['enabled']) {

            $subject = 'Žádost o změnu konfigurace u instituce ' . $source . ' byla zamítnuta';

            $message = 'Vážený administrátore č. ' . $_SESSION['Account']['userId'] . ',\r\n\r\n právě Vám byla Vaše žádost o změnu konfigurace v instituci ' . $source . ' zamítnuta.\r\n\r\n' . $message;

            return $this->sendMailToContactPerson($subject, $message, $to);
        }

        return false;
    }

    /**
     * Sends an email as defined within a config at section named Config_Change_Mailer
     *
     * @param string $subject
     * @param string $message
     */
    protected function sendMailToPortalAdmin($subject, $message)
    {
        $from = new \Zend\Mail\Address($this->emailConfig['from'], $this->emailConfig['fromName']);

        return $this->mailer->send($this->emailConfig['to'], $from, $subject, $message);
    }

    /**
     * Sends an email to a contact person
     *
     * @param string $subject
     * @param string $message
     * @param string $to
     */
    protected function sendMailToContactPerson($subject, $message, $to)
    {
        $from = new \Zend\Mail\Address($this->emailConfig['from'], $this->emailConfig['fromName']);

        return $this->mailer->send($to, $from, $subject, $message);
    }

    private function translate($msg, $tokens = [], $default = null)
    {
        return $this->ctrl->translate($msg, $tokens, $default);
    }

    private function flashMessenger()
    {
        return $this->ctrl->flashMessenger();
    }
}