<?php

/**
CREATE TABLE `t_esios_ree` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`dia` CHAR(10) NOT NULL,
	`hora` CHAR(5) NOT NULL,
	`gen` DECIMAL(5,2) UNSIGNED NOT NULL,
	`noc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`vhc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`cofgen` DECIMAL(20,18) UNSIGNED NOT NULL,
	`cofnoc` DECIMAL(20,18) UNSIGNED NOT NULL,
	`cofvhc` DECIMAL(20,18) UNSIGNED NOT NULL,
	`pmhgen` DECIMAL(5,2) UNSIGNED NOT NULL,
	`pmhnoc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`pmhvhc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`sahgen` DECIMAL(5,2) UNSIGNED NOT NULL,
	`sahnoc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`sahvhc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`fomgen` DECIMAL(5,2) UNSIGNED NOT NULL,
	`fomnoc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`fomvhc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`fosgen` DECIMAL(5,2) UNSIGNED NOT NULL,
	`fosnoc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`fosvhc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`intgen` DECIMAL(5,2) UNSIGNED NOT NULL,
	`intnoc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`intvhc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`pcapgen` DECIMAL(5,2) UNSIGNED NOT NULL,
	`pcapnoc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`pcapvhc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`teugen` DECIMAL(5,2) UNSIGNED NOT NULL,
	`teunoc` DECIMAL(5,2) UNSIGNED NOT NULL,
	`teuvhc` DECIMAL(5,2) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `dia` (`dia`),
	INDEX `hora` (`hora`)
)
COMMENT='Precios de la luz segun REE'
COLLATE='utf8_general_ci'
ENGINE=MyISAM;
/**/

//$nombre = "Juan";
$conn = null;
try {
	// http://codigoprogramacion.com/cursos/php-y-mysql/usar-pdo-en-php-parte-2-hacer-consultas.html
	$conn = new PDO('mysql:host=localhost;dbname=test', 'root', '');
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_CLASS);
	$resultado = null;
	try {
		//$sql = $conn->prepare('SELECT * FROM usuarios WHERE nombre = :Nombre');
		$sql = $conn->prepare('SELECT id FROM t_esioss_ree LIMIT 1');
		//$sql->execute(array('Nombre' => $nombre));
		$sql->execute();
		$resultado = $sql->fetchAll();
	}
	catch(PDOException $e){
		print_r($e->getCode());
		
		if ( $e->getCode() == '42S02') {
			die("No existe la tabla.");
		}
	}

	foreach ($resultado as $row) {
		echo $row["Id"];
	}
} catch(PDOException $e){
	//echo "ERROR: " . $e->getMessage();
    //print_r($e);
    if ( $e->getCode() == '2002') { 
        echo PHP_EOL . 'No hay motor de base de datos.';
    }
}
echo PHP_EOL . 'conn = ' . print_r($conn, true);
exit;
?>

