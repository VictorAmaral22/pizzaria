<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pizzas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php
$db = new SQLite3("pizzaria.db");
$db->exec("PRAGMA foreign_keys = ON");

function url($campo, $valor) {
	$result = array();
	if (isset($_GET["comanda"])) $result["comanda"] = "comanda=".$_GET["comanda"];
	if (isset($_GET["orderby"])) $result["orderby"] = "orderby=".$_GET["orderby"];
	if (isset($_GET["offset"])) $result["offset"] = "offset=".$_GET["offset"];
	$result[$campo] = $campo."=".$valor;
	return("letraG.php?".strtr(implode("&", $result), " ", "+"));
}
if(isset($_GET['comanda'])){
    if(!preg_match("#^[0-9]{1,}$#", $_GET["comanda"])){
        echo "Escreva uma comanda válida!<br>";
    } else {
        $exists = $db->query("select count(*) as qtd from pizza where comanda = ".$_GET['comanda']);
        $tmp;
        while ($row = $exists->fetchArray()) {
            $tmp = $row['qtd'];
        }
        if($tmp){
            
            $parameters = array();
            if (isset($_GET["orderby"])) $parameters[] = "orderby=".$_GET["orderby"];
            if (isset($_GET["offset"])) $parameters[] = "offset=".$_GET["offset"];
            
            echo "<h1>Pizzas da Comanda ".$_GET["comanda"]."</h1>\n";
            echo "<table border=\"1\">\n";
            echo "<tr>\n";
            echo "<td><b>Tamanho</b><a href=\"".url("orderby", "tamanho+asc")."\">&#x25BE;</a> <a href=\"".url("orderby", "tamanho+desc")."\">&#x25B4;</a></td>\n";
            echo "<td><b>Borda</b><a href=\"".url("orderby", "borda+asc")."\">&#x25BE;</a> <a href=\"".url("orderby", "borda+desc")."\">&#x25B4;</a></td>\n";
            echo "<td><b>Sabores</b></td>\n";
            echo "<td><b>Valor</b> <a href=\"".url("orderby", "valor+asc")."\">&#x25BE;</a> <a href=\"".url("orderby", "valor+desc")."\">&#x25B4;</a></td>\n";
            echo "</tr>\n";
            
            $where = array("pizza.comanda = ".$_GET["comanda"]);
            $where = (count($where) > 0) ? "where ".implode(" and ", $where) : "";
            
            $orderby = (isset($_GET["orderby"])) ? $_GET["orderby"] : "pizza.codigo asc";
            
            $results = $db->query("select case 
            when pizza.tamanho = \"P\" then \"PEQUENA\"
            when pizza.tamanho = \"M\" then \"MÉDIA\"
            when pizza.tamanho = \"G\" then \"GRANDE\"
            when pizza.tamanho = \"F\" then \"FAMÍLIA\"
            end as tamanho, borda.nome as borda, group_concat(sabor.nome, ', ') as sabores, tmp2.preco as valor, tmp3.total as total, tmp.qtd as qtd
            from pizza 
            join pizzasabor on pizza.codigo = pizzasabor.pizza
            join sabor on sabor.codigo = pizzasabor.sabor
            join (select pizza.comanda as comanda, count(*) as qtd from pizza group by pizza.comanda) as tmp on tmp.comanda = pizza.comanda
            join (select comanda.numero as comanda, pizza.codigo as pizza,
            max(case
            when borda.preco is null then 0
            else borda.preco
            end+precoportamanho.preco) as preco
            from comanda
            join pizza on pizza.comanda = comanda.numero
            join pizzasabor on pizzasabor.pizza = pizza.codigo
            join sabor on pizzasabor.sabor = sabor.codigo
            join precoportamanho on precoportamanho.tipo = sabor.tipo and precoportamanho.tamanho = pizza.tamanho
            left join borda on pizza.borda = borda.codigo
            group by comanda.numero, pizza.codigo) as tmp2 on tmp2.pizza = pizza.codigo
            join (
                select tmp.numero as comanda, sum(tmp.preco) as total from
                (select comanda.numero, pizza.codigo,
                max(case
                when borda.preco is null then 0
                else borda.preco
                end+precoportamanho.preco) as preco
                        from comanda
                        join pizza on pizza.comanda = comanda.numero
                            join pizzasabor on pizzasabor.pizza = pizza.codigo
                            join sabor on pizzasabor.sabor = sabor.codigo
                            join precoportamanho on precoportamanho.tipo = sabor.tipo and precoportamanho.tamanho = pizza.tamanho
                            left join borda on pizza.borda = borda.codigo
                            group by comanda.numero, pizza.codigo) as tmp
                            join comanda on comanda.numero = tmp.numero
                            group by tmp.numero) as tmp3 on tmp3.comanda = pizza.comanda
                            left join borda on pizza.borda = borda.codigo
                            $where
                            group by pizza.codigo
                            order by $orderby");
                            
                            $total;
                            while ($row = $results->fetchArray()) {
                                echo "<tr>\n";
                                echo "<td>".$row["tamanho"]."</td>\n";
                                echo "<td>".($row["borda"] ? $row["borda"] : "NÃO")."</td>\n";
                                echo "<td>".$row["sabores"]."</td>\n";
                                echo "<td>R$ ".$row["valor"]."</td>\n";
                                echo "</tr>\n";
                                $total = $row["total"];
                            }
                            echo "<tr>\n";
                            echo "<td colspan=3><b>Total</b></td>\n";
                            echo "<td><b>R$ ".$total."</b></td>\n";
                            echo "</tr>\n";
                            echo "</table>\n";
                            echo "<br>\n";
                        } else {
                            echo "Esta comanda não existe ou não tem pizzas!<br>";
                        }
                    }
                } else {
                    echo "Informe uma comanda!<br>";
                }
$db->close();

?>
<button><a href="letraD.php" class="link">Voltar</a></button>

</body>
</html>