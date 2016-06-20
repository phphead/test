<?php namespace Rj\Controller;

use Phalcon\Mvc\Dispatcher;
use Rj\Cache, Rj\FileInfo, Rj\Assert;

trait MediaControllerTrait {

	protected function _getMediaDir() {
		return DOCROOT . '/../app/media/';
	}

	public function getPackage($query) {
		if (preg_match('#^/?([a-zA-Z0-9_-]+)/(.*)$#iD', $query, $pock)) {
			$extList = [ '.zip', '.tar', '.gz' ];
			foreach ($extList as $ext)
				if (file_exists($pkgFileName = $this->_getMediaDir() . $pock[1] . $ext)) {
					return realpath($pkgFileName);
				}
		}
	}

	public function indexAction() {
		$this->view->noRender();
		Assert::found($query = $this->dispatcher->getParam('query'));
		Assert::found($pkg = $this->getPackage($query));

		$data = Cache::get('media-' . sha1($query), null, function() use ($pkg, $query) {
			Assert::found($f = fopen('zip://' . $pkg . '#' . $query, 'r'));
			$content = '';
			while ( ! feof($f)) $content .= fread($f, 8 * 1024);
			fclose($f);

			$mime = null;
			if ($ext = strtolower(pathinfo($query, PATHINFO_EXTENSION)))
				$mime = empty(FileInfo::$types[$ext]) ? null : FileInfo::$types[$ext];

			return [
				'mime'    => $mime,
			    'content' => $content,
			];
		});

		if ( ! empty($data['mime']))
			$this->response->setHeader('Content-Type', $data['mime']);

		return $this->response->setContent($data['content']);
	}

}
