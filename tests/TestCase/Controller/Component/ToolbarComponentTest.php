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
 * @since         DebugKit 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\DebugKit\Test\TestCase\Controller\Component;

use App\Controller\AppController;
use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\DebugKit\Controller\Component\ToolbarComponent;
use Cake\DebugKit\DebugMemory;
use Cake\DebugKit\DebugTimer;

/**
 * Class TestToolbarComponent
 */
class TestToolbarComponent extends ToolbarComponent {

	/**
	 * Load Panels of Toolbar
	 *
	 * @param $panels
	 * @param array $settings
	 */
	public function loadPanels($panels, $settings = array()) {
		$this->_loadPanels($panels, $settings);
	}
}

/**
 * ToolbarComponentTestCase Test case
 *
 */
class ToolbarComponentTestCase extends TestCase {

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = array('core.article');

/**
 * url for test
 *
 * @var string
 */
	public $url;

/**
 * Start test callback
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		Configure::write('Cache.disable', false);

		$this->url = '/';
		$this->markTestIncomplete('Toolbar tests are not working right now.');
	}

/**
 * endTest
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Controller);
		TableRegistry::clear();
		if (class_exists('DebugMemory')) {
			DebugMemory::clear();
		}
		if (class_exists('DebugTimer')) {
			DebugTimer::clear();
		}
		Router::reload();
	}

/**
 * loading test controller
 *
 * @param array $settings
 * @return Controller
 */
	protected function _loadController($settings = array()) {
		$request = new Request($this->url);
		$request->addParams(Router::parse($this->url));
		$this->Controller = new Controller($request);
		$this->Controller->uses = null;
		$this->Controller->components = array('Toolbar' => $settings + array('className' => 'TestToolbar'));
		$this->Controller->constructClasses();
		$this->Controller->Components->trigger('initialize', array($this->Controller));
		return $this->Controller;
	}

/**
 * test Loading of panel classes
 *
 * @return void
 */
	public function testLoadPanels() {
		$this->_loadController();

		$this->Controller->Toolbar->loadPanels(array('session', 'request'));
		$this->assertInstanceOf('SessionPanel', $this->Controller->Toolbar->panels['session']);
		$this->assertInstanceOf('RequestPanel', $this->Controller->Toolbar->panels['request']);

		$this->Controller->Toolbar->loadPanels(array('history'), array('history' => 10));
		$this->assertEquals($this->Controller->Toolbar->panels['history']->history, 10);
	}

/**
 * Test exceptions on bad panel names
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testLoadPanelsError() {
		$this->Controller->Toolbar->loadPanels(array('randomNonExisting', 'request'));
	}

/**
 * test Loading of panel classes from a plugin
 *
 * @return void
 */
	public function testLoadPluginPanels() {
		Plugin::load('DebugkitTestPlugin');
		$this->_loadController();
		$this->Controller->Toolbar->loadPanels(array('DebugkitTestPlugin.PluginTest'));
		$this->assertInstanceOf(
			'PluginTestPanel',
			$this->Controller->Toolbar->panels['plugin_test']
		);
	}

/**
 * test loading of vendor panels from test_app folder
 *
 * @return void
 */
	public function testLibPanels() {
		$this->_loadController(array(
			'panels' => array('test'),
			'className' => 'DebugKit.Toolbar',
		));
		$this->assertTrue(isset($this->Controller->Toolbar->panels['test']));
		$this->assertInstanceOf('TestPanel', $this->Controller->Toolbar->panels['test']);
	}

/**
 * test construct
 *
 * @return void
 */
	public function testConstruct() {
		$this->_loadController();

		$this->assertFalse(empty($this->Controller->Toolbar->panels));

		$memory = DebugMemory::getAll();
		$this->assertTrue(isset($memory['Component initialization']));

		$events = $this->Controller->getEventManager();
		$this->assertNotEmpty($events->listeners('Controller.initialize'));
		$this->assertNotEmpty($events->listeners('Controller.startup'));
		$this->assertNotEmpty($events->listeners('Controller.beforeRender'));
		$this->assertNotEmpty($events->listeners('Controller.shutdown'));
		$this->assertNotEmpty($events->listeners('View.beforeRender'));
		$this->assertNotEmpty($events->listeners('View.afterRender'));
		$this->assertNotEmpty($events->listeners('View.beforeLayout'));
		$this->assertNotEmpty($events->listeners('View.afterLayout'));
	}

/**
 * test initialize w/ custom panels and defaults
 *
 * @return void
 */
	public function testInitializeCustomPanelsWithDefaults() {
		$this->_loadController(array(
			'panels' => array('test'),
		));

		$expected = array(
			'history', 'session', 'request', 'sql_log', 'timer',
			'log', 'variables', 'environment', 'include', 'test'
		);
		$this->assertEquals($expected, array_keys($this->Controller->Toolbar->panels));
	}

/**
 * test syntax for removing panels
 *
 * @return void
 */
	public function testInitializeRemovingPanels() {
		$this->_loadController(array(
			'panels' => array(
				'session' => false,
				'history' => false,
			)
		));

		$expected = array('request', 'sql_log', 'timer', 'log', 'variables', 'environment', 'include');
		$this->assertEquals($expected, array_keys($this->Controller->Toolbar->panels));
	}

/**
 * ensure that Toolbar is not enabled when debug == 0 on initialize
 *
 * @return void
 */
	public function testDebugDisableOnInitialize() {
		$_debug = Configure::read('debug');
		Configure::write('debug', 0);
		$this->_loadController();
		Configure::write('debug', $_debug);

		$this->assertFalse($this->Controller->Components->enabled('Toolbar'));
	}

/**
 * test that passing in forceEnable will enable the toolbar even if debug = 0
 *
 * @return void
 */
	public function testForceEnable() {
		$_debug = Configure::read('debug');
		Configure::write('debug', 0);
		$this->_loadController(array(
			'forceEnable' => true,
		));
		Configure::write('debug', $_debug);

		$this->assertTrue($this->Controller->Components->enabled('Toolbar'));
	}

/**
 * Test disabling autoRunning of toolbar
 *
 * @return void
 */
	public function testAutoRunSettingFalse() {
		$this->_loadController(array(
			'autoRun' => false,
		));
		$this->assertFalse($this->Controller->Components->enabled('Toolbar'));
	}

/**
 * test autorun = false with query string param
 *
 * @return void
 */
	public function testAutoRunSettingWithQueryString() {
		$this->url = '/?debug=1';
		$_GET['debug'] = 1;
		$this->_loadController(array(
			'autoRun' => false,
		));
		$this->assertTrue($this->Controller->Components->enabled('Toolbar'));
	}

/**
 * test startup
 *
 * @return void
 */
	public function testStartup() {
		$this->_loadController(array(
			'panels' => array('timer'),
		));
		$MockPanel = $this->getMock('DebugPanel');
		$MockPanel->expects($this->once())->method('startup');
		$this->Controller->Toolbar->panels['timer'] = $MockPanel;

		$this->Controller->Toolbar->startup($this->Controller);

		$timers = DebugTimer::getAll();
		$this->assertTrue(isset($timers['controllerAction']));
		$memory = DebugMemory::getAll();
		$this->assertTrue(isset($memory['Controller action start']));
	}

/**
 * Test that cache config generation works.
 *
 * @return void
 */
	public function testCacheConfigGeneration() {
		$this->_loadController();
		$this->Controller->Components->trigger('startup', array($this->Controller));

		$results = Cache::config('debug_kit');
		$this->assertTrue(is_array($results));
	}

/**
 * test state saving of toolbar
 *
 * @return void
 */
	public function testStateSaving() {
		$this->_loadController();
		$configName = 'debug_kit';
		$this->Controller->Toolbar->cacheKey = 'toolbar_history';

		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->Controller->set('test', 'testing');
		$this->Controller->Components->trigger('beforeRender', array($this->Controller));

		$result = Cache::read('toolbar_history', $configName);
		$this->assertEquals($result[0]['variables']['content']['test'], 'testing');
		Cache::delete('toolbar_history', $configName);
	}

/**
 * Test Before Render callback
 *
 * @return void
 */
	public function testBeforeRender() {
		$this->_loadController(array(
			'panels' => array('timer', 'session'),
		));
		$MockPanel = $this->getMock('DebugPanel');
		$MockPanel->expects($this->once())->method('beforeRender');
		$this->Controller->Toolbar->panels['timer'] = $MockPanel;
		$this->Controller->Toolbar->beforeRender($this->Controller);

		$this->assertTrue(isset($this->Controller->helpers['DebugKit.Toolbar']));
		$this->assertEquals($this->Controller->helpers['DebugKit.Toolbar']['output'], 'DebugKit.HtmlToolbar');
		$this->assertEquals($this->Controller->helpers['DebugKit.Toolbar']['cacheConfig'], 'debug_kit');
		$this->assertTrue(isset($this->Controller->helpers['DebugKit.Toolbar']['cacheKey']));

		$this->assertTrue(isset($this->Controller->viewVars['debugToolbarPanels']));
		$vars = $this->Controller->viewVars['debugToolbarPanels'];

		$expected = array(
			'plugin' => 'DebugKit',
			'elementName' => 'session_panel',
			'content' => $this->Controller->Toolbar->Session->read(),
			'disableTimer' => true,
			'title' => ''
		);
		$this->assertEquals($expected, $vars['session']);

		$memory = DebugMemory::getAll();
		$this->assertTrue(isset($memory['Controller render start']));
	}

/**
 * test that vars are gathered and state is saved on beforeRedirect
 *
 * @return void
 */
	public function testBeforeRedirect() {
		$this->_loadController(array(
			'panels' => array('session', 'history'),
		));

		$configName = 'debug_kit';
		$this->Controller->Toolbar->cacheKey = 'toolbar_history';
		Cache::delete('toolbar_history', $configName);

		DebugTimer::start('controllerAction', 'testing beforeRedirect');
		$MockPanel = $this->getMock('DebugPanel');
		$MockPanel->expects($this->once())->method('beforeRender');
		$this->Controller->Toolbar->panels['session'] = $MockPanel;
		$this->Controller->Toolbar->beforeRedirect($this->Controller, '/another/url');

		$result = Cache::read('toolbar_history', $configName);
		$this->assertTrue(isset($result[0]['session']));
		$this->assertFalse(isset($result[0]['history']));

		$timers = DebugTimer::getAll();
		$this->assertTrue(isset($timers['controllerAction']));
	}

/**
 * test that loading state (accessing cache) works.
 *
 * @return void
 */
	public function testLoadState() {
		$this->_loadController();
		$this->Controller->Toolbar->cacheKey = 'toolbar_history';

		$data = array(0 => array('my data'));
		Cache::write('toolbar_history', $data, 'debug_kit');
		$result = $this->Controller->Toolbar->loadState(0);
		$this->assertEquals($result, $data[0]);
	}

/**
 * Test that history state urls set prefix = null and admin = null so generated urls do not
 * adopt these params.
 *
 * @return void
 */
	public function testHistoryUrlGenerationWithPrefixes() {
		$this->url = '/debugkit_url_with_prefixes_test';
		Router::connect($this->url, array(
			'controller' => 'posts',
			'action' => 'edit',
			'admin' => 1,
			'prefix' => 'admin',
			'plugin' => 'cms',
		));
		$this->_loadController();
		$this->Controller->Toolbar->cacheKey = 'url_test';
		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->Controller->Components->trigger('beforeRender', array($this->Controller));

		$result = $this->Controller->Toolbar->panels['history']->beforeRender($this->Controller);
		$expected = array(
			'plugin' => 'debug_kit',
			'controller' => 'toolbar_access',
			'action' => 'history_state',
			0 => 1,
			'admin' => false,
		);
		$this->assertEquals($result[0]['url'], $expected);
		Cache::delete('url_test', 'debug_kit');
	}

/**
 * Test that the FireCake toolbar is used on AJAX requests
 *
 * @return void
 */
	public function testAjaxToolbar() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->_loadController();
		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->Controller->Components->trigger('beforeRender', array($this->Controller));
		$this->assertEquals($this->Controller->helpers['DebugKit.Toolbar']['output'], 'DebugKit.FirePhpToolbar');
	}

/**
 * Test that the toolbar does not interfere with requestAction
 *
 * @return void
 */
	public function testNoRequestActionInterference() {
		Router::reload();
		$this->_loadController();

		$result = $this->Controller->requestAction('/debug_kit_test/request_action_return', array('return'));
		$this->assertEquals($result, 'I am some value from requestAction.');

		$result = $this->Controller->requestAction('/debug_kit_test/request_action_render', array('return'));
		$this->assertEquals($result, 'I have been rendered.');
	}
}