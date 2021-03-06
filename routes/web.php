<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use PHPHtmlParser\Dom;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/api', function () use ($router) {
    $docs = [
        'auth' => 'adding query string apiKey in the URL',
        'API' => [
            '/' => [
                'desc'=>'Api documentation',
                'method' => 'GET'
            ],
            '/api/file-list'=> [
                'desc' =>'Get list file',
                'method' => 'GET'
            ],
            '/api/data' => [
                'desc' => 'Get content file',
                'method' => 'GET',
                'query' => [
                    'file' => 'File name from api file list'
                ]
            ],
        'version' => date('Ymd')
        ]
    ];
    return response($docs);
});

$router->get('/api/file-list', ['middleware' => 'auth', function(Request $request) {
    $path = getenv('FOLDER_FILE');
    $files = array_diff(scandir($path), array('.', '..'));
    $res = [];
    foreach($files as $file) {
        $res[] = $file;
    }
    return response(['data' => $res]);
}]);

$router->get('/api/data', ['middleware' => 'auth', function(Request $request) {
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
                    if(!$col->getChildren()) {
                        $val = null;
                        $value[$column[$i]] = $val;
                        continue;
                    }
                    $val = $col->text;
                    if ($val == '') {
                        $font = $col->find('font');
                        if ($font) {
                            $val = $font->text;
                        }
                    }
                    
                    if ($val && $column[$i] == 'Date') {
                        try {
                            $date = Carbon::parse($val);
                            $val = $date->format('Y-m-d H:i:s');
                        }
                        catch(\Exception $e) {
                            $val = $e->getMessage();
                        }
                        
                    }
                    $value[$column[$i]] = $val;
                }
                $array[] = $value;
            }
        }
    }
    $date = Carbon::parse(filemtime($filePath));
    $timezone = "asia/jakarta";
    $date->timezone($timezone);
    return response(['modified_at' => $date->format("Y-m-d H:i:s"), 'data' => $array]);
}]);