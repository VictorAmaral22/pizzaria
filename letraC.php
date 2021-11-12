<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alteração de Sabor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
    // var_dump($_POST);
    echo "<h1>Alteração de Sabores</h1>";
    if (isset($_GET["codigo"])) {
        $db = new SQLite3("pizzaria.db");
        $db->exec("PRAGMA foreign_keys = ON");
        $sabor = $db->query("select * from sabor where codigo = ".$_GET["codigo"]);
        $saboringrediente = $db->query("
        select ingrediente.codigo, ingrediente.nome from sabor
            join saboringrediente on saboringrediente.sabor = sabor.codigo
            join ingrediente on saboringrediente.ingrediente = ingrediente.codigo
        where sabor.codigo = ".$_GET["codigo"]);
        $tmpS = [];
        $tmpSI = [];
        while ($row = $sabor->fetchArray()) {
            $tmpS[] = $row;
        }
        while ($row = $saboringrediente->fetchArray()) {
            $tmpSI[] = $row;
        }
        $sabor = $tmpS[0];
        $saboringrediente = $tmpSI;
        $nome;
        $tipo;
        $ingredientes = [];
        $erros = 0;
        $errorMsg = '';
        if(isset($_POST['nome']) && isset($_POST['tipo'])){
            $nome = $_POST['nome'];
            $nome = strtoupper($nome);       
            $tipo = $_POST['tipo'];
            $nomesCadast = $db->query("select codigo, nome from sabor");
            $tiposCadast = $db->query("select codigo from tipo");
            $tmp = [];
            $tmp2 = [];
            while ($row = $nomesCadast->fetchArray()) {
                $tmp[$row[0]] = $row[1];
            }
            while ($row = $tiposCadast->fetchArray()) {
                $tmp2[] = $row[0];
            }
            $nomesCadast = $tmp;
            $tiposCadast = $tmp2;
            if($nome == '' || $nome == null){
                $erros++;
                $errorMsg .= 'nome inválido; ';
            }
            if(!preg_match('#^([a-zA-Z]+)?(( [a-zA-Z]+)?)+$#', $nome)){
                $erros++;
                $errorMsg .= 'nome inválido; ';
            }
            if(in_array($nome, $nomesCadast) && array_search($nome, $nomesCadast) != $_GET['codigo']){
                $erros++;
                $errorMsg .= 'nome já cadastrado; ';
            }
            if($tipo == '' || $tipo == null){
                $erros++;
                $errorMsg .= 'tipo inválido; ';
            }
            if(!preg_match('#^[0-9]+$#', $tipo)){
                $erros++;
                $errorMsg .= 'tipo inválido; ';
            }
            if(!in_array($tipo, $tiposCadast)){
                $erros++;
                $errorMsg .= 'tipo não cadastrado; ';
            }
            $qtdI = $db->query("select count(*) as qtd from ingrediente");
            $ingrCadast = $db->query("select codigo from ingrediente");
            $resultI;
            $resultII = [];
            while ($row = $qtdI->fetchArray()) { $resultI = $row[0]; } 
            $qtdI = $resultI;
            while ($row = $ingrCadast->fetchArray()) { $resultII[] = $row[0]; } 
            $ingrCadast = $resultII;
            $listaIngr = [];
            for($c = 1; $c <= $qtdI; $c++){
                if(isset($_POST["ingr$c"])){
                    if(!in_array($_POST["ingr$c"], $ingrCadast)){
                        $erros++;
                        $errorMsg .= "ingrediente $c não cadastrado; ";
                    } else {
                        if(in_array($_POST["ingr$c"], $listaIngr)){
                            $erros++;
                            $errorMsg .= "ingrediente $c repetido; ";                            
                        } else {
                            $listaIngr[] = $_POST["ingr$c"];
                        }
                    }
                }
                if($c == $qtdI && $listaIngr == []){
                    $erros++;
                    $errorMsg .= "ingredientes não informados; ";
                }
            }
        } else {
            $erros++;
            if(isset($_POST['nome']) || isset($_POST['tipo'])){
                echo "Erro: dados faltando!";
            }
        }
        if($erros === 0 && $_POST['confirmar'] == 'confirmar'){
            $nome = $_POST['nome'];
            $nome = strtoupper($nome);
            $tipo = $_POST['tipo'];
            $qtd = $db->query("select count(*) as qtd from ingrediente");
            $result;
            while ($row = $qtd->fetchArray()) { $result = $row; } 
            $qtd = $result['qtd'];
            $db->exec("delete from saboringrediente where sabor = ".$_GET['codigo']);
            for($c = 1; $c <= $qtd; $c++){
                if(isset($_POST["ingr$c"])){
                    $ingredientes[] = $_POST["ingr$c"];
                }
            }
            $db->exec("update sabor set nome = '".$nome."', tipo = '".$tipo."' where codigo = ".$_GET['codigo']);
            foreach ($ingredientes as $ingred) {
                $db->exec("insert into saboringrediente (sabor, ingrediente) values ('".$_GET['codigo']."', '".$ingred."')");
            }
            header("Refresh:0");
        }
        if($erros != 0 && isset($_POST['confirmar']) && $_POST['confirmar'] == 'confirmar'){
            echo "Erro: ".($errorMsg == '' ? 'dados faltando!' : $errorMsg);
        }
        echo "<form id=\"insert\" name=\"insert\" action=\"letraC.php?codigo=".$_GET['codigo']."\" method=\"post\">";
        echo "<table>";
        echo "<tr>";
        echo "<td>Nome</td>";
        echo "<td><input id=\"nome\" type=\"text\" name=\"nome\" value=\"".$sabor['nome']."\" size=\"50\" pattern=\"^([a-zA-ZáàãâäÃÂÁÀÄéèêëÉÈÊËíìîïÍÌÎÏóòõôöÓÒÕÔÖúùûüÚÙÛÜçÇ]+)?(( [a-zA-ZáàãâäÃÂÁÀÄéèêëÉÈÊËíìîïÍÌÎÏóòõôöÓÒÕÔÖúùûüÚÙÛÜçÇ]+)?)+$\" onclick=\"unsetError(this)\"></td>";
        echo "</tr><tr><td>Tipo</td><td>";
        echo "<select id=\"tipo\" name=\"tipo\">";
            $tipos = $db->query("select * from tipo");
            while ($row = $tipos->fetchArray()) { 
                if($sabor['tipo'] == $row['codigo']){
                    echo "<option value=\"".$row['codigo']."\" selected>".$row['nome']."</option>\n"; 
                } else {
                    echo "<option value=\"".$row['codigo']."\">".$row['nome']."</option>\n"; 
                }
            }
        echo "</select></td></tr><tr>";
        echo "<td>Ingrediente</td><td>";
        echo "<select id=\"ingrediente\" onclick=\"unsetError(this)\">";
            $ingredientes = $db->query("select * from ingrediente");  
            $ingreds = [];
            foreach ($saboringrediente as $value) {
                $ingreds[] = $value[0];
            }
            while ($row = $ingredientes->fetchArray()) { 
                if(!in_array($row['codigo'], $ingreds)){
                    echo "<option id='option".$row['codigo']."' value='{ \"id\": \"".$row['codigo']."\", \"name\": \"".$row['nome']."\" }'\">".$row['nome']."</option>\n"; 
                }
            }
        echo "</select>";
        echo "<input type=\"button\" id=\"addIngrediente\" onclick=\"addIngr()\" value=\"+\"/>";
        echo "</td></tr><tr>";
        echo "<td>Ingredientes</td>";
        echo "<td><table id=\"tableIngr\" border=\"1\" name=\"tableIngr\">";
        foreach ($saboringrediente as $value) {
            echo "<tr id=\"row".$value['codigo']."\">
                    <td>".$value['nome']."
                        <input type=\"hidden\" value=\"".$value['codigo']."\" name=\"ingr".$value['codigo']."\" />
                    </td>
                    <td>
                        <button onclick=\"removeIngr('".$value['codigo']."', '".$value['nome']."')\">❌</button>
                    </td>
                </tr>";
        }
        echo "</table></td></tr></table>";
        echo "<input id=\"confirmar\" type=\"hidden\" name=\"confirmar\" value=\"\">";
        echo "<input type=\"button\" value=\"Confirmar\" onclick=\"valid()\">";
        echo "</form>";
    } else {
        echo "<p>Informe um sabor!</p>";
    }

?>

<br>
<button><a href="letraA.php" class="link">Voltar</a></button>

<script>
var ingredientes = [];
document.addEventListener('DOMContentLoaded', (e) => {
    var table = document.getElementById('tableIngr').childNodes[0];
    // console.log(table.childNodes[0].childElementCount);
    for(let c = 0; c <= table.childElementCount-1; c++){
        var id = table.childNodes[c].id.split('row')[1];
        ingredientes.push(id);
    }    
});

function addIngr(){
    var ingr = document.getElementById('ingrediente').value;
    ingr = JSON.parse(ingr);
    var table = document.getElementById('tableIngr');
    var option = document.getElementById('option'+ingr.id);
    option.remove();
    ingredientes.push(ingr.id);
    table.innerHTML += `<tr id="row${ingr.id}"><td>${ingr.name}<input type="hidden" value="${ingr.id}" name="ingr${ingr.id}" /></td><td><button onclick="removeIngr('${ingr.id}', '${ingr.name}')">❌</button></td></tr>`;
    console.log(ingredientes);
}
function removeIngr(id, name){
    var table = document.getElementById('tableIngr');
    var row = document.getElementById('row'+id);
    var select = document.getElementById('ingrediente');
    select.innerHTML += `<option id='option${id}' value='{ "id": "${id}", "name": "${name}" }'>${name}</option>\n`
    row.remove();
    for(var i = 0; i < ingredientes.length; i++){ 
        if ( ingredientes[i] == id) {
            ingredientes.splice(i, 1); 
        }    
    }
    console.log(ingredientes);
}
function valid(){
    var nome = document.getElementById('nome');
    var ingrd = document.getElementById('ingrediente');
    var erros = 0;
    console.log(ingredientes);
    if(ingredientes.length === 0){
        console.log('aqui');
        ingrd.className = 'error';
        erros++;
    }
    if(nome.value.trim() == ''){
        nome.className = 'error';
        erros++;
    }
    var regExp = new RegExp(nome.pattern);
    if(!regExp.test(nome.value)){
        nome.className = 'error';
        erros++;
    }

    if(erros == 0){
        var confirm = document.getElementById('confirmar');
        confirm.value = 'confirmar';
        var form = document.getElementById('insert');
        var inputNome = form.nome.value;
        inputNome = inputNome.toUpperCase();
        inputNome = inputNome.split('');
        for(let c = 0; c < inputNome.length; c++) {
            switch (inputNome[c]) {
                case 'Ã':  
                    inputNome[c] = 'A'; 
                    break;
                case 'Â':  
                    inputNome[c] = 'A'; 
                    break;
                case 'Á':  
                    inputNome[c] = 'A'; 
                    break;
                case 'À':  
                    inputNome[c] = 'A'; 
                    break;
                case 'Ä':  
                    inputNome[c] = 'A'; 
                    break;
                case 'É':  
                    inputNome[c] = 'E'; 
                    break;
                case 'È':  
                    inputNome[c] = 'E'; 
                    break;
                case 'Ê':  
                    inputNome[c] = 'E'; 
                    break;
                case 'Ë':  
                    inputNome[c] = 'E'; 
                    break;
                case 'Í':  
                    inputNome[c] = 'I'; 
                    break;
                case 'Ì':  
                    inputNome[c] = 'I'; 
                    break;
                case 'Î':  
                    inputNome[c] = 'I'; 
                    break;
                case 'Ï':  
                    inputNome[c] = 'I'; 
                    break;
                case 'Ó':  
                    inputNome[c] = 'O'; 
                    break;
                case 'Ò':  
                    inputNome[c] = 'O'; 
                    break;
                case 'Õ':  
                    inputNome[c] = 'O'; 
                    break;
                case 'Ô':  
                    inputNome[c] = 'O'; 
                    break;
                case 'Ö':  
                    inputNome[c] = 'O'; 
                    break;
                case 'Ú':  
                    inputNome[c] = 'U'; 
                    break;
                case 'Ù':  
                    inputNome[c] = 'U'; 
                    break;
                case 'Û':  
                    inputNome[c] = 'U'; 
                    break;
                case 'Ü':  
                    inputNome[c] = 'U'; 
                    break;
                case 'Ç': 
                     inputNome[c] = 'C';
                     break
            }
        }
        inputNome = inputNome.join('');
        console.log(inputNome);
        form.nome.value = inputNome;
        form.submit();
    } else {
        alert('Dados inválidos!');
    }
}
function unsetError(self){
    self.className = '';
}

</script>

</body>
</html>