<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir Comanda</title>
</head>
<body>

<?php 
if (isset($_GET["comanda"])) {
	$db = new SQLite3("pizzaria.db");
	$db->exec("PRAGMA foreign_keys = ON");
    if(!preg_match('#^[0-9]+$#', $_GET['comanda'])){
        echo "Digite somente números!";
    } else {
        $numero = $db->query("select numero from comanda except select comanda from pizza group by comanda");
        $tmp = [];
        while($row = $numero->fetchArray()){
            $tmp[] = $row[0];
        }
        $numero = $tmp;
        if(in_array($_GET['comanda'], $numero)){
            echo "Comanda excluída!";
            $db->exec("delete from comanda where numero = ".$_GET["comanda"]);
        } else {
            echo "Comanda inexistente ou inválida!";
        }
    }
	$db->close();
}
?>

<script>
setTimeout(function () { window.open("letraD.php","_self"); }, 2500);
</script>

</body>
</html>
