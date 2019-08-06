<?php

namespace Tablebuilder;

/**
 * Class for making basic data editors.
 */
class TableBuilder
{
    /**
     * Buttons list.
     * @var array 
     */
    private $buttons;
    
    /**
     * List of a table's columns.
     * @var array 
     */
    private $columns = [];
    
    /**
     * Table class name.
     * @var string 
     */
    private $class;
    
    /**
     * An instance of Database class.
     * @var Database 
     */
    private $db;
    
    /**
     * MySQL resultin array with a table's data.
     * @var array 
     */
    private $data;
    
    /**
     * Indent is used for adding additional spaces.
     * @var int 
     */
    private $indent;
    
    /**
     * Button names Russian.
     * @var array 
     */
    private $ru = ['Добавить', 'Изменить', 'Удалить'];
    
    /**
     * Button names English.
     * @var array 
     */
    private $en = ['Add', 'Modify', 'Delete'];
    
    /**
     * Sort columns data.
     * @var string 
     */
    private $sort;
    
    /**
     * Table name.
     * @var string 
     */
    private $table;
    
    /**
     * Table headers list.
     * @var array 
     */
    private $titles;
    
    /**
     * Talbe type of viriables
     * @var array 
     */
    private $types = [];
    
    public function __construct(string $table, string $class = '', string $lang = 'ru') 
    {
        $this->db = Database::db();
        $this->table = $table;
        $this->buttons = $this->$lang;
        $this->class = $class;
        $this->setParams();
        $this->sort = $this->sort();
    }
    
    /**
     * Builds resulting form.
     * @param bool $addFirst If any parameter, add goes in the beginning.
     * @return string
     */
    public function build(bool $addFirst = false): string
    {
        $this->formProcess();
        $this->getData();
        $result = '';
        $result .= $this->makeTitleRow();
        if ($addFirst) {
            $result .= $this->makeAdd();
        }
        $result .= $this->makeRows();
        if (!$addFirst) {
            $result .= $this->makeAdd();
        }
        return $this->table($result);
    }
    
    /**
     * Sets new column list.
     * @param array $columns
     * @return void
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }
    
    /**
     * Sets new indent.
     * @param int $indent
     * @return void
     */
    public function setIndent(int $indent): void
    {
        $this->indent = $indent;
    }
    
    /**
     * Sets new titles for columns.
     * @param array $titles
     * @return void
     */
    public function setTitles(array $titles): void
    {
        $this->titles = $titles;
    }
    
    /**
     * Sets new HTML-input types.
     * @param array $types
     * @return void
     */
    public function setTypes(array $types): void
    {
        $this->types = $types;
    }
    
    /**
     * Service function for setting initial parameters. 
     * $columns, $titles and $types.
     * @return void
     */
    private function setParams(): void
    {
        $db_params = $this->db
                ->table($this->table)
                ->describe()
                ->fetch();
        foreach ($db_params as $par) {
            if ($par['Field'] != 'id') {
                $this->columns[] = $par['Field'];
            }
        }
        $this->titles = $this->columns;
        foreach ($db_params as $t) {
            if ($t['Field'] != 'id') {
                if (!stristr($t['Field'], 'pass')) {
                    if (
                            stristr($t['Type'], 'int') ||
                            stristr($t['Type'], 'decimal') ||
                            stristr($t['Type'], 'float') ||
                            stristr($t['Type'], 'double')
                    ) {
                        if (stristr($t['Type'], 'int(10)')) {
                            $this->types[] = 'date';
                            continue;
                        } else {
                            $this->types[] = 'number';
                            continue;
                        }
                    } elseif (
                            stristr($t['Type'], 'varchar') ||
                            stristr($t['Type'], 'text')
                    ) {
                        $this->types[] = 'text';
                        continue;
                    } elseif (stristr($t['Type'], 'time')) {
                        $this->types[] = 'date';
                        continue;
                    } else {
                        $this->types[] = 'text';
                        continue;
                    }
                } else {
                    $this->types[] = 'password';
                    continue;
                }
            }
        }
    }
    
    /**
     * MySQL request. Writes new data to $this->data.
     * @return bool
     */
    private function getData(): bool
    {
        $this->data = $this->db
                ->table($this->table)
                ->select('id,' . implode(', ',$this->columns))
                ->order($this->sort['order'], $this->sort['desc_get'])
                ->fetch();
        return isset($this->data[0]) ? true : false;
    }
    
    /**
     * Service function for adding spaces to html.
     * @param int $number
     * @return string
     */
    private function blanks(int $number): string
    {
        return str_repeat(' ', $number + $this->indent);
    }
    
    /**
     * Displays sorting arrows.
     * @param string $name Sorting name.
     * @param int $desc 1 - Descending sorting, 0 - Ascending sorting.
     * @return mixed
     */
    private function getSortArrows(string $name, int $desc)
    {
        if (isset($_GET['order'])) {
            if ($_GET['order'] === $name) {
                return $desc === 1 ? '▲' : '▼';
            } else {
                return '';
            }
        }
    }
    
