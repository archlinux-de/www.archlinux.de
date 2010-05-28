<?php
/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/
class DB extends Modul {

private $link = null;


public function connect($host, $user, $password, $database)
	{
	$this->link = mysqli_connect($host, $user, $password, $database);

	if (!$this->link)
		{
		throw new DBConnectException();
		}
	}

public function __destruct()
	{
	if (isset($this->link))
		{
		$this->close();
		}
	}

public function close()
	{
	@mysqli_close($this->link);
	unset($this->link);
	}

public function getInsertId()
	{
	$id = mysqli_insert_id($this->link);

	if ($id == 0)
		{
		throw new DBException($this->link);
		}

	return $id;
	}

public function prepare($query)
	{
	if (!$stm = mysqli_prepare($this->link, $query))
		{
		throw new DBException($this->link, $query);
		}

	return new DBStatement($stm, $this->link);
	}

public function execute($query)
	{
	$result = mysqli_query($this->link, $query);

	if (!$result)
		{
		throw new DBException($this->link);
		}

	if (mysqli_warning_count($this->link))
		{
		throw new DBWarningException($this->link);
		}

// 	if (mysqli_affected_rows($this->link) <= 0)
// 		{
// 		throw new DBNoDataException();
// 		}
	}

private function query($query)
	{
	$result = mysqli_query($this->link, $query, MYSQLI_STORE_RESULT);

	if (!$result)
		{
		throw new DBException($this->link);
		}

	if (mysqli_warning_count($this->link))
		{
		throw new DBWarningException($this->link);
		}

	if (mysqli_num_rows($result) == 0)
		{
		throw new DBNoDataException();
		}

	return $result;
	}

public function getRowSet($query)
	{
	$result = $this->query($query);
	return new DBResult($result);
	}

public function getRow($query)
	{
	$result = $this->query($query);
	if ($row = mysqli_fetch_assoc($result))
		{
		mysqli_free_result($result);
		return $row;
		}
	else
		{
		throw new DBNoDataException($this->link);
		}
	}

public function getColumn($query)
	{
	$result = $this->query($query);
	if ($row = mysqli_fetch_array($result, MYSQLI_NUM))
		{
		mysqli_free_result($result);
		return $row[0];
		}
	else
		{
		throw new DBNoDataException($this->link);
		}
	}

public function getColumnSet($query)
	{
	$result = $this->query($query);
	$columns = array();
	if ($row = mysqli_fetch_array($result, MYSQLI_NUM))
		{
		$columns[] = $row[0];
		while ($row = mysqli_fetch_array($result, MYSQLI_NUM))
			{
			$columns[] = $row[0];
			}

		mysqli_free_result($result);
		return $columns;
		}
	else
		{
		throw new DBNoDataException($this->link);
		}
	}

public function getAffectedRows()
	{
	return mysqli_affected_rows($this->link);
	}

}

// ------------------------------------------------------------------------------------------------------

class DBException extends RuntimeException {

function __construct($link)
	{
	parent::__construct(mysqli_error($link), mysqli_errno($link));
	}
}

class DBNoDataException extends DBException{

function __construct()
	{
	RuntimeException::__construct('', 1);
	}
}

class DBStatementException extends DBException {

function __construct($link)
	{
	RuntimeException::__construct(mysqli_stmt_error($link), mysqli_stmt_errno($link));
	}
}

class DBConnectException extends DBException {

function __construct()
	{
	RuntimeException::__construct(mysqli_connect_error(), mysqli_connect_errno());
	}
}

class DBWarningException extends DBException {

function __construct($link)
	{
	$code = 0;
	$error = '';

	if ($result = mysqli_query($link, 'SHOW WARNINGS'))
		{
		$row = mysqli_fetch_row($result);
		$code = $row[1];
		$error = $row[0].' : '.$row[2];
		mysqli_free_result($result);
		}

	RuntimeException::__construct($error, $code);
	}
}
// ------------------------------------------------------------------------------------------------------

abstract class ADBResult implements Iterator{

public function toArray()
	{
	$array = array();

	foreach ($this as $key => $value)
		{
		if (is_array($value))
			{
			$row = array();
			foreach ($value as $rowKey => $rowValue)
				{
				$row[$rowKey] = $rowValue;
				}
			$array[$key] = $row;
			}
		else
			{
			$array[$key] = $value;
			}
		}

	return $array;
	}

}

class DBResult extends ADBResult{

private $result		= null;
# null on failure
private $row 		= null;
# 0 on failure
private $current 	= 0;

public function __construct($result)
	{
	$this->result = $result;
	$this->next();
	}

public function __destruct()
	{
	$this->row = null;
	$this->current = 0;
	mysqli_free_result($this->result);
	$this->result = null;
	}

public function current()
	{
	return $this->row;
	}

public function key()
	{
	return $this->current;
	}

public function next()
	{
	$this->row = mysqli_fetch_assoc($this->result);
	if ($this->valid())
		{
		$this->current++;
		}
	}

public function rewind()
	{
	if ($this->current > 1)
		{
		mysqli_data_seek($this->result, 0);
		$this->current = 0;
		$this->next();
		}
	}

public function valid()
	{
	return !is_null($this->row);
	}

public function getNumRows()
	{
	return mysqli_num_rows($this->result);
	}

}


