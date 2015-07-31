<?php


function doRequest($url, $method, $proxy = array(), $headers = array(), $values= array(), $isJson = false, $debug = false) {
    
    $output = array('headers' => '', 'content' => '');
    if ( function_exists ( 'curl_init' ) )
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT       , 20);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); # don't check SSL certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); # don't check SSL certificate        

        // http://www.copernica.com/en/support/rest/example-get-post-put-and-delete-requests
        switch ($method)
        {
            case 'POST':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                //http://davidwalsh.name/curl-post
                //url-ify the data for the POST
                if ( !$isJson ) {
                    $values_string = '';
                    if(is_array($values)) {
                        foreach($values as $key=>$value) { $values_string .= $key.'='.urlencode($value).'&'; }
                        rtrim($values_string, '&');
                    }
                    else { $values_string = $values; }
                }
                else {
                    $values_string = json_encode($values);
                    $headers = array_merge_recursive ( $headers, array(
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ));
                }
                
                curl_setopt($ch, CURLOPT_POST, count($values));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $values_string);
                
                $headers = array_merge_recursive ( $headers, array(                                                                           
                    'Content-Length: ' . strlen($values_string) 
                ));
                break;
                
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                //http://davidwalsh.name/curl-post
                //url-ify the data for the POST
                if ( !$isJson ) {
                    if(is_array($values)) {
                        foreach($values as $key=>$value) { $values_string .= $key.'='.urlencode($value).'&'; }
                        rtrim($values_string, '&');
                    }
                    else { $values_string = $values; }
                }
                else {
                    $values_string = json_encode($values);
                    $headers = array_merge_recursive ( $headers, array(
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ));                    
                }
                curl_setopt($ch, CURLOPT_POST, count($values));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $values_string);
                $headers = array_merge_recursive ( $headers, array(                                                                 
                    'Content-Length: ' . strlen($values_string) 
                ));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                break;
        }
        
        if (!empty($headers))
        {
            if ( TRUE === $debug ) {
                print_r($headers);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);            
        }
        
        // proxy
        if (!empty($proxy))
        {
            if ( TRUE === $debug ) {
                print_r($proxy);
            }
            if ( isset($proxy['ip']) && isset($proxy['port']) && isset($proxy['isTor'] ))
            {
                curl_setopt($ch, CURLOPT_PROXY, $proxy['ip'] . ':' . $proxy['port']);    
            }
            // Tor proxy
            if ( isset($proxy['isTor'])  && $proxy['isTor'] === true )
                curl_setopt($ch, CURLOPT_PROXYTYPE, 7);
            
        }

        $output['content'] = curl_exec ($ch);
        $err     = curl_errno($ch); 
        $errmsg  = curl_error($ch) ; 
        $header  = curl_getinfo($ch);
        curl_close($ch);
        
        if(isset($err) && $err > 0){ echo '<pre>Curl error: ' . $err . ' ' . $errmsg . ' - headers de respuesta: ' . print_r($header, true) . '</pre>';}
        else {
            if ( TRUE === $debug ) print_r($header);
            
            // extracting headers
            if ( !empty($output['content'])) {
                $x = explode("\r\n", $output['content']);
                $h = array();
                $con = '';
                $pos = 100;
                foreach($x as $c=>$v) {
                    if(empty($v)) $pos = $v;
                    if ($c < $pos) { 
                        if ($c == 0) $h['request'] = $v; 
                        else {
                            $tt = explode(':', $v);
                            $h[$tt[0]] = $tt[1];
                        }
                    }
                    else { $con .= $v;}
                }
                $output['headers'] = $h;
                $output['content'] = $con;
                // gzipped content
                if ( isset($output['headers']['Content-Encoding']) && trim($output['headers']['Content-Encoding']) == 'gzip') {
                    $output['content'] = gzdecode ($output['content']);
                }
            }
        }        
    }
    else { 
        // try with other connection type: fsockopen(), file_get_contents(), ect
        echo 'curl_init doesnt exists.';
    }
    return $output;
}


function sendReportByEmail( $fromEmail, $toEmail, $subject, $body, $isHtml=false)
{
	
	if(empty($fromEmail) or is_null($fromEmail)) return;
	if(empty($toEmail) or is_null($toEmail)) return;
	if(empty($subject) or is_null($subject)) return;
	
	$headers = array(
		'From: ' . strip_tags($fromEmail),
		'Reply-To: ' . strip_tags($fromEmail),
		'Bcc: ' . strip_tags($toEmail),
		'Subject: ' . strip_tags($subject),
		'X-Mailer: PHP/' . phpversion(),
		'MIME-Version: 1.0',
	);
    if($isHtml) {
        $headers = array_merge(
            $headers,
            array( 'Content-type: text/html; charset=iso-8859-1' )
        );
    }
	$parameters = '';
	//$message = wordwrap($body, 5000, "\r\n");
	$message = $body;
	mail($fromEmail, strip_tags($subject) , $message, implode("\r\n", $headers), $parameters);
}
