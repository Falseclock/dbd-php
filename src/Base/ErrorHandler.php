<?php
/*************************************************************************************
 *   MIT License                                                                     *
 *                                                                                   *
 *   Copyright (C) 2009-2017 by Nurlan Mukhanov <nurike@gmail.com>                   *
 *                                                                                   *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy    *
 *   of this software and associated documentation files (the "Software"), to deal   *
 *   in the Software without restriction, including without limitation the rights    *
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell       *
 *   copies of the Software, and to permit persons to whom the Software is           *
 *   furnished to do so, subject to the following conditions:                        *
 *                                                                                   *
 *   The above copyright notice and this permission notice shall be included in all  *
 *   copies or substantial portions of the Software.                                 *
 *                                                                                   *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR      *
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,        *
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE     *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE   *
 *   SOFTWARE.                                                                       *
 ************************************************************************************/

namespace DBD\Base;

use Exception;
use DateTime;

class ErrorHandler extends Exception
{
	const ERROR_LEVEL = 'Database error';
	
	public function __construct($query, $error, $caller, $options = null) {
		if ($options['ErrorHandler'] !== null) {
			new $options['ErrorHandler']($query, $error, $caller, $options);
		} else {
			if ($options['HTMLError'])
				$print = $this->composeHTMLError($query, $error, $caller, $options);
			else
				$print = $this->composeTETXError($query, $error, $caller, $options);
			
			if ($options['RaiseError']) {
				
				$header = (php_sapi_name() != 'cgi') ? 'HTTP/1.1 ' : 'HTTP/1.1: ';
				header($header . "500 Internal Server Error", TRUE, 500);
				if ($options['PrintError']) {
					echo($print);
				}
				exit();
			}
			if ($options['PrintError']) {
				echo($print);
			}
			//throw new Exception($error);
		}
	}
	
	public function composeData($query, $errstr, $caller)
	{
		$error = array();
		$error['error_level'] = self::ERROR_LEVEL;
		$date = new DateTime("now");

		$error['error_date'] = date("F j, Y, G:i:s T", $date->getTimestamp());
		$error['error_file'] = $caller[0]['file'];
		$error['error_line'] = $caller[0]['line'];
		$error['error_method'] = $caller[0]['function'];
		$error['error_statement'] = $query;
		$error['error_message'] = $errstr;
		$error['error_string'] = $errstr;
		$error['error_string'] = preg_replace("/\r/","",$error['error_string']);
		$error['error_string'] = preg_replace("/\n/","<br/>",$error['error_string']);
		$error['error_string'] = preg_replace("/ /","&nbsp;",$error['error_string']);
		
		$lines = explode("\n", $query);
		foreach ($lines as $buffer) {
			$buffer = preg_replace("/\t\t/","\t",$buffer);
			$buffer = preg_replace("/\t/","&nbsp;&nbsp;",$buffer);
			$buffer = preg_replace("/&nbsp;{4}/","&nbsp;",$buffer);

			$error['context'][] = $buffer;
		}

		foreach ($caller as $debug) {
			$error['stack'][] = array 
			(
				'file' => $debug['file'], 
				'line' => $debug['line'], 
				'function' => $debug['function']
			);
		}
		$error['referer'] = $_SERVER['HTTP_REFERER'];
		$error['error_url'] = ($_SERVER["SERVER_PORT"] == 443 ? "https" : "http" )."://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	
		return $error;
	}
	
	public function composeTETXError($query, $error, $caller, $options)
	{
		$data = $this->composeData($query, $error, $caller);
		
		$return = "";
		$return .= sprintf ( "%s\n", $data['error_message'] );
		$return .= sprintf ( "File: %s, line: %d, method: %s\n", $data['error_file'], $data['error_line'], $data['error_method'] );
		$return .= sprintf ( "Date: %s\n", $data['error_date'] );
		if ($options['ShowErrorStatement']) {
			$return .= sprintf ( "Statement: %s\n", $data['error_statement'] );
		}
		return $return;
	}
	
