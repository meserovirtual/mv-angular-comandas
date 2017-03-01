<?php
session_start();


// Token

if (file_exists('../../../includes/MyDBi.php')) {
    require_once '../../../includes/MyDBi.php';
    require_once '../../../includes/utils.php';
} else {
    require_once 'MyDBi.php';
}


class Comandas extends Main
{
    private static $instance;

    public static function init($decoded)
    {
        self::$instance = new Main(get_class(), $decoded['function']);
        try {
            call_user_func(get_class() . '::' . $decoded['function'], $decoded);
        } catch (Exception $e) {

            $file = 'error.log';
            $current = file_get_contents($file);
            $current .= date('Y-m-d H:i:s') . ": " . $e . "\n";
            file_put_contents($file, $current);

            header('HTTP/1.0 500 Internal Server Error');
            echo $e;
        }
    }

    /* @name: get
     * @param
     * @description: Obtiene todos los usuario con sus direcciones.
     * todo: Sacar dirección y crear sus propias clases dentro de este mismo módulo.
     */
    function getComanda($params)
    {
        $db = self::$instance->db;
        $decoded = json_decode($params["params"]);
        $mesa_id = getDataFromToken('mesa_id');
        $session_id = getDataFromToken('session_id');
        $results = $db->rawQuery('SELECT 
    c.comanda_id,
    c.status,
    c.total,
    cd.comanda_detalle_id,
    cd.producto_id,
    p.nombre,
    cd.precio,
    cd.status,
    cd.comentarios,
    cd.cantidad,
    cd.session_id,
    ce.comanda_extra_id,
    ce.producto_id extra_id,
    pp.nombre extra,
    ce.precio extra_precio,
    ce.cantidad extra_cantidad
FROM
    comandas c
        INNER JOIN
    comandas_detalles cd ON c.comanda_id = cd.comanda_id
        LEFT JOIN
    comandas_extras ce ON cd.comanda_detalle_id = ce.comanda_detalle_id
        LEFT JOIN
    productos p ON cd.producto_id = p.producto_id
        LEFT JOIN
    productos pp ON ce.producto_id = pp.producto_id
' . (($mesa_id != null) ? ' WHERE c.mesa_id = ' . $mesa_id . ' ' : ' WHERE ""="" ') . ' AND c . status <> 5
GROUP BY c . comanda_id , c . status , cd . comanda_detalle_id , cd . producto_id , p . nombre , cd . status , cd . comentarios , cd . cantidad , ce . comanda_extra_id , ce . producto_id , pp . nombre , ce . cantidad;
');


        $final = array();
        foreach ($results as $row) {

            if (!isset($final[$row["comanda_id"]])) {
                $final[$row["comanda_id"]] = array(
                    'comanda_id' => $row["comanda_id"],
                    'status' => $row["status"],
                    'total' => $row["total"],
                    'detalles' => array()
                );
            }
            $have_det = false;
            if ($row["comanda_detalle_id"] !== null) {

                if (sizeof($final[$row['comanda_id']]['detalles']) > 0) {
                    foreach ($final[$row['comanda_id']]['detalles'] as $cat) {
                        if ($cat['comanda_detalle_id'] == $row["comanda_detalle_id"]) {
                            $have_det = true;
                        }
                    }
                } else {
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']] = array(
                        'comanda_detalle_id' => $row['comanda_detalle_id'],
                        'producto_id' => $row['producto_id'],
                        'nombre' => $row['nombre'],
                        'precio' => $row['precio'],
                        'status' => $row['status'],
                        'session_id' => $row['session_id'],
                        'comentarios' => $row['comentarios'],
                        'cantidad' => $row['cantidad']
                    );

                    $have_det = true;
                }

                if (!$have_det) {
                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']], array());
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']] = array(
                        'comanda_detalle_id' => $row['comanda_detalle_id'],
                        'producto_id' => $row['producto_id'],
                        'nombre' => $row['nombre'],
                        'precio' => $row['precio'],
                        'status' => $row['status'],
                        'session_id' => $row['session_id'],
                        'comentarios' => $row['comentarios'],
                        'cantidad' => $row['cantidad']
                    );

//                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']], array(
//                        'comanda_detalle_id' => $row['comanda_detalle_id'],
//                        'producto_id' => $row['producto_id'],
//                        'nombre' => $row['nombre'],
//                        'precio' => $row['precio'],
//                        'status' => $row['status'],
//                        'comentarios' => $row['comentarios'],
//                        'cantidad' => $row['cantidad']
//                    ));
                }
            }


            $have_ext = false;
            if ($row["comanda_extra_id"] !== null) {

                if (sizeof($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras']) > 0) {
                    foreach ($final[$row['comanda_id']]['precios'][$row['comanda_detalle_id']]['extras'] as $cat) {
                        if ($cat['comanda_extra_id'] == $row["comanda_extra_id"]) {
                            $have_ext = true;
                        }
                    }
                } else {
                    $final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras'][] = array(
                        'comanda_extra_id' => $row['comanda_extra_id'],
                        'extra_id' => $row['extra_id'],
                        'extra' => $row['extra'],
                        'precio' => $row['precio'],
                        'cantidad' => $row['cantidad']
                    );

                    $have_ext = true;
                }

                if (!$have_ext) {
                    array_push($final[$row['comanda_id']]['detalles'][$row['comanda_detalle_id']]['extras'], array(
                        'comanda_extra_id' => $row['comanda_extra_id'],
                        'extra_id' => $row['extra_id'],
                        'extra' => $row['extra'],
                        'precio' => $row['precio'],
                        'cantidad' => $row['cantidad']
                    ));
                }
            }


        }
        echo json_encode(array_values($final));
    }

    /**
     * @description Crea un comanda, sus fotos, precios y le asigna las categorias
     * @param $product
     */
    function createComanda($params)
    {

        $db = self::$instance->db;
        $decoded = self::checkComanda(json_decode($params["comanda"]));
        $SQL = 'select * from comandas where mesa_id = ' . $decoded->mesa_id . ' and status = 1';
        $result = $db->rawQuery($SQL);


        $db->startTransaction();


        if (sizeof($result) > 0) {
//            $SQL = 'update comandas set total = (select sum()) where comanda_id = '. $result.' and status = 1';


            $result = $result[0]['comanda_id'];
        } else {
            $data = array(
                'usuario_id' => $decoded->usuario_id,
                'status' => $decoded->status,
                'mesa_id' => $decoded->mesa_id,
                'total' => 0,
                'origen_id' => $decoded->origen_id
            );
            $result = $db->insert('comandas', $data);
        }

        if ($result > -1) {
//            foreach ($decoded->kits as $detalle) {

            $decoded->comanda_id = $result;
            if (!self::createDetalle($decoded, $db)) {
                $db->rollback();
                header('HTTP / 1.0 500 Internal Server Error');
                echo $db->getLastError();
                return;
            }
//            }


            $db->commit();
            header('HTTP / 1.0 200 Ok');
            echo json_encode($result);
        } else {
            $db->rollback();
            header('HTTP / 1.0 500 Internal Server Error');
            echo $db->getLastError();
        }
    }

    /**
     * @description Crea un precio para un comanda determinado
     * @param $precio
     * @param $comanda_id
     * @param $db
     * @return bool
     */
    function createDetalle($params, $db = null)
    {
        $innerCall = true;
        if ($db === null) {
            $db = self::$instance->db;
            $innerCall = false;
            $db->startTransaction();
            $decoded = self::checkDetalles(json_decode($params["detalle"]));
        } else {
            $decoded = self::checkDetalles($params);
        }


        $data = array(
            'producto_id' => $decoded->producto_id,
            'status' => 1,
            'comentarios' => $decoded->comentarios,
            'comanda_id' => $decoded->comanda_id,
            'cantidad' => $decoded->cantidad,
            'precio' => $decoded->precios[0]->precio,
            'session_id' => getDataFromToken('session_id')
        );

        $results = $db->insert('comandas_detalles', $data);


        if ($innerCall) {
            foreach ($decoded->kits as $extra) {

                $extra->comanda_detalle_id = $results;
                if ($extra->selected == 'true' && $extra->opcional == 1) {
                    if (!self::createExtra($extra, $db)) {
                        $db->rollback();
                        header('HTTP / 1.0 500 Internal Server Error');
                        echo $db->getLastError();
                        return false;
                    }
                }
            }
        } else {

            foreach ($decoded->kits as $extra) {

                $extra->comanda_detalle_id = $results;
                if ($extra->selected == true && $extra->opcional == 1) {
                    if (!self::createExtra($extra, $db)) {
                        $db->rollback();
                        header('HTTP / 1.0 500 Internal Server Error');
                        echo $db->getLastError();
                        return false;
                    }
                }
            }


            if ($results > -1) {
                $db->commit();
                header('HTTP / 1.0 200 Ok');
                echo json_encode($results);
            } else {
                $db->rollback();
                header('HTTP / 1.0 500 Internal Server Error');
                echo $db->getLastError();
            }
        }

        return $results > -1;

    }

    /**
     * @description Crea la relación entre un comanda y una categoría
     * @param $categoria
     * @param $comanda_id
     * @param $db
     * @return bool
     */
    function createExtra($params, $db = null)
    {
        $innerCall = true;
        if ($db === null) {
            $db = self::$instance->db;
            $innerCall = false;
            $db->startTransaction();
            $decoded = self::checkExtras(json_decode($params["extra"]));
        } else {

            $decoded = self::checkExtras($params);
        }


        $data = array(
            'producto_id' => $decoded->producto_id,
            'cantidad' => $decoded->cantidad,
            'comanda_detalle_id' => $decoded->comanda_detalle_id,
            'precio' => $decoded->precio
        );

        $results = $db->insert('comandas_extras', $data);

        if ($innerCall) {
            return ($results > -1) ? true : false;
        } else {

            if ($results > -1) {
                $db->commit();
                header('HTTP / 1.0 200 Ok');
                echo json_encode($results);
            } else {
                $db->rollback();
                header('HTTP / 1.0 500 Internal Server Error');
                echo $db->getLastError();
            }
        }

        return $results > -1;
    }

    /**
     * @description Modifica un comanda, sus fotos, precios y le asigna las categorias
     * @param $product
     */
    function updateComanda($params)
    {
        $db = self::$instance->db;
        $db->startTransaction();
        $decoded = self::checkComanda(json_decode($params["comanda"]));


        $db->where('comanda_id', $decoded->comanda_id);

        $data = array(
            'usuario_id' => $decoded->usuario_id,
            'status' => $decoded->status,
            'mesa_id' => $decoded->mesa_id,
            'total' => $decoded->total,
            'origen_id' => $decoded->origen_id
        );

        $result = $db->update('comandas', $data);


//        Borro extras
        $SQL = "delete from comandas_extras where comanda_detalle_id in (select comanda_detalle_id from comandas_detalles where comanda_id = " . $decoded->comanda_id . ")";
        $db->rawQuery($SQL);

//        borro detalles
        $db->where('comanda_id', $decoded->comanda_id);
        $db->delete('comandas_detalles');


        if ($result) {
            foreach ($decoded->detalles as $detalle) {

                $detalle['comanda_id'] = $result;
                if (!self::createDetalle(json_encode(array('detalle' => $detalle), $db))) {
                    $db->rollback();
                    header('HTTP / 1.0 500 Internal Server Error');
                    echo $db->getLastError();
                    return;
                }
            }

            $db->commit();
            header('HTTP / 1.0 200 Ok');
            echo json_encode($result);
        } else {
            $db->rollback();
            header('HTTP / 1.0 500 Internal Server Error');
            echo $db->getLastError();
        }
    }

    /**
     * @description Modifica un comanda, sus fotos, precios y le asigna las categorias
     * @param $product
     */
    function updatePedido($params)
    {
        $db = self::$instance->db;
        $db->startTransaction();
        $decoded = self::checkComanda(json_decode($params["comanda"]));


        $db->where('comanda_id', $decoded->comanda_id);

        $data = array(
            'status' => 2
        );

        $result = $db->update('comandas', $data);


//        Borro extras
        $SQL = "delete from comandas_extras where comanda_detalle_id in (select comanda_detalle_id from comandas_detalles where comanda_id = " . $decoded->comanda_id . ")";
        $db->rawQuery($SQL);

//        borro detalles
        $db->where('comanda_id', $decoded->comanda_id);
        $db->delete('comandas_detalles');


        if ($result) {
            foreach ($decoded->detalles as $detalle) {

                $detalle['comanda_id'] = $result;
                if (!self::createDetalle(json_encode(array('detalle' => $detalle), $db))) {
                    $db->rollback();
                    header('HTTP / 1.0 500 Internal Server Error');
                    echo $db->getLastError();
                    return;
                }
            }

            $db->commit();
            header('HTTP / 1.0 200 Ok');
            echo json_encode($result);
        } else {
            $db->rollback();
            header('HTTP / 1.0 500 Internal Server Error');
            echo $db->getLastError();
        }
    }

    /**
     * @description Modifica un comanda, sus fotos, precios y le asigna las categorias
     * @param $product
     */
    function quitar($params)
    {
        $db = self::$instance->db;
        $db->startTransaction();
        $decoded = $params["comanda_detalle_id"];

//        $SQL = "DELETE FROM comandas_extras WHERE comanda_detalle_id = " . $decoded;
        $db->where('comanda_detalle_id', $decoded);
        $result = $db->delete('comandas_extras');
        $db->where('comanda_detalle_id', $decoded);
        $result = $db->delete('comandas_detalles');
//        $result = $db->rawQuery($SQL);

//        $SQL = "DELETE FROM comandas_detalles WHERE comanda_detalle_id = " . $decoded;
//        $result = $db->rawQuery($SQL);


        if ($result) {
            $db->commit();
            header('HTTP / 1.0 200 Ok');
            echo json_encode($result);
        } else {
            $db->rollback();
            header('HTTP / 1.0 500 Internal Server Error');
            echo $db->getLastError();
        }
    }

    /**
     * @description Verifica todos los campos de comanda para que existan
     * @param $comanda
     * @return mixed
     */
    function checkComanda($comanda)
    {

        $comanda->usuario_id = (!array_key_exists("usuario_id", $comanda)) ? -2 : $comanda->usuario_id;
        $comanda->status = (!array_key_exists("status", $comanda)) ? 0 : $comanda->status;
        $comanda->mesa_id = (!array_key_exists("mesa_id", $comanda)) ? -2 : $comanda->mesa_id;
        $comanda->total = (!array_key_exists("total", $comanda)) ? 0.0 : $comanda->total;
        $comanda->origen_id = (!array_key_exists("origen_id", $comanda)) ? -2 : $comanda->origen_id;
        $comanda->detalles = (!array_key_exists("detalles", $comanda)) ? array() : self::checkDetalles($comanda->detalles);
        return $comanda;
    }

    /**
     * @description Verifica todos los campos de Proveedores y Comandas existan
     * @param $comandas_proveedores
     * @return mixed
     */
    function checkDetalles($detalles)
    {
        foreach ($detalles as $detalle) {
            $detalle->producto_id = (!array_key_exists("producto_id", $detalle)) ? 0 : $detalle->producto_id;
            $detalle->status = (!array_key_exists("status", $detalle)) ? '' : $detalle->status;
            $detalle->preparacion_fin = (!array_key_exists("preparacion_fin", $detalle)) ? '' : $detalle->preparacion_fin;
            $detalle->comentarios = (!array_key_exists("comentarios", $detalle)) ? '' : $detalle->comentarios;
            $detalle->comanda_id = (!array_key_exists("comanda_id", $detalle)) ? '' : $detalle->comanda_id;
            $detalle->cantidad = (!array_key_exists("cantidad", $detalle)) ? '' : $detalle->cantidad;
            $detalle->precio = (!array_key_exists("precio", $detalle)) ? '' : $detalle->precio;
            $detalle->extras = (!array_key_exists("extras", $detalle)) ? array() : self::checkExtras($detalle->extras);
        }
        return $detalles;
    }

    /**
     * @description Verifica todos los campos de fotos para que existan
     * @param $fotos
     * @return mixed
     */
    function checkExtras($extras)
    {
        foreach ($extras as $extra) {
            $extra->producto_id = (!array_key_exists("producto_id", $extra)) ? -2 : $extra->producto_id;
            $extra->cantidad = (!array_key_exists("cantidad", $extra)) ? -2 : $extra->cantidad;
            $extra->comanda_detalle_id = (!array_key_exists("comanda_detalle_id", $extra)) ? -2 : $extra->comanda_detalle_id;
            $extra->precio = (!array_key_exists("precio", $extra)) ? 0.0 : $extra->precio;
            $extra->selected = (!array_key_exists("selected", $extra)) ? false : $extra->selected;
        }
        return $extras;
    }

}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = file_get_contents("php://input");
    $decoded = json_decode($data);
    Comandas::init(json_decode(json_encode($decoded), true));
} else {
    Comandas::init($_GET);
}



