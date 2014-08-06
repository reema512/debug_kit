<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\DebugKit;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Event\EventListener;

/**
 * Base class for debug panels.
 *
 * @since         DebugKit 0.1
 */
class DebugPanel implements EventListener {

/**
 * Defines which plugin this panel is from so the element can be located.
 *
 * @var string
 */
	public $plugin = 'DebugKit';

/**
 * Defines the title for displaying on the toolbar. If null, the class name will be used.
 * Overriding this allows you to define a custom name in the toolbar.
 *
 * @var string
 */
	public $title = null;

/**
 * Panel's css files
 *
 * @var array
 */
	public $css = array();

/**
 * Panel's javascript files
 *
 * @var array
 */
	public $javascript = array();

/**
 * Provide a custom element name for this panel. If null, the underscored version of the class
 * name will be used.
 *
 * @var string
 */
	public $elementName = null;

/**
 * The data collected about a given request.
 *
 * @var array
 */
	protected $_data = [];

/**
 * Empty constructor
 */
	public function __construct() {
	}

/**
 * startup the panel
 *
 * Pull information from the controller / request
 *
 * Old style callbacks for non-event based toolbar.
 *
 * @param \Cake\Controller\Controller $controller controller reference.
 * @return void
 */
	public function startup(Controller $event) {
	}

/**
 * Prepare output vars before Controller Rendering.
 *
 * Old style callbacks for non-event based toolbar.
 *
 * @param \Cake\Controller\Controller $controller controller reference.
 * @return void
 */
	public function beforeRender(Controller $event) {
	}

/**
 * Get the data a panel has collected.
 *
 * @return array
 */
	public function data() {
		return $this->_data;
	}

/**
 * Initialize callback
 *
 * @param \Cake\Event\Event $event The event.
 * @return void
 */
	public function initialize(Event $event) {
	}

/**
 * Shutdown callback
 *
 * @param \Cake\Event\Event $event The event.
 * @return void
 */
	public function shutdown(Event $event) {
	}

/**
 * Get the events this panels supports.
 *
 * @return array
 */
	public function implementedEvents() {
		return [
			'Controller.initialize' => 'initialize',
			'Controller.shutdown' => 'shutdown',
		];
	}
}