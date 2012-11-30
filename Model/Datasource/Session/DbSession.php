<?php

App::uses('DatabaseSession', 'Model/Datasource/Session');
App::uses('CakeSessionHandlerInterface', 'Model/Datasource/Session');
App::uses('AuthComponent', 'Controller/Component');

class DbSession extends DatabaseSession implements CakeSessionHandlerInterface {

	public function __construct() {
		parent::__construct();
		$config = $this->_configure();
		$this->UserSession = ClassRegistry::init('DbSession.UserSession');
		if (!empty($config['datasource'])) {
			$this->UserSession->setDataSource($config['datasource']);
		}
	}

	protected function _configure() {
		$config = Hash::merge(array(
			'timeout' => 600,
			'datasource' => 'default',
			), Configure::read('Session')
		);
		$this->_timeout = $config['timeout'];
		$this->_userMap = $config['handler']['userMap'];
		return $config;
	}

	public function write($id, $data) {
		$stored = parent::write($id, $data);
		if ($this->_userMap) {
			$saved = $this->_storeUserMap($id, $data);
		}
		return $stored;
	}

	public function destroy($id) {
		parent::destroy($id);
		$this->_removeUserMap($id);
	}

	public function gc($expires = null) {
		if (!$expires) {
			$expires = time();
		} else {
			$expires = time() - $expires;
		}
		$expired = $this->_model->find('list', array(
			'conditions' => array(
				$this->_model->alias . '.expires <' => $expires,
			)
		));
		foreach ($expired as $session) {
			$this->_removeUserMap($session);
		}
		parent::gc();
	}

	protected function _storeUserMap($id, $data) {
		$decoded = wddx_deserialize($data);
		if (!is_array($decoded)) {
			return false;
		}
		$uid = Hash::get($decoded, AuthComponent::$sessionKey . '.id');
		if (empty($uid)) {
			return;
		}
		$this->UserSession->create(array(
			'id' => $uid,
			'cake_session_id' => $id,
		));
		return $this->UserSession->save();
	}

	protected function _removeUserMap($id) {
		$this->UserSession->deleteAll(array(
			'UserSession.cake_session_id' => $id,
		), false, false);
	}

}
