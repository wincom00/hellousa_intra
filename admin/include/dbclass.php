<?php
class db {
    protected $connection;
    protected $query;
    protected $show_errors = true;
    protected $query_closed = true;
    public $query_count = 0;

    /**
     * @param string $dbhost        DB host (예: 74.208.178.245)
     * @param string $dbuser
     * @param string $dbpass
     * @param string $dbname
     * @param int    $port          (기본 3306)
     * @param string $charset       (기본 utf8mb4)
     * @param int    $connectTO     연결 타임아웃(초)
     * @param int    $retries       재시도 횟수
     */
    public function __construct(
        $dbhost='localhost',
        $dbuser='root',
        $dbpass='',
        $dbname='',
        $port=3306,
        $charset='utf8mb4',
        $connectTO=5,
        $retries=2
    ) {
        mysqli_report(MYSQLI_REPORT_OFF);
        $this->connection = mysqli_init();

        // 타임아웃 옵션
        @mysqli_options($this->connection, MYSQLI_OPT_CONNECT_TIMEOUT, (int)$connectTO);
        if (defined('MYSQLI_OPT_READ_TIMEOUT')) {
            @mysqli_options($this->connection, MYSQLI_OPT_READ_TIMEOUT, (int)$connectTO);
        }

        // 지수 백오프 재시도
        $delayUs = 250000; // 0.25초
        for ($i=0; $i <= $retries; $i++) {
            if (@mysqli_real_connect($this->connection, $dbhost, $dbuser, $dbpass, $dbname, (int)$port, null, 0)) {
                break;
            }
            if ($i === $retries) {
                $this->error('Failed to connect to MySQL ('.mysqli_connect_errno().') - '.mysqli_connect_error());
            }
            usleep($delayUs);
            $delayUs *= 2; // 0.25s → 0.5s → 1.0s …
        }

        if (!@mysqli_set_charset($this->connection, $charset)) {
            $this->error('Failed to set charset - '.mysqli_error($this->connection));
        }
    }

    private function ensureConnection() {
        if (!$this->connection || !@mysqli_ping($this->connection)) {
            $this->error('MySQL connection lost');
        }
    }

    public function query($query) {
        $this->ensureConnection();

        if (!$this->query_closed && $this->query instanceof mysqli_stmt) {
            $this->query->close();
        }

        $this->query = $this->connection->prepare($query);
        if (!$this->query) {
            $this->error('Unable to prepare MySQL statement - '.$this->connection->error);
        }

        if (func_num_args() > 1) {
            $x = func_get_args();
            $args = array_slice($x, 1);
            $types = '';
            $args_ref = array();

            foreach ($args as $k => &$arg) {
                if (is_array($arg)) {
                    foreach ($arg as $j => &$a) {
                        $types .= $this->_gettype($a);
                        $args_ref[] = &$a;
                    }
                } else {
                    $types .= $this->_gettype($arg);
                    $args_ref[] = &$arg;
                }
            }
            array_unshift($args_ref, $types);
            call_user_func_array(array($this->query, 'bind_param'), $args_ref);
        }

        $this->query->execute();
        if ($this->query->errno) {
            $this->error('Unable to process MySQL query - '.$this->query->error);
        }
        $this->query_closed = false;
        $this->query_count++;
        return $this;
    }

    public function fetchAll($callback = null) {
        $params = array();
        $row = array();
        $meta = $this->query->result_metadata();
        while ($field = $meta->fetch_field()) {
            $params[] = &$row[$field->name];
        }
        call_user_func_array(array($this->query, 'bind_result'), $params);
        $result = array();

        while ($this->query->fetch()) {
            $r = array();
            foreach ($row as $key => $val) $r[$key] = $val;
            if ($callback && is_callable($callback)) {
                $value = call_user_func($callback, $r);
                if ($value === 'break') break;
            } else {
                $result[] = $r;
            }
        }
        $this->query->close();
        $this->query_closed = true;
        return $result;
    }

    public function fetchArray() {
        $params = array();
        $row = array();
        $meta = $this->query->result_metadata();
        while ($field = $meta->fetch_field()) $params[] = &$row[$field->name];
        call_user_func_array(array($this->query, 'bind_result'), $params);
        $result = array();
        while ($this->query->fetch()) {
            foreach ($row as $key => $val) $result[$key] = $val;
        }
        $this->query->close();
        $this->query_closed = true;
        return $result;
    }

    public function close()  { return $this->connection->close(); }
    public function numRows(){ $this->query->store_result(); return $this->query->num_rows; }
    public function affectedRows(){ return $this->query->affected_rows; }
    public function lastInsertID(){ return $this->connection->insert_id; }

    public function error($error) {
        if ($this->show_errors) exit($error);
        // 아니면 throw new \RuntimeException($error);
    }

    private function _gettype($var) {
        if (is_string($var)) return 's';
        if (is_float($var))  return 'd';
        if (is_int($var))    return 'i';
        return 'b';
    }
}
