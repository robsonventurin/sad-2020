<?php
namespace dimensoes;
mysqli_report(MYSQLI_REPORT_STRICT);
require_once('Data.php');
require_once('Sumario.php');
use dimensoes\Sumario;
use dimensoes\Data;
class DimData{
   public function carregarDimData(){
      $dataAtual = date('Y-m-d');
      $sumario = new Sumario();
      try{
         $connDimensao = $this->conectarBanco('dm_comercial');
         $connComercial = $this->conectarBanco('bd_comercial');
      }catch(\Exception $e){
         die($e->getMessage());
      }
      $sqlDim = $connDimensao->prepare('select SK_data, data, ano, semestre, trimestre, bimestre, mes, semana_mes, dia
                                        from dim_data');

      $sqlDim->execute();
      $result = $sqlDim->get_result();
      if($result->num_rows === 0){//Dimensão está
         $sqlComercial = $connComercial->prepare("select * from data"); //Cria variável com comando SQL
         $sqlComercial->execute(); //Executa o comando SQL
         $resultComercial = $sqlComercial->get_result(); //Atribui à variával o resultado da consulta
         if($resultComercial->num_rows !== 0){ //Testa se a consulta retornou dados
            while($linhaData = $resultComercial->fetch_assoc()){ //Atibui à variável cada linha até o último
               $data = new Data();
               
               $data->setData($linhaData['codigo'], $linhaData['nome_data'], $linhaData['unid_medida']);
               $slqInsertDim = $connDimensao->prepare("insert into dim_data
                                                      (codigo, nome, unidade_medida)
                                                      values
                                                      (?,?,?)");
               $slqInsertDim->bind_param("iss", $data->codigo, $data->nome, $data->unidade_medida);
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
         $sqlComercial = $connComercial->prepare('select*from data');
         $sqlComercial->execute();
         $resultComercial = $sqlComercial->get_result();
         while($linhaComercial = $resultComercial->fetch_assoc()){
            $sqlDim = $connDimensao->prepare('SELECT SK_data, nome, unidade_medida
                                             FROM
                                             dim_data
                                             where
                                             codigo = ?');
            $sqlDim->bind_param('i', $linhaComercial['codigo']);
            $sqlDim->execute();
            $resultDim = $sqlDim->get_result();
            if($resultDim->num_rows === 0){// O data da Comercial não está na dimensional
               
               $sqlInsertDim = $connDimensao->prepare("insert into dim_data
                                                      (codigo, nome, unidade_medida)
                                                      values
                                                      (?,?,?)");
               $sqlInsertDim->bind_param("iss", $linhaComercial['codigo'], $linhaComercial['nome_data'], $linhaComercial['unid_medida']);
               $sqlInsertDim->execute();
               $sumario->setQuantidadeInclusoes();
            }else{ // O data da comercial já está na dimensional, verificamos se está tudo ok ou damos um update no banco
               $actual = $resultDim->fetch_assoc();
               

               if (!$this->strIgual($actual['nome'], $linhaComercial['nome_data']) || 
                  !$this->strIgual($actual['unidade_medida'], $linhaComercial['unid_medida'])
               ) {

                  $sqlInsertDim = $connDimensao->prepare("UPDATE dim_data
                                                         SET nome=?, unidade_medida=? WHERE codigo=?");
                  $sqlInsertDim->bind_param("ssi", $linhaComercial['nome_data'], $linhaComercial['unid_medida'], $linhaComercial['codigo']);
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