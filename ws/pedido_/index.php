<?php
session_start();
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

require_once("../conexion.php");
require_once("../encrypted.php");
$conexion = new Conexion();

$frm = json_decode(file_get_contents('php://input'), true);

try {
  
  //  listar todos los posts o solo uno
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      $registradopor = openCypher('decrypt', $_GET['token']);
      if (isset($_GET['id'])) {
        $sql = $conexion->prepare(" select 
                                    pedi.pedi_id as id,
                                    pedi.pedi_fecha as fecha,
                                    pedi.pege_idmesero as idmesero,
                                    pedi.mesa_id as idmesa,
                                    pedi.espe_id as idestado,
                                    espe.espe_descripcion as descripcionestado,
                                    espe.espe_editablepedido as editablepedido
                                    FROM pinchetas_restaurante.pedido pedi
                                    inner join pinchetas_restaurante.estadopedido espe using (espe_id)
                                    WHERE pedi.pedi_id = ? ;");
                    							
        $sql->bindValue(1, $_GET['id']);
        $sql->execute();
        header("HTTP/1.1 200 OK");
        $result = $sql->fetch(PDO::FETCH_ASSOC);
        if ($result == false) {
          $data = (object) array();
          $data->mensaje = "No se encontró el registro.";
          header("HTTP/1.1 400 Bad Request");
          echo json_encode( $data );
          exit();
        } else {
          echo json_encode($result);
          exit();
        }
  	  } else {
        $date = date("Y-m-d");
        $sql = $conexion->prepare(" select 
                                    pedi.pedi_id as id,
                                    pedi.pedi_fecha as fecha,
                                    pedi.pege_idmesero as idmesero,
                                    pedi.mesa_id as idmesa,
                                    pedi.espe_id idestado
                                    FROM pinchetas_restaurante.pedido pedi
                                    order by pedi.pedi_fechacambio;");
        $sql->bindValue(1, $registradopor);
        $sql->bindValue(2, $date);
        $sql->execute();
        $sql->setFetchMode(PDO::FETCH_ASSOC);
        header("HTTP/1.1 200 OK");
        echo json_encode( $sql->fetchAll() );
        exit();
  	  }
  }
  // Crear un nuevo post
  else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $input = $_POST;
          
      $idmesa = $frm['idmesa'];
      $idestado = '1';
      $registradopor = openCypher('decrypt', $frm['token']);
      $date = date("Y-m-d H:i:s");
      $fecha = date("Y-m-d");
      
      $sql = "INSERT INTO 
              pinchetas_restaurante.pedido(pedi_fecha, pege_idmesero, mesa_id, espe_id, pedi_registradopor, pedi_fechacambio)
              VALUES (?, ?, ?, ?, ?, ?); ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $fecha);
      $sql->bindValue(2, $registradopor);
      $sql->bindValue(3, $idmesa);
      $sql->bindValue(4, $idestado);
      $sql->bindValue(5, $registradopor);
      $sql->bindValue(6, $date);
      $sql->execute();
      $postId = $conexion->lastInsertId();
 

    $input['id'] = $postId."x"; 
    $input['mensaje'] = "Registrado con éxito";
    header("HTTP/1.1 200 OK");
    echo json_encode($input);
    exit();
  	  
  }
  //Actualizar
  else if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
      $input = $_GET;
      
      $id = $frm['id'];
      $idestado = $frm['idestado'];
      $registradopor = openCypher('decrypt', $frm['token']);
      $date = date("Y-m-d H:i:s");
      
      if ($idestado == '5') {
          
          $sql = "CALL enviar_a_facturado_el_pedido(?,?,?,?); ";
            
          $sql = $conexion->prepare($sql);
          $sql->bindValue(1, $id);
          $sql->bindValue(2, $idestado);
          $sql->bindValue(3, $registradopor);
          $sql->bindValue(4, $date);
      } else {
          $sql = "UPDATE pinchetas_restaurante.pedido 
              SET espe_id = ?, pedi_registradopor = ?, pedi_fechacambio = ?
              WHERE pedi_id = ?; ";
            
          $sql = $conexion->prepare($sql);
          $sql->bindValue(1, $idestado);
          $sql->bindValue(2, $registradopor);
          $sql->bindValue(3, $date);
          $sql->bindValue(4, $id);
      }
      
      $result = $sql->execute();
      
      if($result) {
        $input['id'] = $result;
        $input['mensaje'] = "Actualizado con éxito";
        header("HTTP/1.1 200 OK");
        echo json_encode($input);
        exit();
  	  } else {
        $input['id'] = $result;
        $input['mensaje'] = "Error actualizando";
        header("HTTP/1.1 400 Bad Request");
        echo json_encode($input);
        exit();
  	  }
  	  
  }
  // Eliminar
  else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
      $input = $_GET;
      $id = $input['id'];
      $registradopor = openCypher('decrypt', $input['token']);

      $date = date("Y-m-d H:i:s");
      
      $sql = "CALL procedimiento_eliminar_pedido(?, ?); ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $id);
      $sql->bindValue(2, $registradopor);
      $result = $sql->execute();
      if($result) {
        $output['id'] = $postId;
        $output['mensaje'] = "Eliminado con éxito";
        header("HTTP/1.1 200 OK");
        echo json_encode($output);
        exit();
  	  } else {
        $output['id'] = $postId;
        $output['mensaje'] = "Error eliminando";
        header("HTTP/1.1 400 Bad Request");
        echo json_encode($output);
        exit();
  	  }
  }

} catch (Exception $e) {
    echo 'Excepción capturada: ', $e->getMessage(), "\n";
}

//En caso de que ninguna de las opciones anteriores se haya ejecutado
// header("HTTP/1.1 400 Bad Request");

?>