<?php

namespace App\Http\Controllers;

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
        $filePath = base_path('import/'.$file);
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
                        for ($c=0; $c < $num; $c++) {
                            $col = $colums[$c];
                            $values[$col] = $data[$c];
                            $array[] = $values;
                        }
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
                        $value[$column[$i]] = $col->text;
                    }
                    $array[] = $value;
                }
            }
        }
        return response(['status'=>true, 'data' => $array]);
    }
}