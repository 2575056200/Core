<?php
namespace didphp\Core;

/**
 * Class DRequest
 * 处理 request 请求数据
 * @package did\Core
 */
class DRequest {
    const VAR_TYPE_INT = 'int';
    const VAR_TYPE_STRING = 'string';
    const VAR_TYPE_ARRAY = 'array';
    const VAR_TYPE_FLOAT = 'float';

    private function convertValue($value='', $default='', $type='') {
        $value = $value == '' ? $default : $value;
        switch ($type) {
            case self::VAR_TYPE_INT:
                $value = is_int($value) ? $value : intval($value);
                break;
            case self::VAR_TYPE_FLOAT:
                $value = is_float($value) ? $value : floatval($value);
                break;
            case self::VAR_TYPE_STRING:
                $value = is_string($value) ? $value : strval($value);
                break;
            case self::VAR_TYPE_ARRAY:
                $value = is_array($value) ? $value : [];
                break;
            default:
                $value = $value;
                break;
        }
        return $value;
    }

    public static function postToString($name='', $default='') {
        return self::post($name, $default, self::VAR_TYPE_STRING);
    }

    public static function postToInt($name='', $default=0) {
        return self::post($name, $default, self::VAR_TYPE_INT);
    }

    public static function postToFloat($name='', $default='') {
        return self::post($name, $default, self::VAR_TYPE_FLOAT);
    }

    public static function postToArray($name='', $default=[]) {
        return self::post($name, $default, self::VAR_TYPE_ARRAY);
    }

    public static function post($name='', $default='', $type='') {
        $input = file_get_contents('php://input');
        $inputData = @json_decode($input, true);
        if (!$inputData) {
            if ($input == '') {
                $postData = $_POST;
            } else {
                parse_str($input, $postData);
            }
        } else {
            $postData = $inputData;
        }
        $lbbRequest = new self();
        $name = trim($name);
        $type = trim($type);
        if ($name == '') {
            if ($postData) {
                foreach ($postData as $postK => $postV) {
                    $postData[$postK] = $lbbRequest->convertValue($postV, $default, $type);
                }
            }
        } else {
            $postData = $lbbRequest->convertValue(isset($postData[$name]) ? $postData[$name] : '', $default, $type);
        }
        return $postData;
    }

    public static function logging() {
        $namespace = str_replace("\\", "/", Route::current()->getAction('namespace'));
        $controller = str_replace($namespace, '', str_replace("\\", "/", Route::current()->getAction('controller')));
        $domain = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $path = explode('?', $uri)[0];
        $queryString = count($_GET) > 0 ? json_encode($_GET, JSON_UNESCAPED_UNICODE) : '';

        $input = file_get_contents('php://input');
        $inputData = @json_decode($input, true);
        if (!$inputData) {
            if ($input == '') {
                $postData = $_POST;
            } else {
                parse_str($input, $postData);
            }
        } else {
            $postData = $inputData;
        }
        $postParams = count($postData) > 0 ? json_encode($postData, JSON_UNESCAPED_UNICODE) : '';

        $authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? trim($_SERVER['HTTP_AUTHORIZATION']) : '';
        $authorizationData = explode('Bearer ', $authorization);
        if ($authorizationData && count($authorizationData) == 2) {
            $token = trim($authorizationData[1]);
        } else {
            $token = isset($_POST['token']) ? trim($_POST['token']) : '';
        }

        $ip = '127.0.0.1';
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER["REMOTE_ADDR"])) {
            $ip = $_SERVER["REMOTE_ADDR"];
        } else if (getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("REMOTE_ADDR")) {
            $ip = getenv("REMOTE_ADDR");
        } else {
            $ip = 'unknow';
        }

        $created = time();

        $admin_id = 0;
        try {
            $user = (new LbbJWTAuth())->parse()->getData();
            $status = 1;
            $admin_id = $user['id'];
        } catch (TokenExpiredException $e) {
            $status = 0;
        } catch (Exception $e) {
            $status = 0;
        }

        $logData = [
            'admin_id' => $admin_id,
            'namespace_name' => $namespace,
            'controller_name' => $controller,
            'domain' => $domain,
            'uri' => $uri,
            'path' => $path,
            'query_string' => $queryString,
            'post_params' => $postParams,
            'token' => $token,
            'ip' => $ip,
            'status' => $status,
            'created' => $created,
        ];
        $res = Logging::query()->insert($logData);
        if ($res) {
            $logFile = 'success_' . date('Ymd', $created) . '.log';
        } else {
            $logFile = 'failed_' . date('Ymd', $created) . '.log';
        }
        $logString = date('Y-m-d H:i:s', $created) . ':' . json_encode($logData, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        $logString .= "=========================================" . PHP_EOL;
        Storage::disk('public')->append("log_util/{$logFile}", $logString);
        return true;
    }
}
