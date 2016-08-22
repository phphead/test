<?php namespace Rj\Controller;

use Phalcon\Mvc\Dispatcher;
use Rj\Cache, Rj\FileInfo, Rj\Assert, Rj\Helper;

trait MediaControllerTrait {

	protected function _getMediaDir() {
		return [ __DIR__ . '/../media/', DOCROOT . '/../app/media/' ];
	}

	public function getPackage($query) {
		if (preg_match('#^/?([a-zA-Z0-9_-]+)/(.*)$#iD', $query, $pock)) {
			$extList = [ '.zip' ];
			foreach ($this->_getMediaDir() as $dir)
				foreach ($extList as $ext)
					if (file_exists($pkgFileName = $dir . $pock[1] . $ext)) {
						return realpath($pkgFileName);
					}
		}
	}

	public function indexAction() {
		$this->view->noRender();
		Assert::found($query = $this->dispatcher->getParam('query'));
		Assert::found($pkg = $this->getPackage($query));

		$dst = DOCROOT . '/../public/media/';
		@ Helper::mkdir($dst . dirname($query), 0777);

		Assert::found($f = fopen('zip://' . $pkg . '#' . $query, 'r'));
		Assert::true ($d = fopen($dst . $query, 'w'));
		$content = '';
		while ( ! feof($f)) {
			$content .= ($data = fread($f, 8 * 1024));
			fwrite($d, $data);
		}
		fclose($f);

		$mime = null;
		if ($ext = strtolower(pathinfo($query, PATHINFO_EXTENSION)))
			$mime = empty(FileInfo::$types[$ext]) ? null : FileInfo::$types[$ext];

		if ($mime)
			$this->response->setHeader('Content-Type', $mime);

		return $this->response->setContent($content);
	}

}
