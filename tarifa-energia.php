<?php
set_time_limit(0);
error_reporting(E_ALL);

/**
Recogemos la información de energia del dia:
URL: http://www.esios.ree.es/web-publica/pvpc/
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


date_default_timezone_set('Europe/Madrid');
$headers = array( 
	'Accept=text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	'Accept-Language=es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
	'Accept-Encoding=gzip, deflate',
	'Host=www.esios.ree.es',
	'Connection=keep-alive'
);
$fecha = Ree_obtenFecha($headers);
if(empty($fecha)) $fecha = date('Ymd');

$url = 'http://www.esios.ree.es/Solicitar?fileName=PVPC_CURV_DD_'.$fecha.'&fileType=txt&idioma=es';

$request = doRequest($url, 'GET', array(), $headers, array(), false, false);
if(!isset($request['content']) || empty($request['content'])) return;


$texto = Ree_trataDatos($fecha, $request['content']);
if(empty($texto)) return;

$emailFrom = 'from@email.com';
$emailTo = 'to@email.com';


function Ree_obtenFecha($headers) {
    $url = 'http://www.esios.ree.es/Listar/FechaMaxima?NOMBRE=PVPC_DD';
    $request = doRequest($url, 'GET', array(), $headers, array(), false, false);
    if(!isset($request['content']) || empty($request['content'])) return '';
    $p = new SimpleXMLElement($request['content']);
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
