<?php
/**
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Debugkit\Model\Table;

use Cake\DebugKit\Model\Table\LazyTableTrait;
use Cake\ORM\Table;

/**
 * The requests table tracks basic information about each request.
 */
class RequestsTable extends Table {

	use LazyTableTrait;

/**
 * initialize method
 *
 * @param array $config Config data.
 * @return void
 */
	public function initialize(array $config) {
		$this->hasMany('DebugKit.Panels');
		$this->addBehavior('Timestamp', [
			'events' => [
				'Model.beforeSave' => ['requested_at' => 'new']
			]
		]);
		$this->ensureTables(['DebugKit.Panel', 'DebugKit.Request']);
	}

/**
 * DebugKit tables are special.
 *
 * @return string
 */
	public static function defaultConnectionName() {
		return 'debug_kit';
	}
}