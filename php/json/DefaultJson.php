<?php
/**
 * User: Jonny Rimkus
 * Date: 07.09.2018
 * Time: 05:18
 */

namespace LastFmTube\Json;
require_once dirname(__FILE__) . '/../../vendor/autoload.php';

use Exception;
use LastFmTube\Util\Functions;

abstract class DefaultJson implements JsonInterface {

    protected $apiName;
    protected $funcs;
    private   $currentMethod = 'unknown';

    protected function __construct($returnOutput = false) {

        $this->apiName = get_class($this);
        $this->funcs   = Functions::getInstance();
        $this->funcs->startSession();
        header("Content-Type: application/json;charset=utf-8");

    }

    /**
     * @param bool $returnOutput
     * @return mixed
     */
    public static abstract function process($returnOutput = false);

    public static final function getVar($name, $defval = null, $vars = null) {
        return is_array($vars) ?
            (isset($vars[$name]) ? $vars[$name] : $defval) :
            (isset($_GET[$name]) ? $_GET[$name] : $defval);
    }

    public static function baseError($msg) {
        $json['handler']       = 'default';
        $json['method']        = 'unknown';
        $json['data']['type']  = 'error';
        $json['data']['value'] = $msg;


        DefaultJson::setResponseHeader(500);
        die('handler: default, method: ' . $_SERVER['REQUEST_METHOD'] . ', error: ' . $msg);
        //die(json_encode($json));
    }

    public static function baseMsg($msg) {
        $json['handler']       = 'default';
        $json['method']        = 'unknown';
        $json['data']['type']  = 'msg';
        $json['data']['value'] = $msg;
        return json_encode($json);
    }

    public final function setMethod($method = 'unknown') {
        $this->currentMethod = $method;
    }

    public function jsonMsg($msg) {
        $json['handler']       = $this->apiName;
        $json['method']        = $this->currentMethod;
        $json['data']['type']  = 'msg';
        $json['data']['value'] = $msg;
        return json_encode($json);
    }

    public function jsonData($data, $dataType = 'data') {
        $json['handler']       = $this->apiName;
        $json['method']        = $this->currentMethod;
        $json['data']['type']  = $dataType;
        $json['data']['value'] = $data;
        return json_encode($json);
    }

    protected final function handleRequest() {
        try {
            $this->currentMethod = $_SERVER['REQUEST_METHOD'];

            // @formatter:off
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET': return $this->get();
                case 'POST': return $this->post();
                case 'PUT': return $this->put();
                case 'DELETE': return $this->delete();
                default: return $this->jsonError('unbekannte Action:' . $_SERVER['REQUEST_METHOD']);
            }
            // @formatter:on
        } catch (Exception $err) {
            $this->jsonError($err);
            return null;
        }

    }

    public function get() {
    }

    public function post() {
    }

    public function put() {
    }

    public function delete() {
    }

    protected function jsonError($msg) {
        $json['handler']       = $this->apiName;
        $json['method']        = $this->currentMethod;
        $json['data']['type']  = 'error';
        $json['data']['value'] = $msg;

        self::setResponseHeader(500);
        die(('handler: ' . $this->apiName . ', method: ' . $this->currentMethod . ', error: ' . $msg));
        //return json_encode($json);
    }

    public static function setResponseHeader($status, $msg = false) {
        if ($msg === false) http_response_code($status);
        else header($_SERVER['SERVER_PROTOCOL'] . $status . ' ' . $msg);
    }
}