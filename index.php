<?php
require 'vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = true;

//$config['dbdns'] = 'mysql:host=127.0.0.1;dbname=kokomodb;charset=utf8';
//$config['dbuser'] = 'root';
//$config['dbpass'] = 'root';

$config['dbdns'] = 'mysql:host=127.0.0.1;dbname=prjdb;charset=utf8';
$config['dbuser'] = 'root';
$config['dbpass'] = 'root';

$app = new \Slim\App(["settings" => $config]);

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/download",
    "secure" => false,
    "users" => [
        "admin" => "mmi365?",
    ]
]));

// Get container
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) {
    return new \Slim\Views\PhpRenderer('templates/');
};

// Register PDO
$container['pdo'] = function ($container) {
    return new \Slim\PDO\Database($container->get('settings')['dbdns'], $container->get('settings')['dbuser'], $container->get('settings')['dbpass']);
};

// Home Page route
$app->get('/', function ($request, $response, $args) {
    return $this->view->render($response, 'index.html', [
        'author' => "RainWalker",
        'thankYou' => ''
    ]);
})->setName('homepage');

// Add to db route
$app->post('/', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $ticket_data = [];

    $pdo = $this->pdo;
    $insertStatement = $pdo->insert(array('id', 'name', 'phone', 'district', 'date'))
                       ->into('users')
                       ->values(array(null, $data["name"], $data["phone"], $data["district"], date("Y-m-d H:i:s") ));
    $insertId = $insertStatement->execute(false);
    return $this->view->render($response, 'index.html', [
        'author' => "RainWalker",
        'thankYou' => "Your name has been posted successfully. Thank you for registering."
    ]);

})->setName('homepage-post');

// Home Page route
$app->get('/download', function ($request, $response, $args) {

    $pdo = $this->pdo;
    $selectStatement = $pdo->select()
                           ->from('registeredUsers');
    $stmt = $selectStatement->execute();
    $data = $stmt->fetchAll();

    $list = array ();
    foreach ($data as $result) {
        array_push($list, array_values($result));
    }

    // Output array into CSV file
    $fp = fopen('php://output', 'w');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="registeredUsers.csv"');
    foreach ($list as $ferow) {
        fputcsv($fp, $ferow);
    }

})->setName('download');

$app->run();

?>