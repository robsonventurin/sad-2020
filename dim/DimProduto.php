<?php
namespace dimensoes;
mysqli_report(MYSQLI_REPORT_STRICT);
require_once('Produto.php');
require_once('Sumario.php');
use dimensoes\Sumario;
use dimensoes\Produto;
class DimProduto{
   public function carregarDimProduto(){
      $dataAtual = date('Y-m-d');
      $sumario = new Sumario();
      try{
         $connDimensao = $this->conectarBanco('dm_comercial');
         $connComercial = $this->conectarBanco('bd_comercial');
      }catch(\Exception $e){
         die($e->getMessage());
      }
      $sqlDim = $connDimensao->prepare('select SK_produto, codigo, nome, unidade_medida
                                        from dim_produto');

      $sqlDim->execute();
      $result = $sqlDim->get_result();
      if($result->num_rows === 0){//Dimensão está
         $sqlComercial = $connComercial->prepare("select * from produto"); //Cria variável com comando SQL
         $sqlComercial->execute(); //Executa o comando SQL
         $resultComercial = $sqlComercial->get_result(); //Atribui à variával o resultado da consulta
         if($resultComercial->num_rows !== 0){ //Testa se a consulta retornou dados
            while($linhaProduto = $resultComercial->fetch_assoc()){ //Atibui à variável cada linha até o último
               $produto = new Produto();
               
               $produto->setProduto($linhaProduto['codigo'], $linhaProduto['nome_produto'], $linhaProduto['unid_medida']);
               $slqInsertDim = $connDimensao->prepare("insert into dim_produto
                                                      (codigo, nome, unidade_medida)
                                                      values
                                                      (?,?,?)");
               $slqInsertDim->bind_param("iss", $produto->codigo, $produto->nome, $produto->unidade_medida);
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
         $sqlComercial = $connComercial->prepare('select*from produto');
         $sqlComercial->execute();
         $resultComercial = $sqlComercial->get_result();
         while($linhaComercial = $resultComercial->fetch_assoc()){
            $sqlDim = $connDimensao->prepare('SELECT SK_produto, nome, unidade_medida
                                             FROM
                                             dim_produto
                                             where
                                             codigo = ?');
            $sqlDim->bind_param('i', $linhaComercial['codigo']);
            $sqlDim->execute();
            $resultDim = $sqlDim->get_result();
            if($resultDim->num_rows === 0){// O produto da Comercial não está na dimensional
               
               $sqlInsertDim = $connDimensao->prepare("insert into dim_produto
                                                      (codigo, nome, unidade_medida)
                                                      values
                                                      (?,?,?)");
               $sqlInsertDim->bind_param("iss", $linhaComercial['codigo'], $linhaComercial['nome_produto'], $linhaComercial['unid_medida']);
               $sqlInsertDim->execute();
               $sumario->setQuantidadeInclusoes();
            }else{ // O produto da comercial já está na dimensional, verificamos se está tudo ok ou damos um update no banco
               $actual = $resultDim->fetch_assoc();
               

               if (!$this->strIgual($actual['nome'], $linhaComercial['nome_produto']) || 
                  !$this->strIgual($actual['unidade_medida'], $linhaComercial['unid_medida'])
               ) {

                  $sqlInsertDim = $connDimensao->prepare("UPDATE dim_produto
                                                         SET nome=?, unidade_medida=? WHERE codigo=?");
                  $sqlInsertDim->bind_param("ssi", $linhaComercial['nome_produto'], $linhaComercial['unid_medida'], $linhaComercial['codigo']);
                  $sqlInsertDim->execute();
                  $sumario->setQuantidadeAlteracoes();
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