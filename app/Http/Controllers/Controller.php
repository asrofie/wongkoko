<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use PHPHtmlParser\Dom;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function indexAction(Request $request) {
        $file = $request->get('file');
        if (!$file) {
            return response(['status'=>false], 404);
        }

        $filePath = getenv('FOLDER_FILE').DIRECTORY_SEPARATOR.$file;
        if (!file_exists($filePath)) {
            return response(['status'=>false], 404);
        }
        $extensions = explode('.', $file);
        $array = [];
        if (strtolower($extensions[count($extensions)-1]) == 'csv') {
            $row = 1;
            $colums = [];
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $num = count($data);
                    if ($row > 1) {
                        $values = [];
                        foreach($colums as $i => $col) {
                            $val = null;
                            if (isset($data[$i])) {
                                $val = $data[$i];
                            }
                            $values[$col] = $val;
                        }
                        $array[] = $values;
                    }
                    else {
                        for ($c=0; $c < $num; $c++) {
                            $colums[$c] = $data[$c];
                        }
                    }
                    $row++;
                    
                }
                fclose($handle);
            }
        }
        else {
            $string = file_get_contents($filePath);
            $string = preg_replace("/<!--(.|\s)*?-->/", "", $string);
            $dom = new Dom;
            $dom->loadStr($string);
            $tables = $dom->find('table');
            $table = $tables[0];
            $rows = $table->find('tr');
            $column = [];
            $array = [];
            foreach($rows as $index => $row) {
                if ($index == 0) {
                    $cols = $row->find('th');
                    foreach($cols as $i => $col) {
                        $column[$i]= $col->text;
                    }
                }
                else {
                    $cols = $row->find('td');
                    $value = [];
                    foreach($cols as $i => $col) {
                        $val = $col->text;
                        if ($column[$i] == 'Date') {
                            $date = Carbon::createFromFormat('d/m/Y H:i:s', $val);
                            $val = $date->format('Y-m-d H:i:s');
                        }
                        $value[$column[$i]] = $val;
                    }
                    $array[] = $value;
                }
            }
        }
        return response($array);
    }
}