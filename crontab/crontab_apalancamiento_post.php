<?php
include_once MDL_PATH."binance/BinanceAPI.php";
include_once MDL_PATH."bot/Operacion.php";

$opr = new Operacion();

$acciones = $opr->getAccionesPost();

if (!empty($acciones))
{
    $procStart = date('Y-m-d H:i:s');
    $msg = 'Bot Apalancamiento - Ejecutando tareas POST';
    Operacion::logBot($msg);

    $usr = new UsrUsuario();
    $opr = new Operacion();

    foreach ($acciones as $idoperacionpost => $accion)
    {
        switch ($accion['accion']) {
            case 'VENTA_PARCIAL':
                $idoperacion = $accion['idoperacion'];
                $idoperacionorden = $params['idoperacionorden'];
                

                /*
                  Condiciones:
                    Solo para la 2 compra en adelante
                    Solo si el precio llega a la compra anterior
                    
                  Proceso:
                    
                    -- EL PROCESO SE DEBE PROGRAMAR LUEGO REVISAR LAS OPERACIONES PARA QUE NO INTERFIERAN LOS DATOS
                  
                    - Eliminar la orden de venta por el total
                    - Vender la orden de compra#1 a precio MARKET
                    - Verificar que se haya vendido y Pasar a completadas la orden vendida y la venta, actualizando el pnlDate de ambas a fecha y hora actual
                    - Eliminar ordenes de compra pendientes
                    - Crear nueva compra apalancada replicando la orden de compra#1
                    - Esperara que se cree la nueva orden de venta 
                */


                break;
            
            default:
                # code...
                break;
        }
    }
}