	public function composeHTMLError($query, $error, $caller, $options)
	{
		$return = "";
		
		$data = $this->composeData($query, $error, $caller);
		
		$return .= sprintf ( "<style>\n" );
		$return .= sprintf ( ".codeError {padding: 10px 10px; background-color: white; position: absolute; float: left; width: 90%%; z-index: 1000;  }\n" );
		$return .= sprintf ( ".codeError table {border: 0; margin: 0; padding: 0; }" );
		$return .= sprintf ( ".codeError td {text-align: left; font: 10px Verdana; vertical-align: top; white-space: nowrap; margin: 0; padding: 0;}\n" );
		$return .= sprintf ( ".codeError .stack {border: 0; margin: 0; padding: 0;} " );
		$return .= sprintf ( ".codeError .stack td {color: #006600; border: 0; margin: 0; padding: 0 15px 0 0; vertical-align: top; }\n" );
		$return .= sprintf ( ".codeError .file td {color: #808080; }\n" );
		$return .= sprintf ( ".codeError .file .current {color: crimson; }\n" );
		$return .= sprintf ( ".codeError .dberror {font: 11px Courier New; color: crimson; vertical-align: top;  margin: 0; padding: 0;}\n" );
		$return .= sprintf ( "</style>" );
		
		$return .= sprintf ( "<div class='codeError'>                                                        \n" );
		$return .= sprintf ( "<table style='position: static !important; float: none !important;'>           \n" );
		$return .= sprintf ( "  <tr>                                                                         \n" );
		$return .= sprintf ( "    <td><strong>error level: </strong>&nbsp;</td>                              \n" );
		$return .= sprintf ( "    <td>{$data['error_level']}</td>                                            \n" );
		$return .= sprintf ( "  </tr>                                                                        \n" );
		$return .= sprintf ( "  <tr>                                                                         \n" );
		$return .= sprintf ( "    <td><strong>error in file: </strong>&nbsp;</td>                            \n" );
		$return .= sprintf ( "    <td>{$data['error_file']}</td>                                             \n" );
		$return .= sprintf ( "  </tr>                                                                        \n" );
		$return .= sprintf ( "  <tr>                                                                         \n" );
		$return .= sprintf ( "    <td><strong>error in file: </strong>&nbsp;</td>                            \n" );
		$return .= sprintf ( "    <td>{$data['error_line']}</td>                                             \n" );
		$return .= sprintf ( "  </tr>                                                                        \n" );
		$return .= sprintf ( "  <tr>                                                                         \n" );
		$return .= sprintf ( "    <td><strong>error string: </strong>&nbsp;</td>                             \n" );
		$return .= sprintf ( "    <td><span class='dberror'>{$data['error_string']}</span></td>              \n" );
		$return .= sprintf ( "  </tr>                                                                        \n" );
		
		if ($options['ShowErrorStatement']) {
			$return .= sprintf ( "  <tr>                                                                     \n" );
			$return .= sprintf ( "    <td><strong>faled query: </strong>&nbsp;</td>                          \n" );
			$return .= sprintf ( "    <td>                                                                   \n" );
			$return .= sprintf ( "    <table class='file'>                                                   \n" );
			$return .= sprintf ( "      <tr>                                                                 \n" );
			$return .= sprintf ( "        <td><strong>......................................</strong></td>   \n" );
			$return .= sprintf ( "      </tr>                                                                \n" );

			foreach ($data['context'] as $context) {
				$context = $this->SQLhighlight($context);
				$return .= sprintf ( "      <tr>                                                             \n" );
				$return .= sprintf ( "        <td>{$context}</td>                                            \n" );
				$return .= sprintf ( "      </tr>                                                            \n" );
			}
			
			$return .= sprintf ( "       <tr>                                                                \n" );
			$return .= sprintf ( "        <td><strong>......................................</strong></td>   \n" );
			$return .= sprintf ( "      </tr>                                                                \n" );
			$return .= sprintf ( "    </table>                                                               \n" );
			$return .= sprintf ( "    </td>                                                                  \n" );
			$return .= sprintf ( "  </tr>                                                                    \n" );
		}
		$return .= sprintf ( "  <tr>                                                                         \n" );
		$return .= sprintf ( "    <td><strong>code stack: </strong>&nbsp;</td>                               \n" );
		$return .= sprintf ( "    <td>                                                                       \n" );
		$return .= sprintf ( "    <table class='stack' cellpadding='0' cellspacing='0'>                      \n" );
		foreach ($data['stack'] as $stack) {                                                                             
			$return .= sprintf ( "      <tr>                                                                 \n" );
			$return .= sprintf ( "        <td valign='top'>{$stack['file']}</td>                             \n" );
			$return .= sprintf ( "        <td>{$stack['line']}</td>                                          \n" );
			$return .= sprintf ( "        <td>{$stack['function']}</td>                                      \n" );
			$return .= sprintf ( "      </tr>                                                                \n" );
		}
		$return .= sprintf ( "    </table>                                                                   \n" );
		$return .= sprintf ( "    </td>                                                                      \n" );
		$return .= sprintf ( "  </tr>                                                                        \n" );
		$return .= sprintf ( "  <tr>                                                                         \n" );
		$return .= sprintf ( "    <td><strong>error_url: </strong>&nbsp;</td>                                \n" );
		$return .= sprintf ( "    <td>%s</td>                                                                \n" , urldecode($data['error_url']));
		$return .= sprintf ( "  </tr>                                                                        \n" );
		$return .= sprintf ( "  <tr>                                                                         \n" );
		$return .= sprintf ( "    <td><strong>referer: </strong>&nbsp;</td>                                  \n" );
		$return .= sprintf ( "    <td>%s</td>                                                                \n" , urldecode($data['referer']));
		$return .= sprintf ( "  </tr>                                                                        \n" );
		$return .= sprintf ( "</table>                                                                       \n" );
		$return .= sprintf ( "<hr size=1>                                                                    \n" );
		$return .= sprintf ( "</div>                                                                         \n" );
		
		return $return;
	}
	
