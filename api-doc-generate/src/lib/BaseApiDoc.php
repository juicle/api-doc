<?php  
/**
 * Created by vsc.
 * User: juicle
 * Date: 2018/2/8
 * Time: 下午8:12
 */
namespace lib\base;

class ApiDocBase{
    
    /**
     * Annotation cache
     *
     * @var array
     */
	private $annotationCache = [];
	
	/**
	 * The file dir
	 *
	 * @var array
	 */
	private $dirs = [];
	
	/**
	 * The annotation data
	 *
	 * @var array
	 */
	private $data = [];
	
	/**
	 * Final output data
	 *
	 * @var array
	 */
	private $output = [];
	
	/**
	 * Template rule
	 *
	 * @var array
	 */
	private $rule = [];
	
	/**
	 * Configuration rules
	 *
	 * @var array
	 */
	private $config = [];
	
	/**
	 * Template storage path
	 *
	 * @var string
	 */
	private $template_path = '/template';
	
	/**
	 * Create a new apidoc instance and Initialization parameters.
	 */
	function __construct()
	{
	    require_once CONF_PATH.'common.php';
	    $this->rule = $config['rule'];
	    $this->config = $config['settings'];
	    $this->template_path = TEMP_PATH;
	}
	
	/**
	 * The transformation configuration parameters.
	 *
	 * @param array   $data
	 * @return obj    $this
	 */
	public function setconf($data = [])
	{
	    if(empty($data)){ return $this; }
        foreach ($data as $configName => $configValue){
            if(isset($this->config[$configName])){
                $this->config[$configName] = $configValue;
            }
        }
	    return $this;
	}
	
	/**
	 * Generate API document
	 * 
	 * @return boolean 
	 */
	public function buildDoc()
	{
		
		$sub_file = $this->config['vender_path'] . "ApiDoc_".date('Y-m-d')."{$this->config['template_ext']}";
		if (file_exists($sub_file) == true) {
			unlink($sub_file);
		}
	    $this->listdirs($this->config['build_path']);
	    $this->getAllAnnotations();
	    return $this->generateTemplate();
	}
	
    /**
	 * Process data and save the final data.
	 *
	 * @return boolean 
	 */
	protected function generateTemplate()
	{
		$this->output = '';
		$sub_data ="";
		foreach ($this->data as $group => $class) {
			foreach ($class as $className => $object) {
				$this->class = $className;
				if(!is_dir($this->config['vender_path'])) mkdir($this->config['vender_path']);
				$sub_file = $this->config['vender_path'] . "ApiDoc_".date('Y-m-d')."{$this->config['template_ext']}";
				// if (file_exists($sub_file) == true) {
				//     unlink($sub_file);
				// }
				//if (!is_dir($this->config['vender_path'] . $className)){} mkdir($this->config['vender_path'] . $className);
				foreach ($object['methods'] as $method => $annotion) {

					$this->method = $method;
					if($this->config['is_generate']){
                        $sub_data = "##".$annotion["comment"]["ApiDescription"][0]." \n\t";
					    $sub_data .= $this->generateItemPage($sub_file, $annotion, $object['comment']['comment']['group']);
					    $this->saveTemplate($sub_file, $sub_data);
					}
					

					//上传至showdoc
					if($this->config['is_upload']){
                        $md_data = $this->generateItemPage($sub_file, $annotion, $object['comment']['comment']['group']);
					    $data = ["api_key"=>"8c991a229ebdbe67a6bad92702a78ff534422485","api_token"=>"1fb9053ae7d3dc07f34666a5c40dfac6880070744","cat_name"=>"",
					    "cat_name_sub"=>"","page_title"=>$annotion["comment"]["ApiDescription"][0],"page_content"=>$md_data,"s_number"=>""];
					    $res = $this->requestApi("https://www.showdoc.cc/server/api/item/updateByApi",$data);
					}
				}
			}
		}
		return true;
	}

