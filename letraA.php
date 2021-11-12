<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Sabores</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<?php
$db = new SQLite3("pizzaria.db");
$db->exec("PRAGMA foreign_keys = ON");

$where = array();
$errosUrl = [];
if (isset($_GET["sabor"])) {
	if(!preg_match('#^([a-zA-Z]+)(( [a-zA-Z]+)?)+$#', $_GET["sabor"])){
		$errosUrl[] = 'Digite apenas letras para o sabor';
	} else {
		$where[] = "sabor.nome like '%".strtr($_GET["sabor"], " ", "%")."%'";
	}
}
if (isset($_GET["tipo"])) {
	if(!preg_match('#^([a-zA-Z]+)(( [a-zA-Z]+)?)+$#', $_GET["tipo"])){
		$errosUrl[] = 'Digite apenas letras para o tipo';
	} else {
		$where[] = "tipo.nome like '%".strtr($_GET["tipo"], " ", "%")."%'";
	}
}
if (isset($_GET["ingrediente"])) {
	if(!preg_match('#^([a-zA-Z]+)(( [a-zA-Z]+)?)+$#', $_GET["ingrediente"])){
		$errosUrl[] = 'Digite apenas letras para o ingrediente';
	} else {
		$where[] = "sabor.codigo in (select sabor.codigo from sabor join saboringrediente on saboringrediente.sabor = sabor.codigo join ingrediente on ingrediente.codigo = saboringrediente.ingrediente where ingrediente.nome like '%".strtr($_GET["ingrediente"], " ", "%")."%')";
	}
}
$where = (count($where) > 0) ? "where ".implode(" and ", $where) : "";

function url($campo, $valor) {
	$result = array();
	if (isset($_GET["sabor"])) $result["sabor"] = "sabor=".$_GET["sabor"];
	if (isset($_GET["tipo"])) $result["tipo"] = "tipo=".$_GET["tipo"];
	if (isset($_GET["ingrediente"])) $result["ingrediente"] = "ingrediente=".$_GET["ingrediente"];
	if (isset($_GET["orderby"])) $result["orderby"] = "orderby=".$_GET["orderby"];
	if (isset($_GET["offset"])) $result["offset"] = "offset=".$_GET["offset"];
	$result[$campo] = $campo."=".$valor;
	return("letraA.php?".strtr(implode("&", $result), " ", "+"));
}
$limit = 7;

echo "<h1>Cadastro de Sabores</h1>\n";
if(count($errosUrl) !== 0){
	echo "<p>Erro: ".implode(', ', $errosUrl)."</p>";
} else {
	echo "<select id=\"campo\" name=\"campo\">\n";
	echo "<option value=\"sabor\"".((isset($_GET["sabor"])) ? " selected" : "").">Sabor</option>\n";
	echo "<option value=\"tipo\"".((isset($_GET["tipo"])) ? " selected" : "").">Tipo</option>\n";
	echo "<option value=\"ingrediente\"".((isset($_GET["ingrediente"])) ? " selected" : "").">Ingrediente</option>\n";
	echo "</select>\n";
	
	$value = "";
	if (isset($_GET["sabor"])) $value = $_GET["sabor"];
	if (isset($_GET["tipo"])) $value = $_GET["tipo"];
	if (isset($_GET["ingrediente"])) $value = $_GET["ingrediente"];
	echo "<input type=\"text\" id=\"valor\" name=\"valor\" value=\"".$value."\" size=\"20\" pattern=\"^([a-zA-Z]+)(( [a-zA-Z]+)?)+$\"> \n";
	
	$parameters = array();
	if (isset($_GET["orderby"])) $parameters[] = "orderby=".$_GET["orderby"];
	if (isset($_GET["offset"])) $parameters[] = "offset=".$_GET["offset"];
	echo "<button id='searchBtn' onclick=\"validSearch()\">&#x1F50E;</button><br>\n";
	echo "<br>\n";
	
	echo "<table border=\"1\">\n";
	echo "<tr>\n";
	echo "<td><a href=\"letraB.php\">➕</a></td>\n";
	echo "<td><b>Sabor</b> <a href=\"".url("orderby", "sabor+asc")."\">&#x25BE;</a> <a href=\"".url("orderby", "sabor+desc")."\">&#x25B4;</a></td>\n";
	echo "<td><b>Tipo</b> <a href=\"".url("orderby", "tipo+asc")."\">&#x25BE;</a> <a href=\"".url("orderby", "tipo+desc")."\">&#x25B4;</a></td>\n";
	echo "<td><b>Ingredientes</b></td>\n";
	echo "<td></td>\n";
	echo "</tr>\n";
	
	$total = $db->query("select count(*) as total from 
		(select * from sabor 
			join tipo on sabor.tipo = tipo.codigo 
			join saboringrediente on saboringrediente.sabor = sabor.codigo 
			join ingrediente on saboringrediente.ingrediente = ingrediente.codigo 
		".$where." 
		group by sabor.codigo)")->fetchArray()["total"];
	
	
	$orderby = (isset($_GET["orderby"])) ? $_GET["orderby"] : "codigo asc";
	
	$offset = (isset($_GET["offset"])) ? max(0, min($_GET["offset"], $total-1)) : 0;
	$offset = $offset-($offset%$limit);
	
	$results = $db->query("select sabor.codigo as codigo, sabor.nome as sabor, tipo.nome as tipo, group_concat(ingrediente.nome, ', ') as ingredientes 
	from sabor 
		join tipo on sabor.tipo = tipo.codigo 
		join saboringrediente on saboringrediente.sabor = sabor.codigo 
		join ingrediente on saboringrediente.ingrediente = ingrediente.codigo 
	$where 
	group by sabor.codigo 
	order by $orderby 
	limit $limit 
	offset $offset");
	
	while ($row = $results->fetchArray()) {
		echo "<tr>\n";
		echo "<td><a href=\"letraC.php?codigo=".$row["codigo"]."\">✏️</a></td>\n";
		echo "<td>".$row["sabor"]."</td>\n";
		echo "<td>".$row["tipo"]."</td>\n";
		echo "<td>".$row["ingredientes"]."</td>\n";
		echo "<td><a href=\"excluir.php?codigo=".$row["codigo"]."\" onclick=\"return(confirm('Excluir ".$row["sabor"]."?'));\">&#x1F5D1;</a></td>\n";
		echo "</tr>\n";
	}
	
	echo "</table>\n";
	echo "<br>\n<br>";
	
	for ($page = 0; $page < ceil($total/$limit); $page++) {
		echo (($offset == $page*$limit) ? ($page+1) : "<a href=\"".url("offset", $page*$limit)."\">".($page+1)."</a>")." \n";
	}
}
$db->close();

?>
<button><a href="index.html" class="link">Voltar</a></button>
<script>
function validSearch(){
	let valor = document.getElementById('valor');
	let regExp = new RegExp(valor.pattern);
	if(regExp.test(valor.value)){
		let value = document.getElementById('valor').value.trim().replace(/ +/g, '+'); 
		let result = '".strtr(implode("&", $parameters), " ", "+")."'; 
		result = ((value != '') ? document.getElementById('campo').value+'='+value+((result != '') ? '&' : '') : '')+result; 
		location.href= 'letraA.php'+((result != '') ? '?' : '')+result;
	} else {
		alert('Faça uma pesquisa válida!');
		valor.className = 'error';
	}	
}
</script>
</body>
</html>