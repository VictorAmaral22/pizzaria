<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inclusão de Sabor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php
    // var_dump($_POST);
    $db = new SQLite3("pizzaria.db");
    $db->exec("PRAGMA foreign_keys = ON");
    $nome;
    $tipo;
    $ingredientes = [];
    $erros = 0;
    $errorMsg = '';
    if(isset($_POST['nome']) && isset($_POST['tipo'])){
        $nome = $_POST['nome'];
        $nome = strtoupper($nome);        
        $tipo = $_POST['tipo'];
        $nomesCadast = $db->query("select nome from sabor");
        $tiposCadast = $db->query("select codigo from tipo");
        $tmp = [];
        $tmp2 = [];
        while ($row = $nomesCadast->fetchArray()) {
            $tmp[] = $row[0];
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
        if(in_array($nome, $nomesCadast)){
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
            $errorMsg .= "dados faltando!";
        }
    }
    if($erros === 0 && $_POST['confirmar'] == 'confirmar'){
        insertSabor($db, $ingredientes);
        $host  = $_SERVER['HTTP_HOST'];
        $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = 'incluir.php';
        header("Location: http://$host$uri/$extra");
        exit;
    }
    if($erros != 0 && isset($_POST['confirmar']) && $_POST['confirmar'] == 'confirmar'){
        echo "Erro: ".($errorMsg == '' ? 'dados faltando!' : $errorMsg);
    }

    function insertSabor($db, $ingredientes){
        $nome = $_POST['nome'];
        $nome = strtoupper($nome);
        $tipo = $_POST['tipo'];
        $qtd = $db->query("select count(*) as qtd from ingrediente");
        $result;
        while ($row = $qtd->fetchArray()) { $result = $row; } 
        $qtd = $result['qtd'];
        for($c = 1; $c <= $qtd; $c++){
            if(isset($_POST["ingr$c"])){
                $ingredientes[] = $_POST["ingr$c"];
            }
        }
        $db->exec("insert into sabor (nome, tipo) values ('".$nome."', '".$tipo."')");
        $saborId = $db->lastInsertRowID();
        foreach ($ingredientes as $ingred) {
            $db->exec("insert into saboringrediente (sabor, ingrediente) values ('".$saborId."', '".$ingred."')");
        }
    }
?>

<h1>Inclusão de Sabores</h1>
<form id="insert" name="insert" action="letraB.php" method="post">
    <table>
        <tr>
            <td>Nome</td>
            <td><input id="nome" type="text" name="nome" value="" size="50" pattern="^([a-zA-ZáàãâäÃÂÁÀÄéèêëÉÈÊËíìîïÍÌÎÏóòõôöÓÒÕÔÖúùûüÚÙÛÜçÇ]+)?(( [a-zA-ZáàãâäÃÂÁÀÄéèêëÉÈÊËíìîïÍÌÎÏóòõôöÓÒÕÔÖúùûüÚÙÛÜçÇ]+)?)+$" onclick="unsetError(this)"></td>
        </tr>
        <tr>
            <td>Tipo</td>
            <td>
                <select id="tipo" name="tipo">
                    <?php 
                        $tipos = $db->query("select * from tipo");
                        while ($row = $tipos->fetchArray()) { echo "<option value=\"".$row['codigo']."\">".$row['nome']."</option>\n"; }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Ingrediente</td>
            <td>
                <select id="ingrediente" onclick="unsetError(this)">
                <?php 
                    $ingredientes = $db->query("select * from ingrediente");
                    while ($row = $ingredientes->fetchArray()) { echo "<option id='option".$row['codigo']."' value='{ \"id\": \"".$row['codigo']."\", \"name\": \"".$row['nome']."\" }'\">".$row['nome']."</option>\n"; }
                ?>
                </select>
                <input type="button" id='addIngrediente' onclick='addIngr()' value="+"/>
            </td>
        </tr>
        <tr>
            <td>Ingredientes</td>
            <td><table id="tableIngr" border="1" name='tableIngr'>
            </table></td>
        </tr>
    </table>
    <input id="confirmar" type="hidden" name="confirmar" value="confirmar">
    <input type="button" value="Confirmar" onclick="valid()">
</form>

<br>
<button><a href="letraA.php" class="link">Voltar</a></button>

<script>
var ingredientes = [];
function addIngr(){
    var ingr = document.getElementById('ingrediente').value;
    ingr = JSON.parse(ingr);
    var table = document.getElementById('tableIngr');
    var option = document.getElementById('option'+ingr.id);
    option.remove();
    ingredientes.push(ingr.id);
    table.innerHTML += `<tr id="row${ingr.id}"><td>${ingr.name}<input type="hidden" value="${ingr.id}" name="ingr${ingr.id}" /></td><td><button onclick="removeIngr('${ingr.id}', '${ingr.name}')">❌</button></td></tr>`;
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
}
function valid(){
    var nome = document.getElementById('nome');
    var ingrd = document.getElementById('ingrediente');
    var erros = 0;
    if(ingredientes.length === 0){
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