	/**
	 * Based on template analysis data.
	 *
	 * @param string  $template
	 * @param array   $params
	 * 
	 * @return string $format_data
	 */
	protected function getOutputParams($template, $params, $type = "response")
	{
		$format_data = '';
		
		foreach($params as $param){
			if($type == "response"){
                $data = [
					'{{params}}'      => $param['name'],
					'{{is_selected}}' => isset($param['is_selected']) ? 'true' : 'false',
					'{{field_type}}'  => $param['type'],
					'{{field_desc}}'  => $param['description']
				];
			}else{
				$data = [
					'{{params}}'      => $param['name'],
					'{{is_selected}}' => isset($param['is_selected']) ? 'true' : 'false',
					'{{field_type}}'  => $param['type'],
					'{{field_desc}}'  => $param['description'],
					'{{field_place}}'  => $param['place']?$param['place']:"",
				];
			}
			
			$format_data .= strtr($template, $data) . "\n";
		}
		return $format_data;
	}
	
	
	/**
	 * Based on template analysis data.
	 *
	 * @param array  $annotion
	 * @param string $sub_file
	 * @param array $group
	 *
	 * @return string $subpage
	 */
	protected function generateItemPage($sub_file, $annotion, $group)
	{
		$templates = $this->getSubPageTemplate();
		
		$comment = $annotion['comment'];
		$params = $comment[$this->rule['params']];
		$return = $comment[$this->rule['return']];
		if(!$return){
		    //默认
		    $return = [
		        [
		            'name' => 'status',
		            'type' => 'integer',
		            'description' => '返回码,当且仅当获取成功时返回1 失败返回0'
		        ],
		        [
		            'name' => 'msg',
		            'type' => 'string',
		            'description' => '返回码描述'
		        ],
		        [
		            'name' => 'result',
		            'type' => 'string',
		            'description' => '返回信息'
		        ]
		    ];
		}
		$siteurl = isset($comment[$this->rule['siteurl']][0]) ? $comment[$this->rule['siteurl']][0] : "{$this->class}/{$this->method}";
		$description = $comment[$this->rule['description']][0];
		$method = isset($comment[$this->rule['method']][0]) ? $comment[$this->rule['method']][0] : 'get';
		$notice = isset($comment[$this->rule['notice']]) ? $comment[$this->rule['notice']][0] : '';
		$success_str = isset($comment[$this->rule['success']]) ? $comment[$this->rule['success']][0]['value'] : '';
		$subpage = strtr($templates['subpage'], [
		    '{{api_title}}' => $group[0]['title'],
		    '{{project}}' => $group[0]['project'],
			'{{site_url}}' => $siteurl,
			'{{description}}' => $description,
			'{{request_method}}' => $method,
			'{{notice}}' => $notice,
			'{{request_format}}' => $this->getOutputParams($templates['request_format'], $params, "request"),
			'{{return_format}}' => $this->getOutputParams($templates['return_format'], $return),
			'{{return_data}}' => $this->jsonFormatItem($success_str),
		]);
		return $subpage;
	}
	
	/**
	 * Get page template.
	 *
	 * @return array $template
	 */
	protected function getSubPageTemplate()
	{
		$ext = $this->config['template_ext'];
		return [
			'subpage' 		 => file_get_contents($this->template_path. $this->config['template']. '/subpage/subpage' . $ext),
			'request_format' => file_get_contents($this->template_path. $this->config['template']. '/subpage/request_format' . $ext),
			'return_format'  => file_get_contents($this->template_path. $this->config['template']. '/subpage/return_format'. $ext),
		];
	}
	
	/**
	 * Save template.
	 *
	 * @param string $file
	 * @param array | string $data
	 * 
	 * @return void
	 */
	protected function saveTemplate($file, $data)
	{
		$handle = fopen($file, "a+");
		if(is_array($data)){
			foreach($data as $item){
				fwrite($handle, $item);
			}
		}else{
			fwrite($handle, $data);
		}
		fclose($handle);
	}
	
	/**
	 * Get target directory.
	 *
	 * @param string   $path
	 * @return array   $this->dirs
	 */
	protected function listdirs($path)
	{
	    $this->dirs[] = "{$path}/*";
	    //TODO:
	    //$dirs = glob($filepath, GLOB_ONLYDIR);
	    // 	if(count($dirs) > 0){
	    // 		foreach ($dirs as $dir) $this->listdirs($dir);
	    // 	}
	    return $this->dirs;
	}
	
	/**
	 * Get all the annotations data.
	 *
	 * @return array $this->data
	 */
	protected function getAllAnnotations()
	{
		foreach($this->dirs as $dir){
			$this->getAnnotations($dir);
		}
		return $this->sortDoc();
	}
	
	/**
	 * Generate doc data
	 *
	 * @return array $this->data
	 */
	protected function sortDoc()
	{
		foreach($this->annotationCache as $class => $annotation){
			if(isset($annotation['class']['comment']['group'])){
				$this->data[$annotation['class']['comment']['group'][0]['name']][$class] = array(
					'comment' => $annotation['class'],
					'methods' => $annotation['methods'],
				);
				//var_dump($this->data[$annotation['class']['comment']['group'][0]['name']][$class]);
			}
		}
		return $this->data;
	}

