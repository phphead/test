<?php namespace Rj;

use Rj\Migration\Table,
	Exception,
	Phalcon\DI;

class Migration {

	public static function test() {
		if (file_exists(self::_getTableDataFileName())) {
			Assert::true(is_writeable(self::_getTableDataFileName()));

		} else {
			Assert::true(is_writeable(dirname(self::_getTableDataFileName())));
		}
	}

	protected static function _dir() {
		return DOCROOT . 'migrations/';
	}

	protected static function _getTableDataFileName() {
		return DOCROOT . '../.tableData';
	}

	protected static function _getNewMigrationFileName() {
		$fileName = 'migration_%s.php';
		$i        = date('Y-m-d_His');

		return sprintf($fileName, $i);
	}

	protected static function _readMeta() {
		list ($a1, $a2) = @ unserialize(file_get_contents(static::_getTableDataFileName()));
		return [ $a1 ?: [], $a2 ?: [] ];
	}

	protected static function _writeMeta($tableData, $migrations) {
		file_put_contents(static::_getTableDataFileName(), serialize([ $tableData, $migrations ]));
	}

	public static function dump() {
		self::test();

		list ($td, $mg) = static::_readMeta();
		static::_writeMeta(Table::getList(), $mg);
	}

	public static function gen() {
		self::test();

		list ($old, $mg) = static::_readMeta();

		if ( ! is_array($old))
			$old = [];

		$new = Table::getList();

		$migration = '';

		foreach ($old as $table) {
			if (empty($new[$table->name])) {
				$migration .= "DROP TABLE `{$table->name}`;\n";
			}
		}

		foreach ($new as $table) {
			if (empty($old[$table->name])) {
				$migration .= $table->getCreateTable() . "\n";

			} else {
				$migration .= $table->diff($old[$table->name]);
			}
		}

		if ( ! trim($migration)) {
			echo "Nothing to do.\n";
			return;
		}

		$fileName = static::_getNewMigrationFileName();
		file_put_contents(static::_dir() . $fileName, "<?php\n\n\$db->execute(\"\n\t" . str_replace("\n", "\n\t", $migration) . "\n\");");

		echo $migration . "\n";
		echo "Created file $fileName\n";

		$mg[] = $fileName;
		static::_writeMeta($new, $mg);
	}

	public static function run() {
		self::test();

		list ($td, $mg) = static::_readMeta();
		$dir = static::_dir();

		$db = DI::getDefault()->getShared('db');

		if ($handle = opendir($dir)) {
			while (false !== ($entry = readdir($handle))) {
				if ( ! is_dir($dir . $entry)) {
					try {
						if (false !== array_search($entry, $mg)) continue;

						echo "Executing $entry...\n";

						include "{$dir}$entry";

						$mg[] = $entry;
						static::_writeMeta($td, $mg);

					} catch (Exception $e) {
						throw $e;
					}
				}
			}
		}
	}

}