	private function SQLhighlight($sql)
	{
		$sql = preg_replace( "#(=|\+|\-|&gt;|&lt;|~|==|\!=|LIKE|NOT LIKE|REGEXP|IS NULL|NOT NULL)#i"            , "<span style='color:orange'><B>\\1</B></span>", $sql );
		$sql = preg_replace_callback( 
			"/(MAX|AVG|SUM|COUNT|MIN|date_trunc|age)\(/i", 
			function($m) { return "<span style=color:#00CC00;font-weight:bold>".strtoupper($m[1])."</span>("; },
			$sql 
		);
		$sql = preg_replace( "#\s{1,}(AND|OR)\s{1,}#i"                                               , " <span style='color:blue;font-weight:bold'>\\1</span> "    , $sql );
		$sql = preg_replace_callback( 
			"#(\s)(SET|LEFT|JOIN|WHERE|MODIFY|CHANGE|ASC|DISTINCT|IN|AS|DESC)([^\w])#i" , 
			function($m) { return "{$m[1]}<span style=color:#9933FF;font-weight:bold>".strtoupper($m[2])."{$m[3]}</span>"; },  
			$sql 
		);
		
		$sql = preg_replace( "/(LIMIT|OFFSET)/i"                                                      , "<span style='color:#FF3399;font-weight:bold'>\\1</span>" , $sql );
		$sql = preg_replace( "/(FROM|INTO|UNION|ALL|ORDER BY|GROUP BY)(\s|\R)?/i"                          , "<span style='color:#669933; font-weight:bold'>\\1</span> <span style='color:orange'>\\2</span> ", $sql );
		$sql = preg_replace( "/(SELECT|INSERT|UPDATE|DELETE|ALTER TABLE|DROP|RETURNING)(\s|\R)?/i"         , "<span style='color:blue;font-weight:bold'>\\1</span> " , $sql );

		return $sql;
	}
}