    /**
     * Wraps something to an html form with post method.
     * @param string $data
     * @return string
     */
    private function form(string $data): string
    {
        $result = $this->blanks(4) . '<form action="" method="post">' . PHP_EOL;
        $result .= $data;
        $result .= $this->blanks(4) . '</form>' . PHP_EOL;
        return $result;
    }
    
    /**
     * Processes form.
     */
    private function formProcess()
    {
        extract($_POST);
        if (isset($add) || isset($modify)) {
            $cols = implode(', ', $this->columns);
            $values = [];
            for ($i = 0; $i < count($this->columns); $i++) {
                $varName = $this->columns[$i];
                if ($this->types[$i] == 'date') {
                    $values[] = strtotime($$varName);
                } elseif($this->types[$i] == 'password') {
                    $values[] = md5($$varName);
                } else {
                    $values[] = $$varName;
                }
            }
            if (isset($add)) {
                $this->db
                        ->table($this->table)
                        ->insert($cols, $values)
                        ->fetch();
            } elseif (isset($modify)) {
                $this->db
                        ->table($this->table)
                        ->update($cols, $values)
                        ->where('id', $id)
                        ->fetch();
            }
        } elseif (isset($delete)) {
            $this->db
                    ->table($this->table)
                    ->delete()
                    ->where('id', $id)
                    ->fetch();
        } 
    }
    
    /**
     * Bulds an html input.
     * @param string $type
     * @param string $name
     * @param string $value
     * @return string
     */
    private function input(string $type, string $name, string $value = ''): string
    {
        return "<input type=\"$type\" name=\"$name\" value=\"$value\">";
    }
    
    /**
     * Add a row with adding new data.
     * @return string
     */
    private function makeAdd(): string
    {
        $res = '';
        for ($i = 0; $i < count($this->columns); $i++) {
            $res .= $this->td($this->input($this->types[$i], $this->columns[$i]));
        }
        $res .= $this->td($this->input('submit', 'add', $this->buttons[0]));
        return $this->form($this->tr($res));
    }
    
    /**
     * Adds a row with a table's header.
     * @return string
     */
    private function makeTitleRow(): string
    {
        $result = '';
        for ($i = 0; $i < count($this->columns); $i++) {
            $result .= $this->th('<a href="?order='.$this->columns[$i].
                    '&desc='.$this->sort['desc'].'">' . $this->titles[$i] . 
                    $this->getSortArrows($this->columns[$i], $this->sort['desc']).
                    '</a>');
        }
        return $this->tr($result);
    }
    
    /**
     * Makes table data rows.
     * @return string
     */
    private function makeRows(): string
    {
       $result = '';
        foreach ($this->data as $data) {
            $res = $this->blanks(8) . 
                    $this->input('hidden', 'id', $data['id']) . PHP_EOL;
            $col = 0;
            foreach ($data as $key => $d) {
                if ($key != 'id') {
                    if ($this->types[$col] == 'password') {
                        $d = '';
                    } elseif ($this->types[$col] == 'date') {
                        $d = date('Y-m-d', $d);
                    }
                    $res .= $this->td($this->input($this->types[$col], $key, $d));
                    $col++;
                }
            }
            $res .= $this->td($this->input('submit', 'modify', $this->buttons[1]));
            $res .= $this->td($this->input('submit', 'delete', $this->buttons[2]));
            $result .= $this->form($this->tr($res));
        }
        return $result;
    }
    
    /**
     * Returns information about order and order direction.
     * @return array
     */
    private function sort(): array
    {
        $desc = 1;
        $desc_get = '';
        if (isset($_GET['desc']) && isset($_GET['order'])) {
            if ($_GET['desc'] == 1) {
                $desc = 0;
                $desc_get = 'DESC';
            }
            $order = $_GET['order'];
        } else {
            $order = $this->columns[0];
        }
        return ['order' => $order, 'desc_get' => $desc_get, 'desc' => $desc];
    }
    
    /**
     * Wraps data to an html table.
     * @param string $data
     * @return string
     */
    private function table(string $data): string
    {
        $result = $this->blanks(0) . '<table class="'.$this->class.'">' . PHP_EOL;
        $result .= $data;
        $result .= $this->blanks(0) . '</table>' . PHP_EOL;
        return $result;
    }
    
    /**
     * Wraps data to an html td.
     * @param string $data
     * @return string
     */
    private function td(string $data): string
    {
        return $this->wrap('td', 8, $this->blanks(12) . $data . PHP_EOL);
    }
    
    /**
     * Wraps data to an html th.
     * @param string $data
     * @return string
     */
    private function th(string $data): string
    {
        return $this->wrap('th', 8, $this->blanks(12) . $data . PHP_EOL);
    }
    
    /**
     * Wraps data to an html tr.
     * @param string $data
     * @return string
     */
    private function tr(string $data): string
    {
        return $this->wrap('tr', 4, $data);
    }
    
    /**
     * Wraps data to a certain html tag.
     * @param string $data
     * @return string
     */
    private function wrap(string $wrapper, int $level, string $data): string
    {
        $result = $this->blanks($level) . '<'.$wrapper.'>' . PHP_EOL;
        $result .= $data;
        $result .= $this->blanks($level) . '</'.$wrapper.'>' . PHP_EOL;
        return $result;
    }
}