<?php
require_once __DIR__.'/../vendor/autoload.php';
//require_once('FirePHPCore/fb.php');

class MySlim extends Slim\Slim {
    public function fail($message, $statusCode=500) {
        $this->response()->body($message);
        $this->response()->status($statusCode);
        $this->stop();
    }
}

$app = new MySlim(array(
    'templates.path' => __DIR__.'/../templates'
));

function getFiles($path, $filter=null) {
    $files = array();
    if (!is_dir($path))
        return array();
    foreach (new DirectoryIterator($path) as $file) {
        if ($file->isDir() && !$file->isDot())
            $files = array_merge($files, getFiles($file->getPathName()));
        elseif ($file->isFile() && ($filter===null || call_user_func($filter, $file)))
            $files[] = $path.'/'.$file->getFilename();
    }
    return $files;
}

$app->get('/', function() use ($app) {
    return $app->render('index.php', array());
});


$app->post('/run/:example', function($example) use ($app) {
    $res = $app->response();
    $res->header('Content-type', 'application/json');

    $post = json_decode($app->request()->getBody(), true);
    if (!isset($post['php']) || !is_array($post['php']))
        $app->fail('No php code in post data');

    $phpFiles = $post['php'];
    $phpCode = array();
    foreach($post['php'] as $file) {
        if (!isset($file['name']) || !is_string($file['name'])
            || !isset($file['content']) || !is_string($file['content']))
            $app->fail('Invalid value in php input!');
    }

    $dir = '../examples/'.$example;
    if (!is_dir($dir))
        $app->fail('Example not found', 404);

    $initCode = '<?php

require_once SANDBOX_ROOT.\'/vendor/ngs/ngs-php/NGS/Requirements.php\';
require_once SANDBOX_ROOT.\'/examples/[[example]]/platform/modules/Modules.php\';
$cfg = parse_ini_file(SANDBOX_ROOT.\'/examples/[[example]]/platform/project.ini\');
$client = new \NGS\Client\RestHttp(
    $cfg[\'api-url\'],
    $cfg[\'username\'],
    $cfg[\'project-id\']
);
\NGS\Client\RestHttp::instance($client);
?>';
    $initCode = str_replace('[[example]]', $example, $initCode);

    $linter = new SyntaxLinter();
    $syntaxErrors = array();
    foreach ($post['php'] as $file) {
        if(!$linter->check($file['content'])) {
            $error = $linter->getError();
            $error['file'] = $file['name'];
            $syntaxErrors[] = $error;
        }
    }
    if($syntaxErrors)
        return $res->body(json_encode(array(
            'syntax' => false,
            'syntaxErrors' => $syntaxErrors)));

    $sandboxProxy = new DslBox\SandboxProxy('http://localhost:43001');
    foreach ($post['php'] as $file)
        $sandboxProxy->add($file['name'], $file['content']);
    $sandboxProxy->add('_init.php', $initCode);

    $output = $sandboxProxy->execute();
    $headers = $sandboxProxy->getWhitelistResponseHeaders();
    $headers = $sandboxProxy->getResponseHeaders();

/*
    foreach($headers as $key=>$value)
        $res->header($key, $value);
    $res->body($output);
    return ;
*/
    $result = array('output' => $output);
    if(isset($headers['Sandbox-Box-Id']))
        $result['boxId'] = $headers['Sandbox-Box-Id'];
    $res->body(json_encode($result));
});


$app->get('/run/:example', function($example) use ($app) {
    $res = $app->response();

    $dir = '../examples/'.$example;
    if (!is_dir($dir))
        $app->fail('Example not found', 404);

    if(!isset($_GET['boxId']))
        $app->fail('No boxId param');
    $boxId = $_GET['boxId'];
    $sandboxProxy = new DslBox\SandboxProxy('http://localhost:43001', $boxId);
    
    $query = isset($_GET['query']) ? $_GET['query'] : null;
    
    $output = $sandboxProxy->httpGet($query);

    $headers = $sandboxProxy->getWhitelistResponseHeaders();
    $headers = $sandboxProxy->getResponseHeaders();

    // passthru
    foreach($headers as $key=>$value)
        $res->header($key, $value);
    $res->body($output);
    return ;

    $res->body(json_encode(array(
        'output' => $output,
        'boxId'  => $headers['Sandbox-Box-Id'],
    )));
});

function getFileTree($path)
{
    $nodes = array();
    foreach (new DirectoryIterator($path) as $item) {
        if ($item->isDot() || $item->getFilename() === 'NGS')
            continue;
        $node = array();
        if ($item->isDir()) {
            $node['isDir'] = true;
            $node['nodes'] = getFileTree($item->getPathname());
        } elseif ($item->isFile()) {
            $node['isFile'] = true;
            $node['isConverter'] = strpos($item->getFilename(), 'Converter.php') !== false;
        }
        $node['name'] = $item->getFilename();
        $nodes[] = $node;
    }
    return $nodes;
}

function getSourceFiles($baseDir, $type)
{
    $dir = $baseDir.'/'.$type;
    if (!is_dir($dir)) {
        throw new LogicException('No '.$type.' folder!');
    }
    $files = getFiles($dir, function ($f) use ($type) {
        return $type === pathinfo($f->getFilename(), PATHINFO_EXTENSION);
    });

       // print_r(get_class_methods($f));die; });

        //return $f->getExtension()===$type; } );
    $contents = array();
    foreach($files as $file)
        $contents[] = array('name'=>basename($file), 'content'=>file_get_contents($file));
    return $contents;
}

// loads example
$app->get('/example/:example', function($example) use ($app) {
    $baseDir = '../examples/'.$example;
    if(!preg_match('/[a-z0-9_-]+/', $example) || !is_dir($baseDir))
        $app->fail('Example not found', 404);

    $modulesDir = $baseDir.'/platform/modules';
    if (!is_dir($modulesDir))
        $app->fail('Modules dir not found! '.$modulesDir);
    
    $intro = $baseDir.'/intro.html';
    $result = array();
    try {
        $result['intro']   = is_file($intro) ? file_get_contents($intro) : '';
        $result['modules'] = getFileTree($modulesDir);
        $result['dsl']     = getSourceFiles($baseDir, 'dsl');
        $result['php']     = getSourceFiles($baseDir, 'php');
        $result['uploads'] = getFiles($baseDir.'/uploads');
    } catch (Exception $ex) {
        //$app->fail($ex->getFile().', line '.$ex->getLine().': '.$ex->getMessage());
        $app->fail($ex->getTraceAsString());
    }

    $app->response()->header('Content-type', 'application/json');
    $app->response()->body(json_encode($result));
});


$app->run();
