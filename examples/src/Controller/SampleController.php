<?php
namespace GEN\Controller;

use \Samas\PHP7\Base\BaseController;
use \Samas\PHP7\Cache\APC;
use \Samas\PHP7\Kit\{AppKit, ArrayKit, HtmlKit, StrKit};
use \Samas\PHP7\Model\{ManagerAgent, ModelAgent};
use \Samas\PHP7\Model\Database\{PDOConnector, DBAdapter, DBSyntax, SQLBuilder};
use \GEN\Manager\UserManager;
use \GEN\Model\UserModel;

class SampleController extends BaseController
{
    public function actionSample0()
    {
        $this->initUserTable();
        $userCopy = new ModelAgent('genersis.user');
        // genersis.user_copy.schema
        $apc = new APC;
        dump($apc->list());
    }

    // PDOConnector
    public function actionSample1()
    {
        $result = [];

        // new PDOConnector
        $dsn      = 'mysql:host=proxysql;port=3306;dbname=genersis;charset=utf8;';
        $user     = 'root';
        $password = 'mysqlPassw0rd';
        $ssl      = [];
        $dbObj    = new PDOConnector($dsn, $user, $password, $ssl);

        // PDOConnector->execCmd($sql)
        $sql = 'CREATE TABLE IF NOT EXISTS `user`(
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `first_name` varchar(30) DEFAULT NULL,
                    `last_name` varchar(30) DEFAULT NULL,
                    `height` int(11) DEFAULT NULL,
                    `weight` int(11) DEFAULT NULL,
                    `inactive_ts` datetime DEFAULT NULL,
                    `json_column` json DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
        $result['create'] = $dbObj->execCmd($sql);

        // PDOConnector->execInsert($sql, $paramArr)
        $sql = 'INSERT INTO user (first_name) VALUES (:name);';
        $paramArr = [':name' => 'Name'];
        $result['insert'] = $dbObj->execInsert($sql, $paramArr);

        // PDOConnector->execSelect($sql, $paramArr)
        $sql = 'SELECT * FROM user WHERE id = :id;';
        $paramArr = [':id' => $result['insert']];
        $result['select'] = $dbObj->execSelect($sql, $paramArr);

        // PDOConnector->execUpdate($sql, $paramArr)
        $sql = 'UPDATE user SET first_name = :name WHERE id = :id;';
        $paramArr = [':name' => 'name', ':id' => $result['insert']];
        $result['update'] = $dbObj->execUpdate($sql, $paramArr);

        // PDOConnector->execDelete($sql, $paramArr)
        $sql = 'DELETE FROM user WHERE id = :id;';
        $paramArr = [':id' => $result['insert']];
        $result['delete'] = $dbObj->execDelete($sql, $paramArr);

        return $this->success('', $result);
    }

    // DBAdapter
    public function actionSample2()
    {
        $result = [];

        // new DBAdapter
        $data_source = 'genersis';
        $database    = 'genersis';
        $table       = 'user';
        $dbObj       = new DBAdapter($data_source, $database, $table);

        // DBAdapter->cmd($sql)
        $sql = "TRUNCATE TABLE `user`;";
        $result['truncate'] = $dbObj->cmd($sql);

        // DBAdapter->insert($data)
        $insertArr = [];
        for ($i = 1; $i <= 5; $i++) {
            $insertArr[] = $dbObj->insert(['first_name' => 'Name']);
        }
        $result['insert'] = $insertArr;

        // DBAdapter->count($whereStr, $whereParamArr)
        $result['count'] = $dbObj->count('first_name = :name', [':name' => 'Name']);

        // DBAdapter->find($whereStr, $whereParamArr)
        $result['find'] = $dbObj->find('first_name = :name', [':name' => 'Name']);

        // DBAdapter->select($whereStr, $whereParamArr)
        $result['select'] = $dbObj->select('first_name = :name', [':name' => 'Name']);

        // DBAdapter->update($data, $whereStr, $whereParamArr)
        $result['update'] = $dbObj->update(['first_name' => 'first'], 'id = :id', [':id' => 2]);

        // DBAdapter->delete($whereStr, $whereParamArr)
        $result['delete'] = $dbObj->delete('id = :id', [':id' => 3]);

        // DBAdapter->query($whereStr, $whereParamArr)
        $result['query'] = $dbObj->query('SELECT * FROM user WHERE first_name = :name;', [':name' => 'Name']);

        // DBSyntax
        $result['DBSyntax'] = $dbObj->update(
            ['inactive_ts' => new DBSyntax(' = NOW()')],
            'id = :id',
            [':id' => 4]
        );

        // DBAdapter->createSQL()
        $result['SQLBuilder'] = $dbObj->createSQL()
                                      ->field(['id', 'inactive_ts'])
                                      ->table('user')
                                      ->where('first_name = :name', [':name' => 'Name'])
                                      ->select();

        return $this->success('', $result);
    }

