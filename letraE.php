<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inclusão de Comanda</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<?php
$db = new SQLite3("pizzaria.db");
$db->exec("PRAGMA foreign_keys = ON");

function leapYear($year){
	return (($year % 4 == 0) && ($year % 100 != 0)) || ($year % 400 == 0);
}
function validData($date){
	$meses = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
	$mesesBi = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
	$data = $date;
	$data[0] = (int)$data[0];
	$data[1] = (int)$data[1];
	$data[2] = (int)$data[2];
	if($data[2] != 0 && $data[1] != 0 && $data[0] != 0){
		if($data[1] >= 1 && $data[1] <= 12){
			$bi = leapYear($data[0]);
			if($bi){
				if(($data[2] >= 1) && ($data[2] <= $mesesBi[$data[1]-1])){
					return true;
				} else {
					return null;
				}
			} else {
				if(($data[2] >= 1) && ($data[2] <= $meses[$data[1]-1])){
					return true;                
				} else {
					return null;
				}
			}
		} else {
			return null;
		}
	} else {
		return null;
	}
}
function validTime($hora){
	$time = $hora;
	$time[0] = (int)$time[0];
	$time[1] = (int)$time[1];
	$time[2] = (int)$time[2];
	if($time[0] > 23 || $time[1] > 59 || $time[2] > 59){
		return false;
	} else {
		return true;
	}
}

if (isset($_POST["confirmar"]) && $_POST["confirmar"] == 'confirmar') {
	$error = "";
	if(!isset($_POST['mesa']) || !isset($_POST['numero'])){
		$error .= 'Campos faltando; ';
	} else {
		$mesa = $_POST['mesa'];
		$numero = $_POST['numero'];
		if($mesa == "" || $mesa === null){
			$error .= 'Mesa não informada; ';
		} else {
			$mesas = $db->query("select codigo from mesa");
			$tmp2 = [];
			while($row = $mesas->fetchArray()){ $tmp2[] = $row[0]; }
			$mesas = $tmp2;
			if(!in_array($mesa, $mesas)){
				$error .= 'Mesa inválida; ';
			}
		}
		if($numero == "" || $numero === null){
			$error .= 'Comanda não informada; ';
		} else {
			$comandas = $db->query("select numero from comanda");
			$tmp3 = [];
			while($row = $comandas->fetchArray()){ $tmp3[] = $row[0]; }
			$comandas = $tmp3;
			if(!in_array($numero, $comandas)){
				$error .= 'Esta comanda não existe; ';
			}
		}
	}	
		
	if ($error == "") {
		$db->exec("update comanda set mesa = ".$mesa." where numero = ".$numero);
		$host  = $_SERVER['HTTP_HOST'];
        $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = 'incluirComanda.php';
        header("Location: http://$host$uri/$extra");
        exit;
	} else {
		echo "<font color=\"red\">".$error."</font>";
	}
}

$db->exec("insert into comanda (mesa) values (1)");
$comandaId = $db->lastInsertRowID();
$ultimaComanda = $db-> query("select numero, strftime('%d/%m/%Y', data) as data, 
	case 
		when strftime('%w', comanda.data) = '0' then 'Dom'
		when strftime('%w', comanda.data) = '1' then 'Seg'
		when strftime('%w', comanda.data) = '2' then 'Ter'
		when strftime('%w', comanda.data) = '3' then 'Qua'
		when strftime('%w', comanda.data) = '4' then 'Qui'
		when strftime('%w', comanda.data) = '5' then 'Sex'
		when strftime('%w', comanda.data) = '6' then 'Sáb'
	end as semana from comanda where numero =".$comandaId); 
$tmp;
while ($row = $ultimaComanda->fetchArray()) { $tmp = $row; }
$ultimaComanda = $tmp;
$data = $ultimaComanda[1];
$diaSemana = $ultimaComanda[2];

echo "<h1>Inclusão de Comandas</h1>\n";
echo "<form id=\"comanda\" name=\"comanda\" action=\"letraE.php?\" method=\"post\">";
echo "<table>";
echo "<tr>";
echo "<td>Número</td>";
echo "<td>".($ultimaComanda[0])."</td>";
echo "</tr>";
echo "<tr>";
echo "<td>Data</td>";
echo "<td>".$diaSemana." ".$data."</td>";
echo "</tr>";
echo "<tr>";
echo "<td>Mesa</td>";
echo "<td><select id=\"mesa\" name=\"mesa\" onclick=\"unsetError(this)\">";
$mesas = $db->query("select * from mesa");
while ($row = $mesas->fetchArray()) { echo "<option id='option".$row['codigo']."' value='".$row['codigo']."'>".$row['nome']."</option>\n"; }
echo "</select></td>";
echo "</tr>";
echo "</table>";
echo "<input id=\"numero\" type=\"hidden\" name=\"numero\" value=\"".$ultimaComanda[0]."\" >";
echo "<input id=\"confirmar\" type=\"hidden\" name=\"confirmar\" value=\"confirmar\">";
echo "<input type=\"button\" value=\"Inclui\" onclick=\"valid()\">";
echo "</form>";

echo "<br>";
echo "<button><a href=\"letraD.php\" class=\"link\">Voltar</a></button>";

$db->close();
?>

<!-- validação js front -->
<script>
	function valid(){
		var form = document.getElementById('comanda');
		form.submit();
	}
	function unsetError(self){
		self.className = '';
	}
</script>
</body>
</html>