	/**
	 * Get annotations.
	 * 
	 * @param string  $path
	 * 
	 * @return array $this->data
	 */
	protected function getAnnotations($path)
	{
		foreach(glob($path.$this->config['allowed_file'], GLOB_BRACE) as $filename){
			require_once $filename;
			$file = pathinfo($filename);
			$this->getAnnoation($file['filename']);
		}
		return $this->annotationCache;
	}
	
	/**
	 * Get annotation.
	 *
	 * @param string  $className
	 * 
	 * @return array $this->annotationCache
	 */
	protected function getAnnoation($className)
	{
		if (!isset($this->annotationCache[$className])) {
			$class = new \ReflectionClass($className);
			$this->annotationCache[$className] = $this->getClassAnnotation($class);
			$this->getMethodAnnotations($class);
		}
		return $this->annotationCache;
	}

	/**
	 * Get method annotations.
	 *
	 * @param string  $className
	 * 
	 * @return array $this->annotationCache
	 */
	protected function getMethodAnnotations($className)
	{
		//print_r($className->getMethods());
		foreach ($className->getMethods() as $object) {
			if($object->name == 'get_instance' || $object->name == $className->getConstructor()->name) continue;
			$method = new \ReflectionMethod($object->class, $object->name);
			//print_r($method);
			$this->annotationCache[$object->class]['methods'][$object->name] = $this->getMethodAnnotation($method);
		}
		//print_r($this->annotationCache);
		return $this->annotationCache;
	}
	
	/**
	 * Get method annotation.
	 *
	 * @param object  $method
	 * 
	 * @return array $this->annotationCache
	 */
	protected function getMethodAnnotation($method)
	{
	    return [
	               'comment' => self::parseAnnotations($method->getDocComment()),
	               'fileName'	=> $method->getFileName(),
	               'method_attribute' => \Reflection::getModifierNames($method->getModifiers()),
	           ];
	}
	
    /**
	 * Get class annotation.
	 *
	 * @param object  $class
	 * 
	 * @return array $this->annotationCache
	 */
	protected function getClassAnnotation($class)
	{
		return ['class' => 
		          [
			         'comment' => self::parseAnnotations($class->getDocComment()),
			         //'parentClass' => $class->getParentClass()->name,
			         'fileName'	=> $class->getFileName(),
		          ]
		      ];
	}

	/**
     * Parse annotations
     *
     * @param  string $docblock
     * 
     * @return array  parsed annotations params
     */
	private static function parseAnnotations($docblock)
	{
		$annotations = [];
		// Strip away the docblock header and footer to ease parsing of one line annotations
		$docblock = substr($docblock, 3, -2);
		if (preg_match_all('/@(?<name>[A-Za-z_-]+)[\s\t]*\((?<args>.*)\)[\s\t]*\r?$/m', $docblock, $matches)) {
			$numMatches = count($matches[0]);
			for ($i = 0; $i < $numMatches; ++$i) {
				if (isset($matches['args'][$i])) {
					$argsParts = trim($matches['args'][$i]);
					$name      = $matches['name'][$i];
					$value     = self::parseArgs($argsParts);
				} else {
					$value = [];
				}
				$annotations[$name][] = $value;
			}
		}
		return $annotations;
	}

