<?php

    include '../../config/db.php';

    //obter método e dados
    $method = $_SERVER['REQUEST_METHOD'];
    //Rota api
    switch($method){
        case 'GET':
            //all
            $sql = "SELECT * FROM pessoa";
            if(isset($_GET['id'])){
                $id = $_GET['id'];
                //filter
                $sql = "SELECT * FROM pessoa WHERE id = {$id}";
            }
            $result = mysqli_query($conn, $sql);
            $pessoa = [];
            
            while($row = mysqli_fetch_assoc($result)){
                $pessoa[] = $row;
                if(isset($_GET['id'])){
                    $pessoa = $row;
                }
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($pessoa);

        break;
    }




?>