    // AbstractManagerClass
    public function actionSample3()
    {
        $this->initUserTable();

        $result = [];

        // new AbstractManagerClass
        $userManager = new UserManager();

        // methods extends from DBAdapter
        $result['extends'] = $userManager->select('last_name = :name', [':name' => 'Chen']);

        // AbstractManagerClass->getBy{$fieldName}()
        $result['getByField'] = $userManager->getByLastName('Yang');

        return $this->success('', $result);
    }

    // AbstractModelClass
    public function actionSample4()
    {
        $this->initUserTable();

        $result = [];

        // new AbstractModelClass - empty
        $userModel = new UserModel();
        $result['initEmpty'] = $userModel->getData();

        // new AbstractModelClass - by id
        $userModel = new UserModel(1);
        $result['initById'] = $userModel->getData();

        // new AbstractModelClass - by data array
        $userData = [
            'first_name'  => 'Freddy',
            'last_name'   => 'Lin',
            'height'      => 175,
            'weight'      => 60,
            'inactive_ts' => null,
            'json_column' => [
                'a' => 6,
                'b' => [
                    'bb' => 66
                ]
            ]
        ];
        $userModel = new UserModel($userData);
        $result['initByData'] = $userModel->getData();



        // AbstractModelClass->setData(int $id)
        $userModel->setData(2);
        $result['setById'] = $userModel->getData();

        // AbstractModelClass->setData(array $data)
        $userModel->setData($userData);
        $result['setByData'] = $userModel->getData();



        // AbstractModelClass->__invoke(int $id)
        $userModel(3);
        $result['invokeById'] = $userModel->getData();

        // AbstractModelClass->__invoke(array $data)
        $userModel($userData);
        $result['invokeByData'] = $userModel->getData();



        // AbstractModelClass->$virtualField
        $result['virtualField'] = $userModel->full_name;

        // AbstractModelClass->$calculatedField
        $result['calculatedField'] = $userModel->height;

        // AbstractModelClass->bindBy($where, array $params)
        $userModel->bindBy('and', [
            'first_name' => 'Doris',
            'last_name'  => 'Lin'
        ]);
        $result['bindBy'] = $userModel->getData();

        // AbstractModelClass->save() - insert
        // $userModel->save();

        // AbstractModelClass->save() - update
        $userModel->height = 176;
        $userModel->save();

        return $this->success('', $result);
    }

    // AbstractManagerClass + AbstractModelClass
    public function actionSample5()
    {
        $this->initUserTable();

        $result = [];

        // new AbstractManagerClass
        $userManager = new UserManager();

        // new AbstractModelClass
        $userModel = new UserModel();

        $userArr = $userManager->select('height <= :height', [':height' => 170]);
        foreach ($userArr as $userData) {
            $userModel($userData);
            $result[] = $userModel->full_name;
        }

        return $this->success('', $result);
    }

    // ManagerAgent ModelAgent
    public function actionSample6()
    {
        $this->initUserTable('user_copy');

        $result = [];

        // new ManagerAgent
        $userManager = new ManagerAgent('user_copy');

        // new ModelAgent
        $userModel = new ModelAgent('user_copy');

        $userArr = $userManager->select('height <= :height', [':height' => 170]);
        foreach ($userArr as $userData) {
            $userModel($userData);
            $userModel->inactive_ts = StrKit::date();
            $userModel->save();
            $result[] = $userModel->first_name . ' ' . $userModel->last_name;
        }

        return $this->success('', $result);
    }

    // AppKit::manager() AppKit::model()
    public function actionSample7()
    {
        $this->initUserTable();
        $this->initUserTable('user_copy');

        $result = [];

        // new AppKit::manager() - defined
        $userManager = AppKit::manager('user');
        is($userManager);


        // new AppKit::model() - defined
        $userModel = AppKit::model('user');
        echo HtmlKit::hr();
        is($userModel);

        $userArr = $userManager->select('height <= :height', [':height' => 170]);
        foreach ($userArr as $userData) {
            $userModel($userData);
            $result[] = $userModel->full_name;
        }



        // new AppKit::manager() - undefined
        $userManager = AppKit::manager('user_copy');
        echo HtmlKit::hr();
        is($userManager);

        // new AppKit::model() - undefined
        $userModel = AppKit::model('user_copy');
        echo HtmlKit::hr();
        is($userModel);

        $userArr = $userManager->select('height <= :height', [':height' => 170]);
        foreach ($userArr as $userData) {
            $userModel($userData);
            $result[] = $userModel->first_name . ' ' . $userModel->last_name;
        }

        echo HtmlKit::hr();
        dump($result);
    }

