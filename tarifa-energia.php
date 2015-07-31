#!/usr/local/bin/php.ORIG.5_4 
<?php
set_time_limit(0);
error_reporting(E_ALL);

/**
Recogemos la información de energia del dia:
URL: http://dashboard.tarifaluzhora.es/
/**/
require_once dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . 'php-library.php';
require_once dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . 'tarifa-energia-database.php';


$headers = array( 
	'User-Agent=Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0',
	'Accept=text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	'Accept-Language=es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
	'Accept-Encoding=gzip, deflate',
	'Cookie=cc_cookie_decline=null; cc_cookie_accept=cc_cookie_accept',
	'Host=dashboard.tarifaluzhora.es',
	'Connection=keep-alive'
);

/**
$url = 'http://www.omie.es/informes_mercado/diario/indicadores.dat';
$request = doRequest($url, 'GET', array(), $headers, array(), false, false);
if(!isset($request['content']) || empty($request['content'])) return;
//print_r($request);
	
$lineas = explode(";", $request['content']);
$n = 5;
$lineas2 = array();
$cont = $index = 0;
foreach ($lineas as $c=>$v) {
	if($cont == $n) {
		$index++;
		$cont = 0;
	} 
	$lineas2[$index][] = $v;
	$cont++;
}
//print_r($lineas2);
$texto = trataLosDatos($lineas2);
$email = 'jorge@nosoynadie.net';
sendReportByEmail( $email, $email, 'Tarifa de la luz ' . date('G:i:s m-d-Y') , $texto);
return;
/**/


/**
para precios "reales" mejor cogerlo de http://www.esios.ree.es/Solicitar?fileName=PVPC_CURV_DD_20150711&fileType=txt&idioma=es
usada en http://www.esios.ree.es/web-publica/pvpc/ 

Proceso que usa la web para sacar los datos:

Pide http://www.esios.ree.es/Listar/FechaMaxima?NOMBRE=PVPC_DD para saber la fecha que luego debe pedir. La transforma para que sea:

de 11/07/2015 a 20150711

y la usa:  http://www.esios.ree.es/Solicitar?fileName=PVPC_CURV_DD_20150711&fileType=txt&idioma=es

Todo sale de http://www.ree.es/es/actividades/operacion-del-sistema-electrico/precio-voluntario-pequeno-consumidor-pvpc.

Incluir in enlace a la página de descarga de los datos: http://www.esios.ree.es/web-publica/pvpc/ 
o al fichero excel directo: http://www.esios.ree.es/Solicitar?fileName=PVPC_DETALLE_DD_20150711&fileType=xls&idioma=es

/**/


date_default_timezone_set('Europe/Madrid');
$headers = array( 
	//'User-Agent=Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0',
	'Accept=text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	'Accept-Language=es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
	'Accept-Encoding=gzip, deflate',
	//'Cookie=visid_incap_257780=UpysaEnSSiWlr8oSqjDJU/LhoFUAAAAAQUIPAAAAAAC27ikEkg3uYyG0aVDCLGBo; JSESSIONID=9mMTVhpVnSszLVrH5R6htFNvfLcSFZ6ny1QbbvQKW7zx3pTPqphq!-1500124049',
	'Host=www.esios.ree.es',
	'Connection=keep-alive'
);
$fecha = Ree_obtenFecha($headers);
if(empty($fecha)) $fecha = date('Ymd');

$url = 'http://www.esios.ree.es/Solicitar?fileName=PVPC_CURV_DD_'.$fecha.'&fileType=txt&idioma=es';
//$url = 'http://www.esios.ree.es/Solicitar';


$request = doRequest($url, 'GET', array(), $headers, array(), false, false);
if(!isset($request['content']) || empty($request['content'])) return;
//print_r($request);


$texto = Ree_trataDatos($fecha, $request['content']);
if(empty($texto)) return;

$emailFrom = 'jorge@nosoynadie.net';
$emailTo = 'jorge@jorgehoya.es,pedreguera7@gmail.com';
//$emailTo = 'jorge@nosoynadie,aquinadie@gmail.com';
//sendReportByEmail( $emailFrom, $emailTo, 'Tarifa de la luz ' . date('G:i:s m-d-Y') , $texto, true);


function Ree_obtenFecha($headers) {
    $url = 'http://www.esios.ree.es/Listar/FechaMaxima?NOMBRE=PVPC_DD';
    $request = doRequest($url, 'GET', array(), $headers, array(), false, false);
    if(!isset($request['content']) || empty($request['content'])) return '';
    //print_r($request);
    
    $p = new SimpleXMLElement($request['content']);
    //echo $p->elemento[0]->fecha;
    $t = array('','','');
    $t = @explode('/', $p->elemento[0]->fecha);
    return $t[2].$t[1].$t[0];
}


