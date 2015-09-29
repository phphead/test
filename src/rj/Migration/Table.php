<?php namespace Rj\Migration;

use Phalcon\DI;

class TableColumn {

	public $tableName, $Field, $Type, $Null, $Default, $Extra;

	public function getQueryAdd() {
		return "ALTER TABLE `{$this->tableName}` ADD `{$this->Field}` {$this->Type} "
			. ($this->Null ? "NULL" : "NOT NULL")
			. ($this->Extra ? " " . $this->Extra : "")
			. ";";
	}

	public function getQueryChange() {
		return "ALTER TABLE `{$this->tableName}` CHANGE `{$this->Field}` `{$this->Field}` {$this->Type} "
		. ($this->Null ? "NULL" : "NOT NULL")
		. ($this->Extra ? " " . $this->Extra : "")
		. ";";
	}

}

class TableKey {

	public $Non_unique, $Key_name, $Seq_in_index, $Column_name, $Index_type;

}

class TableKeyGroup {

	public $tableName, $keys = [], $Non_unique, $Index_type, $Key_name, $primary;

	public function getQueryDrop() {
		foreach ($this->keys as $k => $key) {
			return "ALTER TABLE `{$this->tableName}` DROP " . ($key->Key_name == 'PRIMARY' ? 'PRIMARY KEY' : "KEY `{$key->Key_name}`") . ";";
		}
	}

	public function init() {
		$key = $this->keys[0];

		$this->Key_name   = $key->Key_name;
		$this->Index_type = $key->Index_type;
		$this->primary    = $this->Key_name == 'PRIMARY';
		$this->Non_unique = $key->Non_unique;
	}

	public function getQueryAdd() {
		$ret    = "ALTER TABLE `{$this->tableName}` ADD ";
		$fields = [];
		$type   = '';
		$pri    = false;
		$name   = '';
		foreach ($this->keys as $k => $key) {
			if ($k === 0) {
				$ret .= $key->Key_name == 'PRIMARY' ? 'PRIMARY ' : ($key->Non_unique ? 'KEY ' : 'UNIQUE ');
				$type = $key->Index_type;
				$pri  = $key->Key_name == 'PRIMARY';
				$name = $key->Key_name;
			}
			$fields[] = $key->Column_name;
		}
		$ret .= ($pri ? "" : "`$name` ") . "(`" . implode("`, `", $fields) . "`)" . ($pri ? "" : " USING " . $type) . ";";

		return $ret;
	}

}

class Table {

	/** @return Table[] */
	public static function getList() {
		$ret = [];
		$res = DI::getDefault()->getShared('db')->query("show tables")->fetchAll();
		foreach ($res as $row) {
			$ret[$row[0]] = static::read($row[0]);
		}
		return $ret;
	}

	/** @return Table */
	public static function read($tableName) {
		$table = new Table();
		$table->name = $tableName;

		$ret = DI::getDefault()->getShared('db')->query("describe $tableName")->fetchAll();
		foreach ($ret as $row) {
			$column = new TableColumn();
			$column->Field     = $row['Field'];
			$column->Type      = $row['Type'];
			$column->Null      = $row['Null'] === 'YES';
			$column->Default   = $row['Default'];
			$column->Extra     = $row['Extra'];
			$column->tableName = $tableName;

			$table->columns[$column->Field] = $column;
		}

		$ret = DI::getDefault()->getShared('db')->query("show index from $tableName")->fetchAll();
		foreach ($ret as $row) {
			$index = new TableKey();
			$index->Non_unique   = $row['Non_unique'] == '1';
			$index->Key_name     = $row['Key_name'];
			$index->Seq_in_index = $row['Seq_in_index'];
			$index->Column_name  = $row['Column_name'];
			$index->Index_type   = $row['Index_type'];

			$table->keys[] = $index;
		}

		$ret = DI::getDefault()->getShared('db')->query("show table status like ?", [ $tableName ])->fetchArray();
		$table->engine = $ret[1];

		return $table;
	}

	public $name, $columns = [], $keys = [], $engine;

	public function getCreateTable() {
		$cols = [];
		foreach ($this->columns as $column) {
			$cols[] = "`{$column->Field}` {$column->Type} " . ($column->Null ? 'NULL' : 'NOT NULL')
				. ($column->Default ? " DEFAULT " . $column->Default : '')
				. ($column->Extra ? " " . $column->Extra : "");
		}

		$keys = [];
		foreach ($this->getKeys() as $key) {
			$key->init();

			$fields = [];
			foreach ($key->keys as $k)
				$fields[] = $k->Column_name;

			$keys[] = ($key->primary ? "PRIMARY KEY" : ($key->Non_unique ? "KEY" : "UNIQUE"))
				. ($key->primary ? '' : " `{$key->Key_name}`")
				. " (" . implode(", ", $fields) . ")"
				. " USING " . $key->Index_type;
		}

		return "CREATE TABLE `{$this->name}` (\n\t"
			. implode(",\n\t", $cols) . ($keys ? ",\n\t"
			. implode(",\n\t", $keys) : '')
			. "\n)"
			. ($this->engine ? ' ENGINE = ' . $this->engine : '')
			. ";\n";
	}

	public function diff(Table $oldTable) {
		$ret = [];

		$old = $oldTable->columns;
		$new = $this->columns;

		if ($this->engine != $oldTable->engine) {
			$ret[] = "ALTER TABLE `{$this->name}` ENGINE = {$this->engine};";
		}

		foreach ($new as $name => $column) {
			if (isset($old[$name])) {
				$str = $column->getQueryAdd();
				if ($str != $old[$name]->getQueryAdd()) {
					$ret[] = $column->getQueryChange();
				}

			} else if ( ! isset($old[$name])) {
				$ret[] = $column->getQueryAdd();
			}
		}

		$old = $oldTable->getKeys();
		$new = $this->getKeys();

		foreach ($old as $key => $entries) {
			if ( ! isset($new[$key])) {
				$ret[] = "ALTER TABLE `{$this->name}` DROP INDEX `$key`;";
			}
		}

		foreach ($new as $key => $group) {
			if (isset($old[$key])) {
				$str = $group->getQueryAdd();
				if ($str != $old[$key]->getQueryAdd()) {
					$ret[] = $group->getQueryDrop();
					$ret[] = $group->getQueryAdd();
				}

			} else {
				$ret[] = $group->getQueryAdd();
			}
		}

		return implode("\n", $ret);
	}

	public function generate() {
		return serialize($this);
	}

	/** @return TableKeyGroup[] */
	public function getKeys() {
		$ret = [];
		foreach ($this->keys as $entry) {
			if (empty($ret[$entry->Key_name])) {
				$ret[$entry->Key_name] = new TableKeyGroup();
				$ret[$entry->Key_name]->tableName = $this->name;
			}
			$ret[$entry->Key_name]->keys[] = $entry;
		}
		return $ret;
	}

}
