<?php
    //print_r($_POST);

    include '../config/db.php';

    function validarCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) return false;
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) $d += $cpf[$c] * (($t + 1) - $c);
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }
        return true;
    }
    
    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $id = $_GET['id'] ?? null;
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $cpf = $_POST['cpf'];
        $cep = $_POST['cep'];
        $endereco = $_POST['endereco'];
        $cidade = $_POST['cidade'];
        $estado = $_POST['estado'];

        if(!validarCPF($cpf)){
            die('CPF invalido');
        }

        // $sql = "INSERT INTO pessoa (nome, email, cpf, cep, endereco, cidade, estado)
        //  VALUES
        //  (?, ?, ?, ?, ?, ?, ?);
        // ";
        // $stmt = $conn->prepare($sql);
        // $stmt->bind_param("sssssss", $nome, $email, $cpf, $cep, $endereco, $cidade, $estado);
        // $stmt->execute();
        // $stmt->close();

         if($id){

            $sql = "UPDATE pessoa SET
             nome='$nome', 
             email='$email', 
             cpf='$cpf', 
             cep='$cep', 
             endereco='$endereco', 
             cidade='$cidade', 
             estado='$estado'
             WHERE id='$id'
            ";

        } else {
            $sql = "INSERT INTO pessoa (nome, email, cpf, cep, endereco, cidade, estado)
            VALUES
            ('$nome', '$email', '$cpf', '$cep', '$endereco', '$cidade', '$estado');
            ";
        }
   
        if($conn->query($sql) === TRUE){
            header("Location: index.php");
            exit;
        } else {
            echo 'Error: ' . $conn->error;
        }

        $conn->close();


    
      

    }

?>