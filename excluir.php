<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir</title>
</head>
<body>

<?php 
if (isset($_GET["codigo"])) {
	$db = new SQLite3("pizzaria.db");
	$db->exec("PRAGMA foreign_keys = ON");

    if(!preg_match('#^[0-9]+$#', $_GET['codigo'])){
        echo "Digite somente números!";
    } else {
        $saboresCod = $db->query("select codigo from sabor");
        $tmp = [];
        while($row = $saboresCod->fetchArray()){
            $tmp[] = $row[0];
        }
        $saboresCod = $tmp;
        if(in_array($_GET['codigo'], $saboresCod)){
            $db->exec("delete from saboringrediente where sabor = ".$_GET["codigo"]);
            $db->exec("delete from pizzasabor where sabor = ".$_GET["codigo"]);
            $db->exec("delete from sabor where codigo = ".$_GET["codigo"]);
            echo "Sabor excluído!";
        } else {
            echo "Sabor inexistente!";        
        }

    }
	$db->close();
}
?>

<script>
setTimeout(function () { window.open("letraA.php","_self"); }, 2500);
</script>

</body>
</html>
