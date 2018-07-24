<?php  
/**
 * Created by vsc.
 * User: juicle
 * Date: 2018/2/8
 * Time: 下午8:12
 */
namespace ApiDoc;

define('APP_PATH', rtrim(str_replace('\\', '/', dirname(__DIR__)), '/').'/');
define('DOC_PATH', rtrim(str_replace('\\', '/', dirname(__FILE__)), '/').'/');
define('CONF_PATH', APP_PATH."config/");
define('TEMP_PATH', APP_PATH."template/");

require __DIR__ . '/lib/BaseApiDoc.php';

use lib\base\ApiDocBase;

class ApiDoc extends ApiDocBase {

    public function __construct()
    {
        parent::__construct();
    }
    
    public function build()
    {
        return $this->buildDoc();
    }
    
    public function set($data = [])
    {
        return $this->setconf($data);
    }
    
}
?>