    // AppKit::config()
    public function actionSample8()
    {
        $result = [];

        $result['init'] = AppKit::config();

        $result['get'] = [];
        $result['get']['strKey'] = AppKit::config('env');
        $result['get']['arrayKey'] = AppKit::config(['slim', 'settings']);

        $result['set'] = [];
        $result['set']['strKey'] = AppKit::config('test', []);
        $result['set']['arrayKey'] = AppKit::config(['test', 'msg'], 'message');
        $result['set']['arrayKey'] = AppKit::config(['test', 'data', ''], '123');

        $result['total'] = AppKit::config();

        return $this->success('', $result);
    }

    // BaseController
        // request, response, args
        // beforeAction()
        // JSONResponse
        // render()

    // routes.php
    // BaseController->getManager()
    // BaseController->getModel()
    // BaseController->getObj()

    // is() dump()
    public function actionSample9()
    {
        $test = 1;
        $object = (object)[5];
        $comp = $object;
        $closure = function (&$x, $y = 'a', $z = 3, array $o = [1]) use (&$closure, $test, &$object, $comp) {
            return $x + $z;
        };
        $resource = fopen('/var/www/html/public/index.php', 'r');
        $array = [
            null,
            true,
            1,
            3.3,
            "str\nstr",
            [],
            new class {
                const TEST = 1;
                public $public = 'public';
                protected $protected = 'protected';
                private $private = 'private';
                private static $static = 'static';

                public function pub($pub1, &$pub2, $pub3 = 1, $pub4 = 'a')
                {
                }

                public function pro($pro1, &$pro2, $pro3 = 1, $pro4 = 'a')
                {
                }

                private function pri($pri1, &$pri2, $pri3 = 1, $pri4 = 'a')
                {
                }

                final private function fin()
                {
                }

                private static function sta()
                {
                }
            },
            new UserModel,
            (object)[1, 2, 3],
            $closure,
            $resource,
            [
                null,
                true,
                1,
                3.3,
                "str\nstr",
                [],
                new class {
                    const TEST = 1;
                    public $public = 'public';
                    protected $protected = 'protected';
                    private $private = 'private';
                    private static $static = 'static';

                    public function pub($pub1, &$pub2, $pub3 = 1, $pub4 = 'a')
                    {
                    }

                    public function pro($pro1, &$pro2, $pro3 = 1, $pro4 = 'a')
                    {
                    }

                    private function pri($pri1, &$pri2, $pri3 = 1, $pri4 = 'a')
                    {
                    }

                    final private function fin()
                    {
                    }

                    private static function sta()
                    {
                    }
                },
                new UserModel,
                (object)[1, 2, 3],
                $closure,
                $resource
            ]
        ];

        is($array);
        echo HtmlKit::hr();
        dump($array);
    }

