<?php
namespace dimensoes;
mysqli_report(MYSQLI_REPORT_STRICT);
require_once('Cliente.php');
require_once('Sumario.php');
use dimensoes\Sumario;
use dimensoes\Cliente;
class DimCliente{
   public function carregarDimCliente(){
      $dataAtual = date('Y-m-d');
      $sumario = new Sumario();
      try{
         $connDimensao = $this->conectarBanco('dm_comercial');
         $connComercial = $this->conectarBanco('bd_comercial');
      }catch(\Exception $e){
         die($e->getMessage());
      }
      $sqlDim = $connDimensao->prepare('select SK_cliente, cpf, nome, sexo, idade, rua, bairro,
                                        cidade, uf
                                        from dim_cliente');
      $sqlDim->execute();
      $result = $sqlDim->get_result();
      if($result->num_rows === 0){//Dimensão está
         $sqlComercial = $connComercial->prepare("select * from cliente"); //Cria variável com comando SQL
         $sqlComercial->execute(); //Executa o comando SQL
         $resultComercial = $sqlComercial->get_result(); //Atribui à variával o resultado da consulta
         if($resultComercial->num_rows !== 0){ //Testa se a consulta retornou dados
            while($linhaCliente = $resultComercial->fetch_assoc()){ //Atibui à variável cada linha até o último
               $cliente = new Cliente();
               $cliente->setCliente($linhaCliente['cpf'], $linhaCliente['nome'], $linhaCliente['sexo'],
               $linhaCliente['idade'], $linhaCliente['rua'], $linhaCliente['bairro'], $linhaCliente['cidade'], $linhaCliente['uf']);
               $slqInsertDim = $connDimensao->prepare("insert into dim_cliente
                                                      (cpf, nome, sexo, idade, rua, bairro, cidade, uf, data_ini)
                                                      values
                                                      (?,?,?,?,?,?,?,?,?)");
               $slqInsertDim->bind_param("sssisssss", $cliente->cpf, $cliente->nome, $cliente->sexo,
                                          $cliente->idade, $cliente->rua, $cliente->bairro,$cliente->cidade,
                                          $cliente->uf, $dataAtual);
               $slqInsertDim->execute();
               $sumario->setQuantidadeInclusoes();
            }
            $sqlComercial->close();
            $sqlDim->close();
            $slqInsertDim->close();
            $connComercial->close();
            $connDimensao->close();
         }
      }else{//Dimensão já contém dados
         $sqlComercial = $connComercial->prepare('select*from cliente');
         $sqlComercial->execute();
         $resultComercial = $sqlComercial->get_result();
         while($linhaComercial = $resultComercial->fetch_assoc()){
            $sqlDim = $connDimensao->prepare('SELECT SK_cliente, nome, cpf, sexo, idade,
                                             rua, bairro, cidade, uf
                                             FROM
                                             dim_cliente
                                             where
                                             cpf = ?
                                             and
                                             data_fim is null');
            $sqlDim->bind_param('s', $linhaComercial['cpf']);
            $sqlDim->execute();
            $resultDim = $sqlDim->get_result();
            if($resultDim->num_rows === 0){// O cliente da Comercial não está na dimensional
               $sqlInsertDim = $connDimensao->prepare('INSERT INTO dim_cliente
                                                      (cpf, nome, sexo, idade, rua, bairro,
                                                      cidade, uf, data_ini)
                                                      VALUES
                                                      (?,?,?,?,?,?,?,?,?)');
               $sqlInsertDim->bind_param('sssisssss', $linhaComercial['cpf'], $linhaComercial['nome'],
                                          $linhaComercial['sexo'],$linhaComercial['idade'],$linhaComercial['rua'],
                                          $linhaComercial['bairro'],$linhaComercial['cidade'],$linhaComercial['uf'],
                                          $dataAtual);
               $sqlInsertDim->execute();
               if($sqlInsertDim->error){
                  throw new \Exception('Erro: Cliente novo não incluso');
               }
               $sumario->setQuantidadeInclusoes();
            }else{ // O cliente da comercial já está na dimensional
               $strComercialTeste = $linhaComercial['cpf'].$linhaComercial['nome']
                                    .$linhaComercial['sexo'].$linhaComercial['idade']
                                    .$linhaComercial['rua'].$linhaComercial['bairro']
                                    .$linhaComercial['cidade'].$linhaComercial['uf'];
               $linhaDim = $resultDim->fetch_assoc();
               $strDimensionalTeste = $linhaDim['cpf'].$linhaDim['nome']
                                    .$linhaDim['sexo'].$linhaDim['idade']
                                    .$linhaDim['rua'].$linhaDim['bairro']
                                    .$linhaDim['cidade'].$linhaDim['uf'];
               if(!$this->strIgual($strComercialTeste, $strDimensionalTeste)){
                  $sqlUpdateDim = $connDimensao->prepare('UPDATE dim_cliente SET
                                                         data_fim = ?
                                                         where
                                                         SK_cliente = ?');
                  $sqlUpdateDim->bind_param('si', $dataAtual, $linhaDim['SK_cliente']);
                  $sqlUpdateDim->execute();
                  if(!$sqlUpdateDim->error){
                     $sqlInsertDim = $connDimensao->prepare('INSERT INTO dim_cliente
                                                         (cpf, nome, sexo, idade, rua, bairro, cidade, uf, data_ini)
                                                         VALUES
                                                         (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                      $sqlInsertDim->bind_param("sssisssss", $linhaComercial['cpf'], $linhaComercial['nome'],
                                                $linhaComercial['sexo'], $linhaComercial['idade'],
                                                $linhaComercial['rua'], $linhaComercial['bairro'],
                                                $linhaComercial['cidade'], $linhaComercial['uf'], $dataAtual);
                     $sqlInsertDim->execute();
                     $sumario->setQuantidadeAlteracoes();
                  }else{
                      throw new \Exception('Erro: Erro no processo de alteração!');
                  }
               }
            }
         }
      }
      return $sumario;
   }
   private function strIgual($strAtual, $strNovo){
      $hashAtual = md5($strAtual);
      $hashNovo = md5($strNovo);
      if($hashAtual === $hashNovo){
         return TRUE;
      }else{
         return FALSE;
      }
   }
   private function conectarBanco($banco){
      if(!defined('DS')){
         define('DS', DIRECTORY_SEPARATOR);
      }
      if(!defined('BASE_DIR')){
         define('BASE_DIR', dirname(__FILE__).DS);
      }
      require(BASE_DIR.'config_db.php');
      try{
         $conn = new \MySQLi($dbhost, $user, $password, $banco);
         return $conn;
      }catch(mysqli_sql_exception $e){
         throw new \Exception($e);
         die;
      }
   }
}
?>