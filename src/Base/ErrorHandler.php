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

use DateTime;
use Exception;
use SqlFormatter;

class ErrorHandler extends Exception
{
    const ERROR_LEVEL = 'Database error';

    /**
     * ErrorHandler constructor.
     *
     * @param string $query
     * @param int    $error
     * @param array  $caller
     * @param array  $options
     *
     * @throws Exception
     */
    public function __construct($query, $error, $caller, $options = null) {
        if($options['ErrorHandler'] !== null) {
            new $options['ErrorHandler']($query, $error, $caller, $options);
        }
        else {
            if($options['HTMLError'])
                $print = $this->composeHTMLError($query, $error, $caller, $options);
            else
                $print = $this->composeTETXError($query, $error, $caller, $options);

            if($options['RaiseError']) {
                $header = (php_sapi_name() != 'cgi') ? 'HTTP/1.1 ' : 'HTTP/1.1: ';
                if(php_sapi_name() != 'cli') {
                    header($header . "500 Internal Server Error", true, 500);
                }
                if($options['PrintError']) {
                    echo($print);
                    exit();
                }
                else {
                    throw new Exception($error);
                }
            }
            if($options['PrintError']) {
                echo($print);
            }
            //throw new Exception($error);
        }
    }

    public function composeData($query, $errstr, $caller) {
        $error                = [];
        $error['error_level'] = self::ERROR_LEVEL;
        $date                 = new DateTime("now");

        $error['error_date']      = date("F j, Y, G:i:s T", $date->getTimestamp());
        $error['error_file']      = $caller[0]['file'];
        $error['error_line']      = $caller[0]['line'];
        $error['error_method']    = $caller[0]['function'];
        $error['error_statement'] = $query;
        $error['error_message']   = $errstr;
        $error['error_string']    = $errstr;
        $error['error_string']    = preg_replace("/\r/s", "", $error['error_string']);
        $error['error_string']    = preg_replace("/\n/s", '<br/>', $error['error_string']);
        $error['error_string']    = preg_replace("/ /s", "&nbsp;", $error['error_string']);

        foreach($caller as $debug) {
            $error['stack'][] = [
                'file'     => $debug['file'],
                'line'     => $debug['line'],
                'function' => $debug['function']
            ];
        }
        if(php_sapi_name() != 'cli') {
            $error['useragent'] = $_SERVER['HTTP_USER_AGENT'];
            $error['error_url'] = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $error['referer']   = $_SERVER['HTTP_REFERER'];
            $error['post_vars'] = serialize($_POST);
            $error['get_vars']  = serialize($_GET);
        }

        return $error;
    }

    public function composeHTMLError($query, $error, $caller, $options) {
        $data = $this->composeData($query, $error, $caller);

        SqlFormatter::$reserved_attributes       = 'style="color:blue; font-weight:bold;"';
        SqlFormatter::$pre_attributes            = 'style="color: #505050; "';
        SqlFormatter::$word_attributes           = 'style="color: #505050;"';
        SqlFormatter::$quote_attributes          = 'style="color: #9933FF;"';
        SqlFormatter::$number_attributes         = 'style="color: crimson;"';
        SqlFormatter::$backtick_quote_attributes = 'style="color: cyan;"';
        SqlFormatter::$boundary_attributes       = 'style="color: orange; font-weight: bold;"';
        SqlFormatter::$comment_attributes        = 'style="color: #aaa;"';
        SqlFormatter::$variable_attributes       = 'style="color: orange;"';
        SqlFormatter::$use_pre                   = false;
        extract($data);
        extract($options);
        ob_start();
        /** @noinspection PhpIncludeInspection */
        require(__DIR__ . DIRECTORY_SEPARATOR . 'ErrorHandlerTemplate.php');
        $return = ob_get_contents();
        ob_end_clean();

        return $return;
    }

    public function composeTETXError($query, $error, $caller, $options) {
        $data = $this->composeData($query, $error, $caller);

        $return = "";
        $return .= sprintf("%s\n", $data['error_message']);
        $return .= sprintf("File: %s, line: %d, method: %s\n", $data['error_file'], $data['error_line'], $data['error_method']);
        //$return .= sprintf("Date: %s\n", $data['error_date']);
        if($options['ShowErrorStatement']) {
            $return .= sprintf("Statement: %s\n", $data['error_statement']);
        }

        return $return;
    }
}