<?php
/**
 * CakeManager (http://cakemanager.org)
 * Copyright (c) http://cakemanager.org
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) http://cakemanager.org
 * @link          http://cakemanager.org CakeManager Project
 * @since         1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace CakeAdmin\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;

class AppController extends Controller
{

    /**
     * Initialize AppController
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('Flash');

        $this->loadComponent('CakeAdmin.CakeAdmin');

        $this->loadComponent('Auth', [
            'authorize' => ['Controller'],
            'authenticate' => [
                'Basic' => [
                    'userModel' => 'CakeAdmin.Administrators'
                ],
                'Form' => [
                    'userModel' => 'CakeAdmin.Administrators',
                    'fields' => Configure::read('CA.fields'),
                    'scope' => ['Administrators.cakeadmin' => true]
                ],
            ],
            'loginAction' => [
                'prefix' => 'admin',
                'plugin' => 'CakeAdmin',
                'controller' => 'Users',
                'action' => 'login'
            ],
            'loginRedirect' => [
                'prefix' => 'admin',
                'plugin' => 'CakeAdmin',
                'controller' => 'Dashboard',
                'action' => 'index'
            ],
            'logoutRedirect' => [
                'prefix' => 'admin',
                'plugin' => 'CakeAdmin',
                'controller' => 'Users',
                'action' => 'login'
            ]
        ]);

        $this->Auth->sessionKey = 'Auth.CakeAdmin';
        $this->authUser = $this->Auth->user();

        $this->loadComponent('Utils.GlobalAuth');
        $this->loadComponent('Csrf');
        $this->loadComponent('Utils.Menu');
        $this->loadComponent('CakeAdmin.PostTypes');
        $this->loadComponent('Bakkerij/Notifier.Notifier');

        $event = new Event('CakeAdmin.Controller.afterInitialize', $this);
        EventManager::instance()->dispatch($event);
    }

    /**
     * beforeFilter event.
     *
     * @param Event $event Event.
     * @return void
     */
    public function beforeFilter(Event $event)
    {
        $this->viewBuilder()->className(Configure::read('CA.viewClass'))->theme(Configure::read('CA.theme'));

        if ($this->authUser) {
            $this->viewBuilder()->layout(Configure::read('CA.layout.default'));
        } else {
            $this->viewBuilder()->layout(Configure::read('CA.layout.login'));
        }

        $this->_addNotificationMenu();

        $event = new Event('CakeAdmin.Controller.beforeFilter', $this);
        EventManager::instance()->dispatch($event);
    }

    /**
     * beforeRender event.
     *
     * @param Event $event Event.
     * @return void
     */
    public function beforeRender(Event $event)
    {
        $this->set('authUser', $this->authUser);
        $this->set('title', $this->name);

        $event = new Event('CakeAdmin.Controller.beforeRender', $this);
        EventManager::instance()->dispatch($event);
    }

    /**
     * authorizes every administrator on every action.
     *
     * @param array $user User to authorize.
     * @return bool
     */
    public function isAuthorized($user = null)
    {
        return true;
    }

    /**
     * Initializes all admin menu-items.
     *
     * @return void
     */
    public function initMenuItems()
    {
        $this->Menu->area('headerLeft');

        $this->Menu->add('ca.user', [
            'title' => $this->authUser[Configure::read('CA.fields.username')],
            'url' => '#'
        ]);

        $this->Menu->add('ca.logout', [
            'parent' => 'ca.user',
            'title' => __d('CakeAdmin', 'Logout'),
            'url' => [
                'prefix' => 'admin',
                'plugin' => 'CakeAdmin',
                'controller' => 'Users',
                'action' => 'logout',
            ]
        ]);

        $this->Menu->area('main');

        $this->Menu->add('ca.dashboard', [
            'title' => __d('CakeAdmin', 'Dashboard'),
            'url' => [
                'prefix' => 'admin',
                'plugin' => 'CakeAdmin',
                'controller' => 'Dashboard',
                'action' => 'index',
            ],
            'weight' => 0,
        ]);

        $this->Menu->add('ca.settings', [
            'title' => __d('CakeAdmin', 'Settings'),
            'url' => [
                'prefix' => 'admin',
                'plugin' => 'CakeAdmin',
                'controller' => 'Settings',
                'action' => 'index',
            ],
            'weight' => 50
        ]);

        foreach (Configure::read('CA.Menu.main') as $key => $value) {
            $this->Menu->add($key, $value);
        }
    }

    /**
     * Adds the notification-menu-item to the header menu.
     *
     * @return void
     */
    protected function _addNotificationMenu()
    {
        $this->Menu->area('headerLeft');

        $this->Menu->add('notifier.notifications', [
            'title' => __d('CakeAdmin', 'Notifications ({0})', $this->Notifier->countNotifications(null, true)),
            'url' => '#',
            'weight' => 5
        ]);

        $notifications = $this->Notifier->getNotifications(null, true);

        foreach ($notifications as $not) {
            $this->Menu->add('notifier.notifications.' . $not->id, [
                'parent' => 'notifier.notifications',
                'title' => $not->title,
                'url' => [
                    'prefix' => 'admin',
                    'plugin' => 'CakeAdmin',
                    'controller' => 'Notifications',
                    'action' => 'index'
                ]
            ]);
        }

        $this->Menu->add('notifier.notifications.url', [
            'parent' => 'notifier.notifications',
            'title' => '> ' . __d('CakeAdmin', 'All Notifications'),
            'url' => [
                'prefix' => 'admin',
                'plugin' => 'CakeAdmin',
                'controller' => 'Notifications',
                'action' => 'index'
            ]
        ]);
    }
}
