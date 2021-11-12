<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php
    $db = new SQLite3("pizzaria.db");
    $db->exec("PRAGMA foreign_keys = ON");

    if(isset($_POST['confirmar']) && $_POST['confirmar'] == 'confirmar'){
        // var_dump($_POST);
        $erros = [];
        $sabores = [];
        $results = $db-> query("select count(*) from sabor");
        $tmpSabores;
        while ($row = $results->fetchArray()) { $tmpSabores = $row[0]; }
        for($c = 1; $c <= $tmpSabores; $c++){
            if(isset($_POST['sabor'.$c])){
                if(in_array($_POST['sabor'.$c], $sabores)){
                    $erros[] = "O sabor não pode ser repetido";
                } else {
                    $sabores[] = $_POST['sabor'.$c];
                }
            }
        }
        if(!isset($_GET['comanda']) || !isset($_POST['tamanho']) || !isset($_POST['borda']) || !isset($_POST['tipos']) || $sabores == []){
            $erros[] = "Campos faltando";
        } else {
            $numero = $_GET['comanda'];
            $results = $db-> query("select numero from comanda where pago = 0");
            $tmpComanda = [];
            while ($row = $results->fetchArray()) { $tmpComanda[] = $row[0]; }
            if(!preg_match('#^[0-9]+$#', $_GET['comanda'])){
                $erros[] = "Comanda inválida";
            } else {
                if(!in_array($numero, $tmpComanda)){
                    $erros[] = "Comanda paga ou inexistente";
                }
            }
            $tamanho = $_POST['tamanho'];
            $tamanhosArray = $db-> query("select * from tamanho");
            $tmpTamanhos = [];
            while ($row = $tamanhosArray->fetchArray()) { $tmpTamanhos[$row['codigo']] = $row; }
            $tamanhosArray = $tmpTamanhos;
            $tamanhosArrayKeys = array_keys($tamanhosArray);
            if(!preg_match('#^[A-Z]+$#', $tamanho)){
                $erros[] = "Tamanho inválido";
            } else {
                if(!in_array($tamanho, $tamanhosArrayKeys)){
                    $erros[] = "Tamanho inexistente";
                }
            }
            $borda = $_POST['borda'];
            $bordasArray = $db-> query("select codigo from borda");
            $tmpBordas = [];
            while ($row = $bordasArray->fetchArray()) { $tmpBordas[] = $row[0]; }
            $bordasArray = $tmpBordas;
            if(!preg_match('#^[0-9]+$#', $borda)){
                $erros[] = "Borda inválida";
            } else {
                if(!in_array($borda, $bordasArray) && $borda != "0"){
                    $erros[] = "Borda inexistente";
                }
            }
            $tipo = $_POST['tipos'];
            $tiposArray = $db-> query("select codigo from tipo");
            $tmpTipos = [];
            while ($row = $tiposArray->fetchArray()) { $tmpTipos[] = $row[0]; }
            $tiposArray = $tmpTipos;
            if(!preg_match('#^[0-9]+$#', $tipo)){
                $erros[] = "Tipo inválido";
            } else {
                if(!in_array($tipo, $tiposArray) && $tipo != "0"){
                    $erros[] = "Tipo inexistente";
                }
            }
            $saboresArray = $db-> query("select codigo, tipo from sabor");
            $tmpSabores = [];
            while ($row = $saboresArray->fetchArray()) { $tmpSabores[$row[0]] = $row[1]; }
            $saboresArray = $tmpSabores;
            $saboresArrayKeys = array_keys($tmpSabores);
            if(!in_array("Tamanho inexistente", $erros) && !in_array("Tamanho inválido", $erros)){
                if(count($sabores) > $tamanhosArray[$tamanho]['qtdesabores']){
                    $erros[] = "A pizza tem sabores demais para o tamanho ".$tamanho;
                }
                foreach ($sabores as $sabor) {
                    if(!preg_match('#^[0-9]+$#', $sabor)){
                        $erros[] = "Sabor ".$sabor." inválido";
                    } else {
                        if(!in_array($sabor, $saboresArrayKeys) && $sabor != "0"){
                            $erros[] = "Sabor ".$sabor." inexistente";
                        } else {
                            if(!in_array("Tipo inválido", $erros) && !in_array("Tipo inexistente", $erros)){
                                // $tipo
                                if($saboresArray[$sabor] != $tipo){
                                    $erros[] = "O sabor ".$sabor." deve ser do mesmo tipo";
                                }
                            }
                        }
                    }
                }
            }
        }

        if($erros != []){
            echo "<script>";
            echo "alert('Erro: ".implode(', ', $erros)."');";
            echo "</script>";
        } else {
            $numero = $_GET['comanda'];
            $tamanho = $_POST['tamanho'];
            $borda = $_POST['borda'];
            if($borda == "0"){
                $db->exec("insert into pizza (comanda, tamanho) values (".$numero.", '".$tamanho."')");
            } else {
                $db->exec("insert into pizza (comanda, tamanho, borda) values (".$numero.", '".$tamanho."', ".$borda.")");
            }
            $pizzaId = $db->lastInsertRowID();
            foreach ($sabores as $sabor) {
                $db->exec("insert into pizzasabor (pizza, sabor) values ('".$pizzaId."', '".$sabor."')");
            }
        }
    }

    echo "<h1>Inclusão de Pizza</h1>\n";
    $ok = true;
    if(isset($_GET['comanda']) && preg_match('#^[0-9]+$#', $_GET['comanda'])){
        $comanda = $_GET['comanda'];
        $results = $db-> query("select numero, strftime('%d/%m/%Y', data) as data, 
            case 
                when strftime('%w', comanda.data) = '0' then 'Dom'
                when strftime('%w', comanda.data) = '1' then 'Seg'
                when strftime('%w', comanda.data) = '2' then 'Ter'
                when strftime('%w', comanda.data) = '3' then 'Qua'
                when strftime('%w', comanda.data) = '4' then 'Qui'
                when strftime('%w', comanda.data) = '5' then 'Sex'
                when strftime('%w', comanda.data) = '6' then 'Sáb'
            end as semana, comanda.pago as pago from comanda where numero =".$comanda); 
        $tmp0 = 0;
        while ($row = $results->fetchArray()) { $tmp0 = $row; }
        if(!$tmp0){
            $ok = false;
        }        
    } else {
        $ok = false;
    }

    if($ok){    
        $comanda = $_GET['comanda'];
        $results = $db-> query("select numero, strftime('%d/%m/%Y', data) as data, 
            case 
                when strftime('%w', comanda.data) = '0' then 'Dom'
                when strftime('%w', comanda.data) = '1' then 'Seg'
                when strftime('%w', comanda.data) = '2' then 'Ter'
                when strftime('%w', comanda.data) = '3' then 'Qua'
                when strftime('%w', comanda.data) = '4' then 'Qui'
                when strftime('%w', comanda.data) = '5' then 'Sex'
                when strftime('%w', comanda.data) = '6' then 'Sáb'
            end as semana, comanda.pago as pago from comanda where numero =".$comanda); 
        $tmp0;    
        while ($row = $results->fetchArray()) { $tmp0 = $row; }
        $comanda = $tmp0;
        echo "<form id=\"comanda\" name=\"comanda\" action=\"letraF.php?comanda=".$comanda['numero']."\" method=\"post\">";
        echo "<table>";
        echo "<tr><td>Comanda</td>";
        echo "<td>".($comanda['numero'])."</td></tr>";
    
        echo "<tr><td>Data</td>";
        echo "<td>".$comanda[2]." ".$comanda[1]."</td></tr>";
    
        echo "<tr><td>Tamanho</td>";
        echo "<td><select id=\"tamanhos\" name=\"tamanho\">";
        $tamanhos = $db->query("select * from tamanho");
        $qtdSabores = [];
        while ($row = $tamanhos->fetchArray()) { 
            echo "<option id='optionTamanho".$row['codigo']."' value='".$row['codigo']."'>".$row['nome']."</option>\n"; 
            $qtdSabores[$row['codigo']] = $row['qtdesabores'];
        }
        echo "</select></td></tr>";
    
        echo "<tr><td>Borda</td>";
        echo "<td><select id=\"borda\" name=\"borda\">";
        $tamanhos = $db->query("select * from borda");
        echo "<option id='nao' value='0'>NÃO</option>\n"; 
        while ($row = $tamanhos->fetchArray()) { 
            echo "<option id='optionTamanho".$row['codigo']."' value='".$row['codigo']."'>".$row['nome']."</option>\n"; 
        }
        echo "</select></td></tr>";
    
        echo "<tr><td>Sabor</td>";
        echo "<td><select id=\"tipos\" name=\"tipos\">";
        $tipos = $db->query("select * from tipo");
        while ($row = $tipos->fetchArray()) { 
            echo "<option id='optionTipo".$row['codigo']."' value=".$row['codigo'].">".$row['nome']."</option>\n";
        }
        echo "</select></td>";
        echo "<td>";
        while ($row = $tipos->fetchArray()) { 
            // $row[0]
            echo "<select class=\"hidden\" id=\"sabores".$row[0]."\" >";
            $sabores = $db->query("select * from sabor where tipo = ".$row[0]);
            while ($row2 = $sabores->fetchArray()) { 
                echo "<option id=\"optionSabor".$row2['codigo']."\" value='{\"id\":".$row2['codigo'].", \"nome\":\"".$row2['nome']."\"}'>".$row2['nome']."</option>\n"; 
            }
            echo "</select>";
        }
        echo "<input type=\"button\" id=\"addSabor\" value=\"+\"/>";
        echo "</td></tr>";
    
    
        echo "<tr><td>Sabores</td>";
        echo "<td><table id=\"tableSabores\" border=\"1\" name=\"tableSabores\"></table></td>";
        echo "</tr>";
        echo "</table>";
    
        echo "<input id=\"confirmar\" type=\"hidden\" name=\"confirmar\" value=\"confirmar\">";
        echo "<input type=\"button\" value=\"Inclui\" onclick=\"valid()\">";
        echo "</form>";
    } else {
        echo "<p>Informe uma comanda válida!</p>";
    }
    echo "<br>";
    echo "<button><a href=\"letraD.php\" class=\"link\">Voltar</a></button>";

?>
<script>
var saboresArray = [];
window.addEventListener('DOMContentLoaded', (event) => {
    var tipo = document.getElementById('tipos').value;
    var sabores = document.getElementById('sabores'+tipo);
    sabores.className = 'displaying';
    var add = document.getElementById('addSabor');
    add.onclick = function addSabor(){
        var tipo = document.getElementById('tipos').value;
        var sabor = document.getElementById('sabores'+tipo).value;
        console.log(sabor);
        sabor = JSON.parse(sabor);
        var table = document.getElementById('tableSabores');
        var option = document.getElementById('optionSabor'+sabor.id);
        option.remove();
        saboresArray.push(sabor.id);
        table.innerHTML += `<tr id="row${sabor.id}"><td>${sabor.nome}<input type="hidden" value="${sabor.id}" name="sabor${sabor.id}" /></td><td><button onclick="removeSabor('${sabor.id}', '${sabor.nome}')">❌</button></td></tr>`;
        var tipoSelect = document.getElementById('tipos');
        tipoSelect.disabled = true;
    }
    var tipos = document.getElementById('tipos');
    tipos.onchange = function changeTipo (){
        var tipo = document.getElementById('tipos').value;
        var saboresHidden = document.getElementById('sabores'+tipo);
        var saboresDisplay = document.getElementsByClassName('displaying');
        while (saboresDisplay && saboresDisplay.length) {
            saboresDisplay[0].className = 'hidden';
        }
        saboresHidden.className = 'displaying';
    }
});

function removeSabor(id, name){
    var table = document.getElementById('tableSabores');
    var row = document.getElementById('row'+id);
    var tipo = document.getElementById('tipos').value;
    var sabor = document.getElementById('sabores'+tipo);
    sabor.innerHTML += `<option id='optionSabor${id}' value='{ "id": "${id}", "nome": "${name}" }'>${name}</option>\n`
    row.remove();
    for(var i = 0; i < saboresArray.length; i++){ 
        if (saboresArray[i] == id) {
            saboresArray.splice(i, 1); 
        }    
    }
    if(saboresArray.length === 0){
        var tipoSelect = document.getElementById('tipos');
        tipoSelect.disabled = false;
    }
}

function valid(){
    var erros = [];
    if(saboresArray.length === 0){
        erros.push('Você precisa adicionar pelo menos um sabor');
    }
    var tamanho = document.getElementById('tamanhos').value;
    console.log(tamanho);
    console.log(saboresArray);
    if(tamanho == "P" && saboresArray.length > 1){
        erros.push('A pizza pequena precisa ter somente um sabor');
    }
    if(tamanho == "M" && saboresArray.length > 2){
        erros.push('A pizza média precisa ter até dois sabores');
    }
    if(tamanho == "G" && saboresArray.length > 3){
        erros.push('A pizza grande precisa ter até três sabores');
    }
    if(tamanho == "F" && saboresArray.length > 4){
        erros.push('A pizza família precisa ter até quatro sabores');
    }
    if(erros.length === 0){
        var tipoSelect = document.getElementById('tipos');
        tipoSelect.disabled = false;
        var form = document.getElementById('comanda');
        form.submit();
    } else {
        alert('Erro: '+erros.join(', '));
    }
}
</script>
</body>
</html>