function Ree_trataDatos($fecha, $req) {
    if (empty($req)) return;
    $out = '';
    
    /**
    Los importes vienen en €/MWh, si se quiere en €/KWh enre 1000.
    Del Excel se pueden sacar los nombres de las demas columnas.
    /**/
    
    $lineas = json_decode($req);
	file_put_contents('data.txt', print_r($lineas, true));
    if(!empty($lineas) && isset($lineas->PVPC) && is_array($lineas->PVPC)) {
        // en MWh
        $unidad = 'Euro/MWh'; $dividor = 1;$decimales=2;
        // en kWh
        $unidad = 'Euro/kWh'; $dividor = 1000; $decimales=5;
        //print_r($lineas->PVPC[0]);
        //echo PHP_EOL . 'numero lineas = ' . count($lineas);
        //$out .= '<h3>Precios de REE para el ' . $lineas->PVPC[0]->Dia.'</h3>';
        $out .= '<html><body><table width="600px" align="center">'.
            '<caption>Precios de la electricidad, proporcionados por Red Electrica Espa&ntilde;ola, para el ' . $lineas->PVPC[0]->Dia.' expresados en ' . $unidad .'</caption>'.
            '<tr>'.
                '<th width="10%" align="center">Hora</th>'.
                '<th width="30%" align="center">Tarifa por defecto <br>(2.0 A)</th>'.
                '<th width="30%" align="center">Eficiencia 2 periodos <br>(2.0 DHA)</th>'.
                '<th width="30%" align="center">Vehiculo electrico <br>(2.0 DHS)</th>'.
            '</tr>';
        //file_put_contents ('data.txt', print_r($lineas, true));
        foreach ( $lineas->PVPC as $c=>$v){
            $out .= '<tr>'.
                '<td align="center">'.(isset($v->Hora) ? $v->Hora : '&nbsp;').'</td>'.
                '<td align="center">'.(isset($v->GEN) ? number_format(((float) str_replace(',', '.',$v->GEN) / $dividor), $decimales) : '&nbsp;').'</td>'.
                '<td align="center">'.(isset($v->NOC) ? number_format(((float) str_replace(',', '.',$v->NOC) / $dividor), $decimales) : '&nbsp;').'</td>'.
                '<td align="center">'.(isset($v->VHC) ? number_format(((float) str_replace(',', '.',$v->VHC) / $dividor), $decimales) : '&nbsp;').'</td>'.
            '</tr>';    
        }
        $out .= '</table>';
        $out .= '<p align="center">Acceda a <a href="http://www.esios.ree.es/web-publica/pvpc/">http://www.esios.ree.es/web-publica/pvpc/</a> si desea descargar los datos desde ella o directamente desde <a href="http://www.esios.ree.es/Solicitar?fileName=PVPC_DETALLE_DD_'.$fecha.'&fileType=xls&idioma=es">http://www.esios.ree.es/Solicitar?fileName=PVPC_DETALLE_DD_'.$fecha.'&fileType=xls&idioma=es</a>.</p>'.
        '</body></html>';
        file_put_contents('data.html', $out, strlen($out));
    }
    return $out;
}





function trataLosDatos($lineas) {
	if(empty($lineas)) return;
	/**
		Las 6 primeras lineas son cabeceras
	/**/
	$out = '';
	
	$fecha = (isset($lineas[0][1])) ? $lineas[0][1] : date('G:i:s m-d-Y'); 
	$index = 6;
	$out = 'Fecha: ' . $fecha;
	$min = 100;
	$max = 0;
	$media = 0;
	$unidad = 'Euro/MWh';
	for ( $i = $index; $i < count($lineas); $i++ ){
		if (isset($lineas[$i][1])) {
			$value = str_replace(',', '.', $lineas[$i][1]);
			if ( $min > floatval($value) ) $min = floatval($value);
			if ( $max < floatval($value) ) $max = floatval($value);
			$media += floatval($value);
		}
	}
	$media = $media / (count($lineas) - ($index+1));
	$out .= PHP_EOL . 'Minimo: ' . $min . ' ' . $unidad;
	$out .= PHP_EOL . 'Maximo: ' . $max. ' ' . $unidad;
	$out .= PHP_EOL . 'Media: ' . $media. ' ' . $unidad;
	$out .= PHP_EOL . PHP_EOL .'Hora' . "\t" .  'Importe ('.$unidad.')' . "\t" .'Aviso' . "\t";
	for ( $i = $index; $i < count($lineas); $i++ ){
		if (isset($lineas[$i][1])) {
			$aviso = '';
			$value = str_replace(',', '.', $lineas[$i][1]);
			
			if ( $value < $media ) {
				if ($value == $min ) $aviso = 'MOMENTO MAS BARATO';
				else $aviso = 'Por debajo de la media.'; 
			}
			else {
				if ($value == $max ) $aviso = 'MOMENTO MAS CARO';
				else $aviso = 'Por encima de la media.'; 
			}

				
			$out .= PHP_EOL . ($i-$index) . "\t" .  $lineas[$i][1] . "\t" . $aviso . "\t";
		}
	}
	
	return $out;
}