// ------------------------------------------------------------------------------------------------------


class DBStatement{

private $link 		= null;
private $stm 		= null;
private $bindings 	= array();
private $types 		= '';

public function __construct($stm, $link)
	{
	$this->stm = $stm;
	$this->link = $link;
	}

public function close()
	{
	mysqli_stmt_close($this->stm);
	unset($this->stm);
	}

public function bindString($value)
	{
	$this->bindings[] = $value;
	$this->types .= 's';
	}

public function bindDouble($value)
	{
	$this->bindings[] = $value;
	$this->types .= 'd';
	}

public function bindInteger($value)
	{
	$this->bindings[] = $value;
	$this->types .= 'i';
	}

public function bindBinary($value)
	{
	$this->bindings[] = $value;
	$this->types .= 'b';
	}

private function bindParams($types, $values)
	{
	$params = array_merge(array($this->stm, $types), $values);

	# create a new array with references to the old
	# FIXME: ugly workaround
	# see comments at http://de2.php.net/manual/en/function.call-user-func-array.php
	$args = array();
	foreach($params as &$arg)
		{
		$args[] = &$arg;
		}

	if (!call_user_func_array('mysqli_stmt_bind_param', $args))
		{
		throw new DBStatementException($this->stm);
		}
	}

private function resetBindings()
	{
	$this->bindings = array();
	$this->types = '';
	}

private function executeStatement()
	{
	if (!empty($this->types))
		{
		$this->bindParams($this->types, $this->bindings);
		$this->resetBindings();
		}

	if (!mysqli_stmt_execute($this->stm))
		{
		throw new DBStatementException($this->stm);
		}

	if (mysqli_warning_count($this->link))
		{
		throw new DBWarningException($this->link);
		}

	if (!mysqli_stmt_store_result($this->stm))
		{
		throw new DBStatementException($this->stm);
		}

	if (mysqli_stmt_num_rows($this->stm) == 0)
		{
		throw new DBNoDataException();
		}
	}

public function execute()
	{
	if (!empty($this->types))
		{
		$this->bindParams($this->types, $this->bindings);
		$this->resetBindings();
		}

	if (!mysqli_stmt_execute($this->stm))
		{
		throw new DBStatementException($this->stm);
		}

	if (mysqli_warning_count($this->link))
		{
		throw new DBWarningException($this->link);
		}

// 	if (mysqli_stmt_affected_rows($this->stm) <= 0)
// 		{
// 		throw new DBNoDataException();
// 		}
	}

private function bindResult()
	{
	if (!$data = mysqli_stmt_result_metadata($this->stm))
		{
		throw new DBStatementException($this->stm);
		}

	$params[] = &$this->stm;
	$row = null;

	while ($field = mysqli_fetch_field($data))
		{
		$params[] = &$row[$field->name];
		}

	call_user_func_array('mysqli_stmt_bind_result', $params);

	return $row;
	}

public function getRowSet()
	{
	$this->executeStatement();
	$row = $this->bindResult();
	return new DBStatementResult($this->stm, $row);
	}

public function getRow()
	{
	$this->executeStatement();
	$row = $this->bindResult();

	$result = mysqli_stmt_fetch($this->stm);

	if ($result == true)
		{
		return $row;
		}
	elseif($result == null)
		{
		throw new DBNoDataException();
		}
	else
		{
		throw new DBStatementException($this->stm);
		}
	}

public function getColumnSet()
	{
	$this->executeStatement();
	$column = null;
	mysqli_stmt_bind_result($this->stm, $column);
	return new DBStatementResult($this->stm, $column);
	}

public function getColumn()
	{
	$this->executeStatement();
	$column = null;
	mysqli_stmt_bind_result($this->stm, $column);
	$result = mysqli_stmt_fetch($this->stm);

	if ($result == true)
		{
		return $column;
		}
	elseif($result == null)
		{
		throw new DBNoDataException();
		}
	else
		{
		throw new DBStatementException($this->stm);
		}
	}

public function getNumRows()
	{
	return mysqli_stmt_num_rows($this->stm);
	}

public function getAffectedRows()
	{
	return mysqli_stmt_affected_rows($this->stm);
	}

}

// ------------------------------------------------------------------------------------------------------

class DBStatementResult extends ADBResult{

private $stm 		= null;
private $row 		= null;
# 0 on failure
private $current 	= 0;
# false on failure; null when empty
private $state		= null;

public function __construct($stm, &$row)
	{
	$this->stm = $stm;
	$this->row = &$row;
	$this->next();
	}

public function __destruct()
	{
	$this->row = null;
// 	mysqli_stmt_free_result($this->stm);
	$this->stm = null;
	$this->current = 0;
	$this->state = null;
	}

public function current()
	{
	return $this->row;
	}

public function key()
	{
	return $this->current;
	}

public function next()
	{
	$this->state = mysqli_stmt_fetch($this->stm);
	if ($this->valid())
		{
		$this->current++;
		}
	elseif ($this->state === false)
		{
		throw new DBStatementException($this->stm);
		}
	}

public function rewind()
	{
	if ($this->current > 1)
		{
		mysqli_stmt_data_seek($this->result, 0);
		$this->current = 0;
		$this->next();
		}
	}

public function valid()
	{
	return !is_null($this->state) && $this->state;
	}

}

?>