    // operate JSON column
    public function actionSample10()
    {
        $this->initUserTable();

        echo HtmlKit::h3([], 'operate JSON field by Model');
        // refresh whole column
        $user = new UserModel(5);
        dump($user->json_column['a']);
        $user->json_column['a'] = 100;
        $user->save();
        dump($user->json_column['a']);

        // update JSON field by JSON_SET()
        $user = new UserModel(5);
        $user->json_column->alter('a', 150);
        $user->save();
        dump($user->json_column['a']);

        echo HtmlKit::hr();

        echo HtmlKit::h3([], 'operate JSON field by SQLBuilder');
        $dsn      = 'mysql:host=proxysql;port=3306;dbname=genersis;charset=utf8;';
        $user     = 'root';
        $password = 'mysqlPassw0rd';
        $ssl      = [];
        $dbObj    = new PDOConnector($dsn, $user, $password, $ssl);
        $maker = new SQLBuilder($dbObj);
        // field() to get json field
        $result = $maker->reset()
                        ->table('user')
                        ->field(['first_name', 'last_name', ['json_column' => 'e.0.ea']]);
        dump($result->buildSQL('select'));
        $result->select();

        $result = $maker->reset()
                        ->table('user')
                        ->field(['first_name', 'last_name', ['json_column' => 'd.db.1']]);
        dump($result->buildSQL('select'));
        $result->select();

        // where(string $sql, array $params = [])
        $result = $maker->reset()
                        ->table('user')
                        ->where(['json_column' => ['e.0.ea' => 1]]);
        dump($result->buildSQL('select'));
        $result->select();

        $result = $maker->reset()
                        ->table('user')
                        ->where(['json_column' => ['d.db.1' => 2]]);
        dump($result->buildSQL('select'));
        $result->select();

        // insert(array $data)
        $result = $maker->reset()
                        ->table('user')
                        ->data(['json_column' => ['z' => 999]]);
        dump($result->buildSQL('insert'));
        $result->insert();

        // update(array $data, string $sql, array $params = [])
        $result = $maker->reset()
                        ->table('user')
                        ->data(['json_column' => ['d.db.1' => 3, 'e.0.ea' => 2]])
                        ->where(['json_column' => ['a' => 3]]);
        dump($result->buildSQL('update'));
        $result->update();

        // DBSyntax::jsonRemove()
        $result = $maker->reset()
                        ->table('user')
                        ->field(DBSyntax::jsonRemove('json_column', 'e.0.ea'));
        dump($result->buildSQL('select'));
        $result->select();
    }

    private function initUserTable($table = null)
    {
        // new DBAdapter
        $data_source = 'genersis';
        $database    = 'genersis';
        $table       = $table ?? 'user';
        $dbObj       = new DBAdapter($data_source, $database, $table);

        $sql = "DROP TABLE IF EXISTS `$table`;";
        $dbObj->cmd($sql);

        $sql = "CREATE TABLE `$table`(
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `first_name` varchar(30) DEFAULT NULL,
                    `last_name` varchar(30) DEFAULT NULL,
                    `height` int(11) DEFAULT NULL,
                    `weight` int(11) DEFAULT NULL,
                    `inactive_ts` datetime DEFAULT NULL,
                    `json_column` json DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $dbObj->cmd($sql);

        $users = [
            [
                'first_name'  => 'Allen',
                'last_name'   => 'Chen',
                'height'      => 190,
                'weight'      => 88,
                'inactive_ts' => null,
                'json_column' => [
                    'a' => 1,
                    'b' => ['bb' => 11],
                    'c' => [1, 11, 111],
                    'd' => ['da' => ['daa' => 1], 'db' => [1, 2, 3]],
                    'e' => [['ea' => 1], [1, 2, 3]]
                ]
            ],
            [
                'first_name'  => 'Billy',
                'last_name'   => 'Chen',
                'height'      => 180,
                'weight'      => 77,
                'inactive_ts' => null,
                'json_column' => [
                    'a' => 2,
                    'b' => ['bb' => 22],
                    'c' => [2, 22, 222],
                    'd' => ['da' => ['daa' => 1], 'db' => [1, 2, 3]],
                    'e' => [['ea' => 1], [1, 2, 3]]
                ]
            ],
            [
                'first_name'  => 'Colin',
                'last_name'   => 'Lin',
                'height'      => 170,
                'weight'      => 66,
                'inactive_ts' => null,
                'json_column' => [
                    'a' => 3,
                    'b' => ['bb' => 33],
                    'c' => [3, 33, 333],
                    'd' => ['da' => ['daa' => 1], 'db' => [1, 2, 3]],
                    'e' => [['ea' => 1], [1, 2, 3]]
                ]
            ],
            [
                'first_name'  => 'Doris',
                'last_name'   => 'Lin',
                'height'      => 160,
                'weight'      => 55,
                'inactive_ts' => null,
                'json_column' => [
                    'a' => 4,
                    'b' => ['bb' => 44],
                    'c' => [4, 44, 444],
                    'd' => ['da' => ['daa' => 1], 'db' => [1, 2, 3]],
                    'e' => [['ea' => 1], [1, 2, 3]]
                ]
            ],
            [
                'first_name'  => 'Ed',
                'last_name'   => 'Yang',
                'height'      => 150,
                'weight'      => 44,
                'inactive_ts' => null,
                'json_column' => [
                    'a' => 5,
                    'b' => ['bb' => 55],
                    'c' => [5, 55, 555],
                    'd' => ['da' => ['daa' => 1], 'db' => [1, 2, 3]],
                    'e' => [['ea' => 1], [1, 2, 3]]
                ]
            ]
        ];
        foreach ($users as $user) {
            $dbObj->insert($user);
        }
    }
}
