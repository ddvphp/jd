<?php
namespace DdvPhp\Jd;
use \DdvPhp\DdvUtil\String\Conversion;
/**
 * AOP SDK 入口文件
 * 请不要修改这个文件，除非你知道怎样修改以及怎样恢复
 * @author wuxiao
 */

class JdSdk
{
    public static $jdSdkWorkDir = '/tmp/';
    public static $libRootDir = '';
    public static $jdDir = '';
    public static $builderModelDir = '';
    public static $requestDir = '';
    private static $jdSdkInited = false;
    public static function init($jdSdkWorkDir = null){
        if (self::$jdSdkInited){
            return;
        }
        self::$jdSdkInited = true;
        if (!empty($jdSdkWorkDir)) {
            JdSdk::$jdSdkWorkDir = $jdSdkWorkDir;
        }
        define('JD_SDK_WORK_DIR',JdSdk::$jdSdkWorkDir);
        self::$libRootDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '../../org';
        self::$jdDir = self::$libRootDir . DIRECTORY_SEPARATOR . 'jd';
        self::$builderModelDir = self::$libRootDir . DIRECTORY_SEPARATOR . 'buildermodel';
        self::$requestDir = self::$jdDir . DIRECTORY_SEPARATOR . 'request';
        // 注册自动载入
        spl_autoload_register(JdSdk::class.'::autoload');

    }
    // 自动加载
    public static function autoload($name){
        $nameLast7 = substr($name, -7);
        if ($nameLast7==='Request'){
            $filePath = self::$requestDir.DIRECTORY_SEPARATOR.$name.'.php';
        }elseif($nameLast7==='Builder'){
            $filePath = self::$builderModelDir.DIRECTORY_SEPARATOR.$name.'.php';
        }else{
            $filePath = self::$jdDir.DIRECTORY_SEPARATOR.$name.'.php';
        }
        if (is_file($filePath)){
            try {
                include $filePath;
            }catch (Exception $e){
                throw new Exception('autoload jd file fail', 500, 'AUTOLOAD_JD_FILE_FAIL');
            }
        }
    }


    /**
     * 使用SDK执行接口请求
     * @param unknown $request
     * @param string $token
     * @return Ambigous <boolean, mixed>
     */
    public static function jdclientRequestExecute($jdOrConfig, $request, $token = NULL) {
        if (is_array($jdOrConfig)){
            $jdOrConfig = self::getJdClient($jdOrConfig);
        }
        if (!($jdOrConfig instanceof JdClient)){
            throw new Exception('必须是一个jd实例化对象或者配置文件', 500, 'MUST_INSTANCEOF_AOP_RO_CONFIG_ARRAY');
        }
        $result = $jdOrConfig->execute($request, $token);
        return $result;
    }

    /**
     * 获取一个 JdClient 实例化的实例
     * @param array $config
     * @param bool $isMustConfig
     * @param string $apiVersion
     * @return JdClient
     * @throws Exception
     */
    public static function getJdClient($config, $isMustConfig = false){
        // 自动初始化
        JdSdk::init();
        // 把配置转驼峰key
        $config = self::getHumpConfig($config);
        // 如果需要判断必填配置
        if ($isMustConfig){
            // appId必填配置
            if (empty($config['appKey'])){
                throw new Exception('appKey must config', 500, 'APP_KEY_MUST_CONFIG');
            }
            // 支付宝公钥必须配置
            if (empty($config['appSecret'])){
                throw new Exception('appSecret must config', 500, 'APP_SECRET_MUST_CONFIG');
            }
            // 应用私钥必须配置
            /*if (empty($config['merchantPrivateKey'])){
                throw new Exception('merchantPrivateKey must config', 500, 'MERCHANT_PRIVATE_KEY_MUST_CONFIG');
            }*/
        }
        // 实例化客户端
        $jd = new JdClient();

        isset($config['serverUrl']) && $jd->serverUrl = $config['serverUrl'];
        isset($config['appKey']) && $jd->appKey = $config['appKey'];
        isset($config['appSecret']) && $jd->appSecret = $config['appSecret'];
        isset($config['accessToken']) && $jd->accessToken = $config['accessToken'];
        return $jd;
    }
    public static function getHumpConfig($config){
        foreach ($config as $key => $value) {
            $keyt = Conversion::underlineToHump($key);
            if ($keyt!==$key){
                unset($config[$key]);
                $config[$keyt] = $value;
            }
        }
        return $config;
    }
}