	/**
	 * Parse individual annotation arguments
	 *
	 * @param  string $content arguments string
	 * 
	 * @return array  annotated arguments
	 */
	private static function parseArgs($content)
	{
		$data  = array();
		$len   = strlen($content);
		$i     = 0;
		$var   = '';
		$val   = '';
		$level = 1;
		$prevDelimiter = '';
		$nextDelimiter = '';
		$nextToken     = '';
		$composing     = false;
		$type          = 'plain';
		$delimiter     = null;
		$quoted        = false;
		$tokens        = array('"', '"', '{', '}', ',', '=');

		while ($i <= $len) {
			$c = substr($content, $i++, 1);

			//if ($c === '\'' || $c === '"') {
		    if ($c === '"') {
				$delimiter = $c;
				//open delimiter
				if (!$composing && empty($prevDelimiter) && empty($nextDelimiter)) {
					$prevDelimiter = $nextDelimiter = $delimiter;
					$val           = '';
					$composing     = true;
					$quoted        = true;
				} else {
					// close delimiter
					if ($c !== $nextDelimiter) {
						throw new \Exception(sprintf(
							"Parse Error: enclosing error -> expected: [%s], given: [%s]",
							$nextDelimiter, $c
						));
					}

					// validating sintax
					if ($i < $len) {
						if (',' !== substr($content, $i, 1)) {
						    var_dump($content);
							throw new \Exception(sprintf(
								"Parse Error: missing comma separator near: ...%s<--",
								substr($content, ($i-10), $i)
							));
						}
					}

					$prevDelimiter = $nextDelimiter = '';
					$composing     = false;
					$delimiter     = null;
				}
			} elseif (!$composing && in_array($c, $tokens)) {
				switch ($c) {
				    case '=':
						$prevDelimiter = $nextDelimiter = '';
						$level     = 2;
						$composing = false;
						$type      = 'assoc';
						$quoted = false;
						break;
					case ',':
						$level = 3;

						// If composing flag is true yet,
						// it means that the string was not enclosed, so it is parsing error.
						if ($composing === true && !empty($prevDelimiter) && !empty($nextDelimiter)) {
							throw new \Exception(sprintf(
								"Parse Error: enclosing error -> expected: [%s], given: [%s]",
								$nextDelimiter, $c
							));
						}

						$prevDelimiter = $nextDelimiter = '';
						break;
				    case '{':
						$subc = '';
						$subComposing = true;

						while ($i <= $len) {
							$c = substr($content, $i++, 1);

							if (isset($delimiter) && $c === $delimiter) {
								throw new \Exception(sprintf(
									"Parse Error: Composite variable is not enclosed correctly."
								));
							}

							if ($c === '}') {
								$subComposing = false;
								break;
							}
							$subc .= $c;
						}

						// if the string is composing yet means that the structure of var. never was enclosed with '}'
						if ($subComposing) {
						    throw new \Exception(sprintf(
						        "Parse Error: Composite variable is not enclosed correctly. near: ...%s'",
						        $subc
						    ));
						}

						$val = self::parseArgs($subc);
						break;
				}
			} else {
				if ($level == 1) {
					$var .= $c;
				} elseif ($level == 2) {
					$val .= $c;
				}
			}

		    if ($level === 3 || $i === $len) {
				if ($type == 'plain' && $i === $len) {
					$data = self::castValue($var);
				} else {
					$data[trim($var)] = self::castValue($val, !$quoted);
				}

				$level = 1;
				$var   = $val = '';
				$composing = false;
				$quoted = false;
			}
		}

		return $data;
	}

	private static function castValue($val, $trim = false)
	{
		if (is_array($val)) {
			foreach ($val as $key => $value) {
				$val[$key] = self::castValue($value);
			}
		} elseif (is_string($val)) {
			if ($trim) {
				$val = trim($val);
			}

			$tmp = strtolower($val);

			if ($tmp === 'false' || $tmp === 'true') {
				$val = $tmp === 'true';
			} elseif (is_numeric($val)) {
				return $val + 0;
			}

			unset($tmp);
		}

		return $val;
	}
	
	private function jsonFormatItem($str)
	{
	    if(empty($str)) return false;
	    $success_obj = json_decode(str_replace("'", '"', $str), true);
	    if(!$success_obj) return false;
	    
	    $success = $this->json_foreach($success_obj);
	    
	    return $success;
	}
	
	private function json_foreach($success_obj, $key_aray = '', $space = '')
	{
		$success = "";
		$count = count($success_obj);
		$i = 0;
	    foreach($success_obj as $key => $item){
			$i ++;
	        if(is_array($item)){
				if(is_numeric($key)){
                    $success .= "        {" . "\n\t";
					$success .= $this->json_foreach($item, $key,"        ");
					$success .= "\n\t"."        "."}". "\n\t";
				}else{
                    $success .= "    $key : [ " . "\n\t";
					$success .= $this->json_foreach($item, $key);
					$success .= "    ]";
				}
	            
	        }else{
				$success .= $space;
				if ($i == $count) {
					$success .= "    {$key} : {$item}";
				}else{
                    $success .= "    {$key} : {$item}," . "\n\t";
				}
	            
	        }
	    }
	    return $success;
	}

	private function requestApi($url, $post = '', $cookie = '', $returnCookie = 0)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_AUTOREFERER, 1);

		//重要！
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl,CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)"); //模拟浏览器代理
		//curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
		if ($post) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
		}
		if ($cookie) {
			curl_setopt($curl, CURLOPT_COOKIE, $cookie);
		}
		curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($curl);
		if (curl_errno($curl)) {
			return curl_error($curl);
		}
		curl_close($curl);
		if ($returnCookie) {
			list($header, $body) = explode("\r\n\r\n", $data, 2);
			preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
			$info['cookie'] = substr($matches[1][0], 1);
			$info['content'] = $body;
			return $info;
		} else {
			return $data;
		}
	}


}
?>