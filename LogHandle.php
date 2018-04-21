<?php

/**
 * 日志处理类
 * @author:wangben
 * date:2018-04-20
 */
class LogHandle
{
	const LOG_PATH      = ''; //日志的路径
    const FILE_NAME     = ''; //日志文件名称
    const FILE_MAX_SIZE = '2097152'; //日志记录最大量
    const LOG           = 'log';
    const ERROR         = 'error';
    const INFO          = 'info';
    const SQL           = 'sql';
    const NOTICE        = 'notice';
    const ALERT         = 'alert';
    const DEBUG         = 'debug';

    public static function info($message, $describe = 'info', $path = '')
    {
    	return self::write($message, self::INFO, $path, $describe);
    }

    public static function debug($message, $describe = 'info', $path = '')
    {
    	return self::write($message, self::DEBUG, $path, $describe);
    }

    public static function alert($message, $describe = 'info', $path = '')
    {
    	return self::write($message, self::ALERT, $path, $describe);
    }

    public static function notice($message, $describe = 'info', $path = '')
    {
    	return self::write($message, self::NOTICE, $path, $describe);
    }

    public static function sql($message, $describe = 'info', $path = '')
    {
    	return self::write($message, self::SQL, $path, $describe);
    }

    public static function error($message, $describe = 'info', $path = '')
    {
    	return self::write($message, self::ERROR, $path, $describe);
    }

    public static function log($message, $describe = 'info', $path = '')
    {
    	return self::write($message, self::LOG, $path, $describe);
    }

    /**
     * 日志模板
     */
    private static function template($message, $describe = 'info')
    {
    	$content  = '';
    	$server   = $_SERVER;
    	$header   = [
    		'host'                      => $server['HTTP_HOST'],
    		'port'                      => $server['SERVER_PORT'],
    		'connection'                => $server['HTTP_CONNECTION'],
    		'upgrade-insecure-requests' => $server['HTTP_UPGRADE_INSECURE_REQUESTS'],
    		'user-agent'                => $server['HTTP_USER_AGENT'],
    		'accept'                    => $server['HTTP_ACCEPT'],
    		'accept-encoding'           => $server['HTTP_ACCEPT_ENCODING'],
    		'accept-language'           => $server['HTTP_ACCEPT_LANGUAGE'],
    		'software'                  => $server['SERVER_SOFTWARE'],
    		'root'                      => $server['DOCUMENT_ROOT'],
    		'scheme'                    => $server['REQUEST_SCHEME'],
    		'admin'                     => $server['SERVER_ADMIN'],
    		'filename'                  => $server['SCRIPT_FILENAME'],
    	];

		$content .= '['. date('Y-m-d H:i:s') .'] ' . $server['SERVER_ADDR'] . ' ' . $server['REQUEST_METHOD'] . ' ' . $server['REQUEST_URI'] . "\r\n";
    	$content .= '[header] ' . print_r($header, 1);

    	if ($_REQUEST) {
    		$content .= '[param] ' . print_r($_REQUEST, 1);
    	}

    	$content .= '[' . $describe . '] ' . $message . "\r\n";
    	$content .= '---------------------------------------------------------------' . "\r\n\r\n";

    	return $content;
    }

	/**
     * 日志写入接口
     * @access public
     * @param  string $log         日志信息
     * @param  string $destination 写入目标
     * @return void
     */
    public static function write($message = '', $level = 'info', $path = '', $describe = 'info', $array = true)
    {
    	if (empty($message) || !$message) {
    		return false;
    	}

    	try {
    		
    		if ($array) {
	        	$message = is_array($message) ? print_r($message, 1) : var_export($message, true);
	        } else {
	        	$message = is_string($message) ? : json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	        }

	        //日志内容
	        $content  = self::template($message, $describe);

	        //日志路径&日志文件名
	        $path1    = $path ? : (self::LOG_PATH ? : './log');
	        $path2    = $level ? : 'info';
	        $path     = $path1 . '/' . $path2;
	        $file     = self::FILE_NAME ? : date('Y-m-d');
	        $fileName = $file . '.log';
	        $filePath = $path . '/' . $fileName;

	        //自动创建日志目录
	        if (!is_dir($path)) {
	            @mkdir($path, 0777, true);
	        }

	        // 检测日志文件大小，超过配置大小则备份日志文件重新生成
	        if (is_file($filePath) && floor(self::FILE_MAX_SIZE) <= filesize($filePath)) {
	            rename($filePath, $path . '/' . $file . '_' . time() . '.log');
	        }

	        return error_log($content, 3, $filePath);

    	} catch (\Exception $e) {

    		$error_info = ([
				'code' => $e->getCode(),
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
			]);

            self::info($error_info);

			return false;
    	}
    }

    /**
     * 获取FatalError
     */
    public static function fatalError()
    {
    	$error   = error_get_last();

        if (!$error) {
            return false;
        }
        
    	$message = [
    		'errorNo'      => $error['type'],
    		'errorFile'    => $error['file'],
    		'errorLine'    => $error['line'],
    		'errorMessage' => $error['message'],
    	];

    	return self::write($message, 'fatal_error');
    }
}